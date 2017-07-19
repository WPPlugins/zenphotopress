=== ZenphotoPress ===
Contributors: Simbul
Tags: zenphoto, images, photos, tinymce, posts, gallery, widget, shortcode
Requires at least: 2.5
Tested up to: 4.4
Stable tag: 1.8

ZenphotoPress provides an easy way to include
Zenphoto images into your blog. It supports shortcode and widgets.

== Description ==
ZenphotoPress is a plugin for WordPress that makes it easier
to add images stored in a Zenphoto gallery into a blog.
It provides:

* An interface for adding images into posts and pages
* A shortcode for generating galleries automatically
* A widget for displaying images

ZenphotoPress 1.8 is fully compatible with
WordPress 4.4 and Zenphoto 1.4.
For older Wordpress versions, download Zenphotopress 1.3.1.

== Installation ==
1. Expand the compressed archive in your
   WordPress plugins directory (most likely
   wordpress/wp-content/plugins).
2. Open WordPress and activate the plugin from
   the Plugins menu.
3. Configure ZenphotoPress. Open the Settings menu
   and select ZenphotoPress. Insert the URL of your
   Zenphoto gallery. That's it!

Please notice that you may need to *clear your browser cache* in
order to see the ZenphotoPress icon in the rich text editor.

== Upgrade ==
Since the name change in version 1.3 affected pretty much all plugin
files and Wordpress database entries, the only way to perform a clean
upgrade is to uninstall any previous version of the plugin and reinstall
the latest version from scratch.
In the Options panel an "Upgrade" button will be available to import all
the preferences from past versions of the plugin to the latest version.

== Styling ==
Since ZenphotoPress 1.3, the thumbnails created by the plugin are
assigned a CSS class named .ZenphotoPress\_thumb. When using Word Wrap,
the thumbnails are also assigned classes .ZenphotoPress\_left (when the
image is positioned on the left side and the text wraps on the right
one) and .ZenphotoPress\_right (in the opposite situation).
For good measure, they are also assigned standard Wordpress classes .alignleft
and .alignright.
The badge is assigned a .ZenphotoPress\_badge class. For the shortcode version of
the badge the class .ZenphotoPress\_shortcode is added, while for the widget
version .ZenphotoPress\_widget is added.

Notice that, since ZenphotoPress automatically adds a styling to some of these
classes, it may be necessary to use "!important" to override the value
associated to them, in the theme stylesheet.

PLEASE NOTICE: Previous versions used "ZenPress" for the CSS class names
instead of "ZenphotoPress". All images inserted with plugin versions older
than 1.3 will still retain the old name in their class attribute.

== Widget & Shortcode ==
To activate the ZenphotoPress widget, just enable it from the Design->Widgets
page in Wordpress.

To use the ZenphotoPress shortcode, use [zenphotopress] in your posts. For more
options, see howto_shortcode.txt.

== Frequently Asked Questions ==
= Where's my ZenphotoPress icon? =
If you don't see the icon in the Wordpress editor, it's most
likely because of your browser's cache. Try clearing it and
reloading the page.

= How do I use the shortcode? =
The easiest way is to let ZenphotoPress generate it for you: there is an
option in the ZenphotoPress popup window.

The most basic shortcode works pretty much like the [gallery]
shortcode in Wordpress (except it fetches images from Zenphoto, of course):

	[zenphotopress]

You can also specify one or more of the following parameters:
*sort* (how to sort images), *number* (how many images to show) and *album*
(where to pick the images from).

	[zenphotopress sort=random number=3 album=0]

For more information, see the file howto_shortcode.txt in the
plugin's root directory.

= I'm having problems. Is there a debug mode somewhere? =
I'm glad you asked :D
You can enable a debug mode, which will show more error and info
messages. This can be helpful when trying to solve an issue by yourself
or when asking for help.
To enable debug mode, open the file named classes.php, and change

	$zp_eh = new ZenphotoPressErrorHandler(ZP_E_FATAL);

to

	$zp_eh = new ZenphotoPressErrorHandler(ZP_E_ALL);

Please be aware that some of the debug info contains sensitive data
relative to the webserver. Keep this in mind when pasting it on the net.

= I don't see any album in ZenphotoPress =
There are many possible reasons. On of them is that your WP plugin
folder (or any of its parent folders) is password protected: since
ZenphotoPress doesn't know your password, it cannot access the page
it uses to collect data.

= Nothing works after updating to Zenphoto 1.2.6 =
That's because since 1.2.6 zp-config.php was moved from the zp-core/ folder
to the zp-data/ folder. Run the ZenphotoPress configuration once more and
everything should be working again.

= What about the name? =
ZenphotoPress was once called ZenPress. The name was changed
since version 1.3, because ZenPress was already
a registered trademark.

== Changelog ==
= 1.8 =
* Updating connection between ZenPhotoPress and ZenPhoto.
* Adding support for multi-language galleries and photos.
* Fixed zenphotopress button for editor text mode.
* Replaced references to deprecated functions
* Added image caption to lightbox view

= 1.7.5.1 =
* Just bumping up the release to try and get the connection between SVN and wordpress.org working again.

= 1.7.5 =
* Added support for Zenphoto 1.4.2 (patch by Arnaud Hocevar, <arnaud@hocevar.net>).
* Minor bugfixes.

= 1.7.4 =
* Added compatibility with Zenphoto 1.4 (by ignoring mod_rewrite when building image paths).

= 1.7.3 =
* Added support for custom thumb cropping.
* Added support for WP_CONTENT_URL, WP_CONTENT_DIR, WP_PLUGIN_URL and WP_PLUGIN_DIR.
* Images with text-wrap enabled are now assigned standard classes .alignleft and .alignright.

= 1.7.2 =

* Fix: now ZPP works even when wp-config.php is in the parent of the root folder.

= 1.7.1 =

* Fixed a bug which broke pagination.

= 1.7 =

* Added UI for inserting galleries/shortcodes.
* Added support for Lightbox-like scripts.

= 1.6.2 =

* Fixed a sneaky bug causing images in the selector not to appear in some cases.

= 1.6.1 =

* A couple of minor fixes.

= 1.6 =

* Migrated widget to WP 2.8 architecture. Multiple widgets now a possibility.
* Added support for nesting in albums (subalbums).
* Security fix: protected/unpublished albums and images are not shown anymore.
* Added support for Zenphoto 1.2.6 (config file moved from zp-core/ to zp-data/).
* Added experimental support for UTF-8 database.
* Fixed a bug caused by PHP notices.

= 1.5.3 =

* Fixed a bug preventing mod_rewrite option from being used.

= 1.5.2 =

* Hidden images and albums are not shown in ZenphotoPress.
* Uploaded and tagged on wordpress.org.

= 1.5.1 =

* Restored PHP4 compatibility.
* Various bugfixes.

= 1.5 =

* Added widget (badge).
* Added shortcode [zenphotopress].
* Big changes in the backend to decouple Wordpress and Zenphoto.
* Password-protected albums are not shown in Zenphotopress.
* Video thumbnails are now handled correctly.

= 1.4 =

* Added support for Wordpress 2.5.
* Fixed minor usability issues.

= 1.3.1 =

* Fixed a bug preventing the creation of correct URLs with
 mod_rewrite disabled

= 1.3 =

* Changed name to ZenphotoPress due to trademark issues
* Changed name of the ZenphotoPress variables in the database
* Made configuration easier
* Italian translation is no longer supported

= 1.2.2 =

* Fixed a bug causing conflicts with other plugins

= 1.2.1 =

* Small bugfix

= 1.2 =

* Changed filesystem structure (no more files in wp-includes!)
* Added popup customization options
* Code has been cleaned a little
* Popup radio buttons don't look ugly in IE anymore
* Fixed a bug which made the popup scroll to top when
 opening/closing a menu
* Better options management (more WP-like)

= 1.1.1 =

* Custom-size thumbnails now use ZenPhoto resizing for better quality

= 1.1 =

* Added full internationalization support (zenpress.pot)
* Added italian translation
* Added Text Wrap option
* Added an option to set the size of the thumbnail
* Support for zp-config.php is now default
* Thumbnails can be styled

= 1.0 =

* Clarified (hopefully) the configuration page
* Added support for zp-config.php (future Zenphoto releases)

= 0.9.4 =

* It is now possible to set the number of images to show
* It is now possible to set the sorting order of images
* Brand new error handling, to ease debugging and support
* Brand new database interface class
* Better code organization

= 0.9.3 =

* The popup now works in IE (at the price of uglier code, though... -_-)
* Added some more error messages

= 0.9.2 =

* Added support for Zenphoto database table prefix

= 0.9.1 =

* Added some error messages to ease debugging

= 0.9 =

* First beta release

== Screenshots ==

1. The configuration page. The only required field is the Zenphoto URL,
all the rest is just optional.
2. The dialog for adding images into a post or page.
3. Images, shortcode (gallery) and widget (badge) as a visitor of the
blog will see them.
