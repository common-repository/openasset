<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Fetch all the parent 'keywords'.
$parent_terms = get_terms(
	array(
		'taxonomy'   => 'keyword',
		'hide_empty' => true,
		'parent'     => 0,
	)
);

$nonce = wp_create_nonce( 'keyword_filters_nonce' );
// Get the post type object.
$project = get_post_type_object( 'oa-project' );

// Get the slug.
if ( $project ) {
	$project_slug = $project->rewrite['slug'];
}

$oa_settings_options = get_option( 'openasset_settings' );

$project_criteria_keyword_categories = isset( $oa_settings_options['data-options']['project-criteria-keyword-categories'] ) ? $oa_settings_options['data-options']['project-criteria-keyword-categories'] : array();

foreach ( $parent_terms as $key => $value ) {
	$open_asset_id = get_term_meta( $value->term_id, 'openasset_id', true );
	if ( ! in_array( (int) $open_asset_id, $project_criteria_keyword_categories, true ) ) {
		unset( $parent_terms[ $key ] );
	}
}

if ( empty( $parent_terms ) ) {
	return;
}
?>
<div class="oa-keyword-filters">
	<form action="<?php echo '/' . esc_html( $project_slug ); ?>" method="GET">
		<ul class="oa-parent-keywords">
			<?php foreach ( $parent_terms as $parent_term ): ?>
				<li>
					<label>
						<input type="radio" name="toggler" value="<?php echo esc_attr( $parent_term->slug ); ?>">
						<span>
							<?php echo esc_html( $parent_term->name ); ?>
							<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="12px" height="12px" viewBox="0 0 201.458 201.457" xml:space="preserve"><g><path d="M193.177,46.233l8.28,8.28L100.734,155.241L0,54.495l8.28-8.279l92.46,92.46L193.177,46.233z"/></g></svg>
						</span>
					</label>
				</li>
			<?php endforeach; ?>
		</ul>

		<div class="oa-child-keywords-list">
			<?php foreach ( $parent_terms as $parent_term ) : ?>
				<div class="oa-child-keywords" data-taglist-id="<?php echo esc_attr( $parent_term->slug ); ?>">
					<ul>
						<?php
						// Fetch the child terms for the current parent term.
						$child_terms = get_terms(
							array(
								'taxonomy'   => 'keyword',
								'hide_empty' => true,
								'parent'     => $parent_term->term_id,
							)
						);
						?>
						<?php foreach ( $child_terms as $child_term ) : ?>
							<li>
								<label>
									<input type="checkbox" name="keywordfilters[]" value="<?php echo esc_attr( $child_term->slug ); ?>">
									<span>
										<?php echo esc_html( $child_term->name ); ?>
									</span>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</div>

		<input type="hidden" name="keyword_filters_nonce" value="<?php echo esc_html( $nonce ); ?>">

		<div class="oa-show-hide">
			<div class="selectedbar">
				<div class="selectedtags">
				</div>
				<div class="buttons">
					<a href="<?php echo '/' . esc_html( $project_slug ); ?>">
						<button class="oa-clearall" type="button">
							<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 17 17">
								<g id="Group_5088" data-name="Group 5088" transform="translate(-1033 -357)">
									<g id="Group_5087" data-name="Group 5087" transform="translate(4)">
									<line id="Line_195" data-name="Line 195" x2="5" y2="5" transform="translate(1035 363)" fill="none" stroke="#1f1f1f" stroke-linecap="round" stroke-linejoin="round" stroke-width="1"/>
									<line id="Line_196" data-name="Line 196" y1="5" x2="5" transform="translate(1035 363)" fill="none" stroke="#1f1f1f" stroke-linecap="round" stroke-linejoin="round" stroke-width="1"/>
									</g>
									<g id="Ellipse_1" data-name="Ellipse 1" transform="translate(1033 357)" fill="none" stroke="#1f1f1f" stroke-width="1">
									<circle cx="8.5" cy="8.5" r="8.5" stroke="none"/>
									<circle cx="8.5" cy="8.5" r="8" fill="none"/>
									</g>
								</g>
							</svg> Clear All
						</button>
					</a>
					<button class="oa-applyfilters" type="submit">
						<svg xmlns="http://www.w3.org/2000/svg" width="16.585" height="17" viewBox="0 0 16.585 17">
							<path id="filter" d="M6.309,16.5h0a1.166,1.166,0,0,1-1.165-1.174v-6a1,1,0,0,0-.195-.589l-.006-.009L4.936,8.72-.281,1.359A1.174,1.174,0,0,1-.371.135,1.163,1.163,0,0,1,.668-.5H14.912a1.163,1.163,0,0,1,1.04.636,1.175,1.175,0,0,1-.092,1.226L10.633,8.737a1,1,0,0,0-.195.59V14.2a1.138,1.138,0,0,1-.771,1.1L6.754,16.412A1.178,1.178,0,0,1,6.309,16.5ZM5.752,8.147A2.007,2.007,0,0,1,6.14,9.326v6a.172.172,0,0,0,.049.123.177.177,0,0,0,.19.035l.014-.006,2.964-1.131a.147.147,0,0,0,.084-.15V9.326a2.009,2.009,0,0,1,.385-1.176l.006-.008L15.05.781a.18.18,0,0,0,.016-.19A.17.17,0,0,0,14.914.5H.669A.171.171,0,0,0,.516.591.18.18,0,0,0,.53.779L5.749,8.143Z" transform="translate(0.502 0.5)" fill="#fff"/>
						</svg> Apply Filters
					</button>
				</div>
			</div>
		</div>
	</form>
</div>
