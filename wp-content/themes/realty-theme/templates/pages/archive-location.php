<?php
/**
 * WP entry: архив городов — /locations/
 *
 * @package Realty Theme
 */
get_header();

$paged = max(1, get_query_var('paged', 1));
$per_page = 12;
$total_locations = wp_count_terms('location', array('hide_empty' => true));
if (is_wp_error($total_locations)) {
    $total_locations = 0;
}
$offset = ($paged - 1) * $per_page;
$locations = get_terms(array(
    'taxonomy'   => 'location',
    'hide_empty' => true,
    'orderby'    => 'name',
    'order'      => 'ASC',
    'number'     => $per_page,
    'offset'     => $offset,
));
$total_pages = max(1, ceil($total_locations / $per_page));
$columns = !empty($locations) ? array_chunk($locations, ceil(count($locations) / 2)) : array();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main col-full" role="main">
        <div class="locations-page">
            <?php custom_breadcrumbs('Города'); ?>

            <header class="locations-header">
                <h1>Города</h1>
                <p>Выберите город, чтобы перейти к списку объектов.</p>
            </header>

            <?php if (!empty($locations) && !is_wp_error($locations)) : ?>
                <div class="locations-grid">
                    <?php foreach ($columns as $column) : ?>
                        <div class="locations-grid__column">
                            <?php foreach ($column as $term) : ?>
                                <?php get_template_part('template-parts/component/location-card', null, array('term' => $term)); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php
                $pagination = paginate_links(array(
                    'base'      => user_trailingslashit(home_url('/locations/page/%#%/')),
                    'format'    => '',
                    'current'   => $paged,
                    'total'     => $total_pages,
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
                <div class="locations-empty">Города не найдены.</div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php get_footer(); ?>
