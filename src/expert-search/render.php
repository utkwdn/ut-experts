<?php
/**
 * Render callback for the Expert Search block.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 *
 * @package UtkwdsExperts
 */

if ( ! function_exists( 'expert_search_render_callback' ) ) {
	/**
	 * Render the Expert Search block.
	 *
	 * @return string Rendered HTML.
	 */
	function expert_search_render_callback() {
		return '<div class="areasContainer alignfull" id="filters"></div>';
	}
}

// Output the rendered HTML.
echo wp_kses_post( expert_search_render_callback() );
