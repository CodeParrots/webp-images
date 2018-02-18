# WebP Images #
![Banner Image](github-assets/banner-1550x500.jpg)

**Contributors:** [codeparrots](https://profiles.wordpress.org/codeparrots), [eherman24](https://profiles.wordpress.org/eherman24)  
**Tags:** [webp](https://WordPress.org/plugins/tags/webp/), [image](https://WordPress.org/plugins/tags/image/), [compression](https://WordPress.org/plugins/tags/compression/), [automation](https://WordPress.org/plugins/tags/automation/), [media-library](https://WordPress.org/plugins/tags/media-library/), [conditional](https://WordPress.org/plugins/tags/conditional/), [browser](https://WordPress.org/plugins/tags/browser/), [support](https://WordPress.org/plugins/tags/support/)  
**Requires at least:** 4.0  
**Tested up to:** 4.9  
**Stable tag:** 1.0.0  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Bye-bye third party services - automate the conversion of images in your media library to the `.webp` format (on your own server) and conditionally serve them to supported browsers on the front end.

[![Build Status](https://travis-ci.org/CodeParrots/webp-images.svg?branch=master)](https://travis-ci.org/CodeParrots/webp-images) [![License](https://img.shields.io/badge/license-GPL--2.0-brightgreen.svg)](https://github.com/CodeParrots/webp-images/blob/master/license.txt) [![PHP 5.6](https://img.shields.io/badge/php-5.6-8892bf.svg)](https://secure.php.net/supported-versions.php)  

## Description ##

<strong>Important:</strong> `cwebp` must be installed on the server and readily available in the system `$PATH`, for this plugin to function correctly. This is not available on shared hosting.

<strong>Note:</strong> Not an ideal solution for shared hosting as webp will not be installed. In this case, you may want to take a look at third party services.

*cwebp Installation*

MacOS:
`$ brew install webp`

Additional Installation Instructions:
<a href="https://developers.google.com/speed/webp/download" target="_blank">Downloading and Installing WebP</a>

<strong>Supported Image Types:</strong>
* jpg, jpeg
* png

<h3>How Does This Work</h3>

Anytime an image is uploaded through the media library a related .webp image is generated inside of a subdirectory in the uploads directory.

Example: `wp-content/uploads/webp/`

On the front end of your site, if a `.webp` format is available for the media element than the `.webp` format will be served. If not, or if a browser doesn't support `.webp` formats - then the original image will be served to the browser.

## Install Instructions ##

1. Install cwebp - https://developers.google.com/speed/webp/docs/cwebp
2. Upload and use media library as usual. When an image is uploaded a `.webp` image will be generated inside of the `wp-content/uploads/webp/` directory.
3. If supported, the `.webp` image will be served to the browser, otherwise the original will be referenced.

## Changelog ##

### 1.1.0 - February 18th, 2018 ###

* New: Added stylesheet.
* Update: Removed the `wp_die()` statement when `cwebp` is not installed. Plugin should still work remotely, without the image compression attempts, allowing users to use this locally and upload it to a remote site.
* Update: Updated build process.
* Update: Update .pot file.
* Update: Reworked the compression listings on the media element table.
* Update: Updated the JavaScript.

### 1.0.0 - February 17, 2018 ###

* Initial Release.
