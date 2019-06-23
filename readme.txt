=== APCu Object Cache Backend ===
Contributors: pierreschmitz
Donate link: https://pierre-schmitz.com
Stable tag: 2.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Requires at least: 3.3.0
Tested up to: 5.2
Tags: apcu, apc, backend, cache, object cache, batcache, performance, speed

An object-cache implementation using the APCu extension.

== Description ==

Using this Plugin WordPress is able to store certain regular used elements into a persistent cache. Instead of computing complex operations over and over again on every single page request, its result is stored in memory once and from then on fetched directly from the cache on future page requests.

Such an object cache will reduce access to your database and speed up page loading.

This implementation uses [APCu](https://pecl.php.net/package/APCu)'s variable cache as backend.

== Installation ==

1. You need to install and configure the [APCu PHP extension](https://pecl.php.net/package/APCu).
1. Download and extract the content of the archive file.
1. Upload the file object-cache.php of this plugin into your `/wp-content/` directory. Note that this file needs to be stored directly into your content directory and not under the plugins directory.
1. This plugin should now work without any further configuration. Check if it is listed under `Plugins` -> `Installed Plugins` -> `Drop-ins`.

== Frequently Asked Questions ==

= "Cannot redeclare wp_cache_add()..." =
This error indicates that you likely have two copies of the object cache installed. Make sure you have put the file object-cache.php into your `/wp-content/` directory only. Do not upload it to the `/wp-content/plugins` directory or any subdirectory like `/wp-content/plugins/apcu`. The `APCu Object Cache Backend` is not a regular WordPress plugin but a `Drop-in`. Therefore you cannot store it into the `plugins` directory.

== Changelog ==

= 2.0.0 =
* Maintenance Update
* Compatibility with WordPress 5
* Requires at least PHP 7.1

= 1.0.3 =
* Minor code improvements
* Compatibility with PHP 7.1 and WordPress 4.7

= 1.0.2 =
* WordPress 4.4 support
* Compatibility with PHP 7 and APCu 5

= 1.0.1 =
* WordPress 4.3 support

= 1.0.0 =
* initial version
