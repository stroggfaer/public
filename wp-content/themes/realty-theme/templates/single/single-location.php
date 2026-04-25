<?php
/**
 * WP entry: детальная города — /locations/{slug}
 *
 * @package Realty Theme
 */
get_header();

$term = get_queried_object();
if (!$term || is_wp_error($term)) {
    get_template_part('template-parts/content/content-none');
    get_footer();
    return;
}

$paged = max(1, get_query_var('paged', 1));
$properties_query = new WP_Query(array(
    'post_type'      => 'property',
    'posts_per_page' => 6,
    'paged'          => $paged,
    'tax_query'      => array(
        array(
            'taxonomy' => 'location',
            'field'    => 'term_id',
            'terms'    => $term->term_id,
        ),
    ),
));
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <div class="location-detail">
            <?php
            get_template_part('template-parts/component/breadcrumbs', null, array(
                'term' => $term,
                'taxonomy' => 'location',
                'taxonomy_archive_url' => home_url('/locations/'),
                'taxonomy_archive_label' => 'Города'
            ));
            ?>

            <header class="location-detail__header">
                <?php
                $thumbnail = '';
                if (function_exists('pods')) {
                    $pod = pods('location', $term->term_id);
                    if ($pod) {
                        $thumbnail_field = $pod->field('thumbnail');
                        if (!empty($thumbnail_field)) {
                            $thumbnail = is_array($thumbnail_field) ? ($thumbnail_field['guid'] ?? '') : $thumbnail_field;
                        }
                    }
                }
                ?>
                <div class="location-detail__thumbnail">
                    <img src="<?php echo esc_url($thumbnail ?: get_template_directory_uri() . '/assets/images/no_image.jpg'); ?>" alt="<?php echo esc_attr($term->name); ?>">
                </div>
                <h1><?php echo esc_html($term->name); ?></h1>
                <?php if (!empty($term->description)) : ?>
                    <div class="location-detail__description"><?php echo wp_kses_post(wpautop($term->description)); ?></div>
                <?php endif; ?>
            </header>

            <section class="location-detail__properties">
                <h2>Объекты в этом городе</h2>

                <?php if ($properties_query->have_posts()) : ?>
                    <div class="property-list locations-property-list">
                        <?php while ($properties_query->have_posts()) : $properties_query->the_post(); ?>
                            <?php $pod = pods('property', get_the_ID()); ?>
                            <?php get_template_part('template-parts/component/property-card', null, array('pod' => $pod)); ?>
                        <?php endwhile; ?>
                    </div>

                    <?php
                    $term_link = get_term_link($term);
                    if (is_wp_error($term_link)) {
                        $term_link = home_url('/locations/' . $term->slug . '/');
                    }
                    $pagination = paginate_links(array(
                        'base'      => user_trailingslashit(trailingslashit($term_link) . 'page/%#%/'),
                        'format'    => '',
                        'current'   => $paged,
                        'total'     => $properties_query->max_num_pages,
                        'mid_size'  => 1,
                        'prev_text' => '‹',
                        'next_text' => '›',
                        'type'      => 'list',
                    ));
                    if ($pagination) :
                        echo '<nav class="pagination">' . $pagination . '</nav>';
                    endif;
                    ?>
                <?php else : ?>
                    <div class="locations-empty">В этом городе пока нет объектов.</div>
                <?php endif; ?>

                <?php wp_reset_postdata(); ?>
            </section>
        </div>
    </main>
</div>

<?php get_footer(); ?>
