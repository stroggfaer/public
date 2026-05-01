<?php
/**
 * Vue-компонент формы авторизации / регистрации
 * Рендерит mount-точку и inline-скрипт Vue3 + Element Plus
 *
 * @package RealtyTheme
 * @subpackage MyCabinet
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Рендер компонента формы авторизации
 *
 * @param array $args
 *   - default_tab  'login' | 'register'
 */
function my_cabinet_render_auth_form( $args = array() ) {
    $defaults = array(
        'default_tab' => 'login',
    );
    $args = wp_parse_args( $args, $defaults );

    $app_data = array(
        'defaultTab'    => $args['default_tab'],
        'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
        'loginNonce'    => wp_create_nonce( 'my_cabinet_login_nonce' ),
        'registerNonce' => wp_create_nonce( 'my_cabinet_register_nonce' ),
        'redirectUrl'   => home_url( '/my-cabinet/' ),
    );

    $app_data_json = wp_json_encode( $app_data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );
    ?>

    <div id="auth-form-app" class="auth-page">
        <div class="auth-page__hero">
            <h1 class="auth-page__title">EstateStay</h1>
            <p class="auth-page__subtitle">Простой доступ к вашему личному кабинету и связи с хозяином</p>
        </div>

        <div class="auth-page__card">
            <div class="auth-tabs">
                <button
                    class="auth-tabs__btn"
                    :class="{ 'auth-tabs__btn--active': activeTab === 'login' }"
                    @click="activeTab = 'login'"
                    type="button"
                >Войти</button>
                <button
                    class="auth-tabs__btn"
                    :class="{ 'auth-tabs__btn--active': activeTab === 'register' }"
                    @click="activeTab = 'register'"
                    type="button"
                >Регистрация</button>
            </div>

            <div v-if="activeTab === 'login'" class="auth-form auth-form--login" key="login">
                <auth-login
                    :app-data="appData"
                    @switch-to-register="activeTab = 'register'"
                ></auth-login>
            </div>

            <div v-if="activeTab === 'register'" class="auth-form auth-form--register" key="register">
                <auth-registration
                    :app-data="appData"
                    @switch-to-login="activeTab = 'login'"
                ></auth-registration>
            </div>

        </div>
    </div>

    <script type="module">
        const appData = <?= $app_data_json; ?>;
        const { createAppModule, AuthLogin, AuthRegistration } = window.VueAppModule;
        const { ref } = Vue;

        const AuthFormApp = createAppModule({
            setup() {
                const activeTab = ref(appData.defaultTab || 'login');

                return {
                    activeTab,
                    appData,
                };
            },
        });

        // Регистрируем компоненты форм
        AuthFormApp.component('AuthLogin', AuthLogin);
        AuthFormApp.component('AuthRegistration', AuthRegistration);

        if (window.ElementPlusIconsVue) {
            AuthFormApp.component('Message', window.ElementPlusIconsVue.Message);
            AuthFormApp.component('Lock', window.ElementPlusIconsVue.Lock);
        }

        AuthFormApp.mount('#auth-form-app');
    </script>

    <?php
}
