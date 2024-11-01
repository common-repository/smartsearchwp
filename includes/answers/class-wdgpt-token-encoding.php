<?php
/**
 * This file is responsible for token encoding.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to encode tokens.
 */
class WDGPT_Token_Encoding {

	/**
	 * The token encoding instance.
	 *
	 * @var $instance
	 */
	private $initialized = false;

	/**
	 * The cache of BPE tokens.
	 *
	 * @var array<string>
	 */
	private $bpe_cache = array();

	/**
	 * The raw characters.
	 *
	 * @var array<string>
	 */
	private $raw_characters = array();

	/**
	 * The encoder.
	 *
	 * @var array<string>
	 */
	private $encoder = array();

	/**
	 * The BPE ranks.
	 *
	 * @var array<array<int>>
	 */
	private $bpe_ranks = array();

	/**
	 * The token encoding instance.
	 *
	 * @var $instance
	 * @return WDGPT_Token_Encoding
	 * @throws \RuntimeException If unable to load the data.
	 */
	private function initialize(): void {
		if ( $this->initialized ) {
			return;
		}
		$raw_characters = wp_remote_get( plugin_dir_url( __FILE__ ) . 'data/characters.json' );
		if ( is_wp_error( $raw_characters ) ) {
			throw new \RuntimeException( 'Unable to load characters.json' );
		}
		$this->raw_characters = json_decode( wp_remote_retrieve_body( $raw_characters ), true, 512, JSON_THROW_ON_ERROR );

		$encoder = wp_remote_get( plugin_dir_url( __FILE__ ) . 'data/encoder.json' );
		if ( is_wp_error( $encoder ) ) {
			throw new \RuntimeException( 'Unable to load encoder.json' );
		}
		$this->encoder = json_decode( wp_remote_retrieve_body( $encoder ), true, 512, JSON_THROW_ON_ERROR );

		$bpe_dictionary = wp_remote_get( plugin_dir_url( __FILE__ ) . 'data/vocab.bpe' );
		if ( is_wp_error( $bpe_dictionary ) ) {
			throw new \RuntimeException( 'Unable to load vocab.bpe' );
		}

		$lines = preg_split( '#\r\n|\r|\n#', wp_remote_retrieve_body( $bpe_dictionary ) );
		if ( false === $lines ) {
			throw new \RuntimeException( 'Unable to split vocab.bpe' );
		}

		$bpe_merges           = array();
		$raw_dictionary_lines = array_slice( $lines, 1, count( $lines ), true );
		foreach ( $raw_dictionary_lines as $raw_dictionary_line ) {
			$split_line = preg_split( '#(\s+)#', (string) $raw_dictionary_line );
			if ( false === $split_line ) {
				continue;
			}

			$split_line = array_filter( $split_line, array( $this, 'filter_empty' ) );
			if ( array() !== $split_line ) {
				$bpe_merges[] = $split_line;
			}
		}

		$this->bpe_ranks   = $this->build_bpe_ranks( $bpe_merges );
		$this->initialized = true;
	}

	/**
	 * Encode a string.
	 *
	 * @param string $text The text to encode.
	 * @return array<string>
	 */
	public function encode( string $text ): array {
		if ( empty( $text ) ) {
			return array();
		}

		$this->initialize();

		preg_match_all( "#'s|'t|'re|'ve|'m|'ll|'d| ?\p{L}+| ?\p{N}+| ?[^\s\p{L}\p{N}]+|\s+(?!\S)|\s+#u", $text, $matches );
		if ( ! isset( $matches[0] ) || 0 === ( is_countable( $matches[0] ) ? count( $matches[0] ) : 0 ) ) {
			return array();
		}

		$bpe_tokens = array();
		foreach ( $matches[0] as $token ) {
			$token      = mb_convert_encoding( (string) $token, 'UTF-8', 'ISO-8859-1' );
			$characters = mb_str_split( $token, 1, 'UTF-8' );

			$result_word = '';
			foreach ( $characters as $char ) {
				if ( ! isset( $this->raw_characters[ $this->character_to_unicode( $char ) ] ) ) {
					continue;
				}
				$result_word .= $this->raw_characters[ $this->character_to_unicode( $char ) ];
			}

			$new_tokens_pbe = $this->bpe( $result_word );
			$new_tokens_pbe = explode( ' ', $new_tokens_pbe );
			foreach ( $new_tokens_pbe as $new_pbe_token ) {
				$encoded = $this->encoder[ $new_pbe_token ] ?? $new_pbe_token;
				if ( isset( $bpe_tokens[ $new_pbe_token ] ) ) {
					$bpe_tokens[] = $encoded;
				} else {
					$bpe_tokens[ $new_pbe_token ] = $encoded;
				}
			}
		}

		return array_values( $bpe_tokens );
	}

	/**
	 * Filter empty values.
	 *
	 * @param mixed $variable The variable to filter.
	 * @return bool
	 */
	private function filter_empty( $variable ): bool {
		return null !== $variable && false !== $variable && '' !== $variable;
	}

	/**
	 * Convert a character to unicode.
	 *
	 * @param string $characters The characters to convert.
	 * @return int
	 */
	private function character_to_unicode( string $characters ) {
		$first_character_code = ord( $characters[0] );

		if ( $first_character_code <= 127 ) {
			return $first_character_code;
		}

		if ( $first_character_code >= 192 && $first_character_code <= 223 ) {
			return ( $first_character_code - 192 ) * 64 + ( ord( $characters[1] ) - 128 );
		}

		if ( $first_character_code >= 224 && $first_character_code <= 239 ) {
			return ( $first_character_code - 224 ) * 4096 + ( ord( $characters[1] ) - 128 ) * 64 + ( ord( $characters[2] ) - 128 );
		}

		if ( $first_character_code >= 240 && $first_character_code <= 247 ) {
			return ( $first_character_code - 240 ) * 262144 + ( ord( $characters[1] ) - 128 ) * 4096 + ( ord( $characters[2] ) - 128 ) * 64 + ( ord( $characters[3] ) - 128 );
		}

		if ( $first_character_code >= 248 && $first_character_code <= 251 ) {
			return ( $first_character_code - 248 ) * 16_777_216 + ( ord( $characters[1] ) - 128 ) * 262144 + ( ord( $characters[2] ) - 128 ) * 4096 + ( ord( $characters[3] ) - 128 ) * 64 + ( ord( $characters[4] ) - 128 );
		}

		if ( $first_character_code >= 252 && $first_character_code <= 253 ) {
			return ( $first_character_code - 252 ) * 1_073_741_824 + ( ord( $characters[1] ) - 128 ) * 16_777_216 + ( ord( $characters[2] ) - 128 ) * 262144 + ( ord( $characters[3] ) - 128 ) * 4096 + ( ord( $characters[4] ) - 128 ) * 64 + ( ord( $characters[5] ) - 128 );
		}

		if ( $first_character_code >= 254 ) {
			return 0;
		}

		return 0;
	}

	/**
	 * Build a ranks dictionary, based on the list of BPE merges.
	 *
	 * @param array<array<mixed>> $bpes The list of BPE merges.
	 *
	 * @return array<array<int>>
	 */
	private function build_bpe_ranks( array $bpes ): array {
		$result = array();
		$rank   = 0;
		foreach ( $bpes as $bpe ) {
			if ( ! isset( $bpe[1], $bpe[0] ) ) {
				continue;
			}

			$result[ $bpe[0] ][ $bpe[1] ] = $rank;
			++$rank;
		}

		return $result;
	}

	/**
	 * Return set of symbol pairs in a word.
	 * Word is represented as tuple of symbols (symbols being variable-length strings).
	 *
	 * @param array<int, string> $word The word to build the symbol pairs for.
	 *
	 * @return mixed[]
	 */
	private function build_symbol_pairs( array $word ): array {
		$pairs         = array();
		$previous_part = null;
		foreach ( $word as $i => $part ) {
			if ( $i > 0 ) {
				$pairs[] = array( $previous_part, $part );
			}

			$previous_part = $part;
		}

		return $pairs;
	}

	/**
	 * Apply BPE encoding to a word.
	 *
	 * @param string $token The token to encode.
	 * @return string
	 */
	private function bpe( string $token ): string {
		if ( isset( $this->bpe_cache[ $token ] ) ) {
			return $this->bpe_cache[ $token ];
		}

		$word           = mb_str_split( $token, 1, 'UTF-8' );
		$initial_length = count( $word );
		$pairs          = $this->build_symbol_pairs( $word );
		if ( array() === $pairs ) {
			return $token;
		}

		while ( true ) {
			$min_pairs = array();
			foreach ( $pairs as $pair ) {
				if ( isset( $this->bpe_ranks[ $pair[0] ][ $pair[1] ] ) ) {
					$rank               = $this->bpe_ranks[ $pair[0] ][ $pair[1] ];
					$min_pairs[ $rank ] = $pair;
				} else {
					$min_pairs[ 10e10 ] = $pair;
				}
			}

			$min_pairs_keys = array_keys( $min_pairs );
			sort( $min_pairs_keys, SORT_NUMERIC );
			$minimum_key = $min_pairs_keys[0] ?? null;

			$bigram = $min_pairs[ $minimum_key ];
			if ( ! isset( $this->bpe_ranks[ $bigram[0] ][ $bigram[1] ] ) ) {
				break;
			}

			$first       = $bigram[0];
			$second      = $bigram[1];
			$new_word    = array();
			$i           = 0;
			$count_words = count( $word );
			while ( $i < $count_words ) {
				$j = $this->index_of( $word, $first, $i );
				if ( -1 === $j ) {
					$new_word = array(
						...$new_word,
						...array_slice( $word, $i, null, true ),
					);
					break;
				}

				$slicer = $i > $j || 0 === $j ? array() : array_slice( $word, $i, $j - $i, true );

				$new_word = array(
					...$new_word,
					...$slicer,
				);
				if ( count( $new_word ) > $initial_length ) {
					break;
				}

				$i = $j;
				if ( $word[ $i ] === $first && $i < count( $word ) - 1 && $word[ $i + 1 ] === $second ) {
					$new_word[] = $first . $second;
					$i         += 2;
				} else {
					$new_word[] = $word[ $i ];
					++$i;
				}
			}

			if ( $word === $new_word ) {
				break;
			}

			$word = $new_word;
			if ( 1 === count( $word ) ) {
				break;
			}

			$pairs = $this->build_symbol_pairs( $word );
		}

		$word                      = implode( ' ', $word );
		$this->bpe_cache[ $token ] = $word;

		return $word;
	}

	/**
	 * Return the index of the first occurrence of $search_element in $provided_array.
	 *
	 * @param array<int, string> $provided_array The array to search in.
	 * @param string             $search_element The element to search for.
	 * @param int                $from_index The index to start searching from.
	 */
	private function index_of( array $provided_array, string $search_element, int $from_index ): int {
		$sliced_array = array_slice( $provided_array, $from_index, true );

		$indexed = array_search( $search_element, $sliced_array, true );

		return false === $indexed ? -1 : $indexed;
	}
}
