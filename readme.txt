=== Plugin Name ===
Contributors: rocketwood, jascott, brettgfitzgerald
Donate link: http://kickpress.org/
Tags: api, custom post types, custom taxonomies, custom fields, custom form elements, post relationships, custom pagination, application development framework, OAuth, shortcodes, widgets
Requires at least: 3.2
Tested up to: 4.4.2
Stable tag: 0.3.5

KickPress gives your WordPress website a full featured API, including remote access authentication for 3rd party websites and mobile apps.

== Description ==

This version is a beta pre-release, all functions and variables are subject to change without notice. Please report any issues to help make this a better product.

This plugin addresses many features that WordPress does not have out-of-the-box but that are interrelated in some way and should be included in one plugin as these features need to play well together. Here is a list of some features:

**For Administrators**

1. Allows for point-and-click post type management
2. Adds user roles and capabilities control
3. Adds content publication workflow

**For Designers**

1. Has an expanded multi-view template architecture, e.g. For Events, "Day View", "Week View", "Month View", "List View"
2. Adds robust navigation, pagination, and taxonomy widgets
3. Adds advanced filtering and sorting

**For Developers**

1. Uses session-based data storage
2. Has object relationships (a la group-members, user-favorites, etc)
3. Turns your WordPress install into an public API

* Adds a ReSTful API to any WordPress install
* Uses OAuth authentication for remote API access
* Object-oriented resource controllers (post type modules)
* Post meta-types: people, items, locations, events

== Installation ==

1. Upload the `kickpress` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. Adding new Post Types through the WordPress admin interface.

== Changelog ==

= 0.3.5 =
* Added the creation of missing oauth tables in database
= 0.3.4 =
* Added support for `series` of posts including the supporting API endpoints
= 0.3.3 =
* Updated localization calls and added a languages folder
= 0.3.2 =
* Updated API calls to include exportable meta data in JSON requests
* Several fixes to form elements
= 0.3.1 =
* Made more changes to support multiple views.
= 0.3 =
* Made more changes to support multiple views.
* Made more changes to support front-end forms and content editing.
* Added post status to the filters array
= 0.2.12 =
* Made changes to allow for multiple views on post types.
* Added code to support quick edits on existing content.
= 0.2.11 =
* Fixed a critical error in kickpress_api's toggle_term, add_terms, and remove_terms, where term values being read in as objects instead of as arrays.
= 0.2.10 =
* Added post date to front end editing
* Allow for excludes tag array to be passed to kickpress_the_excerpt
= 0.2.9 =
* Critical patch to tagged release 0.2.8 for hidden input form element generation
= 0.2.8 =
* Fixed bug in kickpress_redirects that prevented post types from being detected
= 0.2.7 =
* Fixed issues with auto-generation of terms in the toggle_terms and add_terms methods of the kickpress_api class
= 0.2.6 =
* Fixed bugs in kickpress_functions in the url generation
= 0.2.5 =
* Fixed bug in kickpress_redirects that prevented post types from being detected
= 0.2.4 =
* Fixed bug where post formats were processed as terms in URL generation
= 0.2.3 =
* Ongoing pagination fixes
= 0.2.2 =
* Fixed issue with kickpress_redirects, again.
= 0.2.1 =
* Fixed issue with kickpress_redirects
= 0.2 =
* One step closer to being a stable plugin
= 0.1.12 =
* Ongoing pagination fixes for multiple types of taxonomies with advanced filtering
= 0.1.11 =
* Fixed pagination issues
* Bug fixes
= 0.1.10 =
* Updated OAuth features
* Bug fixes
= 0.1.9 =
* Added OAuth support for remote API authentication
* Added bookmarks, notes, and comments capabilities through API calls
* Bug fixes
= 0.1.8 =
* Added extensible form elements
* Added activating/deactivating of modules
* Added admin interface for managing custom taxonomies
* Expanded capabilities of URL filtering
= 0.1.7 =
* Added front end image uploads for post thumbnails
* Fixed some bugs
= 0.1.6 =
* Fixed issue with permissions
* Added the wysiwyg editor as a form element with calls the internal WordPress wp_editor
= 0.1.5 =
* Added JSON API responses for remote api calls
* Added Roles and Capabilities
* Added Workflows
* General code cleanup
= 0.1.4 =
* Fixed issues with Adding new custom post types and a fatal error
* General code cleanup
= 0.1.3 =
* Cleaned up API authentication
* General code cleanup
= 0.1.2 =
* Added API authentication for remote access through the KickPress API by means of a token, signature, and timestamp
* General code cleanup
* Switched directory name back from 'components' to 'modules' for the directory that holds files that extend the API
= 0.1.1 =
* Fixed issue with errors when no custom post types exist
* Fixed issue with permalinks not working with newly registered post types
* Added rule to only allow users with permissions to edit options to create custom post types
= 0.1 =
* First upload to WordPress Plugin SVN.