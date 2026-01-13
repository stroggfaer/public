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

        <?php
        if ( have_posts() ) :

            echo '<div class="properties-grid">'; // Container for property items

            while ( have_posts() ) : the_post();

                // Get custom fields for property card preview (using Pods fields)
                $property_price = get_post_meta(get_the_ID(), 'price', true);
                $property_address = get_post_meta(get_the_ID(), 'address', true);
                $property_guest_count = get_post_meta(get_the_ID(), 'guest_count', true);
                
                // Get location and property type terms
                $locations = get_the_terms(get_the_ID(), 'location');
                $property_types = get_the_terms(get_the_ID(), 'property_type');

                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class('property-card'); ?>>
                    <div class="property-thumbnail">
                        <?php
                        // Display featured image if available
                        if (has_post_thumbnail()) {
                            the_post_thumbnail('medium');
                        } else {
                            // Fallback image
                            echo '<img src="' . get_template_directory_uri() . '/assets/images/no_image.png" alt="No Image Available" />';
                        }
                        ?>
                    </div>
                    
                    <div class="property-content">
                        <header class="entry-header">
                            <?php the_title('<h2 class="entry-title"><a href="' . esc_url(get_permalink()) . '">', '</a></h2>'); ?>
                        </header>
                        
                        <div class="property-meta">
                            <?php if ($property_price) : ?>
                                <span class="property-price">Цена: <?php echo number_format($property_price, 0, '', ' '); ?> ₽/сутки</span>
                            <?php endif; ?>
                            
                            <?php if ($property_address) : ?>
                                <div class="property-address">Адрес: <?php echo esc_html($property_address); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($property_guest_count) : ?>
                                <div class="property-guests">Гостей: <?php echo intval($property_guest_count); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($locations) : ?>
                                <div class="property-location">Локация: 
                                    <?php echo esc_html(implode(', ', wp_list_pluck($locations, 'name'))); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($property_types) : ?>
                                <div class="property-type">Тип: 
                                    <?php echo esc_html(implode(', ', wp_list_pluck($property_types, 'name'))); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="entry-summary">
                            <?php the_excerpt(); ?>
                        </div>
                        
                        <footer class="entry-footer">
                            <a href="<?php the_permalink(); ?>" class="more-link">Подробнее</a>
                        </footer>
                    </div>
                </article>
                <?php

            endwhile;

            echo '</div>';

            // Pagination
            the_posts_pagination( array(
                'prev_text'          => '&larr;',
                'next_text'          => '&rarr;',
                'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'realty-theme' ) . ' </span>',
            ) );

        else :

            get_template_part( 'template-parts/content', 'none' );

        endif; ?>

    </main>
</div>

<?php
get_sidebar();
get_footer();