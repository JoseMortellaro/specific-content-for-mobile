=== Specific Content For Mobile - Customize the mobile version without redirections ===
Contributors: giuse
Requires at least: 4.6
Tested up to: 6.7
Stable tag: 0.5.3
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: optimization, mobile, cleanup, amp, device

Specific Content For Mobile allows you to create pages and posts content designed for mobile devices.

== Description ==


Specific Content For Mobile allows you to create pages and posts content designed for mobile devices.

This plugin <strong>DOES NOT</strong> redirect content from the desktop website: it creates brand new mobile-specific content.

It's perfect if in some pages you need different content for the mobile version.

The best way to have a mobile version is always a fully responsive design, but in some cases you may need <strong>specific content for the mobile version</strong>.

[youtube https://youtu.be/-vc3zBI9Prw]


== How to add or remove specific content for the mobile version: ==
* Click on Pages in the main admin menu
* Go with your mouse on the page you want to modify for the mobile version and click on the action link "Create mobile version", or click on the icon "+" you see in the devices column
* Modify your page as you want to see it on mobile
* Save your page mobile version


If you want to create a mobile version for your blog posts, do as explained above, but going to the list of Posts.


On mobile devices, the plugin will load the mobile version you have created for that page or post.

If you create the mobile version for a page reachable at https://your-domain.com/page-example/, and you are logged-in, you will see also https://your-domain.com/page-example-mobile/.
But logged-out users will not see it, and the only URL that exists for the public is https://your-domain.com/page-example/.

The page reachable at https://your-domain.com/page-example/ will show the desktop content on desktop devices, and the mobile content on mobile devices The URL is always the same.

You have <strong>no redirections</strong>, and the plugin just replaces the desktop content with the related mobile version.

For more details read the <a href="https://specific-content-for-mobile.com/documentation/">Documentation</a>.



== Requirements ==
If you have a server cache plugin, be sure to set a different server cache handling for mobile devices, in another case the mobile version of your pages could also be served on desktop devices.
E.g. [W3 Total Cache](https://wordpress.org/plugins/w3-total-cache/), [WP Fastest Cache](https://wordpress.org/plugins/wp-fastest-cache/), and [Powered Cache](https://wordpress.org/plugins/powered-cache/) are caching plugins that can handle the mobile cache.
Be careful if you are using <a href="https://wordpress.org/plugins/w3-total-cache/">W3 Total Cache</a> or whatever other caching plugin that gives you the possibility to manage the mobile version, DO NOT set up any redirection for mobile devices.
If you have issues with the mobile and desktop versions with W3 Total Cache, read <a href="https://wordpress.org/support/topic/mobile-version-showing-in-desktop-view-w3-total-cache-plugin/#post-17279915">Mobile version showing in desktop view – W3 Total Cache Plugin</a>
Specific Content For Mobile <strong>DOES NOT</strong> redirect the mobile users. <strong>The URL of a page visited on mobile is exactly the same as the desktop URL.</strong>



== Differences with AMP plugins ==
Specific Content For Mobile has nothing to do with AMP pages. It gives you the possibility to show specific content for mobile, but without any redirection, and without generating any AMP markup.
If you want to serve AMP pages, Specific Content For Mobile is not for you. In that case, you may be interested in a plugin like <a href="https://wordpress.org/plugins/amp/" target="_blank">AMP</a>, or <a href="https://wordpress.org/plugins/accelerated-mobile-pages/">AMP For WP</a>.
You consider Specific Content For Mobile like an alternative to AMP plugins.


== Limitations of the free version ==
The free version supports only the mobile version of pages and posts, no custom post types, no archives, no terms.


== Additional information ==

As a default, WordPress doesn't output the blog page content before the posts loop.
Some themes do it. In this case, the blog page content output before the loop is handled by the theme templates.
The plugin will check if the theme declares support for the blog page mobile version, if not so the blog page mobile version may take the original desktop content.

As a default Specific Content For Mobile synchronizes the post metadata. This means that when you save a post or page, if they have a mobile version, the same metadata will be saved in the mobile version.
When you save a mobile version, the mobile version metadata will be saved also in the desktop version.
If you want to change this behavior, go to Specific Content For Mobile settings and choose "Allow mobile versions having their own metadata".

For the most popular SEO plugins, you can choose the metadata synchronization specifically for that plugin.


== <a href="https://specific-content-for-mobile.com/documentation/">PRO Version</a> ==

= The <a href="https://specific-content-for-mobile.com/documentation/">PRO version</a> also allows you to: =
* Have a mobile version of all the queriable post types are supported
* Load a different theme specifically on mobile devices
* Unload specific plugins only on mobile devices
* Decide if tablets should be considered as mobile devices or not so
* Have a different navigation on mobile
* Write specific content depending on the device
* Have access to the premium support


== For developers ==

<strong>Template for mobile</strong>

If you need to use a different template file on mobile, copy the template file of your theme and put it in one of these folders:

wp-content/scfm/

wp-content/themes/theme-name/scfm


For example, if your theme is "theme-name" and you want to load a different page.php on mobile, it will be something that looks like:

wp-content/scfm/page.PHP

or

wp-content/themes/theme-name/page.php


In the case of mobile devices, Specific Content For Mobile will look for the custom template file first in wp-content/themes/theme-name/scfm and if it doesn't find it in wp-content/scfm.




<strong>Integration with other plugins</strong>

If you add an option for the metadata synchronization of an external plugin, you can use the filter "eos_scfm_meta_integration_array".

Here an example:

`
add_filter( 'eos_scfm_meta_integration_array','my_custom_scfm_meta_integration',20,2 );
//It adds an option to synchronize your plugin meta data.
function my_custom_scfm_meta_integration( $arr,$options ){
	$slug = 'my_custom_meta';
	$arr[$slug] = array(
		'is_active' => defined( 'WPSEO_FILE' ),
		'args' => array(
			'title' => __( 'My custom meta synchronization','my-textdomain' ),
			'type' => 'select',
			'value' => isset( $options[$slug] ) ? esc_attr( $options[$slug] ) : 'synchronized',
			'options' => array(
				'synchronized' => __( 'Synchronize desktop and mobile metadata','my-textdomain' ),
				'separated' => __( 'Allow mobile versions having their own metadata','my-textdomain' )
			),
		),
		'prefix' => array( '_my_plugin' ),
		'default' => 'synchronized'
	);
	return $arr;
}
`
Then you will see your custom option "My custom meta synchronization" in the main settings page.




<strong>Helper functions</strong>

In your theme, you can use the following functions to give full support to the mobile version content:


`
eos_scfm_related_desktop_id( $post_id );
`
given the post ID, it will get the post ID of the related desktop version.

`
eos_scfm_related_mobile_id( $post_id );
`
given the post ID, it will get the post ID of the related mobile version.


<strong>Theme support</strong>

To add the theme support to the blog content, you can add this line in your theme support action hook:

`
add_theme_support('specific_content_form_mobile',array( 'posts_page' => true ) );
`


== Help ==

If something doesn't work on the free version, open a thread on the <a href="https://wordpress.org/support/plugin/specific-content-for-mobile/">support forum</a>
If you are a PRO user, and you have issues, don't hesitate to open a ticket on the <a href="https://specific-content-for-mobile.com/support/">premium support</a>



== Screenshots ==

1. Pages
2. Metadata synchronization
3. Custom metadata synchronization (for developers)


== Installation ==

1. Upload the entire `specific-content-for-mobile` folder to the `/wp-content/plugins/` directory or install it using the usual installation button in the Plugins administration page.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. After successful activation you will be automatically redirected to the plugin global settings page.
4. All done. Good job!



== Changelog ==

= 0.5.3 =
* Added: Hidden ?scfm-mobile=1 on mobile devices

= 0.5.2 =
* Added: Minor updates

= 0.5.1 =
* Fix: Conflict with the Elementor editing page

= 0.5.0 =
* Fix: Edit mobile and desktop version in the single page editing screen

= 0.4.0 =
* Checked: WordPress 6.7
* Fix: Missing escaping funcitons on mobile preview

= 0.3.0 =
* Fix: Mobile version not redirecting to desktop version on desktop.

= 0.2.5 =
* Fix: WooCommerce actions not working.

= 0.2.4 =
* Added: Rewrite rules to avoid serving the mobile cache to desktop devices and viceversa

= 0.2.3 =
* FIx: False detection message
* FIx: Domain name different than plugin slug

= 0.2.2 =
* FIx: Conflict with Visual Composer

= 0.2.1 =
* FIx: Duplicated posts in the Recent Posts widget

= 0.2.0 =
* FIx: Fatal error if other plugins do array of actions

= 0.1.9.9 =
* FIx: Mobile pages shown in the desktop search results if user not logged in

= 0.1.9.8 =
* Fix: PHP warning in the backend single page

= 0.1.9.7 =
* Fix: Security patch. Nonce not added when unlinking mobile and desktop versions via bulk actions

= 0.1.9.6 =
* Fix: Security patch XSS when unlinking mobile and desktop versions via bulk actions

= 0.1.9.5 =
* Checked: WordPress 6.4
* Added: Link to the PRO version

= 0.1.9.4 =
* Fix: Mobile icon disappeared

= 0.1.9.3 =
* Fix: Mobile icon showing also when it should not

= 0.1.9.2 =
* Checked: Checked WP v. 6.3

= 0.1.9.1 =
* Fix: caching system debugging malfunction

= 0.1.9.0 =
* Added: possibility to switch off the warning about caching plugins by adding defined( 'SCFM_DEBUG_NOTICE',false ); to the wp-config.php file

= 0.1.8.9 =
* Fix: conflicts with Zion Builder when editing again a mobile page

= 0.1.8.8 =
* Fix: conflicts with Zion Builder

= 0.1.8.7 =
* Fix: self-debugging false detection after saving single post in some situations

= 0.1.8.6 =
* Fix: caching issue false detection after saving single post

= 0.1.8.5 =
* Added: self debugging after saving post

= 0.1.8.3 =
* Fix: Conflict with Flatsome

= 0.1.8.2 =
* Fix: PHP notice if $_SERVER['HTTP_USER_AGENT'] not defined

= 0.1.8.1 =
* Fix: SCFM column not appearing on the list of pages and posts in the backend

= 0.1.8 =
* Changed: Mobile page versin not anymore private to avoid issues caused by some SEO plugins

= 0.1.7 =
* Fixed: Conflict with Elementor


= 0.1.6 =
* Fixed: Mobile content not called when coming from single post editing screen
* Fixed: Missing icons on mobile preview
* Fixed: Permalinks on posts archive on mobile
* Added: Possibility to assign a different mobile page
* Added: Support to the mobile version of post excerpts
* Added: Hooks for the PRO version
* Added: Hooks to create the mobile version of the theme template files

= 0.1.5 =
* Fixed: Conflict with WPBakery and Elementor frontend builder
* Fixed: Device images and icons not loading on mobile preview

= 0.1.4 =
* Fixed: Mobile posts showing on archives
* Fixed: Private page visible for not logged users if WooCommerce is active
* Added: Preview on mobile
* Added: Options to synchronize the metadata
* Added: Filter to add metadata synchronization for external plugins
* Added: Bulk action to unlink the mobile versions

= 0.1.3 =
* Fixed: Compatibility with KingComposer
* Fixed: Compatibility with WpDiscuz plugin

= 0.1.2 =
* Fixed: PHP warnings if saving post without revisions
* Fixed: Comments not taken on mobile devices
* Removed: Synchronization between desktop and mobile

= 0.1.1 =
* Fixed: PHP error saving page missing links

= 0.1.0 =
* Fixed: error 404 if not logged on some mobile pages

= 0.0.9 =
* Fixed: PHP memory leak on mobile version trashing and untrashing
* Fixed: is_front_page() returned false on mobile homepage
* Checked: WordPress version 5.3

= 0.0.8 =
* Added: synchronization between desktop and mobile version for simple text, images, and links replacements

= 0.0.7 =
* Fixed: bug when user not logged-in

= 0.0.6 =
* Added: warning after the desktop content change
* Added: hook for the desktop and mobile versions changes synchronization (future PRO version)

= 0.0.5 =
* Fixed: mobile version for blog posts

= 0.0.4 =
* Added: prevent mobile version from being public to avoid SEO problems
* Fixed: issues when mobile versions moved to trash or restored from the trash

= 0.0.3 =
* Added: mobile version metabox in the single page and posts

= 0.0.2 =
* Added: action links in the plugins page
* Added: translation to Italian

= 0.0.1 =
* Initial Release

== Screenshots ==

1. Pages and posts list screen
