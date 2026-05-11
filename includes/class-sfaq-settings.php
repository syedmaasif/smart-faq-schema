<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SFAQ_Settings {

	public static function init() {
		// Nothing needed at boot time — settings are read via get()
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
	 * Returns all color/font CSS variables as an inline <style> block.
	 *
	 * Scoped to .sfaq-section (not :root) so values always win over theme styles
	 * without needing !important, and without touching any global CSS variables.
	 *
	 * Font-family is NOT run through esc_attr because it may contain single/double
	 * quotes (e.g. 'Segoe UI') which esc_attr would encode and break.
	 * It is sanitized via a whitelist of allowed characters instead.
	 */
	public static function get_css_variables() {
		// Color values — safe to esc_attr (hex codes, no quotes needed)
		$color_vars = array(
			'--sfaq-q-text'        => self::get( 'color_q_text',        '#111111' ),
			'--sfaq-q-bg'          => self::get( 'color_q_bg',           '#ffffff' ),
			'--sfaq-q-border'      => self::get( 'color_q_border',       '#dddddd' ),
			'--sfaq-q-active-bg'   => self::get( 'color_q_active_bg',    '#cc0000' ),
			'--sfaq-q-active-text' => self::get( 'color_q_active_text',  '#ffffff' ),
			'--sfaq-q-hover-bg'    => self::get( 'color_q_hover_bg',     '#f5f5f5' ),
			'--sfaq-q-hover-text'  => self::get( 'color_q_hover_text',   '#111111' ),
			'--sfaq-a-text'        => self::get( 'color_a_text',         '#333333' ),
			'--sfaq-a-bg'          => self::get( 'color_a_bg',           '#ffffff' ),
			'--sfaq-icon-color'    => self::get( 'color_icon',           '#cc0000' ),
			'--sfaq-icon-active'   => self::get( 'color_icon_active',    '#ffffff' ),
		);

		// Numeric values
		$numeric_vars = array(
			'--sfaq-font-size-q'   => absint( self::get( 'font_size_q',    '16' ) ) . 'px',
			'--sfaq-font-size-a'   => absint( self::get( 'font_size_a',    '15' ) ) . 'px',
			'--sfaq-font-weight-q' => absint( self::get( 'font_weight_q',  '600' ) ),
			'--sfaq-radius'        => absint( self::get( 'border_radius',   '10' ) ) . 'px',
		);

		// Font family — strip everything except alphanumeric, spaces, commas, quotes, hyphens
		$raw_font   = self::get( 'font_family', 'inherit' );
		$safe_font  = preg_replace( '/[^a-zA-Z0-9\s,\'"\-]/', '', $raw_font );
		$safe_font  = $safe_font ? $safe_font : 'inherit';

		// Build CSS — scoped to .sfaq-section so it overrides theme styles reliably
		$css = '.sfaq-section{';

		foreach ( $color_vars as $prop => $val ) {
			// Sanitize: only allow valid hex colors or named colors
			$safe_val = sanitize_hex_color( $val );
			if ( ! $safe_val ) {
				// If not a hex color, strip anything dangerous
				$safe_val = preg_replace( '/[^a-zA-Z0-9#(),.\s%]/', '', $val );
			}
			$css .= $prop . ':' . $safe_val . ';';
		}

		foreach ( $numeric_vars as $prop => $val ) {
			$css .= $prop . ':' . $val . ';';
		}

		$css .= '--sfaq-font-family:' . $safe_font . ';';
		$css .= '}';

		return $css;
	}
}
