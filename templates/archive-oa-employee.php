<?php
/**
 * The Template for displaying employee archives.
 *
 * This template can be overridden by copying it to yourtheme/openasset/archive-employee.php.
 *
 * @package OpenAsset
 */

defined( 'ABSPATH' ) || exit;

use OpenAsset\Core\Helpers;

$helpers = new Helpers();

$settings = get_option( 'openasset_settings' );

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

?>



	<div class="oa-container container">
		<header>
			<div class="oa-headings">
				<a  href="<?php echo esc_url( get_post_type_archive_link( 'oa-employee' ) ); ?>"><h2><?php echo esc_html( $employee_plural_name ); ?></h2></a>
			</div>
			<!-- search form -->
			<?php $helpers->get_template_part( 'template-parts/partials/search-form' ); ?>
		</header>
		<div class="oa-wide-border"></div>
		<div class="row employee-grid">
			<?php if ( have_posts() ) : ?>
				<?php while ( have_posts() ) : ?>
					<?php the_post(); ?>
					<?php $helpers->get_template_part( 'template-parts/content/content' ); ?>	
				<?php endwhile; ?>
			<?php else : ?>
				<h1 class="page-title">
					<?php
					printf(
						/* translators: %s: Employees custom plural name  */
						esc_html__( 'No %s found', 'openasset' ),
						esc_html( $employee_plural_name ),
					);
					?>
				</h1>
				<div class="page-content default-max-width">
					<p>
						<?php
						printf(
							/* translators: %s: Employees custom plural name  */
							esc_html__( 'There are no %s that match. Please adjust your search.', 'openasset' ),
							esc_html( $employee_plural_name ),
						);
						?>
					</p>
				</div>
			<?php endif; ?>
		</div>

		<?php
		the_posts_pagination();
		?>
	</div>


<?php
get_footer();
