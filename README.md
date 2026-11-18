# 10T Image Resize

Copyright (c) 2026 10T Web Design. Licensed under the [GNU General Public License, version 2 or later](LICENSE).

See [DEPLOYMENT.md](DEPLOYMENT.md) for GitHub setup and automated WordPress.org releases.

Processes new WordPress image uploads before they enter the media library. JPEG (including .jfif), PNG, and HEIC files become WebP; oversized WebP files are resized. Images fit within the configured width and height, preserving aspect ratio without enlargement. GIFs and SVGs pass through unchanged, subject to WordPress's normal upload permissions.

Activate **10T Image Resize** in Plugins, then open **Settings > 10T Image Resize**. Defaults are 1200 pixels wide, 1200 pixels tall, and WebP quality 82. Quality applies to converted images and resized WebP files. Smaller WebP files retain their original bytes. JPEG camera orientation is applied before resizing.

Requires WordPress 6.7+, PHP 7.4+, and a WordPress image editor with WebP encoding support (Imagick or GD). WordPress chooses the editor, normally preferring Imagick. Missing WebP support produces an administrator notice and an upload error. Other processing failures also reject the upload rather than saving an unprocessed original.

Only the processed image enters media storage; no uploaded original is retained. WordPress creates its normal thumbnail sizes from that image. Existing library items are unaffected. Standard uploads and sideloads are supported, including the standard REST media endpoint. Importers that bypass WordPress's upload handlers also bypass this plugin. Animated PNG/WebP preservation is not supported; processing may flatten these images. GIFs are never processed.

Settings use the WordPress Settings API, administrator permissions, and nonce validation. Uninstalling leaves the small settings option intact.

Run the standalone integration checks with:

```sh
php tests/process.php /path/to/wordpress
```

The checks use actual WordPress Imagick/GD editors, with isolated settings and capability stubs, and temporary fixture files. They do not connect to the database or activate the plugin. A browser check of activation, settings saving, and media uploads remains necessary on the target site.

HEIC/HEIF conversion requires Imagick built with libheif decoding support; GD alone cannot decode HEIC. WordPress 6.7 or later is required for native HEIC detection. HEIC image sequences and Live Photo motion are not preserved. Upload permissions remain controlled by WordPress.

To include HEIC integration checks, pass a HEIC fixture as the second argument: `php tests/process.php /path/to/wordpress /path/to/sample.heic`. Successful conversion checks require a working decoder.
