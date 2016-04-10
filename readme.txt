=== WP REST API Log ===
Contributors: gungeekatx
Tags: wp rest api, rest api, wp api, api, json, log
Donate link: https://github.com/petenelson/wp-rest-api-log
Requires at least: 4.0
Tested up to: 4.5
Stable tag: 1.0.0-beta2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WordPress plugin to log WP REST API requests and responses

== Description ==

WordPress plugin to log [WP REST API](http://wp-api.org/) requests and responses (for v2 of the API).

Includes:

* WordPress admin page to view and search log entries
* API endpoint to access log entries via JSON
* filters to customize logging

Find us on [GitHub](https://github.com/petenelson/wp-rest-api-log)!

Roadmap

* Implememt paging in the admin UI
* Better search capabilities


== Installation ==

1. Upload the wp-rest-api-log directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Tools -> WP REST API Log to start viewing log entries


== Changelog ==

= v1.0.0-beta2 April 9, 2016 =
* Switched from custom tables to built-in WordPress tables using a custom post type (wp-rest-api-log)
* Method, status, and source are now tracked using taxonomies
* Viewing log entries now uses the standard WordPress admin UI, includes filters for method, status, and source
* Added admin settings with the option to enable or disable logging
* Added WP-CLI support: wp rest-api-log
* Added .pot file to support translations

= v1.0.0-beta1 July 9, 2015 =
* Initial release


== Upgrade Notice ==

= v1.0.0-beta2 April 9, 2016 =
* Switched from custom tables to built-in WordPress tables using a custom post type (wp-rest-api-log)
* Method, status, and source are now tracked using taxonomies
* Viewing log entries now uses the standard WordPress admin UI, includes filters for method, status, and source
* Added admin settings with the option to enable or disable logging
* Added WP-CLI support: wp rest-api-log
* Added .pot file to support translations


== Frequently Asked Questions ==

= Do you have any questions? =
We can answer them here!


== Screenshots ==

1. Sample admin screen
