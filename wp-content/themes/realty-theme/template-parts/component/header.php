<?php
$pods = pods('site');
$settings = [];
if ($pods && $pods->exists()) {
    $settings = $pods->fetch();
}
$slot_center = apply_filters('realty_header_slot', '');
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
            <?php if(!empty($slot_center)): ?>
                 <?php echo $slot_center ?>
            <?php else: ?>
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
            <?php endif; ?>
            <div class="actions">
                 <a href="<?php echo esc_url( home_url( '/my-auth/' ) ); ?>" class="button__com">Вход</a>
                 <a href="<?php echo esc_url( add_query_arg( 'tab', 'register', home_url( '/my-auth/' ) ) ); ?>" class="button__com link">Регистрация</a>
            </div>
        </div>
    </div>
</header>
