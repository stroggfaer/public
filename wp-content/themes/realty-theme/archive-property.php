<?php
/**
 * The template for displaying property archive pages.
 *
 * @package Realty Theme
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <header class="page-header">
            <h1 class="page-title"><?php post_type_archive_title(); ?></h1>
        </header>
        
        <!-- Подключение шорткода фильтрации -->
        <div class="property-archive-filters">
            <?php echo do_shortcode('[property_filter]'); ?>
        </div>
    </main>
</div>

<style>
.property-archive-filters {
    margin: 20px 0;
}

.properties-archive-list {
    margin: 20px 0;
}

.properties-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.property-card {
    border: 1px solid #e1e1e1;
    border-radius: 8px;
    overflow: hidden;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.property-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.property-thumbnail {
    width: 100%;
    height: 200px;
    overflow: hidden;
}

.property-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.property-content {
    padding: 15px;
}

.entry-title {
    margin: 0 0 10px 0;
    font-size: 18px;
}

.entry-title a {
    text-decoration: none;
    color: #333;
}

.entry-title a:hover {
    color: #007cba;
}

.property-meta {
    margin-bottom: 10px;
}

.property-price {
    font-weight: bold;
    color: #28a745;
    font-size: 16px;
}

.property-address,
.property-capacity,
.property-location,
.property-type {
    font-size: 14px;
    color: #666;
    margin: 3px 0;
}

.entry-summary {
    margin: 10px 0;
    color: #555;
    line-height: 1.4;
}

.more-link {
    display: inline-block;
    padding: 8px 16px;
    background: #007cba;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
}

.more-link:hover {
    background: #005a87;
}

.no-properties {
    text-align: center;
    padding: 40px;
    color: #666;
}

.archive-pagination {
    text-align: center;
    margin: 30px 0;
}

.archive-pagination a,
.archive-pagination span {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 2px;
    text-decoration: none;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.archive-pagination a:hover {
    background: #f5f5f5;
}

.archive-pagination .current {
    background: #007cba;
    color: white;
    border-color: #007cba;
}

@media (max-width: 768px) {
    .properties-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
get_footer();
?>