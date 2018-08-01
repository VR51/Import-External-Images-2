=== Import External Images 2 v2.0.6 ===

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

This plugin is based on Import External Images by Marty Thornley https://github.com/MartyThornley/import-external-images, which is based on the "Add Linked Images to Gallery" plugin by http://www.bbqiguana.com/.

= Donate =

Donate Link: https://paypal.me/vr51

== Installation ==

1. Download the "Import External Images 2" zip file.
2. Upload to your WordPress plugins directory.
3. Activate the plugin via the WordPress Plugins tab.
4. Navigate to Media > Import Images or edit a post to import externally linked media.

== Frequently Asked Questions ==

= How does this plugin work? =

This plugin will find IMG attachments or PDF attachments within posts and pages. Any external attachments will be downloaded to your site's media library and their links in posts/pages will be changed to those of the file in your own website's media library.

= Does it work with MultiSite? =

Yes!

= What if I don't want to import images from a third party image hosting site? =

You can use the settings page to make Import External Images 2 ignore domains.

= Images won't import. What can I do? =

- Install (WP Sweep)[https://en-gb.wordpress.org/plugins/wp-sweep/] Visit Tools > Sweep then sweep the transients (it is the bottom option).
- Confirm the server that hosts the images does not use hotlink protection. If images are protected you won't be able to download them in some cases.
- Make sure the site that hosts the remote images is not in maintenance mode and is not blocking your own site's IP address.

= I can't use the bulk image importer to import images into multiple pages simulataneously. What gives? =

This bug began with the release of WordPress 4.9.1 and vanished with 4.9.3. Hopefully you will not meet this bug.

Sometimes pages need to be visited by the admin user before images will import. The pages only need to be briefly opened then closed in a browser tab in the same browser as your active admin session or in a different browser on the same machine (i.e. you don't need to be logged in when viewing the pages).

Use the site's sitemap to visit pages or use the 'view' button next to each post in the bulk options page then run the importer again.

== Changelog ==
= 2.0.6 =

- Bugfix: Restored https? protocol check to line 439.

= 2.0.5 =

- Bugfix: querystrings no longer prevent image import. Thank you [rothkj1022](https://github.com/rothkj1022)
- Bugfix: image URLs without a protocol no longer prevent image import. Thank you [rothkj1022](https://github.com/rothkj1022).

= 2.0.4 =

- Removed external_image_getext(). Appears not to be used anywhere in the plugin code so deemed redundent.
- Changed title of function is_external_file() to is_allowed_file(). This new name better suits the functions purpose.
- Edited is_allowed_file(). Combine the arrays $allowed and $allowedAlso into single $allowed. Introduced foreach loop.
- Reduced duplicate checks against is_allowed_file. Props [Ivan0xFF](https://github.com/Ivan0xFF) for noticing the superflorous checks.
- Edited external_image_get_img_tags() to fix issues fetching some remote images with query strings in the URLs. Props [Ivan0xFF](https://github.com/Ivan0xFF) for this fix.
- Fixed default options initialisation. Added add_option() configs. No idea how I missed this initially.
- Added view and edit buttons to import results page.
- Prefixed all functions and CSS with vr_

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

