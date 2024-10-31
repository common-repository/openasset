<?php
/**
 * Template part for displaying posts.
 *
 * @package OpenAsset
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post;

$oa_data = get_post_meta( $post->ID, 'openasset_data', true );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( array( 'col-12', 'col-sm-6', 'col-md-4', 'col-lg-3', 'col-xl-2' ) ); ?>>
	<div class="<?php echo is_post_type_archive( 'oa-project' ) ? '' : 'text-center text-md-left'; ?>">
		<figure class="post-thumbnail">
			<a href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
				<?php
				$hero_image_url = get_the_post_thumbnail_url( $post, 'post_thumbnail', '' );
				if ( ! $hero_image_url ) {
					if ( is_post_type_archive( 'oa-project' ) ) : ?>
						<?php
						$hero_image_url = OPENASSET_URL . '/assets/img/project-placeholder.jpg';
					else :
						$hero_image_url = OPENASSET_URL . '/assets/img/employee-placeholder.jpg';
					endif;
				}
				?>
				<img src="<?php echo esc_url( $hero_image_url ); ?>" alt="<?php echo esc_html( get_the_title() ); ?>">
			</a>
		</figure><!-- .post-thumbnail -->
		<?php if ( is_post_type_archive( 'oa-project' ) ) : ?>
			<?php the_title( sprintf( '<p class="project-grid-heading text-left font-weight-bold text-dark text-center text-sm-start mb-1"><a class="text-dark text-decoration-none" href="%s">', esc_url( get_permalink() ) ), '</a></p>' ); ?>
			<!-- Get all keyword parent taxonomy, get the keyword parent id of 'city' and 'country', find the term of the post with parent id of city and country, output the city, country -->
			<?php
			$city_term = get_term_by( 'name', 'City', 'keyword' );
			$country_term = get_term_by( 'name', 'Country', 'keyword' );
			$city = '';
			$country = '';
			
			if ( $city_term && $country_term ) {
				$city_term_id = $city_term->term_id;
				$country_term_id = $country_term->term_id;
				$city_term = get_the_terms( $post->ID, 'keyword' );
				if ( $city_term ) {
					foreach ( $city_term as $term ) {
						if ( $term->parent === $city_term_id ) {
							$city = $term->name;
						}
						if ( $term->parent === $country_term_id ) {
							$country = $term->name;
						}
					}
				}
			}
			?>
			<p class="project-grid-subheading smaller-text text-center text-sm-start"><?php echo $city ? esc_html( $city ) : ''; ?><?php echo $city && $country ? ', ' : ''; ?><?php echo $country ? esc_html( $country ) : ''; ?></p>





		<?php else : ?>
			<?php the_title( sprintf( '<p class="employee-grid-heading text-center text-md-left font-weight-bold text-dark mb-0"><a class="text-dark text-decoration-none" href="%s">', esc_url( get_permalink() ) ), '</a></p>' ); ?>
			<?php $job_title = get_the_content(); ?>
			<p class="employee-grid-subheading text-center text-md-left smaller-text"><?php echo esc_html( $job_title ); ?></p>
		<?php endif; ?>
	</div><!-- .entry-header -->
</article><!-- #post-<?php the_ID(); ?> -->
