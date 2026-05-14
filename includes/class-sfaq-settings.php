<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SFAQ_Settings {

	public static function init() {
		// Nothing needed at boot time
	}

	/**
	 * Get a plugin setting with optional default fallback.
	 */
	public static function get( $key, $default = '' ) {
		return get_option( 'sfaq_' . $key, $default );
	}

	/**
	 * Update a plugin setting and flush cache.
	 */
	public static function update( $key, $value ) {
		update_option( 'sfaq_' . $key, sanitize_text_field( $value ) );
		SFAQ_Cache::flush();
	}

	/**
	 * Returns CSS scoped to .sfaq-section with all color/font variables.
	 * Scoped (not :root) so it wins over theme stylesheet variables.
	 * Font-family sanitized separately (may contain quotes).
	 */
	public static function get_css_variables() {
		$color_map = array(
			'--sfaq-q-text'        => array( 'color_q_text',        '#111111' ),
			'--sfaq-q-bg'          => array( 'color_q_bg',           '#ffffff' ),
			'--sfaq-q-border'      => array( 'color_q_border',       '#dddddd' ),
			'--sfaq-q-active-bg'   => array( 'color_q_active_bg',    '#cc0000' ),
			'--sfaq-q-active-text' => array( 'color_q_active_text',  '#ffffff' ),
			'--sfaq-q-hover-bg'    => array( 'color_q_hover_bg',     '#f5f5f5' ),
			'--sfaq-q-hover-text'  => array( 'color_q_hover_text',   '#111111' ),
			'--sfaq-a-text'        => array( 'color_a_text',         '#333333' ),
			'--sfaq-a-bg'          => array( 'color_a_bg',           '#ffffff' ),
			'--sfaq-icon-color'    => array( 'color_icon',           '#cc0000' ),
			'--sfaq-icon-active'   => array( 'color_icon_active',    '#ffffff' ),
		);

		// Font family — allow quotes, commas, hyphens; strip everything else
		$raw_font  = self::get( 'font_family', 'inherit' );
		$safe_font = preg_replace( '/[^a-zA-Z0-9\s,\'"\-]/', '', $raw_font );
		$safe_font = $safe_font ? $safe_font : 'inherit';

		$css = '.sfaq-section{';

		foreach ( $color_map as $prop => $pair ) {
			$val      = self::get( $pair[0], $pair[1] );
			$safe_val = sanitize_hex_color( $val );
			if ( ! $safe_val ) {
				$safe_val = preg_replace( '/[^a-zA-Z0-9#(),.\s%]/', '', $val );
			}
			$css .= $prop . ':' . $safe_val . ';';
		}

		$css .= '--sfaq-font-family:' . $safe_font . ';';
		$css .= '--sfaq-font-size-q:' . absint( self::get( 'font_size_q', '16' ) ) . 'px;';
		$css .= '--sfaq-font-size-a:' . absint( self::get( 'font_size_a', '15' ) ) . 'px;';
		$css .= '--sfaq-font-weight-q:' . absint( self::get( 'font_weight_q', '600' ) ) . ';';
		$css .= '--sfaq-radius:' . absint( self::get( 'border_radius', '10' ) ) . 'px;';
		$css .= '}';

		return $css;
	}
}
