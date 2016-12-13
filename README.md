=== WP Placespeak Connect ===
Tags: identidy,comments
Tested up to: 4.7
Stable tag: 1.2.1
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin allows organizations with PlaceSpeak Connect apps on placespeak.com to use geoverification tools in their WordPress installation.

== Description ==

[PlaceSpeak Connect](https://www.placespeak.com/connect/about/) is a geographic digital identity authentication application programming interface, or GeoID API. It allows third party applications and websites to verify the location of its users. PlaceSpeak Connect tests a user's address or location against a predetermined area of interest, producing geographically relevant results.

This plugin allows organizations to use PlaceSpeak connect in conjunction with the WordPress commenting system to geo-verify commenters, without the need to write any code.

### Requirements

Your domain must use [HTTPS](https://en.wikipedia.org/wiki/HTTPS) in order for the plugin to work. Communication with the PlaceSpeak server happens according to the [OAuth2](https://en.wikipedia.org/wiki/OAuth) authorization protocol, and OAuth2 requires that network communication happen over encrypted HTTPS (which is just a good idea anyway).

### Features

* Admin can add apps using app key, app secret, and a standard redirect_uri
* "Connect With PlaceSpeak" button available as shortcode or as part of a commenting form
* Commenters that authorize PlaceSpeak will also have verification information, names, and region labels relative to app saved as meta information with comment
* Allows admin to store user information in `WP_USERS` table or custom `placespeak_user` table

### Usage

#### Creating a PlaceSpeak Connect app and connecting it to WordPress

To use PlaceSpeak Connect to verify your Wordpress commenters, you must first create one or more new API "apps" within PlaceSpeak, and update the WordPress plugin to be aware of them.

1. If you don't have one, create a new (free) user account on PlaceSpeak.com.
1. If you don't have one, create a new (free) organization account on PlaceSpeak.com. (Currently this requires clicking on the *Start a Consultation* link and following through to the organization creation step.
1. In your organizational dashboard, click on the *PlaceSpeak Connect* tab, and create a new API client instance (better documentation for this step to come).
1. In your Wordpress admin interface, go to *Settings* > *PlaceSpeak*
1. Copy the *Redirect URL* from the top of the page. Back in PlaceSpeak, paste the URL into the *Redirect URI* field in the settings page for your new API app, and press save.
1. Copy and paste the app key and app secret from PlaceSpeak into the *Add New PlaceSpeak App* form in Wordpress. Also give the app a name (presumably the same one you used when creating it in PlaceSpeak).

Note that you can choose to have PlaceSpeak verification information added directly onto the normal WordPress users table, or you can set the plugin to save that information is a separate table.

#### Using PlaceSpeak connect to verify commenters

1. Create a new page or post. Ensure commenting is turned on.
1. From the *Select PlaceSpeak App* dropdown in the editor, choose the app you just added.
1. Save your post and view it. There should now be an optional button for verifying PlaceSpeak users who submit a comment.

#### Using PlaceSpeak connect to verify form submissions

In addition to the standard PlaceSpeak commenting function, WP-PlaceSpeak-Connect can work with form plugins. Currently this has only been tested with Contact Form 7.

The usage with forms is the same as for regular post comments.

1. Install the [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) plugin.
1. Create a new page or post.
1. From the *Select PlaceSpeak App* dropdown in the editor, choose the app you just added.
1. Save your post and view it. There should now be a button for verifying PlaceSpeak users who fill out the form.

### Future features under consideration

* Single Sign On with PlaceSpeak account into WordPress
* Automatic listing of all available PlaceSpeak Connect API apps for your organization (instead of entering them manually).

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-placespeak-connect` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings->Wordpress screen to configure the plugin (see *Usage*) section for details.

Note: if you previously installed this plugin from Github (i.e. versions < 1.2) you must de-activate and uninstall that version of the plugin before installing this version.

== Changelog == 

= 1.2.1 =

* Initial re-organization to begin to bring code into plugin directory subversion standards.

= 1.2 =

* Multiple internal improvements to adhere to WordPress plugin directory standards.

= 1.1.4 =

* Prevents access to PHP files via the browser.

= 1.1.3 =

* More useful error message when server to server communication fails.

= 1.1.2 =

* Another bug fix for "Failed opening required ... wp-load.php" error on Windows Server.

= 1.1.1 =

* Bug fix for "Failed opening required ... wp-load.php" error on some servers.

