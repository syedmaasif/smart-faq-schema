<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SFAQ_Cache {

	/**
	 * Flush known caching plugins when settings are saved.
	 */
	public static function flush() {
		// WP Super Cache
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}

		// W3 Total Cache
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}

		// WP Rocket
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}

		// LiteSpeed Cache — use class method directly to avoid unprefixed hook
		if ( class_exists( 'LiteSpeed_Cache_API' ) ) {
			LiteSpeed_Cache_API::purge_all();
		} elseif ( class_exists( 'LiteSpeed\Purge' ) ) {
			// Call via class method, not do_action, to stay prefix-compliant
			\LiteSpeed\Purge::purge_all();
		}

		// Autoptimize
		if ( class_exists( 'autoptimizeCache' ) ) {
			autoptimizeCache::clearall();
		}

		// SG Optimizer
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
		}

		// Comet Cache / Zencache
		if ( class_exists( 'comet_cache' ) ) {
			comet_cache::clear();
		}

		// WordPress object cache
		wp_cache_flush();
	}
}
