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

// Fetch selected keywords from the URL
$selected_keywords = isset($_GET['keywordfilters']) ? array_map('sanitize_text_field', (array) $_GET['keywordfilters']) : [];
?>

<div class="oa-keyword-filters-new" data-selected-keywords="<?php echo esc_attr(json_encode($selected_keywords)); ?>">
    <form action="<?php echo '/' . esc_html( $project_slug ); ?>" method="GET" class="d-flex oa-keyword-form">
        <?php foreach ( $parent_terms as $parent_term ) : ?>
            <div class="oa-parent-keyword">
                <select class="oa-child-keywords" name="keywordfilters[]" data-parent-term="<?php echo esc_attr( $parent_term->slug ); ?>" data-placeholder="<?php echo esc_attr( $parent_term->name ); ?>">
                    <option value=""><?php echo esc_html( $parent_term->name ); ?></option>
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
                        <option value="<?php echo esc_attr( $child_term->slug ); ?>" <?php selected(in_array($child_term->slug, $selected_keywords)); ?>>
                            <?php echo esc_html( $child_term->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endforeach; ?>

        <input type="hidden" name="keyword_filters_nonce" value="<?php echo esc_html( $nonce ); ?>">
    </form>
</div>
