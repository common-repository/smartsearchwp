=== Chatbot with ChatGPT Wordpress ===
Contributors: webdigit
Donate link: https://www.smartsearchwp.com/
Tags: chatbot, chatgpt, openai, conversational chat, customer support
Requires at least: 4.7
Tested up to: 6.6
Stable tag: 2.4.7
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


Chatbot with ChatGPT for intuitive customer interaction and conversational search on WordPress. Quick and easy interface.


== Description ==

Advanced Chatbot with ChatGPT for Intuitive Customer Interaction and Smart Search on WordPress. Quick interface for relevant results.

[SmartSearchWP](https://www.smartsearchwp.com/) is a powerful natural language processing tool for websites. It offers relevant search results through an intuitive interface and customizable settings, enhancing user experience.

Please note that the chatbot will only use the context provided by the user in its responses.

Administrators can now customize the chatbot's context by selecting which pages to include or exclude. This ensures that the chatbot only provides information that is relevant to your website.

The chatbot uses OpenAI as a third-party service to generate its answers.
You can find more information about OpenAI by following this link: https://openai.com/ and consult their privacy policy here: https://openai.com/policies/terms-of-use

Why Choose SmartSearchWP?

**Intelligent Search**: Understands user queries through natural language processing to provide relevant results.

**Context Customization**: Administrators can tailor the chatbot's context for more accurate responses.

**Easy to Install and Configure**: Get up and running in minutes with our intuitive user interface.

**OpenAI Integration**: Powered by ChatGPT3.5 & ChatGPT4 for quick and natural responses.

**Secure and Private**: Only uses the context provided by the user, ensuring data privacy.

**Responsive Support**: Quick and effective technical support for all your queries.

**Regular Updates**: Stay up-to-date with frequent new features and improvements.

**Wide Compatibility**: Tested with the latest WordPress and PHP versions.

**Comprehensive Documentation**: Detailed guides and FAQs to help you get started.

**Multilingual Support**: Use SmartSearchWP globally with multiple language support.

== Installation ==

1. Upload the directory plugin to the '/wp-content/plugins/' directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the SmartSearchWP screen to configure the plugin base settings.

== Frequently Asked Questions ==

= How do I activate the bot? =

The bot will not appear in your website until you have activated it.
To activate the bot, you need to generate and activate the embeddings for the pages and posts you want to use, as well as the general settings.

General settings:
1. Go to the SmartSearchWP settings page.
2. Enter your chatGPT API key.
3. Modify the settings to your needs.
4. Save the settings.

= How do I customize the chatbot's context? =

Generate and activate pages:
1. Go to the SmartSearchWP 'Summaries' page.
2. Click on the 'Generate Embeddings' button under the page or post you want to use.
3. Once their embeddings have been generated, click on the 'Activate' button under the page or post you want to use.
4. Repeat steps 2 and 3 for all the pages and posts you want to use.

== Screenshots ==

Screenshots are saved inside the /assets/screenshots directory.

1. Main interface when interacting with the chatbot.
2. Several settings available to modify and enhance your experience.

== Changelog ==

= 2.4.7 =
* Fix embeddings generation if context is too big
* Add compatibility for generating context with pdf file (add-on with pdf compatibility will be required, soon available)

= 2.4.6 =
* Resolved an issue which might expose openAI key.

= 2.4.5 =
* Resolved an issue to SQL & XSS injection in the chatbot interface and user restriction to purge logs.

= 2.4.4 =
* Resolved an issue where the answer stream was truncate.

= 2.4.3 =
* Resolved an issue where the answer couldn't be written in the interface due to empty chunks.

= 2.4.2 =
* Resolved a problem where the Text-to-Speech function was incorrectly processing HTML and Markdown tags in the speech.
* Introduced notifications within the admin panel to alert users about pending updates for addons.
* Corrected a glitch causing the chat interface to scroll to the top upon submitting a prompt.
* Addressed an issue encountered when viewing the chatbot on a WooCommerce product page.

= 2.4.1 =
* Resolved a visual inconsistency issue with the Text-to-Speech (TTS) button on specific websites.
* Introduced a feature that allows users to halt the Text-to-Speech function by clicking the button a second time after it has been activated.
* Implemented a prevention mechanism to avoid the accumulation of Text-to-Speech tasks in the queue, enhancing the overall user experience.

= 2.4.0 =
* The chat now includes built-in Text-to-Speech and speech recognition features! A new button, located to the left of the input field, has been introduced to capture and transcribe user's spoken input. Additionally, each message can be audibly read out using the Text-to-Speech option.

= 2.3.2 =
* Resolved an issue where existing CSS rules on a WordPress site could potentially override the visual style of the chatbot plugin.

= 2.3.1 =
* The small notification bubble above the chat icon has been reinstated! It's now compatible with the customization addon and will display correctly based on the position set within the addon.
* We've eliminated the random number that used to appear on top of the chatbot icon, as the notification bubble now serves the same function.
* The greeting message is now customizable! If you don't alter them in the settings, it will default to the standard translations. This feature has also been implemented for the chatbot bubble.

= 2.3.0 =
* Cleaned up contexts' embeddings generation.
* Prevent the user from regenerating embeddings when there is no need to.

= 2.2.1 =
* Fixed a pagination issue inside the contexts' interface, and only show contexts based on the current language if the user is using WPML.

= 2.2.0 =
* Upgraded to gpt4-o from gpt-4 for faster answer generation and reduced token cost. This change is automatically applied within your plugin.
* With gpt4-o, the generated answers can be up to three times longer compared to gpt4 and gpt-3.5-turbo.
* Resolved an intermittent issue where the addons' catalog was not properly receiving data from the website.
* Fixed a problem with the premium license verification. It should now renew itself correctly. This issue did not affect your license but was not properly automated.
* Added a clickable permalink next to the context's title in the contexts' interface for easy page access.
* Integrated WPML support in the contexts' interface to identify the page language, useful when dealing with pages of the same name in different languages.
* Resolved an issue where Markdown was not always correctly applied when generating answers. This fix is also applied to the logs.
* Introduced a filter in the context's interface to display only posts or pages with the selected status, such as active or having embeddings.
* Fixed an issue where the search box was not functioning correctly under certain conditions.
* Corrected an issue where translations were not properly applied in the addons manager during premium license verification.

= 2.1.0 =
* Rectified a situation wherein the image and title of an addon within the catalog were mistakenly perceived as buttons, rather than directing to the appropriate addon.
* Resolved an issue occurring when the user's API key lacked credit, resulting in improper verification of results.
* Presenting a fresh look! The chat interface now boasts an enhanced default appearance compared to its predecessor.
* Addressed an issue where the disclaimer occasionally extended beyond the confines of the chat frame, subsequently augmenting its size.
* Implemented spacing between the input field and the send button for improved user experience.
* Enhanced aesthetics and space efficiency by substituting the chat's name with its icon when generating responses.
* Optimized the ellipsis used as a loading indicator for improved functionality.

= 2.0.0 =
* Premium addons are now available! You can access them via the "Addons" submenu within the admin page. There's also a free license available for any potential free addons.
* We've resolved an issue where the chatbot container would occasionally hide behind other elements. The container will now consistently maintain its priority.
* Fixed an issue that could sometimes prevent the user from deactivating the plugin.
* Added a verification for the required 'allow_url_fopen' server setting.

= 1.9.0 =
* Added a new disclaimer below the chat interface to inform users that artificial intelligence can occasionally make mistakes. Users are advised to verify critical information.
* Improved answer generation for a more seamless reading experience for users.
* Fixed an issue that prevented links from being clickable while the chatbot was generating a response.
* Resolved an issue where certain segments of words could be omitted during answer generation, leading to potential problems in the output.
* Addressed an issue where certain characters were displayed as their HTML codes instead of their readable forms (e.g., € was displayed as &euro;).
* Corrected a problem where the general settings sliders did not update their values correctly upon page reload.
* Enhanced visual appeal! The answers now support markdown and parse them for users to enjoy a better experience, such as bold or italics. This is also in effect inside the chat logs and the reporting.

= 1.1.8.4 =
* Fixed an issue that could occur if the posts had a special encoding.

= 1.1.8.3 =
* Fixed an issue with the counting of tokens when using GPT-4.

= 1.1.8.2 =
* Added a verification for users that had updated to 1.1.8 before.

= 1.1.8.1 =
* Fixed an issue that prevented some users from correctly updating the database.

= 1.1.8 =
* Added a new validation inside the admin page to verify that the API key is valid.
* Added a new validation inside the admin page to verify if your current API key is able to use gpt-4.
* Added a reporting that the current API key has insufficient credits to be used.
* Added a new validation inside the admin page to let the user know if the OpenAI API is having issues.
* Added a toggle to enable/disable the chatbot on the front-end.
* Fixed an issue with translations not being loaded correctly in the administration page.
* Added a new color inside the administration interface for contexts that have been activated, but their embeddings are not "up to date" with the content of the page.
* Added a legend on top of the contexts table to explain the different colors.
* Added a new chat bubble next to the interface to better show the chatbot to the users. This bubble can be hidden directly, or 5 seconds after the page has been loaded.
* Added an option to hide the chat bubble completely.
* Added a new feature to receive, by mails, a PDF that will contain the conversations made by users in the last day or week. This report will run through a cron every day.

= 1.1.7.1 =
* Fixed an issue with error reporting.

= 1.1.7 =
* Fixed an issue with error reporting on some PHP versions.
* Fixed an issue with the answer generation with bad formatting of posts.

= 1.1.6 =
* Fixed an issue with bigger contexts using the wrong model.

= 1.1.5 =
* Added missing vendor files.

= 1.1.4 =
* Added a new feature to have the chatbot answer "word by word" instead of waiting for the full answer to be generated.
* The new feature will also make the chatbot answer faster, especially on long answers.
* The chatbot will no longer automatically scroll to the bottom when a new answer is generated. This allows the user to start reading the answer while it is being generated.
* Further fixed the clickable links in the chatbot interface to prevent them from not being interpreted correctly.
* Fixed an issue with the chatbot logs delete function on some PHP versions.
* Fixed an issue with the chatbot interface in some resolutions.
* Fixed an issue when holding a conversation with the chatbot for a long time.
* Fixed an issue with the chatbot interface when the user is typing a message while the chatbot is generating an answer.
* Fixed an issue when trying to find the most relevant page to answer a question on certain posts.
* Fixed an issue when using the previous messages in the conversation when trying to generate an answer.

= 1.1.3 =
* Fixed an issue with previous versions of PHP.
* Updated minimum PHP version to 7.4.

= 1.1.2 =
* Fix an issue with some model versions for retrieving the chatbot's answer.

= 1.1.1 =
* Fix issue with some PHP versions.

= 1.1.0 =
* Answer time should now be faster.
* Optimized the chatbot interface for better performance.
* Improved the answer generation algorithm for better quality, especially with longer answers.
* Added a new "reset conversation" button to the chatbot interface.
* Introduced an "Expand/Compress" button to adjust the width of the chatbot interface.
* Added colour to better differentiate activated and deactivated pages in the summaries page.
* Fixed display issues with certain links and line breaks in the chatbot interface.
* Fixed display issues with certain links and line breaks inside the chatbot logs admin page.

= 1.0.3 =
* Fix issue on the chatbot interface on certain sites.

= 1.0.2 =
* Add link to the SmartSearchWP website in the description of the plugin.

= 1.0.1 =
* Add chatgpt Model selection in settings (chatgtp3.5-turbo or chatgpt-4)
* Some CSS fixe

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 2.4.3 =
Resolves a response display problem that can occur when autocompleting certain responses.
