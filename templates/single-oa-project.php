<?php
/**
 * The Template for displaying all single projects
 *
 * This template can be overridden by copying it to yourtheme/openasset/single-project.php.
 *
 * @package OpenAsset
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OpenAsset\Core\Helpers;

$helpers = new Helpers();

$employee_post_type_obj = get_post_type_object( 'oa-employee' );
$project_post_type_obj  = get_post_type_object( 'oa-project' );

if ( ! empty( $employee_post_type_obj ) ) {
	// Get the plural label of the post type.
	$employee_plural_name = $employee_post_type_obj->labels->name;
} else {
	$employee_plural_name = 'Team';
}

if ( ! empty( $project_post_type_obj ) ) {
	// Get the plural label of the post type.
	$project_plural_name = $project_post_type_obj->labels->name;
} else {
	$project_plural_name = 'Projects';
}

get_header();

/* Start the Loop */
while ( have_posts() ) :
	the_post();

	$helpers->get_template_part( 'template-parts/content/content', 'oa-project' );

endwhile; // End of the loop.

get_footer();
