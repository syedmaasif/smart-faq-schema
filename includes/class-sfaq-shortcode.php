<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SFAQ_Shortcode {

	public static function init() {
		add_shortcode( 'smart_faq', array( __CLASS__, 'render' ) );
	}

	/**
	 * [smart_faq] or [smart_faq id="123"]
	 *
	 * Always pass the post ID explicitly to prevent fetching FAQs
	 * from the wrong post (e.g. when Elementor renders on a template page).
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts( array(
			'id' => 0,
		), $atts, 'smart_faq' );

		$post_id = intval( $atts['id'] );

		// Fallback to current post only if no ID given
		if ( $post_id <= 0 ) {
			$post_id = get_the_ID();
		}

		if ( ! $post_id ) {
			return '';
		}

		// Ensure assets are loaded (safe to call multiple times — checks before enqueuing)
		SFAQ_Frontend::enqueue_assets();

		return SFAQ_Frontend::render_faq_html( $post_id );
	}
}
