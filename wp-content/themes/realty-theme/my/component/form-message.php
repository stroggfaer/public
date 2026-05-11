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

// Получаем параметры из $args
$property_id   = $args['property_id'] ?? 0;
$owner_id      = $args['owner_id'] ?? 0;
$checkin_date  = $args['checkin_date'] ?? '';
$checkout_date = $args['checkout_date'] ?? '';
$context       = $args['context'] ?? 'general';

$app_data = array(
    'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
    'messageNonce'       => wp_create_nonce( 'my_cabinet_send_message_nonce' ),
    'getMessagesNonce'   => wp_create_nonce( 'my_cabinet_get_messages_nonce' ),
    'propertyId'         => $property_id,
    'ownerId'            => $owner_id,
    'checkinDate'        => $checkin_date,
    'checkoutDate'       => $checkout_date,
    'context'            => $context
);

$app_data_json = wp_json_encode( $app_data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );

?>

<div id="my-cabinet-message-form" class="my-cabinet-form-message" data-app="<?php echo esc_attr($app_data_json); ?>">
    <message-to-host :app-data="appData"></message-to-host>
</div>

<script type="module">
    const messageAppData = JSON.parse(document.getElementById('my-cabinet-message-form').dataset.app);
    const { createAppModule, MessageToHost } = window.VueAppModule;

    const MessageFormApp = createAppModule({
        setup() {
            return {
                appData: messageAppData,
            };
        },
    });

    MessageFormApp.component('MessageToHost', MessageToHost);
    MessageFormApp.mount('#my-cabinet-message-form');
</script>
