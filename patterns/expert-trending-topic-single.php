<?php
/**
 * Title: Expert Trending Topic Single
 * Slug: utkwds/expert-trending-topic-single
 * Description: Contact card to display an expert in the trending topics section
 * Categories: contact-cards
 * Keywords: profile, card, bio, white
 * Viewport Width: 500
 * Block Types:
 * Post Types:
 * Inserter: true
 *
 * @package utkwds
 */

?>

<!-- wp:group {"layout":{"type":"constrained"},"className":"trending-expert-group"} -->
<div class="trending-expert-group wp-block-group">
	<!-- wp:image {"width":"240px","height":"auto","scale":"cover","align":"right","className":"is-style-default trending-expert-figure"} -->
	<figure class="trending-expert-figure wp-block-image alignright is-resized has-custom-border is-style-default">
		<img 
			src="<?php echo esc_url( get_theme_file_uri( 'assets/images/person-placeholder.jpeg' ) ); ?>" 
			alt="person placeholder" 
		/>
	</figure>
	<!-- /wp:image -->

	<!-- wp:group {"layout":{"type":"flex","orientation":"vertical"}, "classname":"trending-expert-text"} -->
	<div class="trending-expert-text wp-block-group">
		<!-- wp:heading {"level":3, "className":"trending-expert-topic"} -->
		<h3 class="trending-expert-topic wp-block-heading">Topic</h3>
		<!-- /wp:heading -->

		<!-- wp:separator -->
		<hr class="wp-block-separator"/>
		<!-- /wp:separator -->

		<!-- wp:heading {"level":4, "className":"trending-expert-name"} -->
		<h4 class="trending-expert-name wp-block-heading">Expert Name</h4>
		<!-- /wp:heading -->

		<!-- wp:paragraph -->
		<p>A contact card to display an expert in the trending topics section of the expert's guide. Edit topic, name, description, link and photo to display an expert.</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"is-style-utkwds-single-link"} -->
		<p class="is-style-utkwds-single-link">
			<a href="#">View expert profile</a>
		</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
