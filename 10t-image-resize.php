<?php
/**
 * Plugin Name: 10T Image Resize
 * Author: 10T Web Design
 * Author URI: https://10t.co/
 * Description: Resize new JPEG, PNG, and WebP uploads and store them as WebP.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: 10t-image-resize
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TenT_Image_Resize {
	const OPTION = 'tent_image_resize';

	public static function boot() {
		add_filter( 'wp_handle_upload_prefilter', array( __CLASS__, 'process' ) );
		add_filter( 'wp_handle_sideload_prefilter', array( __CLASS__, 'process' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
	}

	public static function settings() {
		return self::sanitize( get_option( self::OPTION, array() ) );
	}

	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$result = array();
		foreach ( array( 'width' => 1200, 'height' => 1200, 'quality' => 82 ) as $key => $default ) {
			$value = isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ? filter_var( $input[ $key ], FILTER_VALIDATE_INT ) : false;
			$result[ $key ] = false !== $value && $value > 0 ? min( $value, 'quality' === $key ? 100 : 30000 ) : $default;
		}
		return $result;
	}

	public static function available() {
		return wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
	}

	/** Process the temporary upload before WordPress assigns its final unique filename. */
	public static function process( $file ) {
		if ( ! empty( $file['error'] ) || empty( $file['tmp_name'] ) || ! is_file( $file['tmp_name'] ) ) {
			return $file;
		}
		$mime = wp_get_image_mime( $file['tmp_name'] );
		if ( ! in_array( $mime, array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
			return $file;
		}
		// Respect allowed upload types before changing the extension or MIME type.
		$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], get_allowed_mime_types() );
		if ( empty( $check['type'] ) || $check['type'] !== $mime || ! in_array( 'image/webp', get_allowed_mime_types(), true ) ) {
			$file['error'] = __( '10T Image Resize: This image type or WebP uploads are not permitted for your account.', '10t-image-resize' );
			return $file;
		}
		if ( ! self::available() ) {
			$file['error'] = __( '10T Image Resize requires Imagick or GD with WebP support. Ask your server administrator to enable it, then retry the upload.', '10t-image-resize' );
			return $file;
		}
		$editor = wp_get_image_editor( $file['tmp_name'], array( 'mime_type' => $mime ) );
		if ( is_wp_error( $editor ) ) {
			return self::failure( $file, $editor );
		}
		if ( ! $editor->supports_mime_type( 'image/webp' ) ) {
			return self::failure( $file, new WP_Error( 'webp', __( 'The selected image editor cannot encode WebP.', '10t-image-resize' ) ) );
		}
		if ( 'image/jpeg' === $mime ) {
			$result = $editor->maybe_exif_rotate();
			if ( is_wp_error( $result ) ) {
				return self::failure( $file, $result );
			}
		}
		$settings = self::settings();
		$size = $editor->get_size();
		$resize = $size['width'] > $settings['width'] || $size['height'] > $settings['height'];
		// Leave already-small WebP files byte-for-byte unchanged.
		if ( 'image/webp' === $mime && ! $resize ) {
			return $file;
		}
		$result = $editor->set_quality( $settings['quality'] );
		if ( is_wp_error( $result ) ) {
			return self::failure( $file, $result );
		}
		if ( $resize ) {
			$result = $editor->resize( $settings['width'], $settings['height'], false );
			if ( is_wp_error( $result ) ) {
				return self::failure( $file, $result );
			}
		}
		// A separate output ensures encoding failures cannot overwrite the source.
		$temp = wp_tempnam( 'tent-image.webp' );
		if ( ! $temp ) {
			return self::failure( $file, new WP_Error( 'temp', __( 'Could not create a temporary image file.', '10t-image-resize' ) ) );
		}
		$output = $temp . '.webp';
		$result = $editor->save( $output, 'image/webp' );
		wp_delete_file( $temp );
		if ( is_wp_error( $result ) ) {
			wp_delete_file( $output );
			return self::failure( $file, $result );
		}
		$output = $result['path'];
		$valid = wp_getimagesize( $output );
		if ( ! $valid || 'image/webp' !== wp_get_image_mime( $output ) || $valid[0] > $settings['width'] || $valid[1] > $settings['height'] ) {
			wp_delete_file( $output );
			return self::failure( $file, new WP_Error( 'output', __( 'The processed image failed validation.', '10t-image-resize' ) ) );
		}
		// Preserve PHP's uploaded-file identity so move_uploaded_file() still works.
		$copied = copy( $output, $file['tmp_name'] );
		wp_delete_file( $output );
		if ( ! $copied ) {
			return self::failure( $file, new WP_Error( 'copy', __( 'Could not replace the temporary upload.', '10t-image-resize' ) ) );
		}
		clearstatcache( true, $file['tmp_name'] );
		$file['name'] = pathinfo( $file['name'], PATHINFO_FILENAME ) . '.webp';
		$file['type'] = 'image/webp';
		$file['size'] = filesize( $file['tmp_name'] );
		return $file;
	}

	private static function failure( $file, $error ) {
		$file['error'] = sprintf(
			/* translators: %s: Error message explaining why image processing failed. */
			__( '10T Image Resize could not process this upload: %s', '10t-image-resize' ),
			$error->get_error_message()
		);
		return $file;
	}

	public static function menu() {
		add_options_page( '10T Image Resize', '10T Image Resize', 'manage_options', 'tent-image-resize', array( __CLASS__, 'page' ) );
	}

	public static function register() {
		register_setting( 'tent_image_resize', self::OPTION, array( 'type' => 'array', 'sanitize_callback' => array( __CLASS__, 'sanitize' ), 'default' => array( 'width' => 1200, 'height' => 1200, 'quality' => 82 ) ) );
		add_settings_section( 'tent_dimensions', __( 'Upload processing', '10t-image-resize' ), '__return_false', 'tent-image-resize' );
		foreach ( array( 'width' => __( 'Maximum width (pixels)', '10t-image-resize' ), 'height' => __( 'Maximum height (pixels)', '10t-image-resize' ), 'quality' => __( 'WebP quality (%)', '10t-image-resize' ) ) as $key => $label ) {
			add_settings_field( $key, $label, array( __CLASS__, 'field' ), 'tent-image-resize', 'tent_dimensions', array( 'key' => $key, 'label_for' => 'tent-' . $key ) );
		}
	}

	public static function field( $args ) {
		$settings = self::settings();
		$key = $args['key'];
		printf( '<input type="number" class="small-text" id="tent-%1$s" name="%2$s[%1$s]" value="%3$d" min="1" max="%4$d" required>', esc_attr( $key ), esc_attr( self::OPTION ), (int) $settings[ $key ], 'quality' === $key ? 100 : 30000 );
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap"><h1>10T Image Resize</h1><p>' . esc_html__( 'New JPEG and PNG uploads become WebP. WebP uploads are resized only when needed. Images keep their aspect ratio and are never enlarged. Original uploads are discarded after processing. GIF and SVG files are unchanged; existing media is unaffected.', '10t-image-resize' ) . '</p><form action="options.php" method="post">';
		settings_fields( 'tent_image_resize' );
		do_settings_sections( 'tent-image-resize' );
		submit_button();
		echo '</form></div>';
	}

	public static function notice() {
		if ( current_user_can( 'manage_options' ) && ! self::available() ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( '10T Image Resize: Imagick or GD with WebP support is unavailable. JPEG, PNG, and WebP uploads will be rejected until your server administrator enables a compatible image editor.', '10t-image-resize' ) . '</p></div>';
		}
	}
}

TenT_Image_Resize::boot();
