# WP-PlaceSpeak-Connect

**Version: 1.2.1**

This plugin allows organizations with PlaceSpeak Connect apps on placespeak.com to use geoverification tools in their WordPress installation.

### Requirements

Your domain must use [HTTPS](https://en.wikipedia.org/wiki/HTTPS) in order for the plugin to work. Communication with the PlaceSpeak server happens according to the [OAuth2](https://en.wikipedia.org/wiki/OAuth) authorization protocol, and OAuth2 requires that network communication happen over encrypted HTTPS (which is just a good idea anyway).

### Features

* Admin can add apps using app key, app secret, and a standard redirect_uri
* "Connect With PlaceSpeak" button available as shortcode or as part of a commenting form
* Commenters that authorize PlaceSpeak will also have verification information, names, and region labels relative to app saved as meta information with comment
* Allows admin to store user information in `WP_USERS` table or custom `placespeak_user` table

### Installation

#### Option one: from the WordPress Plugin Directory.

TBD pending acceptance in the directory.

<!---
* In your WordPress dashboard, go to *Plugins* > *Add New*
* In the search bar, enter "PlaceSpeak".
* When the plugin is found, click "Install Now".
--->

#### Option two: from Github.

Note that if you have previously installed an earlier version of the plugin you must first deactivate and delete it from your WordPress dashboard before installing the new version. Your data will not be lost, despite the warning you will see.

* Click the "Download ZIP" button on this page to [download the files](https://github.com/PlaceSpeak/wp-placespeak-connect/archive/master.zip).
* Unzip the resulting wp-placespeak-connect-master.zip file.
* A folder will be created called `wp-placespeak-connect-master`. Rename it to just `wp-placespeak-connect`, then zip up that renamed folder.
* In your WordPress dashboard, click on the `Plugins` tab on the left nav bar, then `Add New`, then `Upload Plugin`, then `Choose File`.
* Choose the .zip file you created, then click `Install Now`.
* (If you see *"The package could not be installed. No valid plugins were found."* double check that you uploaded the zip containing the renamed folder, not the original one.)
* Click `Activate Plugin`.
* Within the `Settings` tab of the WordPress dashboard, look for the `PlaceSpeak` sub-tab, and add your first API connection.

### Usage

* If you don't have one, create a new (free) user account on PlaceSpeak.com.
* If you don't have one, create a new (free) organization account on PlaceSpeak.com. (Currently this requires clicking on the *Start a Consultation* link and following through to the organization creation step.
* In your organizational dashboard, click on the *PlaceSpeak Connect* tab, and create a new API client instance (better documentation for this step to come).
* In your Wordpress admin interface, go to *Settings* > *PlaceSpeak*
* Copy the *Redirect URL* from the top of the page. Back in PlaceSpeak, paste the URL into the *Redirect URI* field in the settings page for your new API app, and press save.
* Copy and paste the app key and app secret from PlaceSpeak into the *Add New PlaceSpeak App* form in Wordpress. Also give the app a name (presumably the same one you used when creating it in PlaceSpeak).

#### Option one: shortcodes.

* Copy the shortcode from the *Basic shortcode embed* section, i.e. *[placespeak_connect id="APP_ID"]*.
* Create a new post or page.
* In the content box, paste in the short code.
* Modify it to point to the correct app, and optionally, pick a colour for the button. E.g. *[placespeak_connect id="1" button="dark_blue"]*.
* Save your post and view it. There should now be a button for verifying PlaceSpeak users who comment on your page/post.

#### Option two: using Contact Form 7 forms

* Install the [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) plugin.
* Create a new page or post.
* From the *Select PlaceSpeak App* dropdown in the editor, choose the app you just added.
* Save your post and view it. There should now be a button for verifying PlaceSpeak users who fill out the form.

This option currently only works with [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) Let us know if there are other form plugins it should support.

### Version history 

1.2.1

* Initial re-organization to begin to bring code into plugin directory subversion standards.

1.2

* Multiple internal improvements to adhere to WordPress plugin directory standards.

1.1.4

* Prevents access to PHP files via the browser.

1.1.3

* More useful error message when server to server communication fails.

1.1.2

* Another bug fix for "Failed opening required ... wp-load.php" error on Windows Server.

1.1.1

* Bug fix for "Failed opening required ... wp-load.php" error on some servers.

### Future features under consideration

* Single Sign On with PlaceSpeak account into WordPress
* Automatic listing of all available PlaceSpeak Connect API apps for your organization (instead of entering them manually).
