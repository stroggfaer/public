<?php
/**
 * Компонент формы сообщения
 * Модуль "Мой кабинет" для темы Realty Theme
 *
 * @package RealtyTheme
 * @subpackage MyCabinet
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Рендер формы сообщения
 *
 * @param array $args Аргументы формы
 */
function my_cabinet_render_form_message( $args = array() ) {
    $defaults = array(
        'id' => 'my-cabinet-message-form',
        'action' => '',
        'method' => 'post',
        'submit_text' => __( 'Отправить', 'realty-theme' ),
        'success_message' => __( 'Сообщение отправлено!', 'realty-theme' ),
        'error_message' => __( 'Ошибка при отправке сообщения.', 'realty-theme' ),
    );
    
    $args = wp_parse_args( $args, $defaults );
    
    ob_start();
    ?>

    <form id="<?php echo esc_attr( $args['id'] ); ?>" class="my-cabinet-form-message" action="<?php echo esc_url( $args['action'] ); ?>" method="<?php echo esc_attr( $args['method'] ); ?>">
        <div class="form-message-content">
            <div class="form-group">
                <label for="message-title" class="form-label">
                    <?php esc_html_e( 'Тема сообщения', 'realty-theme' ); ?>
                </label>
                <input 
                    type="text" 
                    id="message-title" 
                    name="message_title" 
                    class="form-input" 
                    required
                    placeholder="<?php esc_attr_e( 'Введите тему сообщения', 'realty-theme' ); ?>"
                >
            </div>

            <div class="form-group">
                <label for="message-content" class="form-label">
                    <?php esc_html_e( 'Текст сообщения', 'realty-theme' ); ?>
                </label>
                <textarea 
                    id="message-content" 
                    name="message_content" 
                    class="form-input form-textarea" 
                    rows="5" 
                    required
                    placeholder="<?php esc_attr_e( 'Введите текст сообщения', 'realty-theme' ); ?>"
                ></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?php echo esc_html( $args['submit_text'] ); ?>
                </button>
            </div>
        </div>
    </form>

    <?php
    return ob_get_clean();
}
