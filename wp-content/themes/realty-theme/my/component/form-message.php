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

$app_data = array(
    'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
    'messageNonce' => wp_create_nonce( 'my_cabinet_send_message_nonce' ),
);

$app_data_json = wp_json_encode( $app_data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );

?>

<div id="my-cabinet-message-form" class="my-cabinet-form-message">
    <message-to-host :app-data="appData"></message-to-host>
</div>

<script type="module">
    const appData = <?= $app_data_json; ?>;
    const { createAppModule, MessageToHost } = window.VueAppModule;

    const MessageFormApp = createAppModule({
        setup() {
            return {
                appData,
            };
        },
    });

    MessageFormApp.component('MessageToHost', MessageToHost);
    MessageFormApp.mount('#my-cabinet-message-form');
</script>
