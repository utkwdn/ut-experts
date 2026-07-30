<?php
/**
 * Patterns.
 *
 * Adds a "Media Contact" page under the Experts menu to allow
 * globally setting email and phone.
 *
 * @package utExperts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Registers the Expert Trending Topic Single block pattern.
 *
 * Buffers the pattern markup from the patterns directory so any PHP within it
 * (such as the placeholder image URL) is executed, then registers the pattern
 * under the theme's existing "contact-cards" category.
 *
 * @return void
 */
function utkwds_register_block_patterns() {
	ob_start();
	include plugin_dir_path( __FILE__ ) . '../patterns/expert-trending-topic-single.php';
	$content = ob_get_clean();

	register_block_pattern(
		'utkwds/expert-trending-topic-single',
		array(
			'title'         => __( 'Expert Trending Topic Single', 'utkwds' ),
			'description'   => __( 'Contact card to display an expert in the trending topics section', 'utkwds' ),
			'categories'    => array( 'contact-cards' ),
			'keywords'      => array( 'profile', 'card', 'bio', 'white' ),
			'viewportWidth' => 500,
			'content'       => $content,
		)
	);
}
add_action( 'init', 'utkwds_register_block_patterns' );

/**
 * Enqueues the compiled pattern stylesheet on the front end and in the editor.
 *
 * Hooked to "enqueue_block_assets" so the styles load in both contexts. Uses the
 * file's modification time as the version for cache busting when the build file
 * exists.
 *
 * @return void
 */
function utkwds_enqueue_pattern_styles() {
	$rel  = 'build/patterns-style.css';
	$path = plugin_dir_path( __DIR__ ) . $rel;
	wp_enqueue_style(
		'utkwds-patterns',
		plugins_url( $rel, __DIR__ ),
		array(),
		file_exists( $path ) ? filemtime( $path ) : null
	);
}
add_action( 'enqueue_block_assets', 'utkwds_enqueue_pattern_styles' );
