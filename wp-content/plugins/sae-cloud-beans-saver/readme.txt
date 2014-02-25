=== SAE cloud beans saver ===

Contributors: shiedman
Tags: SAE
Requires at least: 3.5
Tested up to: 3.8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

This plugin save your cloud beans at SAE, by persisting object cache and databsae query result to KVDB. It greatly readuce database requests.
Optionally, you can remap js/css loading url to other server(e.g. cdn server), bypass any http requests to SAE servers for js/css.

== Installation ==

**Quick Tip:** WordPress® can work with one and only one cache plugin, consider remove any existing cache plugins.

= Easy to Install =
1. unzip the plugin zip file to your plugins directory(wp-content/plugins/).
2. **Important:** copy **db.php** to wp-content/. since wp-content is not writable on SAE server, you must do it manully.
3. **Important:** make sure KVDB is activated. 
    Optionlly, you can use SAE storage domain instead of KVDB(no extra beans needed but poor performance): define('SAE_STORAGE','wordpress') in wp-config.php. `wordpress` is the storage domain you have created at SAE.

= How to tell cache is working? =

`define('SAVEQUERIES', true)` in wp-config.php, navigate your site, some statics should print out on the page bottom. If not, check if **db.php** copied to wp-content/.


== Further Details ==

= Transient API =
when object-cache.php exists in wp-content, `wp_using_ext_object_cache(true)` is called, that give hints to [Transients API](http://codex.wordpress.org/Transients_API) to use this external cache and not saving data to mysql.

== changelog ==

= 0.1.3 =

* Initial release
