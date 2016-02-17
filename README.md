# WP-PlaceSpeak-Connect

**Version: 1.0.0**

This plugin allows organizations with PlaceSpeak Connect apps on Placespeak.com to use geoverification tools in their Wordpress installation.

### Features

* Admin can add apps using app key, app secret, and a standard redirect_uri
* "Connect With PlaceSpeak" button available as shortcode or as part of a commenting form
* Commenters that authorize PlaceSpeak will also have verification information, names, and region labels relative to app saved as meta information with comment
* Allows admin to store user information in WP_USERS table or custom placespeak_user table

### Bugs and fixes

* Do we need shortcode? Having it means that wp-placespeak-connect scripts and styles are loaded on every page (just in case they use it)
* Update apps to use https://placespeak.com
* Is user login check returning the user_id from https://placespeak.com?

### Future features under consideration

* Single Sign On with PlaceSpeak account into Wordpress
* App creation from Wordpress (instead of going to PlaceSpeak to create them)
