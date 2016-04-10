# WP REST API Log

WordPress plugin to log [WP REST API](http://v2.wp-api.org/) requests and responses (for v2 of the API).  Includes web-based admin tools to view log entries.

[![Code Climate](https://codeclimate.com/github/petenelson/wp-rest-api-log/badges/gpa.svg)](https://codeclimate.com/github/petenelson/wp-rest-api-log)

## Description

Contact [Pete Nelson](https://twitter.com/gungeekatx)

## Changelog

### v1.0.0-beta2 April 9, 2016
- Switched from custom tables to built-in WordPress tables using a custom post type (wp-rest-api-log)
- Method, status, and source are now tracked using taxonomies
- Viewing log entries now uses the standard WordPress admin UI, includes filters for method, status, and source
- Added admin settings with the option to enable or disable logging
- Added WP-CLI support: wp rest-api-log
- Added .pot file to support translations

### v1.0.0-beta1 July 9, 2015
- Initial beta release

## Roadmap
- WooCommerce API logging

Pull requests are encouraged and welcome!
