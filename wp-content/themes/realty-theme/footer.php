<?php
$pods = pods('site');
$settings = [];
if ($pods && $pods->exists()) {
    $settings = $pods->fetch();
}
?>
<div class="el-footer">
    <div class="col-full flex-footer">
        <div class="footer-info">
            <div class="brand-footer">
                <?php the_custom_logo(); ?>
                <div class="title_logo">
                    <div class="upper"><?= get_bloginfo( 'name' ); ?></div>
                    <div class="desc text-color-orange">С нами тепло!</div>
                </div>
            </div>
            <div class="contact">
                <div class="item flex block__phone">
                    <div class="icon_phone icons__com"></div>
                    <div class="block">
                        <div class="p">
                            <a href="tel:"  class="phone">8 (800) 123 45 67</a>
                        </div>
                        <div class="hint">Звонок бесплатный</div>
                    </div>
                    <div class="social">

                    </div>
                </div>
                <div class="item flex center">
                    <div class="icon_email icons__com icon_pos"></div>
                    <div class="e">
                        <a class="email" href="mailto:sales@zavodtepla.com">sales@zavodtepla.com</a>
                    </div>
                </div>
                <div class="item flex">
                    <div class="icon_time icons__com"></div>
                    <div class="time">Пн-Пт c 8:00 до 19:00</div>
                </div>
            </div>
        </div>
        <div class="footer-widgets">
            <?php if(is_active_sidebar( 'footer-widget-1' )): ?>
               <div class="block footer-widget-1">
                  <?php dynamic_sidebar( 'footer-widget-1' ); ?>
               </div>
            <?php endif; ?>
            <?php if(is_active_sidebar( 'footer-widget-2' )): ?>
                <div class="block footer-widget-2">
                    <?php dynamic_sidebar( 'footer-widget-2' ); ?>
                </div>
            <?php endif; ?>
            <?php if(is_active_sidebar( 'footer-widget-3' )): ?>
                <div class="block footer-widget-3">
                    <?php dynamic_sidebar( 'footer-widget-3' ); ?>
                </div>
            <?php endif; ?>
        </div>
    </div><!-- .col-full -->
</div>
<?php wp_footer();
?>
</body>
</html>
