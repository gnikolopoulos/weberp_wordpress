=== WebERP ===

Contributors: George Nikolopoulos
Tags: weberp, erp, woocommerce
Stable tag: 1.5.2
Requires at least: 4.4.0
Tested up to: 7.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires PHP

Provides Wordpress integration with the WebERP app

== Description ==

This is the long description.  No limit, and you can use Markdown (as well as in the following sections).

For backwards compatibility, if this section is missing, the full length of the short description will be used, and
Markdown parsed.

A few notes about the sections above:

*   "Contributors" is a comma separated list of wp.org/wp-plugins.org usernames
*   "Tags" is a comma separated list of tags that apply to the plugin
*   "Requires at least" is the lowest version that the plugin will work on
*   "Tested up to" is the highest version that you've *successfully used to test the plugin*. Note that it might work on
higher versions… this is just the highest one you've verified.
*   Stable tag should indicate the Subversion "tag" of the latest stable version, or "trunk," if you use `/trunk/` for
stable.

    Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so
if the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used
for displaying information about the plugin.  In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer.  Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes — as long as the trunk's `readme.txt` points to the correct stable tag.

    If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where
you put the stable version, in order to eliminate any doubt.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Install any required plugins
1. Use the plugin options to set the WeERP API URL, username and password

== Changelog ==

= 1.5.2 =
* Code cleanup and refactoring
* Fixes to meet WP plugin coding standards

= 1.5.1 =
* Improved the custom endpoint to make searching faster and more reliable

= 1.5.0 =
* Added endpoint to check whether an image file exists to prevent duplicate images from being created

= 1.4.0 =
* Replace the usage of `woocommerce_thankyou` in favor of `woocommerce_checkout_order_created` and `woocommerce_store_api_checkout_order_processed`
* Improved documentation

= 1.3.0 =
* Fix for order-related hook actions
* Added comments

= 1.2.0 =
* Show stock data as a button or a pre-populated table

= 1.1.0 =
* Ability to show live stock data

= 1.0.0 =
* Initial release