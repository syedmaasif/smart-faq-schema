<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SFAQ_Schema {

	public static function init() {
		// Priority 99 — fires late in wp_head, after the global $post is fully set
		add_action( 'wp_head', array( __CLASS__, 'output_schema' ), 99 );
	}

	/**
	 * Output FAQPage schema in <head>.
	 * Uses get_queried_object_id() which is always correct in wp_head,
	 * unlike get_the_ID() which depends on the loop being active.
	 */
	public static function output_schema() {
		if ( ! is_singular() ) {
			return;
		}

		// get_queried_object_id() is reliable in wp_head — always returns the current page's ID
		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return;
		}

		// Global schema toggle
		$global_schema = SFAQ_Settings::get( 'schema_enabled', '1' );
		if ( '1' !== $global_schema ) {
			return;
		}

		// Per-post schema toggle — default ON (empty = new post = on)
		$show_schema = get_post_meta( $post_id, '_sfaq_show_schema', true );
		if ( '' === $show_schema ) {
			$show_schema = '1';
		}
		if ( '0' === $show_schema ) {
			return;
		}

		// Get FAQs — schema outputs even when show_faq=0 (hidden schema use case)
		$faqs = get_post_meta( $post_id, '_sfaq_faqs', true );
		if ( empty( $faqs ) || ! is_array( $faqs ) ) {
			return;
		}

		// Schema requires both question AND answer
		$valid_faqs = array();
		foreach ( $faqs as $faq ) {
			$q = trim( isset( $faq['question'] ) ? $faq['question'] : '' );
			$a = trim( isset( $faq['answer'] )   ? $faq['answer']   : '' );
			if ( '' !== $q && '' !== $a ) {
				$valid_faqs[] = array( 'question' => $q, 'answer' => $a );
			}
		}

		if ( empty( $valid_faqs ) ) {
			return;
		}

		$schema_type = SFAQ_Settings::get( 'schema_type', 'standard' );

		if ( 'custom' === $schema_type ) {
			$schema_json = self::build_custom_schema( $post_id, $valid_faqs );
		} else {
			$schema_json = self::build_standard_schema( $valid_faqs );
		}

		if ( ! $schema_json ) {
			return;
		}

		echo '<script type="application/ld+json">' . "\n";
		echo wp_json_encode( $schema_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode is safe for JSON-LD output.
		echo "\n" . '</script>' . "\n";
	}

	/**
	 * Standard FAQPage schema — Google-recommended format.
	 */
	private static function build_standard_schema( $faqs ) {
		$entities = array();
		foreach ( $faqs as $faq ) {
			$entities[] = array(
				'@type'          => 'Question',
				'name'           => $faq['question'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $faq['answer'] ),
				),
			);
		}

		return array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		);
	}

	/**
	 * Custom schema template with {{placeholder}} replacement.
	 */
	private static function build_custom_schema( $post_id, $faqs ) {
		$template = SFAQ_Settings::get( 'custom_schema', '' );
		if ( empty( $template ) ) {
			return self::build_standard_schema( $faqs );
		}

		$post       = get_post( $post_id );
		$post_url   = get_permalink( $post_id );
		$post_title = $post ? $post->post_title : '';

		$schema_str = $template;
		$schema_str = str_replace( '{{post_url}}',   esc_url( $post_url ),                    $schema_str );
		$schema_str = str_replace( '{{post_title}}', esc_html( $post_title ),                 $schema_str );
		$schema_str = str_replace( '{{site_name}}',  esc_html( get_bloginfo( 'name' ) ),      $schema_str );

		$items = array();
		foreach ( $faqs as $faq ) {
			$items[] = array(
				'@type'          => 'Question',
				'name'           => $faq['question'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $faq['answer'] ),
				),
			);
		}
		$items_json = wp_json_encode( $items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		$schema_str = str_replace( '"{{faq_items}}"', $items_json, $schema_str );

		$decoded = json_decode( $schema_str, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return self::build_standard_schema( $faqs );
		}

		return $decoded;
	}
}
