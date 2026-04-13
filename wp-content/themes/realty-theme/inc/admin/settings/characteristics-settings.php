<?php
/**
 * Страница управления справочником характеристик недвижимости
 * 
 * Позволяет администраторам создавать и управлять:
 * - Группами характеристик (char_group taxonomy)
 * - Характеристиками (characteristic CPT)
 * 
 * @package Realty_Theme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper: получить массив опций для стиля отображения
 * 
 * Используется в форме группы и формы характеристики
 * 
 * @param string $selected Выбранное значение
 * @param bool $as_options Вернуть как HTML option теги
 * @return string|array HTML options или массив данных
 */
function realty_get_display_style_options( $selected = 'standard', $as_options = true ) {
    $styles = array(
        'circle'     => __( 'Circle — Круглая иконка с лейблом', 'realty-theme' ),
        'standard'   => __( 'Standard — Стандартная иконка и текст', 'realty-theme' ),
        'prohibited' => __( 'Prohibited — Зачеркнутый (запрет)', 'realty-theme' ),
        'text'       => __( 'Text — Только текст', 'realty-theme' ),
    );
    
    if ( ! $as_options ) {
        return $styles;
    }
    
    $html = '';
    foreach ( $styles as $value => $label ) {
        $html .= '<option value="' . esc_attr( $value ) . '" ' . selected( $selected, $value, false ) . '>';
        $html .= esc_html( $label );
        $html .= '</option>';
    }
    
    return $html;
}

/**
 * Регистрация страницы справочника характеристик в меню админки
 * 
 * Добавляет подменю "Справочник" в раздел "Недвижимость"
 */
function realty_register_characteristics_admin_page() {
    add_submenu_page(
        'edit.php?post_type=property',      // Родительское меню (Недвижимость)
        'Справочник Характеристик',          // Заголовок страницы
        'Справочник',                        // Название в меню
        'manage_options',                    // Требуемые права
        'characteristics-reference',         // Slug страницы
        'realty_characteristics_page_content' // Callback функция
    );
}
add_action( 'admin_menu', 'realty_register_characteristics_admin_page' );

/**
 * Основной контент страницы справочника характеристик
 * 
 * Отображает интерфейс управления группами и характеристиками
 */
function realty_characteristics_page_content() {
    // Проверка прав доступа
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'У вас недостаточно прав для доступа к этой странице.', 'realty-theme' ) );
    }
    
    // Получаем все группы характеристик
    $groups = get_terms( array(
        'taxonomy'   => 'char_group',
        'hide_empty' => false,
        'orderby'    => 'meta_value_num',
        'meta_key'   => 'sort_order',
        'order'      => 'ASC',
    ) );
    
    // Текущая выбранная группа
    $current_group_id = isset( $_GET['group_id'] ) ? absint( $_GET['group_id'] ) : 0;
    
    ?>
    <div class="wrap characteristics-admin-wrap">
        <h1 class="wp-heading-inline">
            <?php esc_html_e( 'Справочник Характеристик', 'realty-theme' ); ?>
        </h1>
        
        <hr class="wp-header-end">
        
        <div class="characteristics-admin-container">
            <!-- Левая колонка: Список групп -->
            <div class="characteristics-groups-panel">
                <h2><?php esc_html_e( 'Группы', 'realty-theme' ); ?></h2>
                
                <button type="button" class="button button-primary" id="add-new-group-btn">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e( 'Новая группа', 'realty-theme' ); ?>
                </button>
                
                <ul class="characteristics-groups-list">
                    <?php if ( ! empty( $groups ) && ! is_wp_error( $groups ) ) : ?>
                        <?php foreach ( $groups as $group ) : ?>
                            <?php $display_style = get_term_meta( $group->term_id, 'display_style', true ) ?: 'standard'; ?>
                            <li class="group-item <?php echo ( $group->term_id === $current_group_id ) ? 'active' : ''; ?>" 
                                data-group-id="<?php echo esc_attr( $group->term_id ); ?>"
                                data-display-style="<?php echo esc_attr( $display_style ); ?>">
                                <a href="<?php echo esc_url( add_query_arg( 'group_id', $group->term_id ) ); ?>">
                                    <?php echo esc_html( $group->name ); ?>
                                </a>
                                <span class="group-actions">
                                    <button type="button" class="button-link edit-group-btn" 
                                            data-group-id="<?php echo esc_attr( $group->term_id ); ?>"
                                            title="<?php esc_attr_e( 'Редактировать', 'realty-theme' ); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                    </button>
                                    <button type="button" class="button-link delete-group-btn" 
                                            data-group-id="<?php echo esc_attr( $group->term_id ); ?>"
                                            data-group-name="<?php echo esc_attr( $group->name ); ?>"
                                            title="<?php esc_attr_e( 'Удалить', 'realty-theme' ); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <li class="no-groups">
                            <?php esc_html_e( 'Нет групп. Создайте первую!', 'realty-theme' ); ?>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Правая колонка: Характеристики выбранной группы -->
            <div class="characteristics-values-panel">
                <?php if ( $current_group_id ) : ?>
                    <?php
                    // Получаем информацию о текущей группе
                    $current_group = get_term( $current_group_id, 'char_group' );
                    
                    if ( $current_group && ! is_wp_error( $current_group ) ) :
                    ?>
                        <h2>
                            <?php echo esc_html( $current_group->name ); ?>
                            <span class="group-meta-info">
                                <?php
                                $type_ui = get_term_meta( $current_group_id, 'type_ui', true );
                                if ( $type_ui ) {
                                    echo ' (UI: ' . esc_html( $type_ui ) . ')';
                                }
                                ?>
                            </span>
                        </h2>
                        
                        <button type="button" class="button button-primary" id="add-new-characteristic-btn">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php esc_html_e( 'Добавить характеристику', 'realty-theme' ); ?>
                        </button>
                        
                        <?php
                        // Получаем характеристики этой группы
                        $characteristics = get_posts( array(
                            'post_type'      => 'characteristic',
                            'posts_per_page' => -1,
                            'orderby'        => 'meta_value_num',
                            'meta_key'       => 'sort_order',
                            'order'          => 'ASC',
                            'tax_query'      => array(
                                array(
                                    'taxonomy' => 'char_group',
                                    'field'    => 'term_id',
                                    'terms'    => $current_group_id,
                                ),
                            ),
                        ) );
                        ?>
                        
                        <?php if ( ! empty( $characteristics ) ) : ?>
                            <table class="wp-list-table widefat fixed striped characteristics-table">
                                <thead>
                                    <tr>
                                        <th class="col-order"><?php esc_html_e( 'Порядок', 'realty-theme' ); ?></th>
                                        <th class="col-icon"><?php esc_html_e( 'Иконка', 'realty-theme' ); ?></th>
                                        <th class="col-name"><?php esc_html_e( 'Название', 'realty-theme' ); ?></th>
                                        <th class="col-label"><?php esc_html_e( 'Лейбл', 'realty-theme' ); ?></th>
                                        <th class="col-value"><?php esc_html_e( 'Значение', 'realty-theme' ); ?></th>
                                        <th class="col-style"><?php esc_html_e( 'Стиль', 'realty-theme' ); ?></th>
                                        <th class="col-actions"><?php esc_html_e( 'Действия', 'realty-theme' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="characteristics-sortable">
                                    <?php foreach ( $characteristics as $char ) : ?>
                                        <?php
                                        $icon      = get_post_meta( $char->ID, 'icon', true );
                                        $icon_type = get_post_meta( $char->ID, 'icon_type', true );
                                        $style     = get_post_meta( $char->ID, 'style', true );
                                        $label     = get_post_meta( $char->ID, 'label', true );
                                        $value     = get_post_meta( $char->ID, 'value', true );
                                        $sort_order = get_post_meta( $char->ID, 'sort_order', true );
                                        ?>
                                        <tr class="characteristic-row" data-characteristic-id="<?php echo esc_attr( $char->ID ); ?>">
                                            <td class="col-order">
                                                <span class="dashicons dashicons-menu characteristics-drag-handle"></span>
                                                <?php echo esc_html( $sort_order ?: '—' ); ?>
                                            </td>
                                            <td class="col-icon">
                                                <?php if ( $icon_type === 'upload' ) : ?>
                                                    <?php
                                                    $media_id = get_post_meta( $char->ID, 'media_id', true );
                                                    if ( $media_id ) {
                                                        echo wp_get_attachment_image( $media_id, array( 32, 32 ) );
                                                    }
                                                    ?>
                                                <?php elseif ( $icon ) : ?>
                                                    <span class="material-symbols-outlined characteristic-icon-preview">
                                                        <?php echo esc_html( $icon ); ?>
                                                    </span>
                                                <?php else : ?>
                                                    <span class="dashicons dashicons-format-image"></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="col-name">
                                                <strong><?php echo esc_html( $char->post_title ); ?></strong>
                                            </td>
                                            <td class="col-label">
                                                <?php echo esc_html( $label ?: '—' ); ?>
                                            </td>
                                            <td class="col-value">
                                                <?php echo esc_html( $value ?: '—' ); ?>
                                            </td>
                                            <td class="col-style">
                                                <span class="style-badge style-<?php echo esc_attr( $style ); ?>">
                                                    <?php echo esc_html( $style ?: '—' ); ?>
                                                </span>
                                            </td>
                                            <td class="col-actions">
                                                <button type="button" class="button-link edit-characteristic-btn" 
                                                        data-characteristic-id="<?php echo esc_attr( $char->ID ); ?>"
                                                        title="<?php esc_attr_e( 'Редактировать', 'realty-theme' ); ?>">
                                                    <?php esc_html_e( 'Изменить', 'realty-theme' ); ?>
                                                </button>
                                                |
                                                <button type="button" class="button-link delete-characteristic-btn" 
                                                        data-characteristic-id="<?php echo esc_attr( $char->ID ); ?>"
                                                        data-characteristic-name="<?php echo esc_attr( $char->post_title ); ?>"
                                                        title="<?php esc_attr_e( 'Удалить', 'realty-theme' ); ?>">
                                                    <?php esc_html_e( 'Удалить', 'realty-theme' ); ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else : ?>
                            <div class="no-characteristics-notice">
                                <p><?php esc_html_e( 'В этой группе пока нет характеристик.', 'realty-theme' ); ?></p>
                                <p><?php esc_html_e( 'Нажмите "Добавить характеристику" чтобы создать первую.', 'realty-theme' ); ?></p>
                            </div>
                        <?php endif; ?>
                        
                    <?php endif; ?>
                <?php else : ?>
                    <div class="no-group-selected-notice">
                        <h3><?php esc_html_e( 'Выберите группу', 'realty-theme' ); ?></h3>
                        <p><?php esc_html_e( 'Выберите группу из списка слева или создайте новую.', 'realty-theme' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Модальное окно для группы -->
        <div id="group-modal" class="characteristics-modal" style="display: none;">
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="group-modal-title"><?php esc_html_e( 'Добавить группу', 'realty-theme' ); ?></h3>
                    <button type="button" class="modal-close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="group-form">
                        <input type="hidden" id="group-id" name="group_id" value="">
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="group-name"><?php esc_html_e( 'Название группы', 'realty-theme' ); ?> <span class="required">*</span></label></th>
                                <td>
                                    <input type="text" id="group-name" name="group_name" class="regular-text" required>
                                    <p class="description"><?php esc_html_e( 'Обязательное поле', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="group-description"><?php esc_html_e( 'Описание', 'realty-theme' ); ?></label></th>
                                <td>
                                    <textarea id="group-description" name="group_description" class="large-text" rows="3"></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="group-type-ui"><?php esc_html_e( 'Тип UI для фильтров', 'realty-theme' ); ?></label></th>
                                <td>
                                    <select id="group-type-ui" name="group_type_ui">
                                        <option value="checkbox">Checkbox (множественный выбор)</option>
                                        <option value="radio">Radio (одиночный выбор)</option>
                                        <option value="select">Select (выпадающий список)</option>
                                        <option value="multi_select">Multi Select (множественный выбор)</option>
                                        <option value="chips">Chips (теги)</option>
                                        <option value="switch">Switch (вкл/выкл)</option>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Как группа будет отображаться в фильтрах на сайте', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="group-display-style"><?php esc_html_e( 'Стиль отображения', 'realty-theme' ); ?> <span class="required">*</span></label></th>
                                <td>
                                    <select id="group-display-style" name="group_display_style" required>
                                        <?php echo realty_get_display_style_options( 'standard' ); ?>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Определяет как характеристики этой группы будут отображаться на сайте', 'realty-theme' ); ?></p>
                                    <div id="group-style-warning" class="notice notice-warning inline" style="display: none; margin-top: 10px;">
                                        <p><strong><?php esc_html_e( 'Внимание:', 'realty-theme' ); ?></strong> <?php esc_html_e( 'Нельзя изменить стиль после создания характеристик. Удалите все характеристики группы для изменения.', 'realty-theme' ); ?></p>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="group-sort-order"><?php esc_html_e( 'Порядок', 'realty-theme' ); ?></label></th>
                                <td>
                                    <input type="number" id="group-sort-order" name="group_sort_order" value="0" min="0">
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Настройки', 'realty-theme' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="group-active" name="group_active" value="1" checked>
                                        <?php esc_html_e( 'Активна', 'realty-theme' ); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" id="group-use-in-filters" name="group_use_in_filters" value="1" checked>
                                        <?php esc_html_e( 'Использовать в фильтрах', 'realty-theme' ); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" id="group-show-in-archive" name="group_show_in_archive" value="1">
                                        <?php esc_html_e( 'Показывать в архиве', 'realty-theme' ); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button modal-cancel-btn"><?php esc_html_e( 'Отмена', 'realty-theme' ); ?></button>
                    <button type="button" class="button button-primary" id="save-group-btn"><?php esc_html_e( 'Сохранить', 'realty-theme' ); ?></button>
                </div>
            </div>
        </div>
        
        <!-- Модальное окно для характеристик -->
        <div id="characteristic-modal" class="characteristics-modal" style="display: none;">
            <div class="modal-overlay"></div>
            <div class="modal-content modal-large">
                <div class="modal-header">
                    <h3 id="characteristic-modal-title"><?php esc_html_e( 'Добавить характеристику', 'realty-theme' ); ?></h3>
                    <button type="button" class="modal-close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="characteristic-form">
                        <input type="hidden" id="characteristic-id" name="characteristic_id" value="">
                        <input type="hidden" id="characteristic-group-id" name="group_id" value="<?php echo esc_attr( $current_group_id ); ?>">
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="char-title"><?php esc_html_e( 'Название', 'realty-theme' ); ?> <span class="required">*</span></label></th>
                                <td>
                                    <input type="text" id="char-title" name="char_title" class="regular-text" required>
                                    <p class="description"><?php esc_html_e( 'Обязательное поле. Например: "GUESTS", "Pet Friendly"', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="char-description"><?php esc_html_e( 'Описание', 'realty-theme' ); ?></label></th>
                                <td>
                                    <textarea id="char-description" name="char_description" class="large-text" rows="2"></textarea>
                                    <p class="description"><?php esc_html_e( 'Краткое описание (опционально)', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr class="char-field-label">
                                <th><label for="char-label"><?php esc_html_e( 'Лейбл', 'realty-theme' ); ?></label></th>
                                <td>
                                    <input type="text" id="char-label" name="char_label" class="regular-text">
                                    <p class="description"><?php esc_html_e( 'Отображаемый лейбл (опционально). Например: "GUESTS"', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr class="char-field-hint">
                                <th><label for="char-hint"><?php esc_html_e( 'Хинт/Подпись', 'realty-theme' ); ?></label></th>
                                <td>
                                    <input type="text" id="char-hint" name="char_hint" class="regular-text">
                                    <p class="description"><?php esc_html_e( 'Подпись под названием (опционально). Например: "Allowed"', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr class="char-field-value">
                                <th><label for="char-value"><?php esc_html_e( 'Значение', 'realty-theme' ); ?></label></th>
                                <td>
                                    <input type="text" id="char-value" name="char_value" class="regular-text">
                                    <p class="description"><?php esc_html_e( 'Дополнительное значение (опционально). Например: "Up to 4"', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="char-style"><?php esc_html_e( 'Стиль отображения', 'realty-theme' ); ?> <span class="required">*</span></label></th>
                                <td>
                                    <input type="text" id="char-style-display" class="regular-text" readonly style="background: #f9f9f9;">
                                    <input type="hidden" id="char-style" name="char_style" value="standard">
                                    <p class="description"><?php esc_html_e( 'Наследуется от группы. Измените стиль в настройках группы.', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr class="char-field-icon-type">
                                <th><?php esc_html_e( 'Тип иконки', 'realty-theme' ); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="radio" name="char_icon_type" value="material_symbol" checked>
                                            <?php esc_html_e( 'Material Symbol', 'realty-theme' ); ?>
                                        </label><br>
                                        <label>
                                            <input type="radio" name="char_icon_type" value="upload">
                                            <?php esc_html_e( 'Загруженный файл', 'realty-theme' ); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr id="material-symbol-row" class="char-field-icon-type">
                                <th><label for="char-icon"><?php esc_html_e( 'Material Symbol', 'realty-theme' ); ?></label></th>
                                <td>
                                    <div class="icon-picker-container">
                                        <input type="text" id="char-icon" name="char_icon" class="regular-text" placeholder="Например: group, pets, bed">
                                        <button type="button" class="button" id="preview-icon-btn"><?php esc_html_e( 'Preview', 'realty-theme' ); ?></button>
                                        <div id="icon-preview-box" class="icon-preview-box">
                                            <span class="material-symbols-outlined" style="font-size: 32px;">help</span>
                                        </div>
                                    </div>
                                    <p class="description"><?php esc_html_e( 'Введите название иконки из Material Symbols Outlined', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr id="media-upload-row" class="char-field-icon-type" style="display: none;">
                                <th><label for="char-media-id"><?php esc_html_e( 'Загрузить файл', 'realty-theme' ); ?></label></th>
                                <td>
                                    <div class="media-uploader-container">
                                        <button type="button" class="button" id="upload-media-btn"><?php esc_html_e( 'Выбрать файл', 'realty-theme' ); ?></button>
                                        <input type="hidden" id="char-media-id" name="char_media_id" value="">
                                        <div id="media-preview-box" class="media-preview-box">
                                            <p class="description"><?php esc_html_e( 'Файл не выбран', 'realty-theme' ); ?></p>
                                        </div>
                                    </div>
                                    <p class="description"><?php esc_html_e( 'Поддерживаемые форматы: PNG, SVG, JPG, GIF. Макс. размер: 1MB', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="char-parent"><?php esc_html_e( 'Родительская характеристика', 'realty-theme' ); ?></label></th>
                                <td>
                                    <select id="char-parent" name="char_parent">
                                        <option value="0"><?php esc_html_e( '— Нет (корневая)', 'realty-theme' ); ?></option>
                                        <?php
                                        // Получаем все характеристики текущей группы для выбора родителя
                                        if ( $current_group_id ) {
                                            $parent_chars = get_posts( array(
                                                'post_type'      => 'characteristic',
                                                'posts_per_page' => -1,
                                                'orderby'        => 'title',
                                                'order'          => 'ASC',
                                                'post_status'    => 'publish',
                                                'tax_query'      => array(
                                                    array(
                                                        'taxonomy' => 'char_group',
                                                        'field'    => 'term_id',
                                                        'terms'    => $current_group_id,
                                                    ),
                                                ),
                                            ) );
                                            
                                            if ( ! empty( $parent_chars ) ) {
                                                foreach ( $parent_chars as $parent_char ) {
                                                    echo '<option value="' . esc_attr( $parent_char->ID ) . '">' . esc_html( $parent_char->post_title ) . '</option>';
                                                }
                                            }
                                        }
                                        ?>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Опционально. Для создания иерархии (подхарактеристики)', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="char-sort-order"><?php esc_html_e( 'Порядок', 'realty-theme' ); ?></label></th>
                                <td>
                                    <input type="number" id="char-sort-order" name="char_sort_order" value="0" min="0">
                                    <p class="description"><?php esc_html_e( 'Позиция в списке (0 = автоматически)', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Настройки отображения', 'realty-theme' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="char-show-in-filter" name="char_show_in_filter" value="1">
                                        <?php esc_html_e( 'Отображать в фильтре', 'realty-theme' ); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e( 'Если включено, характеристика будет доступна в фильтрах на сайте', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Статус', 'realty-theme' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="char-active" name="char_active" value="1" checked>
                                        <?php esc_html_e( 'Активна', 'realty-theme' ); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button modal-cancel-btn"><?php esc_html_e( 'Отмена', 'realty-theme' ); ?></button>
                    <button type="button" class="button button-primary" id="save-characteristic-btn"><?php esc_html_e( 'Сохранить', 'realty-theme' ); ?></button>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .characteristics-admin-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .characteristics-groups-panel {
            background: #fff;
            padding: 15px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .characteristics-groups-list {
            list-style: none;
            margin: 15px 0;
            padding: 0;
        }
        
        .group-item {
            padding: 10px;
            margin: 5px 0;
            background: #f9f9f9;
            border: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .group-item.active {
            background: #e5f5fa;
            border-color: #0073aa;
        }
        
        .group-item a {
            text-decoration: none;
            color: #333;
            flex: 1;
        }
        
        .group-item:hover {
            background: #f0f0f0;
        }
        
        .group-actions {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .delete-group-btn {
            color: #d63638;
        }
        
        .delete-group-btn:hover {
            color: #b32d2e;
        }
        
        .no-group-selected-notice,
        .no-characteristics-notice {
            background: #fff;
            padding: 40px;
            text-align: center;
            border: 1px solid #ddd;
        }
        
        .characteristics-table {
            margin-top: 15px;
        }
        
        .characteristics-drag-handle {
            cursor: move;
            color: #999;
        }
        
        .style-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .style-circle { background: #e5f5fa; color: #0073aa; }
        .style-standard { background: #f0f0f0; color: #333; }
        .style-prohibited { background: #ffe5e5; color: #d63638; }
        .style-text { background: #f5f5f5; color: #666; }
        
        /* Modal Styles */
        .characteristics-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 100000;
        }
        
        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 0;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-large {
            max-width: 800px;
        }
        
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #ddd;
            text-align: right;
        }
        
        .modal-close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        
        .required {
            color: #d63638;
        }
        
        .characteristic-icon-preview {
            font-size: 24px;
        }
        
        /* Characteristic Form Styles */
        .icon-picker-container,
        .media-uploader-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .icon-preview-box,
        .media-preview-box {
            min-height: 60px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px dashed #ccc;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .icon-preview-box .material-symbols-outlined {
            font-size: 32px;
            color: #333;
        }
        
        .media-preview-box img {
            max-width: 100px;
            max-height: 100px;
            object-fit: contain;
        }
        
        #preview-icon-btn {
            align-self: flex-start;
        }
        
        #upload-media-btn {
            align-self: flex-start;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Modal handlers
        function openModal(modalId) {
            $(modalId).show();
        }
        
        function closeModal(modalId) {
            $(modalId).hide();
        }
        
        // Add new group button
        $('#add-new-group-btn').on('click', function() {
            $('#group-modal-title').text('<?php esc_html_e( "Добавить группу", "realty-theme" ); ?>');
            $('#group-form')[0].reset();
            $('#group-id').val('');
            
            // Сбрасываем состояние поля стиля (разблокируем)
            $('#group-display-style').prop('disabled', false);
            $('#group-style-warning').hide();
            
            openModal('#group-modal');
        });
        
        // Edit group button
        $('.edit-group-btn').on('click', function(e) {
            e.preventDefault();
            var groupId = $(this).data('group-id');
            
            // Загружаем данные группы через AJAX
            $.post(ajaxurl, {
                action: 'characteristic_get_group',
                nonce: '<?php echo wp_create_nonce( "characteristic_get_group_nonce" ); ?>',
                group_id: groupId
            }, function(response) {
                if (response.success) {
                    var group = response.data;
                    
                    // Заполняем форму
                    $('#group-modal-title').text('<?php esc_html_e( "Редактировать группу", "realty-theme" ); ?>');
                    $('#group-id').val(group.term_id);
                    $('#group-name').val(group.name);
                    $('#group-description').val(group.description || '');
                    $('#group-type-ui').val(group.type_ui || 'checkbox');
                    $('#group-display-style').val(group.display_style || 'standard');
                    $('#group-sort-order').val(group.sort_order || 0);
                    $('#group-active').prop('checked', group.active == 1);
                    $('#group-use-in-filters').prop('checked', group.use_in_filters == 1);
                    $('#group-show-in-archive').prop('checked', group.show_in_archive == 1);
                    
                    // Проверяем, есть ли характеристики в группе
                    if (group.has_characteristics) {
                        $('#group-display-style').prop('disabled', true);
                        $('#group-style-warning').show();
                    } else {
                        $('#group-display-style').prop('disabled', false);
                        $('#group-style-warning').hide();
                    }
                    
                    openModal('#group-modal');
                } else {
                    alert(response.data || '<?php esc_html_e( "Ошибка загрузки данных", "realty-theme" ); ?>');
                }
            });
        });
        
        // Close modal buttons
        $('.modal-close-btn, .modal-cancel-btn, .modal-overlay').on('click', function() {
            closeModal('#group-modal');
            closeModal('#characteristic-modal');
        });
        
        // Add new characteristic button
        $('#add-new-characteristic-btn').on('click', function() {
            // Сбрасываем форму
            $('#characteristic-form')[0].reset();
            $('#characteristic-id').val('');
            $('#characteristic-modal-title').text('<?php esc_html_e( "Добавить характеристику", "realty-theme" ); ?>');
            
            // Устанавливаем значения по умолчанию
            $('input[name="char_icon_type"][value="material_symbol"]').prop('checked', true);
            toggleIconType('material_symbol');
            $('#icon-preview-box').html('<span class="material-symbols-outlined" style="font-size: 32px;">help</span>');
            $('#media-preview-box').html('<p class="description"><?php esc_html_e( "Файл не выбран", "realty-theme" ); ?></p>');
            
            // Наследуем стиль от группы
            inheritStyleFromGroup();
            
            // Обновляем видимость полей
            updateFieldVisibility($('#char-style').val());
            
            openModal('#characteristic-modal');
        });
        
        // Функция наследования стиля от группы
        function inheritStyleFromGroup() {
            var groupId = $('#characteristic-group-id').val();
            if (!groupId) return;
            
            // Получаем стиль группы
            var groupItem = $('.group-item[data-group-id="' + groupId + '"]');
            var groupStyle = groupItem.data('display-style') || 'standard';
            
            // Устанавливаем стиль характеристики
            $('#char-style').val(groupStyle);
            
            // Отображаем человекочитаемое название
            var styleLabels = {
                'circle': 'Circle — Круглая иконка с лейблом',
                'standard': 'Standard — Стандартная иконка и текст',
                'prohibited': 'Prohibited — Зачеркнутый (запрет)',
                'text': 'Text — Только текст'
            };
            
            $('#char-style-display').val(styleLabels[groupStyle] || groupStyle);
        }
        
        // Функция обновления видимости полей в зависимости от стиля
        function updateFieldVisibility(style) {
            // Скрываем все поля сначала
            $('.char-field-label, .char-field-hint, .char-field-value, .char-field-icon-type').hide();
            
            // Показываем в зависимости от стиля
            switch(style) {
                case 'circle':
                    // Circle: Хинт + Значение + Иконка (Лейбл скрыт)
                    $('.char-field-hint, .char-field-value, .char-field-icon-type').show();
                    break;
                    
                case 'standard':
                    // Standard: Значение + Иконка (Лейбл и Хинт скрыты)
                    $('.char-field-value, .char-field-icon-type').show();
                    break;
                    
                case 'prohibited':
                    // Prohibited: Хинт + Значение + Иконка (Лейбл скрыт)
                    $('.char-field-hint, .char-field-value, .char-field-icon-type').show();
                    break;
                    
                case 'text':
                    // Text: Лейбл + Хинт + Значение (без иконки)
                    $('.char-field-label, .char-field-hint, .char-field-value').show();
                    break;
            }
        }
        
        // Edit characteristic button
        $('.edit-characteristic-btn').on('click', function() {
            var charId = $(this).data('characteristic-id');
            loadCharacteristicData(charId);
        });
        
        // Load characteristic data for editing
        function loadCharacteristicData(charId) {
            $.post(ajaxurl, {
                action: 'characteristic_get',
                nonce: '<?php echo wp_create_nonce( "characteristic_get_nonce" ); ?>',
                characteristic_id: charId
            }, function(response) {
                if (response.success) {
                    var char = response.data;
                    
                    // Заполняем форму
                    $('#characteristic-modal-title').text('<?php esc_html_e( "Редактировать характеристику", "realty-theme" ); ?>');
                    $('#characteristic-id').val(char.ID);
                    $('#char-title').val(char.post_title);
                    $('#char-description').val(char.post_content || '');
                    $('#char-label').val(char.label || '');
                    $('#char-hint').val(char.hint || '');
                    $('#char-value').val(char.value || '');
                    
                    // Стиль наследуется от группы (но при редактировании показываем текущий)
                    inheritStyleFromGroup();
                    
                    $('#char-icon-type').val(char.icon_type || 'material_symbol');
                    $('#char-icon').val(char.icon || '');
                    $('#char-media-id').val(char.media_id || '');
                    $('#char-parent').val(char.post_parent || 0);
                    $('#char-sort-order').val(char.sort_order || 0);
                    $('#char-show-in-filter').prop('checked', char.show_in_filter == 1);
                    $('#char-active').prop('checked', char.post_status === 'publish');
                    
                    // Обновляем preview
                    if (char.icon_type === 'upload' && char.media_id) {
                        $('input[name="char_icon_type"][value="upload"]').prop('checked', true);
                        toggleIconType('upload');
                        updateMediaPreview(char.media_id);
                    } else {
                        $('input[name="char_icon_type"][value="material_symbol"]').prop('checked', true);
                        toggleIconType('material_symbol');
                        updateIconPreview(char.icon || 'help');
                    }
                    
                    // Обновляем видимость полей
                    updateFieldVisibility($('#char-style').val());
                    
                    openModal('#characteristic-modal');
                } else {
                    alert(response.data || '<?php esc_html_e( "Ошибка загрузки данных", "realty-theme" ); ?>');
                }
            });
        }
        
        // Icon type toggle
        $('input[name="char_icon_type"]').on('change', function() {
            toggleIconType($(this).val());
        });
        
        function toggleIconType(type) {
            if (type === 'material_symbol') {
                $('#material-symbol-row').show();
                $('#media-upload-row').hide();
            } else {
                $('#material-symbol-row').hide();
                $('#media-upload-row').show();
            }
        }
        
        // Icon preview
        $('#preview-icon-btn').on('click', function() {
            var iconName = $('#char-icon').val().trim();
            if (iconName) {
                updateIconPreview(iconName);
            } else {
                alert('<?php esc_html_e( "Введите название иконки", "realty-theme" ); ?>');
            }
        });
        
        function updateIconPreview(iconName) {
            $('#icon-preview-box').html('<span class="material-symbols-outlined" style="font-size: 32px;">' + escHtml(iconName) + '</span>');
        }
        
        function escHtml(text) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }
        
        // Media uploader
        var mediaUploader;
        $('#upload-media-btn').on('click', function(e) {
            e.preventDefault();
            
            // Если uploader уже создан, открываем его
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            // Создаем новый uploader
            mediaUploader = wp.media({
                title: '<?php esc_html_e( "Выбрать иконку", "realty-theme" ); ?>',
                button: {
                    text: '<?php esc_html_e( "Использовать", "realty-theme" ); ?>'
                },
                multiple: false,
                library: {
                    type: ['image/png', 'image/svg+xml', 'image/jpeg', 'image/gif']
                }
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                
                // Проверяем размер файла (1MB)
                if (attachment.filesize > 1048576) {
                    alert('<?php esc_html_e( "Файл слишком большой. Максимальный размер: 1MB", "realty-theme" ); ?>');
                    return;
                }
                
                $('#char-media-id').val(attachment.id);
                updateMediaPreview(attachment.id);
            });
            
            mediaUploader.open();
        });
        
        function updateMediaPreview(mediaId) {
            if (!mediaId) {
                $('#media-preview-box').html('<p class="description"><?php esc_html_e( "Файл не выбран", "realty-theme" ); ?></p>');
                return;
            }
            
            $.post(ajaxurl, {
                action: 'characteristic_get_media_url',
                nonce: '<?php echo wp_create_nonce( "characteristic_media_nonce" ); ?>',
                media_id: mediaId
            }, function(response) {
                if (response.success) {
                    $('#media-preview-box').html('<img src="' + response.data.url + '" alt="">');
                } else {
                    $('#media-preview-box').html('<p class="description"><?php esc_html_e( "Ошибка загрузки", "realty-theme" ); ?></p>');
                }
            });
        }
        
        // Save characteristic
        $('#save-characteristic-btn').on('click', function() {
            var $btn = $(this);
            
            // Валидация
            var title = $('#char-title').val().trim();
            if (!title) {
                alert('<?php esc_html_e( "Название обязательно!", "realty-theme" ); ?>');
                $('#char-title').focus();
                return;
            }
            
            var style = $('#char-style').val();
            if (!style) {
                alert('<?php esc_html_e( "Выберите стиль отображения!", "realty-theme" ); ?>');
                return;
            }
            
            // Собираем данные
            var iconType = $('input[name="char_icon_type"]:checked').val();
            var data = {
                action: 'characteristic_save',
                nonce: '<?php echo wp_create_nonce( "characteristic_save_nonce" ); ?>',
                characteristic_id: $('#characteristic-id').val(),
                group_id: $('#characteristic-group-id').val(),
                char_title: title,
                char_description: $('#char-description').val(),
                char_label: $('#char-label').val(),
                char_hint: $('#char-hint').val(),
                char_value: $('#char-value').val(),
                char_style: style,
                char_icon_type: iconType,
                char_icon: iconType === 'material_symbol' ? $('#char-icon').val() : '',
                char_media_id: iconType === 'upload' ? $('#char-media-id').val() : '',
                char_parent: $('#char-parent').val(),
                char_sort_order: $('#char-sort-order').val(),
                char_show_in_filter: $('#char-show-in-filter').is(':checked') ? 1 : 0,
                char_active: $('#char-active').is(':checked') ? 'publish' : 'draft'
            };
            
            // Отправляем AJAX
            $btn.prop('disabled', true).text('<?php esc_html_e( "Сохранение...", "realty-theme" ); ?>');
            
            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    // Успешно — перезагружаем страницу
                    window.location.reload();
                } else {
                    // Ошибка
                    alert(response.data || '<?php esc_html_e( "Ошибка при сохранении", "realty-theme" ); ?>');
                    $btn.prop('disabled', false).text('<?php esc_html_e( "Сохранить", "realty-theme" ); ?>');
                }
            }).fail(function() {
                alert('<?php esc_html_e( "Ошибка сети. Проверьте соединение.", "realty-theme" ); ?>');
                $btn.prop('disabled', false).text('<?php esc_html_e( "Сохранить", "realty-theme" ); ?>');
            });
        });
        
        // Delete characteristic button
        $('.delete-characteristic-btn').on('click', function() {
            var charId = $(this).data('characteristic-id');
            var charName = $(this).data('characteristic-name');
            
            if (confirm('<?php esc_html_e( "Вы уверены, что хотите удалить характеристику", "realty-theme" ); ?> "' + charName + '"?')) {
                // TODO: AJAX delete
                alert('Удаление будет реализовано в следующих фазах');
            }
        });
        
        // Delete group button
        $('.delete-group-btn').on('click', function(e) {
            e.preventDefault();
            var groupId = $(this).data('group-id');
            var groupName = $(this).data('group-name');
            
            if (confirm('<?php esc_html_e( "Вы уверены, что хотите удалить группу", "realty-theme" ); ?> "' + groupName + '"?\n\n<?php esc_html_e( "Все характеристики в этой группе будут отвязаны.", "realty-theme" ); ?>')) {
                var $btn = $(this);
                $btn.prop('disabled', true);
                
                $.post(ajaxurl, {
                    action: 'characteristic_delete_group',
                    nonce: '<?php echo wp_create_nonce( "characteristic_delete_group_nonce" ); ?>',
                    group_id: groupId
                }, function(response) {
                    if (response.success) {
                        // Успешно — перезагружаем страницу
                        window.location.reload();
                    } else {
                        // Ошибка
                        alert(response.data || '<?php esc_html_e( "Ошибка при удалении", "realty-theme" ); ?>');
                        $btn.prop('disabled', false);
                    }
                }).fail(function() {
                    alert('<?php esc_html_e( "Ошибка сети. Проверьте соединение.", "realty-theme" ); ?>');
                    $btn.prop('disabled', false);
                });
            }
        });
        
        // Save group button
        $('#save-group-btn').on('click', function() {
            var $btn = $(this);
            var $form = $('#group-form');
            
            // Валидация
            var groupName = $('#group-name').val().trim();
            if (!groupName) {
                alert('<?php esc_html_e( "Название группы обязательно!", "realty-theme" ); ?>');
                $('#group-name').focus();
                return;
            }
            
            // Собираем данные
            var data = {
                action: 'characteristic_save_group',
                nonce: '<?php echo wp_create_nonce( "characteristic_group_nonce" ); ?>',
                group_id: $('#group-id').val(),
                group_name: groupName,
                group_description: $('#group-description').val(),
                group_type_ui: $('#group-type-ui').val(),
                group_display_style: $('#group-display-style').val(),
                group_sort_order: $('#group-sort-order').val(),
                group_active: $('#group-active').is(':checked') ? 1 : 0,
                group_use_in_filters: $('#group-use-in-filters').is(':checked') ? 1 : 0,
                group_show_in_archive: $('#group-show-in-archive').is(':checked') ? 1 : 0
            };
            
            // Отправляем AJAX
            $btn.prop('disabled', true).text('<?php esc_html_e( "Сохранение...", "realty-theme" ); ?>');
            
            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    // Успешно — перезагружаем страницу с активной новой группой
                    var redirectUrl = window.location.pathname + '?post_type=property&page=characteristics-reference';
                    
                    // Если создана новая группа (не редактирование), делаем её активной
                    if (!data.group_id && response.data.term_id) {
                        redirectUrl += '&group_id=' + response.data.term_id;
                    } else if (data.group_id) {
                        // При редактировании остаемся на текущей группе
                        redirectUrl += '&group_id=' + data.group_id;
                    }
                    
                    window.location.href = redirectUrl;
                } else {
                    // Ошибка
                    alert(response.data || '<?php esc_html_e( "Ошибка при сохранении", "realty-theme" ); ?>');
                    $btn.prop('disabled', false).text('<?php esc_html_e( "Сохранить", "realty-theme" ); ?>');
                }
            }).fail(function() {
                alert('<?php esc_html_e( "Ошибка сети. Проверьте соединение.", "realty-theme" ); ?>');
                $btn.prop('disabled', false).text('<?php esc_html_e( "Сохранить", "realty-theme" ); ?>');
            });
        });
    });
    </script>
    <?php
}

/**
 * Подключение Material Symbols для preview иконок
 */
function realty_enqueue_characteristics_admin_assets() {
    $screen = get_current_screen();
    
    if ( $screen && $screen->id === 'property_page_characteristics-reference' ) {
        // Material Symbols CDN
        wp_enqueue_style(
            'material-symbols-outlined',
            'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0',
            array(),
            '1.0.0'
        );
        
        // Подключаем WordPress Media Uploader
        wp_enqueue_media();
    }
}
add_action( 'admin_enqueue_scripts', 'realty_enqueue_characteristics_admin_assets' );

/**
 * AJAX обработчик для сохранения/обновления групп характеристик
 * 
 * Security: nonce + current_user_can
 * Sanitization: все входные данные санитизируются
 */
function realty_ajax_save_characteristic_group() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'characteristic_group_nonce' ) ) {
        wp_send_json_error( __( 'Ошибка безопасности. Обновите страницу.', 'realty-theme' ) );
    }
    
    // Проверка прав
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'У вас недостаточно прав.', 'realty-theme' ) );
    }
    
    // Санитизация входных данных
    $group_id          = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    $group_name        = isset( $_POST['group_name'] ) ? sanitize_text_field( $_POST['group_name'] ) : '';
    $group_description = isset( $_POST['group_description'] ) ? sanitize_textarea_field( $_POST['group_description'] ) : '';
    $group_type_ui     = isset( $_POST['group_type_ui'] ) ? sanitize_text_field( $_POST['group_type_ui'] ) : 'checkbox';
    $group_display_style = isset( $_POST['group_display_style'] ) ? sanitize_text_field( $_POST['group_display_style'] ) : 'standard';
    $group_sort_order  = isset( $_POST['group_sort_order'] ) ? absint( $_POST['group_sort_order'] ) : 0;
    $group_active      = isset( $_POST['group_active'] ) ? absint( $_POST['group_active'] ) : 1;
    $group_use_filters = isset( $_POST['group_use_in_filters'] ) ? absint( $_POST['group_use_in_filters'] ) : 1;
    $group_show_archive = isset( $_POST['group_show_in_archive'] ) ? absint( $_POST['group_show_in_archive'] ) : 0;
    
    // Валидация обязательных полей
    if ( empty( $group_name ) ) {
        wp_send_json_error( __( 'Название группы обязательно!', 'realty-theme' ) );
    }
    
    // Валидация type_ui
    $allowed_ui_types = array( 'checkbox', 'radio', 'select', 'multi_select', 'chips', 'switch' );
    if ( ! in_array( $group_type_ui, $allowed_ui_types, true ) ) {
        $group_type_ui = 'checkbox';
    }
    
    // Валидация display_style
    $allowed_styles = array( 'circle', 'standard', 'prohibited', 'text' );
    if ( ! in_array( $group_display_style, $allowed_styles, true ) ) {
        $group_display_style = 'standard';
    }
    
    // Создаем slug из названия
    $slug = sanitize_title( $group_name );
    
    try {
        if ( $group_id > 0 ) {
            // Обновление существующей группы
            $result = wp_update_term( $group_id, 'char_group', array(
                'name'        => $group_name,
                'slug'        => $slug,
                'description' => $group_description,
            ) );
            
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( $result->get_error_message() );
            }
            
            $term_id = $group_id;
        } else {
            // Создание новой группы
            $result = wp_insert_term( $group_name, 'char_group', array(
                'slug'        => $slug,
                'description' => $group_description,
            ) );
            
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( $result->get_error_message() );
            }
            
            $term_id = $result['term_id'];
        }
        
        // Сохраняем meta поля
        update_term_meta( $term_id, 'type_ui', $group_type_ui );
        update_term_meta( $term_id, 'display_style', $group_display_style );
        update_term_meta( $term_id, 'active', $group_active );
        update_term_meta( $term_id, 'sort_order', $group_sort_order );
        update_term_meta( $term_id, 'use_in_filters', $group_use_filters );
        update_term_meta( $term_id, 'show_in_archive', $group_show_archive );
        update_term_meta( $term_id, 'description_full', $group_description );
        
        // Успех
        wp_send_json_success( array(
            'message'  => $group_id > 0 ? __( 'Группа обновлена', 'realty-theme' ) : __( 'Группа создана', 'realty-theme' ),
            'term_id'  => $term_id,
            'group_name' => $group_name,
        ) );
        
    } catch ( Exception $e ) {
        wp_send_json_error( __( 'Ошибка при сохранении: ', 'realty-theme' ) . $e->getMessage() );
    }
}
add_action( 'wp_ajax_characteristic_save_group', 'realty_ajax_save_characteristic_group' );

/**
 * AJAX обработчик для получения данных группы
 */
function realty_ajax_get_characteristic_group() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'characteristic_get_group_nonce' ) ) {
        wp_send_json_error( __( 'Ошибка безопасности.', 'realty-theme' ) );
    }
    
    // Проверка прав
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'У вас недостаточно прав.', 'realty-theme' ) );
    }
    
    $group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    
    if ( ! $group_id ) {
        wp_send_json_error( __( 'ID группы не указан.', 'realty-theme' ) );
    }
    
    // Получаем группу
    $group = get_term( $group_id, 'char_group' );
    
    if ( ! $group || is_wp_error( $group ) ) {
        wp_send_json_error( __( 'Группа не найдена.', 'realty-theme' ) );
    }
    
    // Получаем meta
    $has_characteristics = get_posts( array(
        'post_type'      => 'characteristic',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'tax_query'      => array(
            array(
                'taxonomy' => 'char_group',
                'field'    => 'term_id',
                'terms'    => $group_id,
            ),
        ),
    ) );
    
    $data = array(
        'term_id'          => $group->term_id,
        'name'             => $group->name,
        'slug'             => $group->slug,
        'description'      => $group->description,
        'type_ui'          => get_term_meta( $group_id, 'type_ui', true ),
        'display_style'    => get_term_meta( $group_id, 'display_style', true ),
        'active'           => get_term_meta( $group_id, 'active', true ),
        'sort_order'       => get_term_meta( $group_id, 'sort_order', true ),
        'use_in_filters'   => get_term_meta( $group_id, 'use_in_filters', true ),
        'show_in_archive'  => get_term_meta( $group_id, 'show_in_archive', true ),
        'has_characteristics' => ! empty( $has_characteristics ),
    );
    
    wp_send_json_success( $data );
}
add_action( 'wp_ajax_characteristic_get_group', 'realty_ajax_get_characteristic_group' );

/**
 * AJAX обработчик для удаления группы характеристик
 * 
 * Security: nonce + current_user_can
 * При удалении группы все характеристики остаются, но теряют привязку к группе
 */
function realty_ajax_delete_characteristic_group() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'characteristic_delete_group_nonce' ) ) {
        wp_send_json_error( __( 'Ошибка безопасности. Обновите страницу.', 'realty-theme' ) );
    }
    
    // Проверка прав
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'У вас недостаточно прав.', 'realty-theme' ) );
    }
    
    $group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    
    if ( ! $group_id ) {
        wp_send_json_error( __( 'ID группы не указан.', 'realty-theme' ) );
    }
    
    // Проверяем, существует ли группа
    $group = get_term( $group_id, 'char_group' );
    
    if ( ! $group || is_wp_error( $group ) ) {
        wp_send_json_error( __( 'Группа не найдена.', 'realty-theme' ) );
    }
    
    try {
        // Подсчитываем характеристики в этой группе
        $characteristics = get_posts( array(
            'post_type'      => 'characteristic',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'char_group',
                    'field'    => 'term_id',
                    'terms'    => $group_id,
                ),
            ),
        ) );
        
        $char_count = count( $characteristics );
        
        // Удаляем группу
        $result = wp_delete_term( $group_id, 'char_group' );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }
        
        if ( ! $result ) {
            wp_send_json_error( __( 'Не удалось удалить группу.', 'realty-theme' ) );
        }
        
        // Успех
        $message = sprintf(
            __( 'Группа "%s" удалена.', 'realty-theme' ),
            $group->name
        );
        
        if ( $char_count > 0 ) {
            $message .= ' ' . sprintf(
                __( '%d характеристик(а) остались без группы.', 'realty-theme' ),
                $char_count
            );
        }
        
        wp_send_json_success( array(
            'message'         => $message,
            'group_name'      => $group->name,
            'char_count'      => $char_count,
        ) );
        
    } catch ( Exception $e ) {
        wp_send_json_error( __( 'Ошибка при удалении: ', 'realty-theme' ) . $e->getMessage() );
    }
}
add_action( 'wp_ajax_characteristic_delete_group', 'realty_ajax_delete_characteristic_group' );

/**
 * AJAX обработчик для сохранения/обновления характеристики
 * 
 * Security: nonce + current_user_can
 * Sanitization: все входные данные санитизируются
 */
function realty_ajax_save_characteristic() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'characteristic_save_nonce' ) ) {
        wp_send_json_error( __( 'Ошибка безопасности. Обновите страницу.', 'realty-theme' ) );
    }
    
    // Проверка прав
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'У вас недостаточно прав.', 'realty-theme' ) );
    }
    
    // Санитизация входных данных
    $char_id         = isset( $_POST['characteristic_id'] ) ? absint( $_POST['characteristic_id'] ) : 0;
    $group_id        = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    $char_title      = isset( $_POST['char_title'] ) ? sanitize_text_field( $_POST['char_title'] ) : '';
    $char_desc       = isset( $_POST['char_description'] ) ? sanitize_textarea_field( $_POST['char_description'] ) : '';
    $char_label      = isset( $_POST['char_label'] ) ? sanitize_text_field( $_POST['char_label'] ) : '';
    $char_hint       = isset( $_POST['char_hint'] ) ? sanitize_text_field( $_POST['char_hint'] ) : '';
    $char_value      = isset( $_POST['char_value'] ) ? sanitize_text_field( $_POST['char_value'] ) : '';
    $char_style      = isset( $_POST['char_style'] ) ? sanitize_text_field( $_POST['char_style'] ) : 'standard';
    $char_icon_type  = isset( $_POST['char_icon_type'] ) ? sanitize_text_field( $_POST['char_icon_type'] ) : 'material_symbol';
    $char_icon       = isset( $_POST['char_icon'] ) ? sanitize_text_field( $_POST['char_icon'] ) : '';
    $char_media_id   = isset( $_POST['char_media_id'] ) ? absint( $_POST['char_media_id'] ) : 0;
    $char_parent     = isset( $_POST['char_parent'] ) ? absint( $_POST['char_parent'] ) : 0;
    $char_sort_order = isset( $_POST['char_sort_order'] ) ? absint( $_POST['char_sort_order'] ) : 0;
    $char_show_filter = isset( $_POST['char_show_in_filter'] ) ? absint( $_POST['char_show_in_filter'] ) : 0;
    $char_status     = isset( $_POST['char_active'] ) ? sanitize_text_field( $_POST['char_active'] ) : 'publish';
    
    // Валидация обязательных полей
    if ( empty( $char_title ) ) {
        wp_send_json_error( __( 'Название характеристики обязательно!', 'realty-theme' ) );
    }
    
    if ( ! $group_id ) {
        wp_send_json_error( __( 'Группа не выбрана.', 'realty-theme' ) );
    }
    
    // Валидация стиля
    $allowed_styles = array( 'circle', 'standard', 'prohibited', 'text' );
    if ( ! in_array( $char_style, $allowed_styles, true ) ) {
        $char_style = 'standard';
    }
    
    // Валидация типа иконки
    $allowed_icon_types = array( 'material_symbol', 'upload' );
    if ( ! in_array( $char_icon_type, $allowed_icon_types, true ) ) {
        $char_icon_type = 'material_symbol';
    }
    
    // Если тип иконки - upload, проверяем что media_id указан
    if ( $char_icon_type === 'upload' && ! $char_media_id ) {
        wp_send_json_error( __( 'Для типа иконки "Загруженный файл" необходимо выбрать файл.', 'realty-theme' ) );
    }
    
    // Создаем slug из названия
    $post_slug = sanitize_title( $char_title );
    
    try {
        // Подготавливаем данные поста
        $post_data = array(
            'post_title'    => $char_title,
            'post_content'  => $char_desc,
            'post_status'   => $char_status,
            'post_type'     => 'characteristic',
            'post_parent'   => $char_parent,
        );
        
        if ( $char_id > 0 ) {
            // Обновление существующей характеристики
            $post_data['ID'] = $char_id;
            $post_data['post_name'] = $post_slug;
            
            $result = wp_update_post( $post_data, true );
            
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( $result->get_error_message() );
            }
            
            $post_id = $result;
        } else {
            // Создание новой характеристики
            $post_data['post_name'] = $post_slug;
            
            $result = wp_insert_post( $post_data, true );
            
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( $result->get_error_message() );
            }
            
            $post_id = $result;
        }
        
        // Сохраняем meta поля
        update_post_meta( $post_id, 'label', $char_label );
        update_post_meta( $post_id, 'hint', $char_hint );
        update_post_meta( $post_id, 'value', $char_value );
        update_post_meta( $post_id, 'style', $char_style );
        update_post_meta( $post_id, 'icon_type', $char_icon_type );
        update_post_meta( $post_id, 'sort_order', $char_sort_order );
        update_post_meta( $post_id, 'show_in_filter', $char_show_filter );
        
        // Сохраняем иконку в зависимости от типа
        if ( $char_icon_type === 'material_symbol' ) {
            update_post_meta( $post_id, 'icon', $char_icon );
            update_post_meta( $post_id, 'media_id', 0 );
        } else {
            update_post_meta( $post_id, 'icon', '' );
            update_post_meta( $post_id, 'media_id', $char_media_id );
        }
        
        // Привязываем к группе (таксономия)
        wp_set_object_terms( $post_id, array( intval( $group_id ) ), 'char_group' );
        
        // Успех
        wp_send_json_success( array(
            'message' => $char_id > 0 ? __( 'Характеристика обновлена', 'realty-theme' ) : __( 'Характеристика создана', 'realty-theme' ),
            'post_id' => $post_id,
        ) );
        
    } catch ( Exception $e ) {
        wp_send_json_error( __( 'Ошибка при сохранении: ', 'realty-theme' ) . $e->getMessage() );
    }
}
add_action( 'wp_ajax_characteristic_save', 'realty_ajax_save_characteristic' );

/**
 * AJAX обработчик для получения данных характеристики
 */
function realty_ajax_get_characteristic() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'characteristic_get_nonce' ) ) {
        wp_send_json_error( __( 'Ошибка безопасности.', 'realty-theme' ) );
    }
    
    // Проверка прав
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'У вас недостаточно прав.', 'realty-theme' ) );
    }
    
    $char_id = isset( $_POST['characteristic_id'] ) ? absint( $_POST['characteristic_id'] ) : 0;
    
    if ( ! $char_id ) {
        wp_send_json_error( __( 'ID характеристики не указан.', 'realty-theme' ) );
    }
    
    // Получаем характеристику
    $char = get_post( $char_id );
    
    if ( ! $char || $char->post_type !== 'characteristic' || is_wp_error( $char ) ) {
        wp_send_json_error( __( 'Характеристика не найдена.', 'realty-theme' ) );
    }
    
    // Получаем meta
    $data = array(
        'ID'             => $char->ID,
        'post_title'     => $char->post_title,
        'post_content'   => $char->post_content,
        'post_status'    => $char->post_status,
        'post_parent'    => $char->post_parent,
        'label'          => get_post_meta( $char_id, 'label', true ),
        'hint'           => get_post_meta( $char_id, 'hint', true ),
        'value'          => get_post_meta( $char_id, 'value', true ),
        'style'          => get_post_meta( $char_id, 'style', true ),
        'icon_type'      => get_post_meta( $char_id, 'icon_type', true ),
        'icon'           => get_post_meta( $char_id, 'icon', true ),
        'media_id'       => get_post_meta( $char_id, 'media_id', true ),
        'sort_order'     => get_post_meta( $char_id, 'sort_order', true ),
        'show_in_filter' => get_post_meta( $char_id, 'show_in_filter', true ),
    );
    
    wp_send_json_success( $data );
}
add_action( 'wp_ajax_characteristic_get', 'realty_ajax_get_characteristic' );

/**
 * AJAX обработчик для получения URL медиа-файла
 */
function realty_ajax_get_characteristic_media_url() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'characteristic_media_nonce' ) ) {
        wp_send_json_error( __( 'Ошибка безопасности.', 'realty-theme' ) );
    }
    
    // Проверка прав
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'У вас недостаточно прав.', 'realty-theme' ) );
    }
    
    $media_id = isset( $_POST['media_id'] ) ? absint( $_POST['media_id'] ) : 0;
    
    if ( ! $media_id ) {
        wp_send_json_error( __( 'ID медиа-файла не указан.', 'realty-theme' ) );
    }
    
    // Получаем URL
    $url = wp_get_attachment_url( $media_id );
    
    if ( ! $url ) {
        wp_send_json_error( __( 'Медиа-файл не найден.', 'realty-theme' ) );
    }
    
    wp_send_json_success( array(
        'url' => $url,
    ) );
}
add_action( 'wp_ajax_characteristic_get_media_url', 'realty_ajax_get_characteristic_media_url' );
