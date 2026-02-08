<?php
$pods = pods('site');
$settings = [];
if ($pods && $pods->exists()) {
    $settings = $pods->fetch();
}
 ?>
<header id="masthead" class="site-header">
    <?php if(is_active_sidebar( 'top-header-custom-widget' )): ?>
        <?php dynamic_sidebar( 'top-header-custom-widget' ); ?>
    <?php endif; ?>
    <div class="col-full">
        <div class="col-flex">
            <a class="site-branding" href="<?php echo esc_url( home_url( '/' ) );?>">
                <?php if (function_exists('the_custom_logo')) : ?>
                    <div class="custom-logo-link" rel="home" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) );?>">
                        <?php the_custom_logo(); ?>
                    </div>
                <?php endif; ?>
                <div class="block">
                    <div class="title_logo"><?php bloginfo( 'name' ); // Display the blog name ?></div>
                    <div class="desc"><?php bloginfo( 'description' );?></div>
                </div>
            </a>
<!--            <div class="search-form__header">-->
<!--                --><?php //get_search_form(); ?>
<!--            </div>-->
            <nav class="main-navigation">
                <?php
                wp_nav_menu([
                        'container' => false,
                        'menu_class' => 'menu',
                        'menu'=> 'header_top_menu',
                        'theme_location' => 'header_top_menu',
                ]);
                ?>
            </nav>
            <div class="actions">
                 <button class="button__com">Subscribe</button>
                 <a href="#" class="button__com link">Sign Up</a>
            </div>
        </div>
    </div>
</header>
