# WP REST API Log

WordPress plugin to log [WP REST API](http://wp-api.org/) requests and responses (for v2 of the API).  Includes web-based admin tools to view log entries.

## Description

Contact [Pete Nelson](https://twitter.com/gungeekatx)

## Changelog

### v1.0.0 July ?, 2015
- Initial release

## Roadmap

- implement logmeta in admin UI
- implement parameter searching
- add timepicker to admin UI
- changed response data to have a Request and Response object, move these to classes instead of stdClass
- change search to support multiple routes, methods, params, etc
- switch to different prettifier https://cdn.rawgit.com/google/code-prettify/master/loader/run_prettify.js https://github.com/google/code-prettify
- implement paging in the admin ui
- add tablesorting to admin ui
- remove purge, change it to a DELETE request on entities
- add ability to log requests for invalid routes and status codes
- add ability to log raw querystring
- cron to purge old records
- admin settings
- testing
- codeclimate updates
- WordPress repo assets and readme

Pull requests are encouraged and welcome!


