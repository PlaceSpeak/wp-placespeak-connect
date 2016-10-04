# WP-PlaceSpeak-Connect

**Version: 1.1.3**

This plugin allows organizations with PlaceSpeak Connect apps on placespeak.com to use geoverification tools in their WordPress installation.

### Requirements

Your domain must use [HTTPS](https://en.wikipedia.org/wiki/HTTPS) in order for the plugin to work. Communication with the PlaceSpeak server happens according to the [OAuth2](https://en.wikipedia.org/wiki/OAuth) authorization protocol, and OAuth2 requires that network communication happen over encrypted HTTPS (which is just a good idea anyway).

### Features

* Admin can add apps using app key, app secret, and a standard redirect_uri
* "Connect With PlaceSpeak" button available as shortcode or as part of a commenting form
* Commenters that authorize PlaceSpeak will also have verification information, names, and region labels relative to app saved as meta information with comment
* Allows admin to store user information in `WP_USERS` table or custom `placespeak_user` table

### Installation

Note that if you have previously installed an earlier version of the plugin you must first deactivate and delete it from your WordPress dashboard before installing the new version. Your data will not be lost, despite the warning you will see.

* Click the "Download ZIP" button on this page to [download the files](https://github.com/PlaceSpeak/wp-placespeak-connect/archive/master.zip).
* Unzip the resulting wp-placespeak-connect-master.zip file.
* A folder will be created called `wp-placespeak-connect-master`. Rename it to just `wp-placespeak-connect`, then zip up that renamed folder.
* In your WordPress dashboard, click on the `Plugins` tab on the left nav bar, then `Add New`, then `Upload Plugin`, then `Choose File`.
* Choose the .zip file you created, then click `Install Now`.
* (If you see *"The package could not be installed. No valid plugins were found."* double check that you uploaded the zip containing the renamed folder, not the original one.)
* Click `Activate Plugin`.
* Within the `Settings` tab of the WordPress dashboard, look for the `PlaceSpeak` sub-tab, and add your first API connection.

## Release notes for 1.1.3

* More useful error message when server to server communication fails.

### Future features under consideration

* Single Sign On with PlaceSpeak account into WordPress
* Automatic listing of all available PlaceSpeak Connect API apps for your organization (instead of entering them manually).
