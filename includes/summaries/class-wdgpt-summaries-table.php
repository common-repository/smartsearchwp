<?php
/**
 * This file is responsible to create the summaries table.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import the WP_List_Table class.
 */
require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

/**
 * Require the custom tooltip class.
 */
require_once WD_CHATBOT_PATH . 'includes/summaries/class-wdgpt-custom-tooltip.php';


/**
 * Create summaries table.
 */
class WDGPT_Summaries_Table extends WP_List_Table {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		global $status, $page;

		parent::__construct(
			array(
				'singular' => __( 'summary', 'webdigit-chatbot' ),
				'plural'   => __( 'summaries', 'webdigit-chatbot' ),
			)
		);
	}

	/**
	 * Get the default column value.
	 *
	 * @param array  $item The current item.
	 * @param string $column_name The column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Retrieve the column title content.
	 *
	 * @param array $item The current item.
	 * @return string
	 */
	public function column_title( $item ) {

		if ( ! $item['hasEmptyContent'] ) {
			$common_actions = array();
			$embeddings     = $item['embeddings'] ? __( 'Regenerate Embeddings', 'webdigit-chatbot' ) : __( 'Generate Embeddings', 'webdigit-chatbot' );
			$yellow_row = ( $item['embeddings'] &&
							'' !== $item['last_generation'] &&
							strtotime( $item['last_generation'] ) < strtotime( $item['updated_at'] ) ) ?
							'yellow-row' :
							'';
			$green_row  = $item['embeddings'] ? 'green-row' : '';
			$css_class  = '' !== $yellow_row ? $yellow_row : $green_row;
			$disabled = 'green-row' === $css_class ? 'disabled' : '';
			$common_actions = array(
				'generate_embeddings' => sprintf( '<a href="#" class="%s generate-embeddings-link" data-id="%s">%s</a>', $disabled, $item['post_id'], $embeddings ),
			);

			if ( $item['embeddings'] ) {
				if ( $item['is_active'] ) {
					$actions = $common_actions + array(
						'deactivate' => sprintf( '<a href="#" class="toggle-summary" data-id="%s" data-action="deactivate">%s</a>', $item['post_id'], __( 'Deactivate', 'webdigit-chatbot' ) ),
					);
				} else {
					$actions = $common_actions + array(
						'activate' => sprintf( '<a href="#" class="toggle-summary" data-id="%s" data-action="activate">%s</a>', $item['post_id'], __( 'Activate', 'webdigit-chatbot' ) ),
					);
				}
			} else {
				$actions = $common_actions;
			}
		} else {
			$actions    = array();
			$tooltip    = new WDGPT_Custom_Tooltip( __( 'This summary has no content. Please add some content to it before generating embeddings.', 'webdigit-chatbot' ) );
			$item_title = sprintf( '%s %s', $item['post_title'], $tooltip->get_html( true ) );
		}

		$permalink = get_permalink( $item['post_id'] );

		$item_title_with_permalink = sprintf(
			'%s <a href="%s" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>',
			$item_title ?? $item['post_title'],
			$permalink
		);

		return sprintf(
			'%s %s',
			$item_title_with_permalink,
			$this->row_actions( $actions, true )
		);
	}

	/**
	 * Modify the row attributes colour based on the status.
	 *
	 * @param array $item The current item.
	 */
	public function single_row( $item ) {
		/**
		 * If the last generation date is not empty and is older than the last post modification date, the row will be yellow.
		 */
		$yellow_row = ( $item['is_active'] &&
						$item['embeddings'] &&
						'' !== $item['last_generation'] &&
						strtotime( $item['last_generation'] ) < strtotime( $item['updated_at'] ) ) ?
						'yellow-row' :
						'';
		$green_row  = ( $item['is_active'] && $item['embeddings'] ) ? 'green-row' : '';
		$css_class  = '' !== $yellow_row ? $yellow_row : $green_row;
		echo '<tr class="' . esc_html( $css_class ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Retrieve the column updated_at content.
	 *
	 * @param array $item The current item.
	 * @return string
	 */
	public function column_updated_at( $item ) {
		return sprintf(
			'<span class="date" data-id="' . $item['post_id'] . '">%s</span>',
			gmdate( 'Y-m-d H:i:s', strtotime( $item['updated_at'] ) )
		);
	}

	/**
	 * Retrieve the column last_generation content.
	 *
	 * @param array $item The current item.
	 * @return string
	 */
	public function column_last_generation( $item ) {
		return '' !== $item['last_generation'] ? sprintf(
			'<span class="date" data-id="' . $item['post_id'] . '">%s</span>',
			gmdate( 'Y-m-d H:i:s', strtotime( $item['last_generation'] ) )
		) : '<span class="date" data-id="' . $item['post_id'] . '"></span>';
	}

	/**
	 * Retrieve the column active content.
	 *
	 * @param array $item The current item.
	 * @return string
	 */
	public function column_active( $item ) {
		$class = $item['is_active'] ? 'fa-check' : 'fa-times';
		return sprintf(
			'<i class="fa ' . $class . '" data-id="' . $item['post_id'] . '" aria-hidden="true"></i>'
		);
	}

	/**
	 * Retrieve the column embeddings content.
	 *
	 * @param array $item The current item.
	 * @return string
	 */
	public function column_embeddings( $item ) {
		$class = $item['embeddings'] ? 'fa-check' : 'fa-times';
		return sprintf(
			'<i class="fa ' . $class . '" data-id="' . $item['post_id'] . '" aria-hidden="true"></i>'
		);
	}

	/**
	 * Retrieve the total number of items for a specific query.
	 *
	 * @param array $post_types The post types to be considered.
	 * @return array
	 */
	public function get_total_items( $post_types ) {
		$post_types = empty( $post_types ) ? $this->get_post_types() : $post_types;

		$args   = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);
		$search = false;
		$filter = 'all';
		if ( isset( $_GET['wdgpt_summaries_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['wdgpt_summaries_nonce'] ) ), 'wdgpt_summaries' ) ) {
			$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : false;
			$filter = isset( $_REQUEST['filter'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter'] ) ) : 'all';
		}
		if ( $search ) {
			$args['s'] = $search;
		}

		$request = new WP_Query( $args );

		$posts = $request->get_posts();
		$items = array();
		foreach ( $posts as $post ) {
			$item = array(
				'post_id'         => $post->ID,
				'post_type'       => $post->post_type,
				'updated_at'      => $post->post_modified,
				'is_active'       => ( get_post_meta( $post->ID, 'wdgpt_is_active', true ) !== '' &&
								get_post_meta( $post->ID, 'wdgpt_is_active', true ) !== 'false' ) ?
									get_post_meta( $post->ID, 'wdgpt_is_active', true ) :
									false,
				'embeddings'      => get_post_meta( $post->ID, 'wdgpt_embeddings', true ) ? true : false,
				'last_generation' => get_post_meta( $post->ID, 'wdgpt_embeddings_last_generation', true ) ? get_post_meta( $post->ID, 'wdgpt_embeddings_last_generation', true ) : '',
			);
			if ( $this->is_post_valid_for_filter( $item, $filter ) ) {
				$items[] = $item;
			}
		}

		$count_posts = array( 'all' => count( $items ) );
		foreach ( $items as $item ) {
			$count_posts[ $item['post_type'] ] = ( $count_posts[ $item['post_type'] ] ?? 0 ) + 1;
		}

		return $count_posts;
	}

	/**
	 * Retrieve the different views.
	 *
	 * @param bool $only_current Whether to return only the current view.
	 * @return array
	 */
	public function get_views( $only_current = false ) {
		$post_types = $this->get_post_types();
		$views      = array(
			'all' => __( 'All', 'webdigit-chatbot' ),
		);
		foreach ( $post_types as $post_type ) {
			$views[ $post_type ] = ucfirst( $post_type );
		}
		$current      = 'all';
		$search_query = false;
		$filter       = 'all';
		$referer      = '';
		if ( isset( $_REQUEST['wdgpt_summaries_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['wdgpt_summaries_nonce'] ) ), 'wdgpt_summaries' ) ) {
			$current      = isset( $_REQUEST['view'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['view'] ) ) : 'all';
			$search_query = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : false;
			$filter       = isset( $_REQUEST['filter'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter'] ) ) : 'all';
			$referer      = isset( $_REQUEST['_wp_http_referer'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wp_http_referer'] ) ) : '';
		}
		if ( $only_current ) {
			return $current;
		}
		$this->search_box( __( 'Search' ), 'post-search-input' );
		$count_posts = $this->get_total_items( $post_types );

		foreach ( $views as $key => $label ) {
			$url = add_query_arg( array( 'view' => $key ) );
			if ( $search_query ) {
				$url = add_query_arg( array( 's' => $search_query ), $url );
			}
			if ( $filter ) {
				$url = add_query_arg( array( 'filter' => $filter ), $url );
			}
			if ( $referer ) {
				$url = remove_query_arg( array( '_wp_http_referer' ), $url );
			}
			$class         = ( $current === $key ) ? ' class="current"' : '';
			$count  = $count_posts[$key] ?? 0;
			$url           = wp_nonce_url( $url, 'wdgpt_summaries', 'wdgpt_summaries_nonce' );
			$views[ $key ] = "<a href='{$url}' {$class}>{$label}</a><span class='count'>({$count})</span>";
		}
		return $views;
	}

	/**
	 * Retrieve the list of filters available.
	 *
	 * @return array
	 */
	public function get_filters() {
		$options = array(
			'all'           => __( 'All', 'webdigit-chatbot' ),
			'active'        => __( 'Active', 'webdigit-chatbot' ),
			'inactive'      => __( 'Inactive', 'webdigit-chatbot' ),
			'embeddings'    => __( 'Embeddings', 'webdigit-chatbot' ),
			'no-embeddings' => __( 'No Embeddings', 'webdigit-chatbot' ),
			'updated'       => __( 'Up to date', 'webdigit-chatbot' ),
			'not-updated'   => __( 'Not up to date', 'webdigit-chatbot' ),
		);
		return $options;
	}

	/**
	 * Generate the table navigation above or below the table.
	 *
	 * @param string $which The position of the navigation.
	 * @return void
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			echo '<div class="alignleft actions">';
			$filters = $this->get_filters();

			$current = isset( $_REQUEST['wdgpt_summaries_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['wdgpt_summaries_nonce'] ) ), 'wdgpt_summaries' ) ? ( isset( $_REQUEST['filter'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter'] ) ) : 'all' ) : 'all';

			echo '<select name="filter">';
			foreach ( $filters as $key => $label ) {
				$selected = ( $current === $key ) ? 'selected' : '';
				echo '<option value="' . esc_attr( $key ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select>';
			/**
			 * Note: As the views are outside of the form, we need to add the view as a hidden input.
			 * This allows us to keep the current view when modifying the filter.
			 */
			$views = $this->get_views( true );

			echo '<input type="hidden" name="view" value="' . esc_attr( $views ) . '">';
			submit_button( __( 'Filter', 'webdigit-chatbot' ), 'button', false, false, array( 'id' => 'post-query-submit' ) );
			echo '</div>';
		}
	}

	/**
	 * Retrieve the post types.
	 * 
	 * @return array
	 */
	public function get_post_types() {
		$default_post_types = ['post', 'page'];
		if ( ! class_exists( 'WDGPT_Custom_Type_Manager_Data' )) {
			return $default_post_types;
		}
		$custom_type_manager_data = WDGPT_Custom_Type_Manager_Data::instance();
		
		return array_merge( $default_post_types, $custom_type_manager_data->get_post_types() );
	}


	/**
	 * Retrieve the columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$tooltip = new WDGPT_Custom_Tooltip( __( 'Embeddings are used to find the most similar posts/pages to the user query. Embeddings are generated automatically when a post/page is saved. If you want to regenerate the embeddings for a post/page, click on the "Generate Embeddings" link.', 'webdigit-chatbot' ) );

		$columns = array(
			'title' => __( 'Title', 'webdigit-chatbot' ),
		);

		if ( $this->is_wpml_active() ) {
			$columns['language'] = __( 'Language', 'webdigit-chatbot' );
		}

		$columns = array_merge(
			$columns,
			array(
				'updated_at'      => __( 'Last Post Modification', 'webdigit-chatbot' ),
				'embeddings'      => __( 'Embeddings', 'webdigit-chatbot' ) . ' ' . $tooltip->get_html(),
				'last_generation' => __( 'Last Embeddings Generation', 'webdigit-chatbot' ),
				'Active'          => __( 'Active', 'webdigit-chatbot' ),
			)
		);

		return $columns;
	}
	/**
	 * Retrieve the hidden columns.
	 *
	 * @return array
	 */
	public function get_hidden_columns() {
		$columns = array(
			'id'      => __( 'ID', 'webdigit-chatbot' ),
			'content' => __( 'Content', 'webdigit-chatbot' ),
		);
		return $columns;
	}

	/**
	 * Prepare the items for the table to process.
	 *
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;

		$per_page              = 20;
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$san_order_by = 'post_title';
		$san_order    = 'asc';
		$paged        = 0;
		$orderby      = 'post_title';
		$order        = 'asc';
		$view         = 'all';
		$post_types   = $this->get_post_types();
		$filter       = 'all';
		// It is not an issue to not have a nonce on this parameter since it is just paging.
		// phpcs:ignore
		$paged        = isset( $_REQUEST['paged'] ) ? max( 0, intval( $_REQUEST['paged'] - 1 ) * $per_page ) : 0;
		if ( isset( $_REQUEST['wdgpt_summaries_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['wdgpt_summaries_nonce'] ) ), 'wdgpt_summaries' ) ) {
			$san_order_by = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'post_title';
			$san_order    = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'asc';
			$orderby      = ( in_array( $san_order_by, array_keys( $this->get_sortable_columns() ), true ) ) ? $san_order_by : 'post_title';
			$order        = ( in_array( $san_order, array( 'asc', 'desc' ), true ) ) ? $san_order : 'asc';
			$view         = isset( $_REQUEST['view'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['view'] ) ) : 'all';
			$post_types   = in_array( $view, $this->get_post_types() ) ? array( $view ) : $this->get_post_types();
			$filter       = isset( $_REQUEST['filter'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter'] ) ) : 'all';
		}

		$count_posts = $this->get_total_items( $post_types );
		$total_items = $count_posts[$view] ?? 0;
		$args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'offset'         => $paged,
			'orderby'        => $orderby,
			'order'          => $order,
		);

		$has_added_filter = false;

		switch ($filter) {
			case 'active':
				$args['meta_query'] = array(
					array(
						'key'     => 'wdgpt_is_active',
						'value'   => 'true',
						'compare' => '=',
					),
				);
				break;
			case 'inactive':
				$args['meta_query'] = array(
					'relation' => 'OR',
					array(
						'key'     => 'wdgpt_is_active',
						'value'   => 'false',
						'compare' => '=',
					),
					array(
						'key'     => 'wdgpt_is_active',
						'compare' => 'NOT EXISTS', // this will match posts where the 'wdgpt_is_active' meta key doesn't exist
					),
				);
				break;
			case 'embeddings':
				$args['meta_query'] = array(
					array(
						'key'     => 'wdgpt_embeddings',
						'compare' => 'EXISTS',
					),
				);
				break;
			case 'no-embeddings':
				$args['meta_query'] = array(
					array(
						'key'     => 'wdgpt_embeddings',
						'compare' => 'NOT EXISTS',
					),
				);
				break;
			case 'updated':
				$args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'     => 'wdgpt_is_active',
						'value'   => 'true',
						'compare' => '=',
					),
					array(
						'key'     => 'wdgpt_embeddings',
						'compare' => 'EXISTS',
					),
				);

				function wdgpt_search_by_last_generation($where, $query) {
					global $wpdb;
					$where .= " AND UNIX_TIMESTAMP((SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = {$wpdb->posts}.ID AND meta_key = 'wdgpt_embeddings_last_generation')) > UNIX_TIMESTAMP({$wpdb->posts}.post_modified)";
					return $where;
				}
				add_filter( 'posts_where', 'wdgpt_search_by_last_generation', 1, 2 );
				$has_added_filter = true;
				break;
			case 'not-updated':
				$args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'     => 'wdgpt_is_active',
						'value'   => 'true',
						'compare' => '=',
					),
					array(
						'key'     => 'wdgpt_embeddings',
						'compare' => 'EXISTS',
					),
				);
				function wdgpt_search_by_last_generation($where, $query) {
					global $wpdb;
					$where .= " AND UNIX_TIMESTAMP((SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = {$wpdb->posts}.ID AND meta_key = 'wdgpt_embeddings_last_generation')) <= UNIX_TIMESTAMP({$wpdb->posts}.post_modified)";
					return $where;
				}
				add_filter( 'posts_where', 'wdgpt_search_by_last_generation', 1, 2 );
				$has_added_filter = true;
				break;
			default:
				break;
		}

		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : false;
		if ( $search ) {
			$args['s'] = $search;
		}

		$request = new WP_Query( $args );
		$posts = $request->get_posts();
		if ( $has_added_filter ) {
			remove_filter( 'posts_where', 'wdgpt_search_by_last_generation', 1 );
		}
		foreach ( $posts as $post ) {
			/**
			 * To prevent issues with previous version of the chatbot, we will set the last generation embeddings date to the post modified date if it is not set.
			 * This will tell the user that there is no issues when they are going to the summaries page for the first time after the update.
			 * Note, it should be done only if there already has been embeddings generated for the post.
			 */
			if ( get_post_meta( $post->ID, 'wdgpt_embeddings', true ) && ! get_post_meta( $post->ID, 'wdgpt_embeddings_last_generation', true ) ) {
				update_post_meta( $post->ID, 'wdgpt_embeddings_last_generation', $post->post_modified );
			}

			$item = array(
				'post_id'         => $post->ID,
				'post_title'      => $post->post_title,
				'updated_at'      => $post->post_modified,
				'content'         => $post->post_content,
				'is_active'       => ( get_post_meta( $post->ID, 'wdgpt_is_active', true ) !== '' &&
								get_post_meta( $post->ID, 'wdgpt_is_active', true ) !== 'false' ) ?
									get_post_meta( $post->ID, 'wdgpt_is_active', true ) :
									false,
				'embeddings'      => get_post_meta( $post->ID, 'wdgpt_embeddings', true ) ? get_post_meta( $post->ID, 'wdgpt_embeddings', true ) : '',
				'last_generation' => get_post_meta( $post->ID, 'wdgpt_embeddings_last_generation', true ) ? get_post_meta( $post->ID, 'wdgpt_embeddings_last_generation', true ) : '',
				'hasEmptyContent' => empty( $post->post_content ),
			);

			$this->items[] = $item;
		}
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * Verify if the post is valid for the filter.
	 *
	 * @param array  $item The current item.
	 * @param string $filter The filter to be applied.
	 */
	public function is_post_valid_for_filter( $item, $filter ) {
		$filters = array(
			'all'           => true,
			'active'        => $item['is_active'],
			'inactive'      => ! $item['is_active'],
			'embeddings'    => $item['embeddings'],
			'no-embeddings' => ! $item['embeddings'],
			'updated'       => $item['is_active'] && $item['embeddings'] && '' !== $item['last_generation'] && strtotime( $item['last_generation'] ) >= strtotime( $item['updated_at'] ),
			'not-updated'   => $item['is_active'] && $item['embeddings'] && '' !== $item['last_generation'] && strtotime( $item['last_generation'] ) < strtotime( $item['updated_at'] ),
		);

		return isset( $filters[ $filter ] ) ? $filters[ $filter ] : true;
	}

	/**
	 * Verify if the user has the plugin WPML active.
	 * From WPML's documentation, this is the best way to check if WPML is active.
	 *
	 * @return bool
	 */
	public function is_wpml_active() {
		return defined( 'ICL_SITEPRESS_VERSION' );
	}

	/**
	 * Retrieve the column language content.
	 *
	 * @param array $item The current item.
	 * @return string
	 */
	public function column_language( $item ) {
		if ( $this->is_wpml_active() ) {
			$language      = apply_filters( 'wpml_post_language_details', null, $item['post_id'] );
			$languages     = apply_filters( 'wpml_active_languages', null, 'orderby=id&order=desc' );
			$language_code = $language['language_code'];
			$language_name = $languages[ $language_code ]['country_flag_url'];
			return sprintf(
				'<img src="%s" alt="%s" title="%s" />',
				$language_name,
				$language_code,
				$language_code
			);
		}
		return '';
	}
}
