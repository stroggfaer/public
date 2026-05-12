<?php
/**
 * Шаблон личного кабинета
 * Модуль "Мой кабинет" для темы Realty Theme
 *
 * @package RealtyTheme
 * @subpackage MyCabinet
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
get_header();
?>

<div class="my-dashboard-layout col-full">
    <!-- Боковое меню -->
    <?php get_template_part('my/component/my-sidebar'); ?>
    <!-- Основной контент -->
    <main class="my-main-content">
        <h1 class="my-page-title"><?php esc_html_e( 'Личный кабинет', 'realty-theme' ); ?></h1>
        
        <?php
        // Читаем GET параметры для режима бронирования
        $property_id   = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
        $checkin_date  = isset($_GET['checkin_date']) ? sanitize_text_field($_GET['checkin_date']) : '';
        $checkout_date = isset($_GET['checkout_date']) ? sanitize_text_field($_GET['checkout_date']) : '';
        $owner_id      = isset($_GET['owner_id']) ? intval($_GET['owner_id']) : 0;
        
        // Валидация дат на сервере
        $has_booking_context = ($property_id > 0 && $checkin_date && $checkout_date);
        
        if ($has_booking_context) {
            // Проверяем корректность дат
            $checkin_timestamp = strtotime($checkin_date);
            $checkout_timestamp = strtotime($checkout_date);
            
            if ($checkin_timestamp && $checkout_timestamp && $checkin_timestamp < $checkout_timestamp) {
                // РЕЖИМ С GET ПАРАМЕТРАМИ: показываем карточку бронирования и форму
                ?>
                <div class="my-content-grid">
                    <!-- Левая колонка: Карточка бронирования -->
                    <div class="my-content-main">
                        <!-- Карточка бронирования -->
                        <?php 
                        get_template_part('my/component/card-booking', null, array(
                            'property_id'   => $property_id,
                            'checkin_date'  => $checkin_date,
                            'checkout_date' => $checkout_date,
                            'owner_id'      => $owner_id
                        )); 
                        ?>
                        <!-- Форма сообщения -->
                        <div class="my-message-section">
                            <h3 class="my-page-title"><?php esc_html_e( 'Написать хозяину', 'realty-theme' ); ?></h3>
                            <?php 
                            get_template_part('my/component/form-message', null, array(
                                'property_id'   => $property_id,
                                'owner_id'      => $owner_id,
                                'checkin_date'  => $checkin_date,
                                'checkout_date' => $checkout_date,
                                'context'       => 'booking_inquiry'
                            )); 
                            ?>
                        </div>
                    </div>

                    <!-- Правая колонка: Боковая панель действий -->
                    <div class="my-content-sidebar">
                        <?php get_template_part('my/component/info-sidebar'); ?>
                    </div>
                </div>
                <?php
            } else {
                // Ошибка валидации дат
                ?>
                <div class="my-error-message">
                    <p><?php esc_html_e('Ошибка: Неверный формат дат. Пожалуйста, выберите корректные даты заезда и выезда.', 'realty-theme'); ?></p>
                    <a href="/property/" class="button__com"><?php esc_html_e('Вернуться к поиску', 'realty-theme'); ?></a>
                </div>
                <?php
            }
        } else {
            // РЕЖИМ БЕЗ GET ПАРАМЕТРОВ: показываем таблицу диалогов по объектам
            ?>
            <div class="my-content-grid">
                <!-- Левая колонка: Таблица диалогов -->
                <div class="my-content-main">
                    <h2 class="my-section-title"><?php esc_html_e('Ваши сообщения', 'realty-theme'); ?></h2>
                    
                    <!-- Контейнер для таблицы диалогов -->
                    <div id="my-threads-container" class="my-threads-container" 
                         data-nonce="<?php echo wp_create_nonce( 'my_cabinet_get_threads_nonce' ); ?>"
                         data-ajax-url="<?php echo admin_url( 'admin-ajax.php' ); ?>">
                        <div class="my-threads-loading">
                            <span class="material-symbols-outlined spinning">hourglass_empty</span>
                            <p><?php esc_html_e('Загрузка диалогов...', 'realty-theme'); ?></p>
                        </div>
                    </div>
                    
                    <!-- Заглушка если нет диалогов (показывается если AJAX вернет пустой список) -->
                    <div id="my-threads-empty" class="my-threads-placeholder" style="display: none;">
                        <span class="material-symbols-outlined my-empty-icon">chat_bubble_outline</span>
                        <h3><?php esc_html_e('У вас пока нет сообщений', 'realty-theme'); ?></h3>
                        <p><?php esc_html_e('Выберите недвижимость и напишите хозяину.', 'realty-theme'); ?></p>
                        <a href="/property/" class="button__com"><?php esc_html_e('Перейти к поиску', 'realty-theme'); ?></a>
                    </div>
                </div>

                <!-- Правая колонка: Боковая панель действий -->
                <div class="my-content-sidebar">
                    <?php get_template_part('my/component/info-sidebar'); ?>
                </div>
            </div>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const container = document.getElementById('my-threads-container');
                const emptyState = document.getElementById('my-threads-empty');
                const nonce = container.dataset.nonce;
                const ajaxUrl = container.dataset.ajaxUrl;
                
                // Загружаем диалоги
                const body = new URLSearchParams();
                body.append('action', 'my_cabinet_get_user_threads');
                body.append('nonce', nonce);
                
                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data.threads.length > 0) {
                        // Показываем таблицу
                        renderThreadsTable(result.data.threads);
                    } else {
                        // Показываем заглушку
                        container.style.display = 'none';
                        emptyState.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Ошибка загрузки диалогов:', error);
                    container.innerHTML = '<p class="my-error">Ошибка загрузки данных</p>';
                });
                
                function renderThreadsTable(threads) {
                    const tableHtml = `
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
                                ${threads.map(thread => `
                                    <tr>
                                        <td class="col-preview">
                                            ${thread.property_image 
                                                ? `<img src="${thread.property_image}" alt="${thread.property_title}" class="thread-property-img">`
                                                : '<span class="material-symbols-outlined no-image">image_not_supported</span>'
                                            }
                                        </td>
                                        <td class="col-name">
                                            <a href="${thread.property_url}" target="_blank" class="thread-property-link">
                                                ${thread.property_title}
                                            </a>
                                        </td>
                                        <td class="col-city">${thread.location || '—'}</td>
                                        <td class="col-address">${thread.address || '—'}</td>
                                        <td class="col-dates">${thread.dates_display || '—'}</td>
                                        <td class="col-status">
                                            <span class="status-badge status-${thread.status.toLowerCase()}">
                                                ${thread.status}
                                            </span>
                                        </td>
                                        <td class="col-actions">
                                            <a href="/my/dashboard/?property_id=${thread.property_id}&checkin_date=${thread.checkin_date}&checkout_date=${thread.checkout_date}" 
                                               class="button__com thread-view-btn">
                                                Перейти в диалог
                                            </a>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                    
                    container.innerHTML = tableHtml;
                }
            });
            </script>
            <?php
        }
        ?>
    </main>
</div>

<?php
get_footer();
