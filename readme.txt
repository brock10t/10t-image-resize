=== 10T Image Resize ===
Contributors: brock10t
Tags: images, resize, webp, media, optimization
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Resize new image uploads without enlarging them, and convert JPEG, PNG, and HEIC uploads to WebP before they enter your media library.

== Description ==

10T Image Resize processes new images before WordPress stores them in the media library.

* Convert JPEG (including .jfif), PNG, and HEIC uploads to WebP.
* Resize oversized WebP uploads; leave smaller WebP files unchanged.
* Preserve aspect ratio without cropping or enlargement.
* Configure maximum width, maximum height, and WebP quality under Settings > 10T Image Resize.
* Default to a maximum width and height of 1200 pixels and quality of 82.
* Leave GIF and SVG files unchanged, subject to WordPress's normal upload permissions.
* Apply JPEG camera orientation before resizing.

Only the processed image is stored; the uploaded original is not retained. WordPress generates its normal thumbnail sizes from the processed image. Existing media is unaffected.

Requires Imagick or GD with WebP encoding support. The plugin displays an administrator notice when no compatible editor is available and rejects affected uploads with an explanation. Processing failures also reject the upload.

Animated PNG and WebP preservation is not supported: processing may flatten these images. GIFs are never processed. Importers that bypass WordPress's standard upload and sideload handlers also bypass this plugin.

HEIC/HEIF conversion requires Imagick built with libheif decoding support; GD alone cannot decode HEIC. WordPress 6.7 or later is required for native HEIC detection. HEIC image sequences and Live Photo motion are not preserved. Upload permissions remain controlled by WordPress.

== Installation ==

1. Upload the 10t-image-resize folder to /wp-content/plugins/.
2. Activate 10T Image Resize under Plugins.
3. Open Settings > 10T Image Resize to configure dimensions and quality.
4. Upload new images through the media library.

== Frequently Asked Questions ==

= Does this change existing media? =

No. It processes new uploads and standard sideloads only.

= Are original images kept? =

No. Keep your own copies if you need the originals later.

= Does it enlarge small images? =

No. Images always retain their aspect ratio and are never enlarged.

= Does it enable SVG uploads? =

No. WordPress's normal file permissions still apply.

= What happens without WebP support? =

An administrator notice explains the requirement. JPEG, PNG, and WebP uploads are rejected until a compatible editor is available. Other file types continue through WordPress normally.

== Changelog ==

= 1.2.0 =
* Recognize .jfif JPEG uploads and convert them to WebP.

= 1.1.0 =
* Add HEIC/HEIF conversion to WebP with server capability checks.
* Require WordPress 6.7 for native HEIC detection.

= 1.0.0 =
* Initial release with configurable dimensions and quality, WebP conversion, and server support notices.
