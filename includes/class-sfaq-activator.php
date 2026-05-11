<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SFAQ_Activator {

    public static function activate() {
        // Set default options on first activation
        $defaults = array(
            'ui_style'             => 'style1',
            'global_title'         => 'Frequently Asked Questions',
            'show_section_title'   => '1',
            'display_method'       => 'shortcode', // 'shortcode' or 'auto'
            'schema_enabled'       => '1',
            'schema_type'          => 'standard', // 'standard' or 'custom'
            'custom_schema'        => '',
            // Colors
            'color_q_text'         => '#111111',
            'color_q_bg'           => '#ffffff',
            'color_q_border'       => '#dddddd',
            'color_q_active_bg'    => '#cc0000',
            'color_q_active_text'  => '#ffffff',
            'color_q_hover_bg'     => '#f5f5f5',
            'color_q_hover_text'   => '#111111',
            'color_a_text'         => '#333333',
            'color_a_bg'           => '#ffffff',
            'color_icon'           => '#cc0000',
            'color_icon_active'    => '#ffffff',
            // Typography
            'font_family'          => 'inherit',
            'font_size_q'          => '16',
            'font_size_a'          => '15',
            'font_weight_q'        => '600',
            'border_radius'        => '10',
        );

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( 'sfaq_' . $key ) ) {
                add_option( 'sfaq_' . $key, $value );
            }
        }

        // Store version
        update_option( 'sfaq_version', SFAQ_VERSION );
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }
}
