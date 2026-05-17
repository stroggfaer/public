<?php
/**
 * Список сообщения
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<!-- Контейнер для таблицы диалогов (Vue 3) -->
<!-- Preloader visible before Vue mounts -->
<div id="my-threads-preloader" class="my-threads-loading">
    <span class="material-symbols-outlined spinning">progress_activity</span>
    <p><?php esc_html_e('Загрузка диалогов...', 'realty-theme'); ?></p>
</div>
<div id="vue-my-threads" style="display: none;">
    <!-- Loading state -->
    <div v-if="loading" class="my-threads-loading">
        <span class="material-symbols-outlined spinning">progress_activity</span>
        <p>{{ loadingText }}</p>
    </div>

    <!-- Threads table -->
    <div v-else-if="threads.length > 0" id="my-threads-container" class="my-threads-container"
         :data-nonce="nonce"
         :data-ajax-url="ajaxUrl">
        <table class="my-threads-table">
            <thead>
            <tr>
                <th class="col-preview">Превью</th>
                <th class="col-name">Название</th>
                <th class="col-city">Город</th>
                <th class="col-address">Адрес</th>
                <th class="col-dates">Даты</th>
                <th class="col-status">Статус</th>
                <th class="col-actions">Действия</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="thread in threads" :key="thread.property_id + '_' + thread.checkin_date">
                <td class="col-preview">
                    <img v-if="thread.property_image"
                         :src="thread.property_image"
                         :alt="thread.property_title"
                         class="thread-property-img">
                    <span v-else class="material-symbols-outlined no-image">image_not_supported</span>
                </td>
                <td class="col-name">
                    <a :href="thread.property_url" target="_blank" class="thread-property-link">
                        {{ thread.property_title }}
                    </a>
                </td>
                <td class="col-city">{{ thread.location || '—' }}</td>
                <td class="col-address">{{ thread.address || '—' }}</td>
                <td class="col-dates">{{ thread.dates_display || '—' }}</td>
                <td class="col-status">
                                            <span :class="['status-badge', 'status-' + thread.status_key]">
                                                {{ thread.status }}
                                            </span>
                </td>
                <td class="col-actions">
                    <a :href="getThreadUrl(thread)"
                       class="button__com thread-view-btn">
                        Перейти в диалог
                    </a>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <!-- Empty state -->
    <div v-else id="my-threads-empty" class="my-threads-placeholder">
        <span class="material-symbols-outlined my-empty-icon">chat_bubble_outline</span>
        <h3><?php esc_html_e('У вас пока нет сообщений', 'realty-theme'); ?></h3>
        <p><?php esc_html_e('Выберите недвижимость и напишите хозяину.', 'realty-theme'); ?></p>
        <a href="/property/" class="button__com"><?php esc_html_e('Перейти к поиску', 'realty-theme'); ?></a>
    </div>

    <!-- Error state -->
    <div v-if="error" class="my-error-message">
        <p>{{ error }}</p>
    </div>
</div>
<!-- Vue 3 Script -->
<script type="module">
    (function() {
        const { createAppModule } = window.VueAppModule;
        const { ref, onMounted } = Vue;

        const AppMyThreads = createAppModule({
            setup() {
                const threads = ref([]);
                const loading = ref(true);
                const error = ref(null);
                const loadingText = '<?php esc_html_e('Загрузка диалогов...', 'realty-theme'); ?>';
                const nonce = '<?php echo wp_create_nonce( 'my_cabinet_get_threads_nonce' ); ?>';
                const ajaxUrl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';

                // Загружаем диалоги
                const loadThreads = async () => {
                    try {
                        const body = new URLSearchParams();
                        body.append('action', 'my_cabinet_get_user_threads');
                        body.append('nonce', nonce);

                        const response = await fetch(ajaxUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: body.toString()
                        });

                        const result = await response.json();

                        if (result.success) {
                            threads.value = result.data.threads || [];
                        } else {
                            error.value = result.data?.message || 'Ошибка загрузки данных';
                        }
                    } catch (err) {
                        console.error('Ошибка загрузки диалогов:', err);
                        error.value = 'Ошибка загрузки данных';
                    } finally {
                        loading.value = false;
                    }
                };

                // Формируем URL для перехода в диалог
                const getThreadUrl = (thread) => {
                    const params = new URLSearchParams();
                    params.append('property_id', thread.property_id);
                    if (thread.checkin_date) params.append('checkin_date', thread.checkin_date);
                    if (thread.checkout_date) params.append('checkout_date', thread.checkout_date);
                    return `/my/dashboard/?${params.toString()}`;
                };

                onMounted(() => {
                    // Hide static preloader and show Vue component
                    const preloader = document.getElementById('my-threads-preloader');
                    const vueContainer = document.getElementById('vue-my-threads');
                    if (preloader) preloader.style.display = 'none';
                    if (vueContainer) vueContainer.style.display = 'block';

                    loadThreads();
                });

                return {
                    threads,
                    loading,
                    error,
                    loadingText,
                    nonce,
                    ajaxUrl,
                    getThreadUrl
                };
            }
        });

        AppMyThreads.mount('#vue-my-threads');
    })();
</script>
