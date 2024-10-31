<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$url = '';
if ( is_post_type_archive( 'oa-project' ) ) {
	$project = get_post_type_object( 'oa-project' );

	// Get the slug.
	if ( $project ) {
		$project_slug = $project->rewrite['slug'];
		$url          = '/' . $project_slug;
	} else {
		$url = '/project';
	}
} else {
	$employee = get_post_type_object( 'oa-employee' );

	// Get the slug.
	if ( $employee ) {
		$employee_slug = $employee->rewrite['slug'];
		$url           = '/' . $employee_slug;
	} else {
		$url = '/employee';
	}
}
?>
<form role="search" method="get" class="oa-search-form" action="<?php echo esc_url( home_url( $url ) ); ?>">
	<label>
		<span class="screen-reader-text"><?php echo esc_html__( 'Search by name:', 'openasset' ); ?></span>
		<div class="custom-input hide-icon">
			<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'openasset_custom_search' ) ); ?>" />
			<input type="search" class="search-field" placeholder="<?php echo esc_attr__( 'Search by name', 'placeholder' ); ?>" value="<?php echo get_search_query(); ?>" name="s" title="<?php echo esc_attr__( 'Search for:', 'openasset' ); ?>" />
			<span class="clear-search"></span>
		</div>
	</label>
</form>
