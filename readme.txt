=== WP REST API Log ===
Contributors: gungeekatx
Tags: wp rest api, rest api, wp api, api, json, log
Donate link: https://github.com/petenelson/wp-rest-api-log
Requires at least: 4.0
Tested up to: 4.5
Stable tag: 1.2.0
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

* Better search capabilities for log entries via the REST API endpoint
* WooCommerce REST API Logging


== Installation ==

1. Upload the wp-rest-api-log directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings -> REST API Log to enable or disable logging
3. Go to Tools -> REST API Log to start viewing log entries


== Changelog ==

= v1.2.0 ??? 2016 =
* TODO

= v1.1.1 May 15, 2016 =
* Fixed error during activation (props pavelevap)

= v1.1.0 April 28, 2016 =
* Added cron job to cleanup old log entries
* Added setting to exclude the WP core /oembed API endpoint
* Don't diplay log entries in the Insert Link modal

= v1.0.0-beta2 April 10, 2016 =
* Switched from custom tables to built-in WordPress tables using a custom post type (wp-rest-api-log)
* Method, status, and source are now tracked using taxonomies
* Viewing log entries now uses the standard WordPress admin UI, includes filters for method, status, and source
* Added admin settings with the option to enable or disable logging
* Added WP-CLI support: wp rest-api-log
* Added .pot file to support translations

**NOTE: if you are upgrading from the previous version, you can run the "wp rest-api-log migrate" WP-CLI command to migrate your existing logs into the new custom post type**

= v1.0.0-beta1 July 9, 2015 =
* Initial release


== Upgrade Notice ==

= v1.1.1 May 15, 2016 =
* Fixed error during activation (props pavelevap)

= v1.1.0 April 28, 2016 =
* Added cron job to cleanup old log entries
* Added setting to exclude the WP core /oembed API endpoint
* Don't diplay log entries in the Insert Link modal

= v1.0.0-beta2 April 9, 2016 =
* Switched from custom tables to built-in WordPress tables using a custom post type (wp-rest-api-log)
* Method, status, and source are now tracked using taxonomies
* Viewing log entries now uses the standard WordPress admin UI, includes filters for method, status, and source
* Added admin settings with the option to enable or disable logging
* Added WP-CLI support: wp rest-api-log
* Added .pot file to support translations

**NOTE: if you are upgrading from the previous version, you can run the "wp rest-api-log migrate" WP-CLI command to migrate your existing logs into the new custom post type**


== Frequently Asked Questions ==

= Do you have any questions? =
We can answer them here!


== Screenshots ==

1. Sample admin screen
