=== Import External Images 2 ===

Makes local copies of all externally linked images and (Optionally) PDFs in a post or page.

== Description ==

Imports remotely hosted images and PDFs where they are referenced within a post and updates their links to point to the local file.

The plugin shows a number next to the title of posts that contain external images. This number includes the count of links to external images that can be updated. Consider this number to be a count of total changes to be made i.e. it includes images to be imported and links to be updated.

= Features =

- Import externally hosted images
- Import post-by-post during post editing
- Import to multiple posts at a time by visiting Dashboard > Media > Import Images

= Credits =

Version 1.5.x and above: Released by [VR51](https://github.com/VR51/Import-External-Images-2).

This plugin is based Import External Images by Marty Thornley https://github.com/MartyThornley/import-external-images, which is based on "Add Linked Images to Gallery" plugin by http://www.bbqiguana.com/

= Donate =

Donate Link: https://paypal.me/vr51

== Installation ==

1. Download the "Import External Images" zip file.
2. Upload to your WordPress plugins directory.
3. Activate the plugin via the WordPress Plugins tab.
4. Navigate to Media > Import Images or edit a post to import externally linked media.

== Frequently Asked Questions ==

= How does this plugin work? =

This plugin will find IMG attachments or PDF attachments within posts and pages. Any external attachments will be brought into downloaded to your site's media library and their links in posts/pages will be changed to those of your own website.

= Does it work with MultiSite? =

Yes!

= What if i don't want to import images from a third party image hosting site? =

You can make it ignore any domain you want on the settings page, in case you work with a CDN or photo hosting site and want to keep those images where they are.

= This works on single pages after a post import but bulk image import fails. What gives? =

Sometimes pages need to be visited by the admin user before images will import. Use a sitemap to visit pages/posts or use the 'view' button next to each post in the bulk options page.

== Changelog ==

= 2.0.3 =

- Various bugfixes
- Added 'view' post button to bulk import admin page. A change in WP 4.9 prevents image imports unless a page has been viewed from the IP address of the admin user.

= 2.0.2 =

- Bugfixes

= 2.0.1 =

- Bugfix: Use home_url instead of siteurl to prevent external image false positives. Thanks to budrick.
- Bugfix: Fixed handling of PDF files. Thanks to dcbradley.

= 2.0.0 =

- Released as version 2 under it's own Github repository so that we can better manage bug reports and suggestions.

= 1.5.2 =

- Merged ajax.php into main file import-external-images.php. This done to make functional the 'posts to process count' feature.
- Fixed counting bug that prevented limits being set for the number of posts to process.
- Fixed counting bug that prevented limits being set for the number of images to process per post.
- Removed superfluous function 'external_images_verify_permission()'. Was disabled prior to code edits. No longer needed now that ajax.php is merged with import-external-images.php.
- Corrected comments in import-external-images.js file. Comments suggested we were resizing images when we were only importing images.
- Adjusted plugin text to better match the text meaning to the actual functions of the plugin.

= 1.5.1 =

- Improved posts layout table.
- Various code changes.

= 1.5 =


This is the first VR51 release of this plugin.

- Fixed bug that caused the plugin to download images hosted on HTTP sites. Now fetches imedia from HTTPS sites too.
- Changed post query to explicitly loop through all post types with any post status.
- Added option to import externally linked PDFs.
- Fixed undefined variable error.
- Fixed undefined key error.
- Changed link of Bulk Image Resizer plugin to Regenerate Thumbnails hosted on wordpress.org.
- Added option to adjust number of images to process per run.
- Added option to adjust number of posts to process per run.

= 1.3 =

- Fixed case sensitivity, thanks to https://github.com/SidFerreira
- Fixed duplicate EXTERNAL_IMAGES_DIR notice

= 1.1 =

- Fixed title in readme.

