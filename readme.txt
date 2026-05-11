=== Smart FAQ Schema ===
Contributors: syedmaasif
Tags: faq, schema, rich results, accordion, seo
Requires at least: 5.6
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Beautiful FAQ sections with FAQPage JSON-LD schema for Google Rich Results. 5 accordion styles, Elementor-safe, mobile-optimized.

== Description ==

**Smart FAQ Schema** lets you add FAQ sections to any post, page, or WooCommerce product — complete with valid FAQPage schema markup for Google's FAQ rich results.

= Key Features =

* **Rank Math-style meta box** below the post editor (Classic Editor, Block Editor, and Elementor)
* **Add 1–20 FAQs per post** with drag-to-reorder support
* **FAQPage JSON-LD schema** (Google-recommended format) output in `<head>`
* **5 accordion UI styles** — Classic, Card, Minimal Line, Bold Border, Numbered
* **Per-post toggles**: Show/Hide FAQ section + Enable/Disable schema per post
* **Hidden schema mode** — generate schema without showing the FAQ section (for homepage SEO)
* **Global + per-post section title** — set "Frequently Asked Questions" globally, override per post
* **Shortcode** `[smart_faq id="POST_ID"]` for Elementor and any page builder
* **Auto-append mode** — automatically append FAQs after post content (no shortcode needed)
* **Bulk Manager** — see all posts with FAQ status, filter, search, and jump to edit
* **Full color & font control** — question/answer/hover/active colors, font family, size, weight
* **Custom schema support** — write your own JSON-LD template with dynamic placeholders
* **Cache auto-flush** on save (WP Rocket, W3TC, LiteSpeed, WP Super Cache, Autoptimize, SG Optimizer)
* **Elementor-safe** — no jQuery conflicts, no inline scripts, vanilla JS frontend
* **Conditional display** — FAQ section only shows if at least 1 FAQ is filled (safe for 100+ existing posts)

= Shortcode Usage =

`[smart_faq id="123"]`

Replace `123` with your post ID (shown in the meta box).

= Schema Placeholders (Custom Mode) =

* `{{faq_items}}` — Full FAQ array (questions + answers)
* `{{post_url}}` — Current post URL
* `{{post_title}}` — Current post title
* `{{site_name}}` — Your site name

== Installation ==

1. Upload the `smart-faq-schema` folder to `/wp-content/plugins/`
2. Activate via **Plugins → Installed Plugins**
3. Go to **Smart FAQ → Guide** for full usage instructions
4. Open any post/page, scroll below the editor, and find the **Smart FAQ Schema** panel

== Frequently Asked Questions ==

= Will this break my existing posts? =

No. The FAQ section only appears if at least 1 FAQ with a question is filled in. All existing posts without FAQs are untouched.

= Does this work with Elementor? =

Yes. Copy the shortcode from the meta box (`[smart_faq id="POST_ID"]`) and paste it into an Elementor Shortcode widget. The plugin uses vanilla JS and isolated CSS to prevent any conflicts.

= What schema type does this use? =

Standard `FAQPage` with `mainEntity` — the format recommended by Google for FAQ rich results. You can also supply a custom JSON-LD template.

= Can I hide the FAQ from visitors but still have schema? =

Yes. In the meta box, turn off "Show FAQs" but keep "Generate Schema" on. The accordion will not appear to visitors but the schema will still be in `<head>`.

= Does saving settings clear my cache? =

Yes. The plugin automatically calls cache-clear functions for WP Rocket, W3 Total Cache, LiteSpeed Cache, WP Super Cache, Autoptimize, SG Optimizer, and WordPress object cache.

== Screenshots ==

1. Smart FAQ meta box in the Classic Editor
2. 5 accordion UI styles
3. Appearance & Style settings
4. Schema settings with custom template example
5. Bulk Manager showing FAQ status across all posts

== Changelog ==

= 1.1.0 =
* Fixed all PHPCS/WordPress coding standard warnings and errors
* Added translators comments to all i18n strings with placeholders
* Fixed nonce verification and input sanitization across all files
* Added transient caching to direct database query in dashboard stats
* Updated Tested up to WordPress 6.9
* Replaced LiteSpeed do_action hook with direct class method call

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.1.1 =
Security and coding standards update. Upgrade recommended.


## Plugin Testing - PDF file
* Dashboard
* Bulk Manager
* Frontend UI
* Schema Validated
* Plugin Check Passed
* VirusTotal Clean
