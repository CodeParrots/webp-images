=== WebP Images ===
Contributors: codeparrots, eherman24
Tags: cwebp, webp, image, compression
Requires at least: 4.0
Tested up to: 4.9
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Bye-bye third party services - automate the conversion of images in your media library to the `.webp` format (on your own server) and conditionally serve them to supported browsers on the front end.

<strong>Note:</strong> `cwebp` must be installed on the server and readily available in the system `$PATH`, for this plugin to function correctly. This is not available on shared hosting.

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

== Description ==

1. Install cwebp - https://developers.google.com/speed/webp/docs/cwebp
2. Upload and use media library as usual. When an image is uploaded a `.webp` image will be generated inside of the `wp-content/uploads/webp/` directory.
3. If supported, the `.webp` image will be served to the browser, otherwise the original will be referenced.
