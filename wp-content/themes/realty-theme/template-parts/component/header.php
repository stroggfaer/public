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
    <div class="top-header">
        <div class="col-full">
            <nav class="main-navigation">
                <button class="menu-toggle" aria-label="Открыть меню">☰</button>
                <?php
                wp_nav_menu([
                    'container' => false,
                    'menu_class' => 'menu',
                    'menu'=> 'header_top_menu'
                ]);
                ?>
            </nav>
        </div>
    </div>
    <div class="bottom-header">
        <div class="col-full">
            <div class="col-flex">
                <div class="site-branding">
                    <?php if (function_exists('the_custom_logo')) : ?>
                        <a href="<?php echo esc_url( home_url( '/' ) );?>"  class="custom-logo-link" rel="home" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) );?>">
                            <?php the_custom_logo(); ?>
                        </a>
                    <?php endif; ?>
                    <div class="block">
                        <div class="title_logo"><?php bloginfo( 'name' ); // Display the blog name ?></div>
                        <div class="desc"><?php bloginfo( 'description' );?></div>
                    </div>
                </div>
                <div class="search-form__header">
                    <?php get_search_form(); ?>
                </div>
                <div class="item block__phone">
                    <div class="icon_phone icons__com"></div>
                    <div class="block">
                        <div class="p">
                            <?php if(!empty($settings['phone'])): ?>
                                <a href="tel:<?php echo str_phone($settings['phone']) ?>"  class="phone"><?=$settings['phone']?></a>
                            <?php endif;?>
                        </div>
                        <div class="hint">Звонок бесплатный</div>
                    </div>
                    <div class="social">
                        <?php if(!empty($settings['tt'])): ?>
                            <a href="<?=$settings['tt']; ?>" class="no_border"><div class="icon_tt icons__com"></div></a>
                        <?php endif; ?>
                        <?php if(!empty($settings['ws'])): ?>
                            <a href="<?=$settings['ws']; ?>" class="no_border"><div class="icon_wa icons__com"></div></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
