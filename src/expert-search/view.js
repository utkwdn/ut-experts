import apiFetch from '@wordpress/api-fetch';
import { decodeEntities } from '@wordpress/html-entities';
import { useState, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import Placeholder from 'react-bootstrap/Placeholder';

export default function View() {
	const [experts, setExperts] = useState([]);
	const [resultsPerPage] = useState(10);
	const [currentPage, setCurrentPage] = useState(1);
	const [totalPages, setTotalPages] = useState(1);
	const [isLoading, setIsLoading] = useState(true);
	const [isBackToVisible, setIsBackToVisible] = useState(false);
	const [isSticky, setIsSticky] = useState(false);
	const [loadingMore, setLoadingMore] = useState(false);
	const fetchController = useRef(null);
	const searchTimeout = useRef(null);
	const stickyEl = useRef(null);

	const [searchTerm, setSearchTerm] = useState(
		new URLSearchParams(window.location.search).get('search') || ''
	);
	const [areaFilter, setAreaFilter] = useState(
		new URLSearchParams(window.location.search).get('area') || ''
	);
	const [areaFilterName, setAreaFilterName] = useState('');
	const [areaMap, setAreaMap] = useState([]);
	const [allSubareas, setAllSubareas] = useState([]);
	const [subareaFilter, setSubareaFilter] = useState(
		new URLSearchParams(window.location.search).get('subarea') || ''
	);
	const [subareaFilterName, setSubareaFilterName] = useState('');
	const [subareaMap, setSubareaMap] = useState([]);

	const fetchExperts = (page = 1, append = false, signal) => {
		setIsLoading(page === 1);
		setLoadingMore(page > 1);

		const baseURL = `/wp/v2/expert?_embed&search_columns=post_title&orderby=title&order=asc&per_page=${resultsPerPage}&page=${page}`;
		const urlParams = new URLSearchParams(window.location.search);

		// Translate area/subarea params for API search
		const area = urlParams.get('area');
		const subarea = urlParams.get('subarea');
		urlParams.delete('area');
		urlParams.delete('subarea');

		if (subarea) {
			urlParams.set(
				'area_of_expertise',
				expandAreaIds(subarea).join(',')
			);
		} else if (area) {
			urlParams.set('area_of_expertise', expandAreaIds(area).join(','));
		}

		const params = urlParams.toString();

		apiFetch({ path: `${baseURL}&${params}`, signal, parse: false }) // Use parse: false to access headers
			.then((response) => {
				const totalPages =
					Number(response.headers.get('X-WP-TotalPages')) || 1;

				return response.json().then((data) => ({
					data,
					totalPages,
				}));
			})
			.then(({ data, totalPages }) => {
				setTotalPages(totalPages); // Save the total pages in state

				const formattedExperts = data.map((expert) => {
					const name = expert.title.rendered;
					let firstName = 'expert';
					if (typeof name === 'string' && name.trim() !== '') {
						const nameParts = name.trim().split(/\s+/);
						firstName = nameParts[0];
					}
					const title = expert.acf?.['expert_title'] || '';
					const excerpt = expert.excerpt.rendered;
					const areas =
						expert._embedded?.['wp:term']
							?.flat()
							.filter(
								(term) =>
									term.taxonomy ===
									'ut_expert_area_of_expertise'
							)
							.map((area) => ({
								id: area.id,
								name: area.name,
								link: area.link,
							})) || [];
					const bioLink = expert.link;

					const media = expert._embedded?.['wp:featuredmedia']?.[0];
					const expertImage = media?.source_url || '';
					const expertImageSrcSet = media?.media_details?.sizes
						? Object.values(media.media_details.sizes)
								.map(
									(size) =>
										`${size.source_url} ${size.width}w`
								)
								.join(', ')
						: '';
					const expertImageAlt = media?.alt_text || '';

					return {
						id: expert.id,
						name,
						firstName,
						title,
						excerpt,
						areas,
						bioLink,
						expertImage,
						expertImageSrcSet,
						expertImageAlt,
					};
				});
				setExperts((prev) =>
					append ? [...prev, ...formattedExperts] : formattedExperts
				);
			})
			.catch((error) => {
				if (error.name !== 'AbortError') {
					console.error('Error fetching experts:', error);
				}
			})
			.finally(() => {
				setIsLoading(false);
				setLoadingMore(false);
			});
	};

	// Fetch data on initial page load or filter/search changes
	useEffect(() => {
		// Don't fetch with an area/subarea filter until the taxonomy list
		// is loaded, otherwise expandAreaIds can't append child IDs.
		if ((areaFilter || subareaFilter) && allSubareas.length === 0) {
			return;
		}

		if (searchTimeout.current) clearTimeout(searchTimeout.current);

		searchTimeout.current = setTimeout(() => {
			if (fetchController.current) fetchController.current.abort();
			fetchController.current = new AbortController();
			const { signal } = fetchController.current;
			fetchExperts(currentPage, false, signal);
		}, 500);

		return () => {
			if (searchTimeout.current) clearTimeout(searchTimeout.current);
			fetchController.current?.abort();
		};
	}, [searchTerm, areaFilter, subareaFilter, currentPage, allSubareas]);

	// Fetch area data to populate select boxes
	useEffect(() => {
		apiFetch({ path: '/wp/v2/area_of_expertise?per_page=100' })
			.then((data) => {
				if (Array.isArray(data)) {
					const parents = data
						.filter((area) => area.parent === 0)
						.map((area) => ({
							name: decodeEntities(area.name),
							id: area.id,
						}));

					const children = data
						.filter((area) => area.parent !== 0)
						.map((area) => ({
							name: decodeEntities(area.name),
							id: area.id,
							parent: area.parent,
						}));

					setAreaMap(parents);
					setAllSubareas(children);
				} else {
					console.error('Unexpected data format:', data);
				}
			})
			.catch((error) => console.error('Error fetching areas:', error));
	}, []);

	const prevAreaFilter = useRef(areaFilter);

	// When a parent area is selected, populate the subarea select with its children
	useEffect(() => {
		if (!areaFilter) {
			setSubareaMap([]);
			if (subareaFilter) {
				setSubareaFilter('');
				updateURLParams('subarea', '');
			}
			prevAreaFilter.current = areaFilter;
			return;
		}

		const children = allSubareas.filter(
			(sub) => sub.parent === parseInt(areaFilter)
		);
		setSubareaMap(children);

		// Only clear the subarea when the parent area changes.
		if (prevAreaFilter.current !== areaFilter) {
			setSubareaFilter('');
			updateURLParams('subarea', '');
		}
		prevAreaFilter.current = areaFilter;
	}, [areaFilter, allSubareas]);

	// Match area ID with name for filter chips.
	useEffect(() => {
		const match = areaMap.find((obj) => obj.id === parseInt(areaFilter));
		if (match && match.name) {
			setAreaFilterName(match.name);
		}
	}, [areaFilter, areaMap]);

	// Match subarea ID with name for filter chips.
	useEffect(() => {
		const match = subareaMap.find(
			(obj) => obj.id === parseInt(subareaFilter)
		);
		if (match && match.name) {
			setSubareaFilterName(match.name);
		}
	}, [subareaFilter, subareaMap]);

	const scrollToElement = () => {
		const element = document.getElementById('filters');
		if (element) {
			window.scrollTo({
				top: element.getBoundingClientRect().top + window.scrollY,
				behavior: 'smooth',
			});
		}
	};

	useEffect(() => {
		const handleScroll = () => {
			// Back to top visibility
			setIsBackToVisible(window.scrollY > 1200);

			// Sticky filters
			if (stickyEl.current) {
				const adminBar = document.getElementById('wpadminbar');
				const rect = stickyEl.current.getBoundingClientRect();
				let offset = adminBar ? adminBar.offsetHeight : 0;

				setIsSticky(rect.top <= offset);
			}
		};

		window.addEventListener('scroll', handleScroll);
		return () => window.removeEventListener('scroll', handleScroll);
	}, []);

	const handleFilterChange = (key, value, setter) => {
		// Update filters for fetch and display.
		setter(value);
		updateURLParams(key, value);
		setCurrentPage(1);
	};

	const resetAllFilters = () => {
		handleFilterChange('search', '', setSearchTerm);
		handleFilterChange('area', '', setAreaFilter);
		handleFilterChange('subarea', '', setSubareaFilter);
	};

	const expandAreaIds = (parentId) => {
		if (!parentId) return [];
		const childIds = allSubareas
			.filter((child) => child.parent == parentId)
			.map((child) => child.id);
		return [parentId, ...childIds];
	};

	const updateURLParams = (key, value) => {
		const params = new URLSearchParams(window.location.search);
		if (value) {
			params.set(key, value);
		} else {
			params.delete(key);
		}
		const seperator = params.size > 0 ? '?' : '';
		window.history.replaceState(
			{},
			'',
			`${window.location.pathname}${seperator}${params.toString()}`
		);
	};

	const buildPageList = (current, total, endSize = 1, midSize = 1) => {
		const pages = [];
		let last = 0;
		for (let i = 1; i <= total; i++) {
			const atEnds = i <= endSize || i > total - endSize;
			const nearCurrent =
				i >= current - midSize && i <= current + midSize;
			if (atEnds || nearCurrent) {
				if (last && i - last > 1) pages.push('dots');
				pages.push(i);
				last = i;
			}
		}
		return pages;
	};

	const Pagination = ({ current, total, onChange }) => {
		if (total <= 1) return null;

		const go = (e, page) => {
			e.preventDefault();
			if (page < 1 || page > total || page === current) return;
			onChange(page);
		};

		return (
			<nav
				className="wp-block-query-pagination is-layout-flex wp-block-query-pagination-is-layout-flex"
				aria-label="Pagination"
				style={{ marginTop: 'var(--wp--preset--spacing--small)' }}
			>
				<div className="wp-block-query-pagination-numbers">
					{buildPageList(current, total).map((p, i) =>
						p === 'dots' ? (
							<span
								key={`dots-${i}`}
								className="page-numbers dots"
							>
								&hellip;
							</span>
						) : p === current ? (
							<span
								key={p}
								aria-current="page"
								className="page-numbers current"
							>
								{p}
							</span>
						) : (
							<a
								key={p}
								className="page-numbers"
								href="#"
								onClick={(e) => go(e, p)}
							>
								{p}
							</a>
						)
					)}
				</div>
			</nav>
		);
	};

	const displayPlaceholders = (numItems = 3) => {
		const bar = (width, height) => (
			<Placeholder animation="glow" style={{ display: 'block' }}>
				<Placeholder style={{ width, height, borderRadius: 4 }} />
			</Placeholder>
		);

		return (
			<>
				{Array.from({ length: numItems }).map((_, index) => (
					<div
						key={index}
						className="experts-filter-result wp-block-group has-background has-global-padding"
					>
						<figure className="experts-filter-image wp-block-image size-full has-custom-border">
							<Placeholder
								animation="glow"
								style={{ display: 'block' }}
							>
								<Placeholder
									className="experts-filter-thumbnail"
									style={{ width: '100%', aspectRatio: '1' }}
								/>
							</Placeholder>
						</figure>

						<div
							className="experts-filter-header"
							style={{ marginBottom: '20px' }}
						>
							{bar('35%', 24)}
							{bar('55%', 18)}
						</div>

						<div className="experts-filter-body">
							{bar('55%', 14)}
							{bar('55%', 14)}

							<div style={{ width: '50%', height: '30px' }}></div>

							{bar('30%', 16)}

							<div
								className="experts-categories taxonomy-category wp-block-post-terms"
								style={{ marginBottom: '20px' }}
							>
								<Placeholder animation="glow">
									<Placeholder
										style={{
											width: 60,
											height: 24,
											marginRight: 8,
										}}
									/>
									<Placeholder
										style={{
											width: 70,
											height: 24,
											marginRight: 8,
										}}
									/>
									<Placeholder
										style={{ width: 50, height: 24 }}
									/>
								</Placeholder>
							</div>

							{bar('40%', 16)}
						</div>
					</div>
				))}
			</>
		);
	};

	const CloseIcon = () => {
		return (
			<svg
				xmlns="http://www.w3.org/2000/svg"
				width="16"
				height="16"
				viewBox="0 0 16 16"
				fill="currentColor"
				aria-hidden="true"
			>
				<path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293z" />
			</svg>
		);
	};

	const ResetIcon = () => {
		return (
			<svg
				xmlns="http://www.w3.org/2000/svg"
				width="16"
				height="16"
				viewBox="0 0 16 16"
				fill="currentColor"
				aria-hidden="true"
			>
				<path d="M8 15C9.95417 15 11.6094 14.3219 12.9656 12.9656C14.3219 11.6094 15 9.95417 15 8C15 6.04583 14.3219 4.39062 12.9656 3.03437C11.6094 1.67812 9.95417 1 8 1C6.99375 1 6.03125 1.20781 5.1125 1.62344C4.19375 2.03906 3.40625 2.63333 2.75 3.40625V1H1V7.125H7.125V5.375H3.45C3.91667 4.55833 4.55469 3.91667 5.36406 3.45C6.17344 2.98333 7.05208 2.75 8 2.75C9.45833 2.75 10.6979 3.26042 11.7188 4.28125C12.7396 5.30208 13.25 6.54167 13.25 8C13.25 9.45833 12.7396 10.6979 11.7188 11.7188C10.6979 12.7396 9.45833 13.25 8 13.25C6.87708 13.25 5.86354 12.9292 4.95938 12.2875C4.05521 11.6458 3.42083 10.8 3.05625 9.75H1.21875C1.62708 11.2958 2.45833 12.5573 3.7125 13.5344C4.96667 14.5115 6.39583 15 8 15Z" />
			</svg>
		);
	};

	const ChevronUpIcon = () => {
		return (
			<svg
				xmlns="http://www.w3.org/2000/svg"
				width="16"
				height="16"
				viewBox="0 0 16 16"
				fill="currentColor"
				aria-hidden="true"
			>
				<path
					fill-rule="evenodd"
					d="M7.646 4.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1-.708.708L8 5.707l-5.646 5.647a.5.5 0 0 1-.708-.708z"
				/>
			</svg>
		);
	};

	return (
		<>
			<div className="experts-container-banner wp-block-block alignfull has-orange-background-color has-background" />
			<div className="experts-container wp-block-group alignfull has-global-padding is-layout-constrained wp-block-group-is-layout-constrained">
				<div className="experts-filters alignwide">
					{/* Filters  */}
					<h2
						className="wp-block-heading has-condensed-font-family"
						style={{ fontWeight: '600', margin: '5px 0 15px' }}
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
									value={searchTerm}
									onChange={(e) =>
										handleFilterChange(
											'search',
											e.target.value,
											setSearchTerm
										)
									}
									placeholder="Search by name"
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
									onChange={(e) =>
										handleFilterChange(
											'area',
											e.target.value,
											setAreaFilter
										)
									}
								>
									<option value="">Select a topic</option>
									{areaMap.map((area) => (
										<option
											key={area.id}
											aria-label="option"
											value={area.id}
											selected={
												areaFilter == area.id
													? true
													: false
											}
										>
											{area.name}
										</option>
									))}
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
									disabled={subareaMap.length === 0}
									aria-label="Subtopic"
									onChange={(e) =>
										handleFilterChange(
											'subarea',
											e.target.value,
											setSubareaFilter
										)
									}
								>
									<option value="">Select a subtopic</option>
									{subareaMap.map((subarea) => (
										<option
											key={subarea.id}
											aria-label="option"
											value={subarea.id}
											selected={
												subareaFilter == subarea.id
													? true
													: false
											}
										>
											{subarea.name}
										</option>
									))}
								</select>
								<label for="subarea-of-expertise">
									Subtopic
								</label>
							</div>
						</div>
					</div>
					{/* Filter Chips  */}
					<div
						ref={stickyEl}
						className={`experts-filters-sticky${
							isSticky ? ' experts-filters-sticky--fixed' : ''
						}`}
					>
						{(searchTerm.length > 0 || areaFilter.length > 0) && (
							<div className="experts-filters-chips">
								{searchTerm.length > 0 && (
									<button
										className="experts-filters-chip"
										onClick={() =>
											handleFilterChange(
												'search',
												'',
												setSearchTerm
											)
										}
									>
										<span>{searchTerm}</span> <CloseIcon />
									</button>
								)}
								{areaFilter.length > 0 && (
									<button
										className="experts-filters-chip"
										onClick={() =>
											handleFilterChange(
												'area',
												'',
												setAreaFilter
											)
										}
									>
										<span>{areaFilterName}</span>{' '}
										<CloseIcon />
									</button>
								)}
								{subareaFilter.length > 0 && (
									<button
										className="experts-filters-chip"
										onClick={() =>
											handleFilterChange(
												'subarea',
												'',
												setSubareaFilter
											)
										}
									>
										<span>{subareaFilterName}</span>{' '}
										<CloseIcon />
									</button>
								)}
								{/* Reset Filters button */}
								<button
									className="experts-reset-filters-chip"
									onClick={resetAllFilters}
								>
									<ResetIcon />
									<span>Reset filters</span>{' '}
								</button>
							</div>
						)}
					</div>
					{/* Results  */}
					<div
						className="experts-filters-results"
						id="expert-results"
					>
						{isLoading ? (
							displayPlaceholders(3)
						) : experts.length === 0 && !isLoading ? (
							<div className="experts-filters-no-results">
								<div className="experts-filters-no-results-content">
									<h2>
										There are no matches for your search.
									</h2>
									<p>
										Try searching again with different
										terms.
									</p>
								</div>
							</div>
						) : (
							<>
								{experts.map((expert) => (
									<div
										key={expert.id}
										className="experts-filter-result wp-block-group has-background has-global-padding"
									>
										{expert.expertImage && (
											<figure className="experts-filter-image wp-block-image size-full has-custom-border">
												<img
													decoding="async"
													src={expert.expertImage}
													srcSet={
														expert.expertImageSrcSet ||
														undefined
													}
													sizes="(max-width: 600px) 100vw, 600px"
													alt={expert.expertImageAlt}
													className="experts-filter-thumbnail"
												/>
											</figure>
										)}

										<div className="experts-filter-header">
											<h4 className="experts-name wp-block-heading">
												{expert.name}
											</h4>
											<p className="experts-title wp-block-paragraph">
												<strong>{expert.title}</strong>
											</p>
										</div>

										<div className="experts-filter-body">
											<div
												className="wp-block-paragraph"
												dangerouslySetInnerHTML={{
													__html: expert.excerpt,
												}}
											/>
											<p className="experts-area-title wp-block-paragraph">
												Area of expertise
											</p>
											<div className="experts-categories taxonomy-category wp-block-post-terms">
												{expert.areas.map((area) => (
													<a
														key={area.id}
														href={area.link}
														rel="tag"
													>
														{decodeEntities(
															area.name
														)}
													</a>
												))}
											</div>
											<p className="experts-bio-link is-style-utkwds-single-link wp-block-paragraph">
												<a href={expert.bioLink}>
													View {expert.firstName}'s
													Profile
												</a>
											</p>
										</div>
									</div>
								))}
							</>
						)}
						<Pagination
							current={currentPage}
							total={totalPages}
							onChange={(p) => {
								setCurrentPage(p);
								scrollToElement();
							}}
						/>
						{loadingMore && displayPlaceholders(5)}
						{isBackToVisible && (
							<button
								className="experts-back-to"
								onClick={scrollToElement}
								aria-label="Back to top"
								title="Back to top"
							>
								<ChevronUpIcon />
							</button>
						)}
					</div>
				</div>
			</div>
		</>
	);
}

const root = createRoot(document.getElementById('filters'));
root.render(<View />);
