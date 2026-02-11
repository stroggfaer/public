<?php
/**
 * Настройки главной страницы
 *
 * @package RealtyTheme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Кастомный вывод настроек главной страницы
 *
 * @since 1.0.0
 */
function realty_render_home_settings() {
    ?>
    <h2><?php esc_html_e( 'Настройки главной страницы', 'realty-theme' ); ?></h2>

    <div class="realty-tab-description">
        <p><?php esc_html_e( 'Настройки главной страницы сайта.', 'realty-theme' ); ?></p>
    </div>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">
                <label for="realty_home_title">
                    <?php esc_html_e( 'Заголовок главной страницы:', 'realty-theme' ); ?>
                </label>
            </th>
            <td>
                <input type="text" name="realty_home_title" id="realty_home_title"
                    value="<?php echo esc_attr( get_option( 'realty_home_title', __( 'Найдите свой идеальный дом', 'realty-theme' ) ) ); ?>"
                    class="regular-text" />
                <p class="description">
                    <?php esc_html_e( 'Введите заголовок для главной страницы.', 'realty-theme' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="realty_home_subtitle">
                    <?php esc_html_e( 'Подзаголовок:', 'realty-theme' ); ?>
                </label>
            </th>
            <td>
                <textarea name="realty_home_subtitle" id="realty_home_subtitle" rows="3" class="large-text">
                    <?php echo esc_textarea( get_option( 'realty_home_subtitle', '' ) ); ?>
                </textarea>
                <p class="description">
                    <?php esc_html_e( 'Введите подзаголовок для главной страницы.', 'realty-theme' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="realty_home_cta_text">
                    <?php esc_html_e( 'Текст кнопки:', 'realty-theme' ); ?>
                </label>
            </th>
            <td>
                <input type="text" name="realty_home_cta_text" id="realty_home_cta_text"
                    value="<?php echo esc_attr( get_option( 'realty_home_cta_text', __( 'Начать поиск', 'realty-theme' ) ) ); ?>"
                    class="regular-text" />
                <p class="description">
                    <?php esc_html_e( 'Текст для кнопки призыва к действию.', 'realty-theme' ); ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}

// Вывод настроек при включении файла
realty_render_home_settings();
