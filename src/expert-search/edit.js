/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit() {
	return (
		<>
			<div className="areasContainer alignfull" id="filters">
				<div className="experts-container-banner wp-block-block alignfull has-orange-background-color has-background"></div>
				<div className="experts-container wp-block-group alignfull has-global-padding is-layout-constrained wp-block-group-is-layout-constrained">
					<div className="experts-filters alignwide">
						<h2
							className="wp-block-heading has-condensed-font-family"
							style={{ fontWeight: 600, margin: '5px 0px 15px' }}
						>
							FIND AN EXPERT
						</h2>
						<div className="experts-filters-fields">
							<div className="experts-filters-field">
								<div className="form-floating">
									<input
										className="form-control"
										aria-label="Expert Search"
										id="expert-search"
										name="search"
										type="search"
										placeholder="Search by name"
										value=""
									/>
									<label for="expert-search">
										Search by name
									</label>
								</div>
							</div>
							<div className="experts-filters-field">
								<div className="form-floating">
									<select
										name="area"
										className="form-select"
										id="area-of-expertise"
										aria-label="Topic"
									>
										<option value="">Select a topic</option>
									</select>
									<label for="area-of-expertise">Topic</label>
								</div>
							</div>
							<div className="experts-filters-field">
								<div className="form-floating">
									<select
										name="subarea"
										className="form-select"
										id="subarea-of-expertise"
										disabled=""
										aria-label="Subtopic"
									>
										<option value="">
											Select a subtopic
										</option>
									</select>
									<label for="subarea-of-expertise">
										Subtopic
									</label>
								</div>
							</div>
						</div>
						<div className="experts-filters-sticky"></div>
						<div
							className="experts-filters-results"
							id="expert-results"
						>
							<div className="experts-filter-result wp-block-group has-background has-global-padding">
								<figure className="experts-filter-image wp-block-image size-full has-custom-border">
									<img
										decoding="async"
										src="/wp-content/themes/utkwds/assets/images/person-placeholder.jpeg"
										alt=""
										className="experts-filter-thumbnail"
									/>
								</figure>
								<div className="experts-filter-header">
									<h4 className="experts-name wp-block-heading">
										Expert Name
									</h4>
									<p className="experts-title wp-block-paragraph">
										<strong>Expert Title</strong>
									</p>
								</div>
								<div className="experts-filter-body">
									<div className="wp-block-paragraph">
										<p style={{ marginTop: '20px' }}>
											Lorem ipsum dolor sit amet,
											consectetur adipiscing elit, sed do
											eiusmod tempor incididunt ut labore
											et dolore magna aliqua. Ut enim ad
											minim veniam, quis nostrud
											exercitation ullamco laboris nisi ut
											aliquip ex ea commodo consequat.
										</p>
									</div>
									<p className="experts-area-title wp-block-paragraph">
										Area of expertise
									</p>
									<div className="experts-categories taxonomy-category wp-block-post-terms">
										<a
											href="http://plugins.local/area_of_expertise/entertainment/"
											rel="tag"
										>
											Area 1
										</a>
										<a
											href="http://plugins.local/area_of_expertise/journalism/"
											rel="tag"
										>
											Area 2
										</a>
										<a
											href="http://plugins.local/area_of_expertise/media/"
											rel="tag"
										>
											Area 3
										</a>
									</div>
									<p
										className="experts-bio-link is-style-utkwds-single-link wp-block-paragraph"
										style={{ marginTop: '20px' }}
									>
										<a href="http://plugins.local/experts/ahmad-hayat/">
											View Expert's Profile
										</a>
									</p>
								</div>
							</div>
							<div className="experts-filter-result wp-block-group has-background has-global-padding">
								<figure className="experts-filter-image wp-block-image size-full has-custom-border">
									<img
										decoding="async"
										src="/wp-content/themes/utkwds/assets/images/person-placeholder.jpeg"
										alt=""
										className="experts-filter-thumbnail"
									/>
								</figure>
								<div className="experts-filter-header">
									<h4 className="experts-name wp-block-heading">
										Expert Name
									</h4>
									<p className="experts-title wp-block-paragraph">
										<strong>Expert Title</strong>
									</p>
								</div>
								<div className="experts-filter-body">
									<div className="wp-block-paragraph">
										<p style={{ marginTop: '20px' }}>
											Lorem ipsum dolor sit amet,
											consectetur adipiscing elit, sed do
											eiusmod tempor incididunt ut labore
											et dolore magna aliqua. Ut enim ad
											minim veniam, quis nostrud
											exercitation ullamco laboris nisi ut
											aliquip ex ea commodo consequat.
										</p>
									</div>
									<p className="experts-area-title wp-block-paragraph">
										Area of expertise
									</p>
									<div className="experts-categories taxonomy-category wp-block-post-terms">
										<a
											href="http://plugins.local/area_of_expertise/entertainment/"
											rel="tag"
										>
											Area 1
										</a>
										<a
											href="http://plugins.local/area_of_expertise/journalism/"
											rel="tag"
										>
											Area 2
										</a>
										<a
											href="http://plugins.local/area_of_expertise/media/"
											rel="tag"
										>
											Area 3
										</a>
									</div>
									<p
										className="experts-bio-link is-style-utkwds-single-link wp-block-paragraph"
										style={{ marginTop: '20px' }}
									>
										<a href="http://plugins.local/experts/ahmad-hayat/">
											View Expert's Profile
										</a>
									</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</>
	);
}
