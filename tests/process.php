<?php
if ( PHP_SAPI !== 'cli' ) { exit; }
/** Standalone integration check: php tests/process.php /path/to/wordpress */
$root = isset( $argv[1] ) ? rtrim( $argv[1], '/' ) : dirname( __DIR__, 3 );
define( 'ABSPATH', $root . '/' );
define( 'WPINC', 'wp-includes' );
define( 'WP_DEBUG', false );
define( 'WP_RUN_CORE_TESTS', true );
define( 'WP_CONTENT_DIR', sys_get_temp_dir() );
define( 'WP_CONTENT_URL', 'https://example.test/wp-content' );
require ABSPATH . WPINC . '/plugin.php';
require ABSPATH . WPINC . '/cache.php';

define( 'DAY_IN_SECONDS', 86400 );
require ABSPATH . WPINC . '/class-wp-error.php';
require ABSPATH . WPINC . '/load.php';
wp_cache_init();
if ( file_exists( ABSPATH . WPINC . '/utf8.php' ) ) {
 require ABSPATH . WPINC . '/compat.php';
 require ABSPATH . WPINC . '/compat-utf8.php';
 require ABSPATH . WPINC . '/utf8.php';
}
require ABSPATH . WPINC . '/formatting.php';
require ABSPATH . WPINC . '/functions.php';
require ABSPATH . WPINC . '/shortcodes.php';
require ABSPATH . WPINC . '/media.php';
require ABSPATH . WPINC . '/class-wp-image-editor.php';
require ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';
require ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
require ABSPATH . 'wp-admin/includes/image.php';
require ABSPATH . 'wp-admin/includes/file.php';
function wp_generate_password( $length = 12, $special = true, $extra = false ) { return substr( bin2hex( random_bytes( $length ) ), 0, $length ); }
function __( $text, $domain = '' ) { return $text; }
add_filter( 'pre_option', static function ( $pre, $name, $default ) { return false === $default ? 0 : $default; }, 10, 3 );
function current_user_can( $cap ) { return true; }
require dirname( __DIR__ ) . '/10t-image-resize.php';
function check( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	echo "PASS: $message\n";
}
foreach ( array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' ) as $backend ) {
	$select = static function () use ( $backend ) { return array( $backend ); };
	add_filter( 'wp_image_editors', $select );
	if ( ! $backend::test() || ! $backend::supports_mime_type( 'image/webp' ) ) {
		echo "SKIP: $backend unavailable\n";
		remove_filter( 'wp_image_editors', $select );
		continue;
	}
	foreach ( array( array( 'jpeg', 2400, 1600, 1200, 800 ), array( 'png', 800, 2400, 400, 1200 ), array( 'png', 80, 40, 80, 40 ), array( 'webp', 2400, 800, 1200, 400 ), array( 'webp', 80, 40, 80, 40 ), array( 'gif', 1600, 800, 1600, 800 ) ) as $case ) {
		list( $format, $width, $height, $expected_w, $expected_h ) = $case;
		$path = tempnam( sys_get_temp_dir(), 'tent-test-' );
		$image = imagecreatetruecolor( $width, $height );
		$write = 'image' . $format;
		$write( $image, $path );
		imagedestroy( $image );
		$before = hash_file( 'sha256', $path );
		try {
			$file = TenT_Image_Resize::process( array( 'tmp_name' => $path, 'name' => 'sample.' . $format, 'type' => 'image/' . $format, 'error' => 0, 'size' => filesize( $path ) ) );
			check( empty( $file['error'] ), "$backend $format {$width}x{$height}: " . ( isset( $file['error'] ) ? $file['error'] : '' ) );
			$size = getimagesize( $path );
			check( $size[0] === $expected_w && $size[1] === $expected_h, 'Expected dimensions and aspect ratio' );
			if ( 'gif' === $format || ( 'webp' === $format && $width === 80 ) ) {
				check( $before === hash_file( 'sha256', $path ), 'Bypassed file unchanged' );
			} else {
				check( 'image/webp' === $size['mime'] && 'sample.webp' === $file['name'], 'WebP bytes and filename' );
			}
		} finally {
			unlink( $path );
		}
	}
	remove_filter( 'wp_image_editors', $select );
}
check( TenT_Image_Resize::sanitize( array( 'width' => -1, 'height' => 0, 'quality' => 200 ) ) === array( 'width' => 1200, 'height' => 1200, 'quality' => 100 ), 'Settings validation' );

$disable = static function () { return array(); };
add_filter( 'wp_image_editors', $disable );
$path = tempnam( sys_get_temp_dir(), 'tent-failure-' );
$image = imagecreatetruecolor( 20, 20 );
imagepng( $image, $path );
imagedestroy( $image );
try {
 $before = hash_file( 'sha256', $path );
 $file = TenT_Image_Resize::process( array( 'tmp_name' => $path, 'name' => 'test.png' ) );
 check( ! empty( $file['error'] ), 'Missing encoder rejects upload with error' );
 check( $before === hash_file( 'sha256', $path ), 'Failed processing preserves source bytes' );
} finally { unlink( $path ); }
remove_filter( 'wp_image_editors', $disable );
$path = tempnam( sys_get_temp_dir(), 'tent-svg-' );
file_put_contents( $path, '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>' );
try {
 $file = array( 'tmp_name' => $path, 'name' => 'test.svg' );
 check( $file === TenT_Image_Resize::process( $file ), 'SVG upload bypasses processing' );
} finally { unlink( $path ); }
