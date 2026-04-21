# Справочник Характеристик Недвижимости (Гибридный)

## 1. Описание Задачи

Разработать **универсальный гибкий справочник** для хранения и отображения характеристик недвижимости с поддержкой:

### Основные требования:
1. **Иерархическая структура:** Группа → Подгруппа (тип) → Значения
2. **Разные стили отображения:**
   - **icon-circle** — круглая иконка (GUESTS Up to 4)
   - **icon-standard** — стандартный (Pet Friendly)
   - **icon-prohibited** — зачеркнутый (Нельзя проводить вечеринки)
   - **text-only** — текстовая информация (Правила размещения: Заселение >15:00)
3. **Гибкие поля:**
   - Название (обязательно)
   - Иконка из Material Symbols Outlined (опционально)
   - Дополнительное значение / подпись (опционально)
   - Стиль отображения (выбор: circle/standard/prohibited/text)
4. **Привязка к свойствам:** Множество значений, drag-drop сортировка в админке
5. **Фронт:** Отображение в зависимости от стиля

**Текущее состояние:**
- `property-features.php` — жёстко zakodirован HTML с icon-circle стилем
- Нужна связь с БД через справочник
- Порядок сохраняется через поле сортировки

---

## 2. Место Реализации

### Админка (структура):
```
/wp-content/themes/realty-theme/inc/admin/
├── admin-function.php                      (текущие функции)
├── pods-registration.php                   (регистрация CPT/таксономий) ← ОБНОВИТЬ
├── settings/
│   └── characteristics-settings.php         ← НОВЫЙ ФАЙЛ (страница управления)
├── assets/
│   ├── js/
│   │   ├── characteristics-admin.js         ← НОВЫЙ ФАЙЛ (drag-drop, AJAX, UI)
│   │   ├── icon-picker.js                  ← НОВЫЙ ФАЙЛ (autocomplete иконок)
│   │   └── media-picker.js                 ← НОВЫЙ ФАЙЛ (загрузка файлов)
│   └── css/
│       ├── characteristics-admin.css        ← НОВЫЙ ФАЙЛ (админ интерфейс)
│       ├── metabox-characteristics.css      ← НОВЫЙ ФАЙЛ (метабокс на свойстве)
│       └── icon-preview.css                 ← НОВЫЙ ФАЙЛ (preview иконок/медиа)
└── ... (существующие файлы)
```

### Структура БД (расширенная - CPT с иерархией + media):
```
Post Type: characteristic
├── Поле: post_title              — Название значения (обязательно, "Pet Friendly")
├── Поле: post_content            — Краткое описание (опционально)
├── Meta: icon                    — material-symbols-outlined или ID media (опционально)
├── Meta: style                   — circle|standard|prohibited|text
├── Meta: hint                    — Хинт/подпись (опционально, "Allowed")
├── Meta: label                   — Лейбл (опционально, "Pet Policy")
├── Meta: value                   — Дополнительное значение (опционально, "Up to 4")
├── Meta: sort_order              — Порядок (для drag-drop)
├── Meta: icon_type               — "material_symbol" | "upload" (тип иконки)
├── Meta: media_id                — ID вложения (если icon_type='upload')
├── Поле: post_parent             — Иерархия (дочерние значения в подгруппе)
├── Таксономия: char_group        — Группа ("Квартира", "Услуги", "Правила")
└── Таксономия: char_subtype      — Подтип (опционально для фильтрации)

Taxonomy: char_group (расширенная)
├── Term Meta: type_ui            — checkbox|radio|select|multi_select|chips|switch
├── Term Meta: description        — Краткий текст описания группы
├── Term Meta: active             — 0|1 (активна/неактивна)
├── Term Meta: sort_order         — Позиция группы в списке
├── Term Meta: use_in_filters     — 0|1 (использовать ли в фильтрах на фронте)
└── Term Meta: show_in_archive    — 0|1 (показывать ли на странице архива)
```

### Новые функции в структуре:
- **`register_characteristic_cpt()`** — регистрация CPT + meta fields + media support
- **`setup_characteristic_groups_taxonomy()`** — таксономия для групп с расширенными мета
- **`realty_get_characteristics_by_group()`** — получение по группе для админки и фронта
- **`render_characteristic_filter()`** — фильтр UI на фронте (обращение к PropertyFilter.js)
- **AJAX endpoints:**
  - `characteristic_icon_search` — поиск material-symbols + preview
  - `characteristic_media_upload` — загрузка файлов (PNG/SVG/JPG)
  - `characteristic_save_order` — drag-drop
  - `get_characteristics_by_group` — для метабокса и фильтров

### Фронт интеграция:
- `PropertyFilter.js` — новая логика для фильтров (группы с type_ui)
- `property-features.php` — обновить для медиа-иконок
- Vue компонент `<CharacteristicFilter />` — опционально для Element-plus

---

## 3. Логика Работы и Архитектура

### 3.1 Модель Данных (Иерархия)

```
📦 Группа (Таксономия: char_group, Тип 1)
  ├─ "Квартира" (Терм)
  │   └─ Característics (иерархия post_parent):
  │       ├─ ID:10 (title: "GUESTS", icon: "group", style: "circle",
  │       │         value: "Up to 4", order: 1, parent: 0)
  │       ├─ ID:11 (title: "BEDROOMS", icon: "bed", style: "circle",
  │       │         value: "2 Queen", order: 2, parent: 0)
  │       └─ ID:12 (title: "BATHS", icon: "bathtub", style: "circle",
  │                 value: "1.5 Baths", order: 3, parent: 0)
  │
  ├─ "Услуги и удобства" (Терм)
  │   └─ Characteristics:
  │       ├─ ID:20 (title: "Pet Friendly", icon: "pets",
  │       │         style: "standard", value: "Allowed", order: 1)
  │       ├─ ID:21 (title: "WiFi", icon: "wifi_cable",
  │       │         style: "standard", order: 2)
  │       └─ ID:22 (title: "Kitchen", icon: "restaurant",
  │                 style: "standard", order: 3)
  │
  └─ "Правила размещения" (Терм)
      └─ Characteristics:
          ├─ ID:30 (title: "Нельзя проводить вечеринки",
          │         icon: "music_off", style: "prohibited", order: 1)
          ├─ ID:31 (parent: 30, title: "С животными нельзя",
          │         icon: "pets_off", style: "prohibited", order: 1.1)
          └─ ID:32 (title: "Заселение: после 15:00",
          │         style: "text", order: 2)
```

### 3.2 Стили Отображения (Style Templates)

| Стиль | Класс CSS | Структура HTML | Пример |
|-------|-----------|----------------|---------|
| **circle** | `.feature-item__com` | `<icon-circle> + <label> + <value>` | GUESTS Up to 4 |
| **standard** | `.amenity-item` | `<icon> + <title> + [value]` | Pet Friendly |
| **prohibited** | `.prohibited-item` | `<icon-strikethrough> + <title>` | Нельзя проводить вечеринки |
| **text** | `.rules-item` | Чистый текст (без иконки) | Заселение: после 15:00 |

### 3.2b Типы UI Фильтрации (Группа → type_ui)

**Поле `type_ui` для таксономии `char_group`** (управляется в админке):

| UI Тип | Элемент | Выбор | Пример на фронте |
|--------|---------|-------|------------------|
| **checkbox** | Элемент Plus Checkbox | Multi-select | ☑ Pet Friendly, ☐ WiFi, ☑ Kitchen |
| **radio** | Элемент Plus Radio | Single select | ◉ После 15:00  ○ После 14:00 |
| **select** | Select (выпадашка) | Single select | [Select one ▼] |
| **multi_select** | Select (multi) | Multi-select | [Select multiple ▼] показ выбранных |
| **chips** | Tag / Chips | Multi-select | [Pet Friendly] [WiFi] [Kitchen] |
| **switch** | Switch (вкл/выкл) | Binary | ON/OFF для каждого элемента |

### 3.3 Типы Иконок (Icon Management)

**Поле `icon_type` для характеристик:**

| Тип | Источник | Хранение | Отображение |
|-----|----------|----------|------------|
| **material_symbol** | Google Material Symbols | Text (название иконки) | `<span class="material-symbols-outlined">group</span>` |
| **upload** | Загруженный файл (PNG/SVG/JPG) | Meta: media_id | `<img src="...">` или SVG inline |

### 3.3 Структура Полей для Группы (Term Meta)

```
char_group (Таксономия):
├── name                    — "Квартира" (обязательное, встроенное поле термина)
├── description             — "Основные характеристики квартиры" (краткий текст)
├── type_ui                 — "checkbox" | "radio" | "select" | "multi_select" | "chips" | "switch"
├── active                  — 1 | 0 (показывать ли в админке и на фронте)
├── sort_order              — 1, 2, 3 (позиция в списке групп)
├── use_in_filters          — 1 | 0 (использовать ли эту группу в фильтрах PropertyFilter.js)
└── show_in_archive         — 1 | 0 (показывать ли на странице архива)
```

### 3.4 Структура Полей для Значения (Characteristic Post)

```
characteristic (Custom Post Type):

Post Level (встроенные в WP):
├── post_title              — "Pet Friendly" (обязательное - название значения)
├── post_content            — "Питомцы приветствуются" (краткое описание, опционально)
└── post_status             — "publish" | "draft" (активность)

Meta Level (custom fields):
├── label                   — "Pet Policy" (лейбл, опционально)
├── hint                    — "Allowed" (хинт/подпись, опционально)
├── value                   — "Up to 4" (доп. значение, опционально)
├── icon_type               — "material_symbol" | "upload" (тип иконки)
├── icon                    — "pets" (если material_symbol) или ID медиа (если upload)
├── media_id                — ID attachment (если icon_type='upload')
├── style                   — "circle" | "standard" | "prohibited" | "text"
├── sort_order              — 1, 2, 3 (позиция внутри группы)
└── post_parent             — ID родительской характеристики (для иерархии, опционально)

Таксономия:
├── char_group              — ID термина группы ("Квартира", "Услуги", итд)
└── char_subtype            — (опционально) для дополнительной фильтрации
```

### 3.4 Примеры Данных

**Пример 1: Группа "Квартира" с типом UI `checkbox`**
```php
// Term Meta (char_group):
[
    'name' => 'Квартира',
    'description' => 'Основные характеристики',
    'type_ui' => 'checkbox',          // ← В админке выбирается checkbox
    'active' => 1,
    'sort_order' => 1,
    'use_in_filters' => 1,            // ← Использовать в PropertyFilter
    'show_in_archive' => 1
]

// Characteristics (posts) в этой группе:
[
    [
        'post_title' => 'GUESTS',
        'icon_type' => 'material_symbol',
        'icon' => 'group',
        'label' => 'GUESTS',
        'value' => 'Up to 4',
        'style' => 'circle',
        'sort_order' => 1
    ],
    [
        'post_title' => 'BEDROOMS',
        'icon_type' => 'material_symbol',
        'icon' => 'bed',
        'label' => 'BEDROOMS',
        'value' => '2 Queen',
        'style' => 'circle',
        'sort_order' => 2
    ]
]
```

**Пример 2: Группа "Услуги" с типом UI `chips` и медиа-иконками**
```php
// Term Meta:
[
    'name' => 'Услуги и удобства',
    'type_ui' => 'chips',             // ← Chips UI для фронта
    'active' => 1,
    'use_in_filters' => 1
]

// Characteristics:
[
    [
        'post_title' => 'Pet Friendly',
        'icon_type' => 'upload',       // ← Загруженная иконка
        'media_id' => 1234,            // ← ID attachment
        'hint' => 'Allowed',
        'style' => 'standard',
        'sort_order' => 1
    ],
    [
        'post_title' => 'WiFi',
        'icon_type' => 'material_symbol',
        'icon' => 'wifi_cable',
        'style' => 'standard',
        'sort_order' => 2
    ]
]
```

### 3.5 Поток Работы в Админке

#### Сценарий 1: Управление Справочником (расширенный)

1. **Редактор открывает:** `Недвижимость → Справочник Характеристик`

2. **Таб 1: Группы** (левая колонка)
   - Список групп с быстрыми действиями
   - Кнопка "+ Новая группа"
   - При клике → Modal форма:
     - Поле: Название группы (required)
     - Textarea: Краткий описание
     - Select: **Выбор UI фильтрации** ← НОВОЕ
       - default (checkbox)
       - radio
       - select
       - multi_select
       - chips
       - switch
     - Checkbox: "Использовать в фильтрах"
     - Checkbox: "Показывать в архиве"
     - Checkbox: "Активна"
     - Select/Input: Позиция (sort_order)
     - Кнопка: Сохранить

3. **Таб 2: Значения** (основная область)
   - При клике на группу → показываются all characteristics этой группы
   - Таблица с колонками:
     - Иконка (preview material-symbols или загруженный файл)
     - Название
     - Лейбл
     - Хинт
     - Стиль
     - Действия (edit/delete)
   - **Drag-drop сортировка** (jQuery UI Sortable) → обновляет sort_order
   - Кнопка "+ Добавить новое значение"

4. **Modal/Form: Добавить/Редактировать Значение** (расширенная)
   - Поле (required): "Название значения" (post_title)
   - Textarea (опционально): "Краткое описание"
   - Поле (опционально): "Лейбл" (label)
   - Поле (опционально): "Хинт" (hint)
   - Поле (опционально): "Дополнительное значение" (value)
   - Select (required): "Стиль" (circle/standard/prohibited/text)
   - **Выбор иконки (НОВОЕ):**
     - Radio: "Material Symbol" (по умолчанию)
       - Text input + Autocomplete AJAX → returns иконка с preview
     - Radio: "Загруженный файл"
       - Media picker (WP встроенный) → [Выбрать файл]
       - Поддерживаемые форматы: PNG, SVG, JPG
       - Preview загруженного файла
   - Select (опционально): "Родительская характеристика" (post_parent)
   - Input: "Позиция" (sort_order)
   - Checkbox: "Активна" (post_status)
   - Кнопка: Сохранить → AJAX save

#### Сценарий 2: Привязка к Свойству (без изменений)

1. **Редактор открывает свойство**
2. **Метабокс:** двухколоночный UI (как в предыдущем разделе)

---

## 3.6 Поток Данных на Фронте (Фильтры)

**PropertyFilter.js новая логика:**
```php
// Получить группы с type_ui для фильтров
$groups = get_terms([
    'taxonomy' => 'char_group',
    'meta_query' => [
        ['key' => 'use_in_filters', 'value' => 1]
    ]
]);

// Для каждой группы:
foreach ($groups as $group) {
    $type_ui = get_term_meta($group->term_id, 'type_ui', true);
    $characteristics = get_characteristics_by_group($group->term_id);

    // Рендер в зависимости от type_ui:
    render_filter_by_type($type_ui, $characteristics, $group);
}
```

**Рендер по типу UI (Vue/Element-Plus):**
```javascript
// PropertyFilter.js
switch (type_ui) {
    case 'checkbox':
        // el-checkbox-group с массивом значений
        break;
    case 'radio':
        // el-radio-group (single select)
        break;
    case 'select':
        // el-select (single)
        break;
    case 'multi_select':
        // el-select multiple
        break;
    case 'chips':
        // Кастомные chips/tags
        break;
    case 'switch':
        // el-switch для каждого значения
        break;
}
```

**Post Type: characteristic (иерархический CPT)**
```php
// CPT опции:
'hierarchical' => true,              // Иерархия через post_parent
'supports' => ['title', 'editor'],  // title = название, editor = description
'show_in_menu' => false,            // Только в админ-меню через подменю
'show_ui' => true                   // Админ-панель

// Meta Fields (через Pods или register_meta):
- icon (text) - "pets", "bed", "group" и т.д.
- style (select) - "circle" | "standard" | "prohibited" | "text"
- value (text) - "Up to 4", "2 Queen", "Allowed" и т.д.
- sort_order (number) - 1, 2, 3, ... (для drag-drop)

// Таксономия:
- char_group: "Квартира", "Услуги и удобства", "Правила", ...
- char_subtype: (опционально) "amenity", "rule", "feature" (для фильтрации в админке)
```

### 3.4 Поток Работы в Админке

#### Сценарий 1: Управление Справочником

1. **Редактор открывает:** `Недвижимость → Справочник Характеристик`
2. **Таб 1: Группы** (слева)
   - Список групп: "Квартира", "Услуги", "Правила"
   - Кнопка "+ Новая группа"
   - CRUD для групп (add/edit/delete)

3. **Таб 2: Значения** (основная область)
   - При клике на группу → показываются все characteristics (этой группы)
   - Таблица с колонками:
     - Иконка (preview)
     - Название
     - Стиль
     - Действия (edit/delete)
   - **Drag-drop слева** — меняется sort_order

4. **Модальное окно: Добавить / Редактировать**
   - Поле: Название (required, text)
   - Поле: Иконка (text input → autocomplete material-symbols)
   - Select: Стиль (circle/standard/prohibited/text)
   - Поле: Дополнительное значение (опционально)
   - Checkbox: Сделать подчинённым (если используется иерархия)

#### Сценарий 2: Привязка к Свойству

1. **Редактор открывает свойство** (edit.php?post_type=property&post=123)
2. **Метабокс: "Характеристики"** с двухколоночной UI:
   - **Левая колонка:** Группы (Квартира | Услуги | Правила)
   - **Правая колонка:** Characteristics для выбранной группы
   - **Drag-drop:** Перетягивать из правой в левую → добавляет к свойству
   - Порядок в левой колонке = порядок на фронте
   - Кнопка "Удалить" рядом с каждым элементом

3. **Data Structure (Meta):**
   ```php
   // Meta: property_characteristics
   $data = [
       [
           'characteristic_id' => 10,  // ID поста characteristic
           'order' => 1,               // Порядок на фронте
           'group_term_id' => 5        // (опционально, для группировки на фронте)
       ],
       [
           'characteristic_id' => 11,
           'order' => 2,
       ],
       // ... и так далее
   ];
   ```

### 3.5 Поток Данных на Фронте

**Получение:**
```php
$characteristics = get_post_meta($post_id, 'property_characteristics', true);
// [
//   ['characteristic_id' => 10, 'order' => 1],
//   ['characteristic_id' => 11, 'order' => 2],
// ]
```

**Отображение (property-features.php):**
```php
foreach ($characteristics as $char_data) {
    $char = get_post($char_data['characteristic_id']);
    $icon = get_post_meta($char->ID, 'icon', true);
    $style = get_post_meta($char->ID, 'style', true);
    $value = get_post_meta($char->ID, 'value', true);

    // Рендерим в зависимости от $style
    echo render_characteristic_item($char->post_title, $icon, $value, $style);
}
```

**Вспомогательная функция:**
```php
function render_characteristic_item($title, $icon, $value, $style) {
    switch ($style) {
        case 'circle':
            // icon-circle HTML
            break;
        case 'standard':
            // icon-standard HTML
            break;
        case 'prohibited':
            // icon-prohibited (зачеркнутый)
            break;
        case 'text':
            // plain text (no icon)
            break;
    }
}
```

---

## 4. Технический Подход

### 4.1 Backend - Регистрация CPT (расширенная)

**Место:** `pods-registration.php`, функция `setup_property_pods()` (строка 30)

**Добавить регистрацию CPT (Pure WP API):**
```php
register_post_type('characteristic', [
    'label' => 'Характеристики',
    'public' => false,                  // Не на фронте
    'show_ui' => true,                  // В админ
    'show_in_menu' => false,            // Подменю только
    'hierarchical' => true,             // Иерархия через post_parent
    'supports' => ['title', 'editor'],  // title + description
    'capability_type' => 'post',
]);

register_taxonomy('char_group', ['characteristic'], [
    'label' => 'Группы характеристик',
    'public' => false,
    'show_ui' => true,
    'hierarchical' => true,             // Иерархия групп
]);

// Meta fields для characteristic posts:
register_meta('post', 'icon', [
    'object_subtype' => 'characteristic',
    'type' => 'string',
    'single' => true,
    'sanitize_callback' => 'sanitize_text_field'
]);
register_meta('post', 'style', [
    'object_subtype' => 'characteristic',
    'type' => 'string',
    'single' => true,
    'enum' => ['circle', 'standard', 'prohibited', 'text'],
    'sanitize_callback' => 'sanitize_text_field'
]);
register_meta('post', 'label', [
    'object_subtype' => 'characteristic',
    'type' => 'string',
    'single' => true,
    'sanitize_callback' => 'sanitize_text_field'
]);
register_meta('post', 'hint', [
    'object_subtype' => 'characteristic',
    'type' => 'string',
    'single' => true,
    'sanitize_callback' => 'sanitize_text_field'
]);
register_meta('post', 'value', [
    'object_subtype' => 'characteristic',
    'type' => 'string',
    'single' => true,
    'sanitize_callback' => 'sanitize_text_field'
]);
register_meta('post', 'sort_order', [
    'object_subtype' => 'characteristic',
    'type' => 'number',
    'single' => true,
    'sanitize_callback' => 'absint'
]);
register_meta('post', 'icon_type', [
    'object_subtype' => 'characteristic',
    'type' => 'string',
    'single' => true,
    'enum' => ['material_symbol', 'upload'],
    'sanitize_callback' => 'sanitize_text_field'
]);
register_meta('post', 'media_id', [
    'object_subtype' => 'characteristic',
    'type' => 'number',
    'single' => true,
    'sanitize_callback' => 'absint'
]);

// Meta fields для char_group taxonomy terms (НОВОЕ):
register_term_meta('char_group', 'type_ui', [
    'type' => 'string',
    'single' => true,
    'sanitize_callback' => 'sanitize_text_field',
    // Допустимые значения:
    // 'checkbox', 'radio', 'select', 'multi_select', 'chips', 'switch'
]);
register_term_meta('char_group', 'description_full', [
    'type' => 'string',
    'single' => true,
    'sanitize_callback' => 'sanitize_textarea_field'
]);
register_term_meta('char_group', 'active', [
    'type' => 'number',
    'single' => true,
    'sanitize_callback' => 'absint'
]);
register_term_meta('char_group', 'sort_order', [
    'type' => 'number',
    'single' => true,
    'sanitize_callback' => 'absint'
]);
register_term_meta('char_group', 'use_in_filters', [
    'type' => 'number',
    'single' => true,
    'sanitize_callback' => 'absint'
]);
register_term_meta('char_group', 'show_in_archive', [
    'type' => 'number',
    'single' => true,
    'sanitize_callback' => 'absint'
]);
```

### 4.2 Backend - Админ-интерфейс (расширенный)

**Страница управления:** `settings/characteristics-settings.php` (новый файл)

**Функции:**
```php
// 1. Регистрация страницы в меню
add_submenu_page(
    'edit.php?post_type=property',
    'Справочник Характеристик',
    'Справочник',
    'manage_options',
    'characteristics-reference',
    'realty_characteristics_page_content'
);

// 2. Основная функция отрисовки админ-странички
function realty_characteristics_page_content() {
    // Таб переключение (AJAX или Page Load)
    // Tab 1: Группы (CRUD форма)
    // Tab 2: Значения (Таблица + Popup для редактирования)
    //        + Drag-drop сортировка
}

// 3. Вспомогательные функции
function get_characteristic_groups() { ... }
function get_characteristics_by_group($group_id) { ... }
function render_characteristic_form($characteristic = null) { ... }
```

### 4.3 Backend - AJAX обработчики (расширенные)

**Регистрация в `PropertyFeature`::**
```php
// 1. Icon autocomplete (material-symbols)
add_action('wp_ajax_characteristic_icon_search', [$this, 'ajaxIconSearch']);

// 2. Media upload (для PNG/SVG/JPG иконок)
add_action('wp_ajax_characteristic_media_upload', [$this, 'ajaxMediaUpload']);

// 3. Save sort order (drag-drop)
add_action('wp_ajax_characteristic_save_order', [$this, 'ajaxSaveOrder']);

// 4. Get characteristics by group (для метабокса и фильтров)
add_action('wp_ajax_get_characteristics_group', [$this, 'ajaxGetCharacteristicsByGroup']);

// 5. Save group meta (type_ui, active, etc.)
add_action('wp_ajax_characteristic_save_group', [$this, 'ajaxSaveGroupMeta']);
```

**Функция icon search:**
```php
public function ajaxIconSearch() {
    // GET: term
    // Возвращает список иконок material-symbols-outlined
    // JSON: [
    //     {'label': 'pets', 'value': 'pets', 'icon': 'pets'},
    //     {'label': 'group', 'value': 'group', 'icon': 'group'},
    // ]

    // Может работать с:
    // 1. Кэшированным JSON списком (~1500 иконок)
    // 2. Или жесткий список из whitelist (~100 популярных)
}
```

**Функция media upload (НОВАЯ):**
```php
public function ajaxMediaUpload() {
    // POST: files[] (из jQuery file upload)
    // Валидация: PNG, SVG, JPG, GIF
    // Максимальный размер: 1MB
    // Вызов функция: wp_handle_upload()
    // Возвращает: attachment ID + URL + type
    // JSON: {
    //     'success': true,
    //     'attachment_id': 1234,
    //     'url': '/uploads/2026/04/icon.png',
    //     'type': 'image/png'
    // }
}
```

### 4.4 Backend - Админ-интерфейс (расширенный)

**Регистрация в админке:**
```php
add_meta_box(
    'property_characteristics',
    'Характеристики',
    'render_property_characteristics_metabox',
    'property',
    'normal',
    'high'
);

function render_property_characteristics_metabox($post) {
    // Двухколоночный UI:
    // - Левая: Группы (вкладки)
    // - Правая: Characteristics (с drag-drop в левую)
    // - Левая нижняя: Выбранные (drag-drop сортировка внутри левой)

    // JS логика: jQuery UI Sortable
    // AJAX save: wp_ajax_save_property_characteristics
}
```

### 4.5 Frontend - Фронт-энд Отображение

**Файл:** `property-features.php` (обновить)

**Логика:**
```php
$characteristics = get_post_meta($post_id, 'property_characteristics', true);

if (!empty($characteristics)) {
    foreach ($characteristics as $char_data) {
        $char = get_post($char_data['characteristic_id']);
        if (!$char) continue;

        $icon = get_post_meta($char->ID, 'icon', true);
        $style = get_post_meta($char->ID, 'style', true);
        $value = get_post_meta($char->ID, 'value', true);

        // Рендер в зависимости от стиля
        render_characteristic_by_style($char->post_title, $icon, $value, $style);
    }
}
```

### 4.6 Иконки - Material Symbols & Media Upload (расширено)

**Опция 1: Material Symbols Outlined (жесткий список)**
```php
$allowed_icons = [
    'pets' => 'Питомцы',
    'group' => 'Люди/Гости',
    'bed' => 'Кровать',
    'bathtub' => 'Ванная',
    'wifi_cable' => 'WiFi',
    'restaurant' => 'Кухня',
    'music_off' => 'Без музыки',
    'pets_off' => 'Без питомцев',
    'wifi_off' => 'Без WiFi',
    'check_circle' => 'Разрешено',
    'cancel_circle' => 'Запрещено',
    // ... и т.д. (нужно составить список ~100 популярных)
];
```

**Опция 2: Загруженные файлы (PNG/SVG/JPG - НОВОЕ)**
```php
// Когда выбирается icon_type = 'upload':
// 1. Media picker WP (встроенный)
// 2. Валидация типов: image/png, image/svg+xml, image/jpeg, image/gif
// 3. Максимум: 1MB
// 4. Сохранение: attachment post (ID в meta media_id)
// 5. На фронте: <img src="get_attached_file()" alt="...">
// 6. Или SVG inline: file_get_contents() → echo
```

### 4.7 CSS/JS Структура в /inc/admin/assets/

**JavaScript файлы:**
```
/inc/admin/assets/js/

1. characteristics-admin.js (основной)
   - Управление вкладками (Tab 1: Группы | Tab 2: Значения)
   - Drag-drop сортировка (jQuery UI Sortable)
   - AJAX CRUD операции
   - Modal окна (open/close)
   - Валидация формы
   - Error/Success уведомления

2. icon-picker.js (автокомплит иконок)
   - jQuery UI Autocomplete
   - Поиск по material-symbols
   - Preview иконки при выборе
   - Fallback для неверных названий (icon: 'help')

3. media-picker.js (загрузка файлов)
   - WP Media Modal integration
   - jQuery file upload для прямой загрузки
   - Drag-drop зона для файлов
   - Валидация типов и размера
   - Preview загруженного файла
```

**CSS файлы:**
```
/inc/admin/assets/css/

1. characteristics-admin.css (основные стили)
   - Админ интерфейс layout
   - Таблица с колонками
   - Modal окна стили
   - Form inputs (text, select, checkbox)
   - Buttons (primary, secondary, danger)
   - Drag-drop hover效果

2. metabox-characteristics.css (метабокс на свойстве)
   - Двухколоночный layout
   - Левая колонка (группы вкладки)
   - Правая колонка (список значений)
   - Нижняя левая (выбранные)
   - Drag-drop hover

3. icon-preview.css (превью иконок)
   - .icon-preview { font-family: 'Material Symbols Outlined'; }
   - .icon-preview.upload { /* для загруженных файлов */ }
   - .icon-empty { /* fallback при ошибке */ }
   - Preview box стили
```

**Регистрация скриптов в админке:**
```php
// admin-function.php или в hooks функция:
function realty_enqueue_characteristics_assets() {
    if (!is_admin() || get_current_screen()->id !== 'property_page_characteristics-reference') {
        return;
    }

    // jQuery UI Sortable (встроенный в WP)
    wp_enqueue_script('jquery-ui-sortable');

    // jQuery UI Autocomplete (встроенный в WP)
    wp_enqueue_script('jquery-ui-autocomplete');

    // Кастомные скрипты
    wp_enqueue_script('characteristics-admin',
        get_template_directory_uri() . '/inc/admin/assets/js/characteristics-admin.js',
        ['jquery', 'jquery-ui-sortable', 'jquery-ui-autocomplete'],
        '1.0.0',
        true
    );

    wp_enqueue_script('icon-picker',
        get_template_directory_uri() . '/inc/admin/assets/js/icon-picker.js',
        ['jquery', 'jquery-ui-autocomplete'],
        '1.0.0',
        true
    );

    wp_enqueue_script('media-picker',
        get_template_directory_uri() . '/inc/admin/assets/js/media-picker.js',
        ['jquery'],
        '1.0.0',
        true
    );

    // Кастомные стили
    wp_enqueue_style('characteristics-admin',
        get_template_directory_uri() . '/inc/admin/assets/css/characteristics-admin.css',
        [],
        '1.0.0'
    );

    wp_enqueue_style('metabox-characteristics',
        get_template_directory_uri() . '/inc/admin/assets/css/metabox-characteristics.css',
        [],
        '1.0.0'
    );

    wp_enqueue_style('icon-preview',
        get_template_directory_uri() . '/inc/admin/assets/css/icon-preview.css',
        [],
        '1.0.0'
    );

    // Localize для AJAX
    wp_localize_script('characteristics-admin', 'characteristicsData', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('characteristics_nonce'),
        'allowed_icon_types' => ['image/png', 'image/svg+xml', 'image/jpeg', 'image/gif'],
        'max_upload_size' => 1048576, // 1MB
        'ui_types' => ['checkbox', 'radio', 'select', 'multi_select', 'chips', 'switch'],
        'styles' => ['circle', 'standard', 'prohibited', 'text']
    ]);
}
add_action('admin_enqueue_scripts', 'realty_enqueue_characteristics_assets');
```

---

## 5. Шаги Реализации

### Фаза 1: Регистрация CPT + Таксономия (3-4 часа)

**1.1 Обновить `pods-registration.php`:**
- Добавить `register_post_type('characteristic', [...])`
- Добавить `register_taxonomy('char_group', [...])`
- Зарегистрировать meta fields для posts (icon, style, label, hint, value, sort_order, icon_type, media_id)
- Зарегистрировать term_meta для таксономии (type_ui, description_full, active, sort_order, use_in_filters, show_in_archive)
- Вызов функции в хуке `init`

**1.2 Тестирование:**
- Проверить появление "Характеристики" CPT в админке
- Проверить таксономию "Группы характеристик"
- Проверить наличие meta полей (через DB или WP CLI)

### Фаза 2: CSS/JS структура (1-2 часа)

**2.1 Создать директории:**
```
/inc/admin/assets/js/
/inc/admin/assets/css/
```

**2.2 Создать файлы (пустые, заголовки):**
- `characteristics-admin.js` — заголовок + обработчики событий (структура)
- `icon-picker.js` — заголовок + jQuery UI Autocomplete wrapper
- `media-picker.js` — заголовок + WP Media integration
- `characteristics-admin.css` — базовые стили
- `metabox-characteristics.css` — стили метабокса
- `icon-preview.css` — стили preview иконок

**2.3 Регистрация скриптов в админке:**
- Функция `realty_enqueue_characteristics_assets()` (в admin-function.php)
- Локализация данных (nonce, AJAX URL, allowedTypes и т.д.)

### Фаза 3: Админ-интерфейс управления (4-5 дней)

**3.1 Файл `settings/characteristics-settings.php` (новый):**
- Функция `realty_characteristics_page_content()` — главная отрисовка
- Tab 1: Управление группами (CRUD форма)
  - Список групп с drag-drop
  - Modal: добавить/редактировать группу
    - Поля: name, description, type_ui, active, sort_order, use_in_filters, show_in_archive
  - AJAX save/update/delete groups
- Tab 2: Управление значениями
  - Фильтр по группе (левая колонка)
  - Таблица с characteristics (icon preview, title, label, hint, style)
  - Modal: добавить/редактировать значение
    - Поля: title, description, label, hint, value, style, icon_type, icon/media_id, sort_order, parent
    - Icon pick: autocomplete material-symbols ИЛИ media picker
  - Drag-drop сортировка (изменяет sort_order)

**3.2 AJAX endpoints (в PropertyFeature или отдельный класс):**
- `characteristic_icon_search` — поиск material-symbols
- `characteristic_media_upload` — загрузка PNG/SVG/JPG
- `characteristic_crud` — save/update/delete characteristic
- `characteristic_group_crud` — save/update/delete group (term) + meta
- `characteristic_save_order` — drag-drop sort_order update
- `get_characteristics_by_group` — для фильтров + метабокса
- Все с nonce + capabilities check

**3.3 Nonce / Security:**
- Везде `wp_verify_nonce()`
- Везде sanitize INPUT: `sanitize_text_field()`, `absint()`, `sanitize_textarea_field()`
- Везде escape OUTPUT: `esc_html()`, `esc_attr()`, `wp_kses_post()`
- Везде `current_user_can('manage_options')`

### Фаза 4: Метабокс на свойстве (2-3 дня) [без изменений]

### Фаза 5: Фронт-энд отображение (1-2 дня) [обновлено]

**5.1 Обновить `property-features.php`:**
- Получить meta `property_characteristics`
- Перебор через foreach
- Для каждого элемента:
  - Загрузить post characteristic
  - Извлечь: icon/media_id, icon_type, style, label, value
  - Если icon_type='material_symbol': `<span class="material-symbols-outlined">{icon}</span>`
  - Если icon_type='upload': `<img src="{media_url}">`
- Вызвать `render_characteristic_by_style()`

**5.2 Функция render_characteristic_by_style:**
```php
// При icon_type='upload' и style='standard':
// <img src="..." alt=""> + <span>Pet Friendly</span>

// При icon_type='upload' и style='circle':
// <div class="icon-circle"><img src="..."></div> + <label>GUESTS</label> + <value>Up to 4</value>

// Et cetera для всех комбинаций
```

### Фаза 6: Фильтры на фронте - PropertyFilter.js (2-3 дня) [НОВАЯ]

**6.1 Обновить компонент `PropertyFilter.js` (Vue):**
- Получить группы characteristics с `use_in_filters=1`
- Для каждой группы:
  - Получить type_ui из term_meta
  - Получить characteristics of this group
  - Рендер UI элемента в зависимости от type_ui:
    ```javascript
    case 'checkbox': render_el_checkbox_group(characteristics);
    case 'radio': render_el_radio_group(characteristics);
    case 'select': render_el_select(characteristics);
    case 'multi_select': render_el_select_multiple(characteristics);
    case 'chips': render_chips_custom(characteristics);
    case 'switch': render_el_switch_foreach(characteristics);
    ```

**6.2 AJAX поиск недвижимости по выбранным characteristics:**
```php
// Filter свойств по selected characteristics IDs
// WHERE meta_key='property_characteristics' AND meta_value LIKE '%characteristic_id%'
// Или normalizovat JSON массив и compare
```

**6.3 Icons в фильтрах:**
- Если icon_type='material_symbol': `<span class="material-symbols-outlined">{icon}</span>`
- Если icon_type='upload': `<img src="{url}">`
- Слева от label в каждом чекбоксе/опции

### Фаза 7: AJAX Icon Search & Media Upload (1-2 дня)

**7.1 Собрать список material-symbols иконок:**
- Жесткий список (~100-200 популярных) или динамический
- Кэширование через transient (1 день) или статический JSON файл

**7.2 AJAX endpoints:**
- `characteristic_icon_search` — autocomplete
- `characteristic_media_upload` — file upload + resize если нужно

### Фаза 8: Тестирование & Документация (1-2 дня)

**8.1 Функциональное тестирование:**
- CRUD groups (с type_ui, active и т.д.)
- CRUD characteristics (icon_type material/upload)
- Drag-drop сортировка групп и значений
- Привязка к свойствам (метабокс)
- Фронт отображение (все 4 стиля + медиа иконки)
- Фильтры на фронте (все type_ui: checkbox, radio, select, etc.)
- Иконки отображаются корректно

**8.2 Security тесты:**
- Нету AJAX errors в консоле
- Nonce проверки работают
- Права доступа (только admin в админке, фронт for all)
- XSS protection (no injections)
- File upload validation (type/size)

**8.3 Performance:**
- Кэширование иконок
- Кэширование groups + characteristics
- N+1 queries check
- Load time on фильтре

**8.4 Документация:**
- Комментарии в коде
- UPDATE CLAUDE.md
- User guide для админки
- Developer guide для Vue компонентов

---

## 6. Риски и Ограничения

### Критические риски:

1. **Иерархия + Стили:**
   - Если подчинённая характеристика наследует стиль родителя → может быть конфликт
   - **Решение:** каждый уровень имеет свой стиль (явно)

2. **Performance на фронте:**
   - Если свойство привязано к 50+ характеристикам — медленно рендер
   - **Решение:** кэширование через wp_cache + оптимизация DOM

3. **Material-symbols иконки:**
   - Если иконка не существует — не отобразится (белый квадрат)
   - **Решение:** whitelis или fallback иконка (icon: 'help')

4. **Drag-drop сортировка:**
   - jQuery UI может конфликтовать с другими плагинами
   - **Решение:** использовать более современный подход (React/Vue) или изолировать

### Средние риски:

5. **REST API:**
   - CPT `characteristic` нужен `show_in_rest` для Vue фронта
   - **Решение:** регистрировать в REST + добавить `_links` для relationship

6. **Удаление характеристики:**
   - При удалении → orphaned записи в meta свойств
   - **Решение:** hook на `before_delete_post` → очистить meta

7. **Локализация:**
   - Если используется WPML — нужна стратегия синхронизации характеристик
   - **Решение:** документация или отдельный плагин

### Низкие риски:

8. **Кэширование:**
   - Администратор добавит характеристику, но на фронте не видна (кэш)
   - **Решение:** очистка кэша на save_post

9. **Версионирование:**
   - Не отслеживаются изменения характеристик (нет revision)
   - **Решение:** если нужно — enable revisions для CPT

---

## 7. Что Уточнено ✅

1. **Иконки:** Material Symbols Outlined, вводить название (т.е. "group", "pets")
2. **Стили:** Да, 4 типа (circle, standard, prohibited, text)
3. **Иерархия:** Да, группы с подгруппами + значениями
4. **Привязка:** Без ограничений, множество значений
5. **Сортировка:** Да, drag-drop в админке + сохранение порядка

**Дополнительно:**
- Детальный попап — потом (not in scope)
- Обязательность выбора — НЕТ (опционально)

---

## 8. Definition of Done

### ✅ Админка готова, когда:

- [ ] CPT `characteristic` регистрируется и видна в админ-меню
- [ ] Таксономия `char_group` создана и работает
- [ ] Страница `characteristics-settings.php` открывается

- [ ] **Таб "Группы" (НОВОЕ):**
  - [ ] Форма добавления новой группы работает
  - [ ] Поля в форме:
    - [ ] Название группы (required)
    - [ ] Краткое описание (optional)
    - [ ] Выбор UI фильтрации (select): checkbox|radio|select|multi_select|chips|switch
    - [ ] Чекбокс "Использовать в фильтрах" (для PropertyFilter.js)
    - [ ] Чекбокс "Показывать в архиве"
    - [ ] Чекбокс "Активна"
    - [ ] Input "Позиция" (sort_order)
  - [ ] Список групп отображается с сортировкой
  - [ ] Edit/delete групп работают
  - [ ] Drag-drop сортировка групп работает
  - [ ] Все поля сохраняются как term_meta

- [ ] **Таб "Характеристики":**
  - [ ] Таблица с characteristic постов отображается
  - [ ] AJAX фильтр по группам работает
  - [ ] Форма "Добавить" открывает modal

  - [ ] **Modal при добавлении/редактировании (РАСШИРЕННОЕ):**
    - [ ] Поле (required): "Название значения" (post_title)
    - [ ] Textarea (optional): "Краткое описание" (post_content)
    - [ ] Поле (optional): "Лейбл" (meta: label)
    - [ ] Поле (optional): "Хинт/Подпись" (meta: hint)
    - [ ] Поле (optional): "Дополнительное значение" (meta: value)
    - [ ] Select (required): "Стиль" (circle|standard|prohibited|text)
    - [ ] **Icon Type Selection (НОВОЕ):**
      - [ ] Radio: "Material Symbol"
        - [ ] Text input + AJAX Autocomplete
        - [ ] Preview иконки при вводе
      - [ ] Radio: "Загруженный файл"
        - [ ] WP Media Picker / File Upload
        - [ ] Preview загруженного файла
        - [ ] Валидация типов: PNG, SVG, JPG, GIF
        - [ ] Макс размер: 1MB
    - [ ] Select (optional): "Родительская характеристика" (post_parent)
    - [ ] Input: "Позиция" (sort_order)
    - [ ] Checkbox: "Активна" (post_status)

  - [ ] Icon autocomplete works (AJAX search material-symbols)
  - [ ] Media upload works (file validation + preview)
  - [ ] Save / Update работают (AJAX + database)
  - [ ] Delete работает (с confirmation)
  - [ ] Drag-drop сортировка работает → sort_order обновляется
  - [ ] Все операции имеют nonce проверки
  - [ ] Все операции проверяют `current_user_can('manage_options')`

- [ ] **CSS/JS структура:**
  - [ ] Директории созданы: `/inc/admin/assets/js/` и `/inc/admin/assets/css/`
  - [ ] Файлы скриптов:
    - [ ] `characteristics-admin.js` — основная логика
    - [ ] `icon-picker.js` — автокомплит иконок
    - [ ] `media-picker.js` — загрузка файлов
  - [ ] Файлы стилей:
    - [ ] `characteristics-admin.css` — админ интерфейс
    - [ ] `metabox-characteristics.css` — метабокс
    - [ ] `icon-preview.css` — preview иконок
  - [ ] Скрипты зарегистрированы в хуке admin_enqueue_scripts
  - [ ] Локализованы данные (ajax_url, nonce, etc.)

- [ ] **Метабокс на свойстве:**
  - [ ] Метабокс регистрируется на странице property edit
  - [ ] UI: левая колонка (группы) + правая колонка (characteristics)
  - [ ] Клик на группу → загружаются characteristics этой группы (AJAX)
  - [ ] Drag-drop из правой в левую → добавляет в свойство
  - [ ] Левая нижняя: drag-drop внутри для сортировки
  - [ ] Кнопка delete рядом с каждым элементом
  - [ ] Save работает: meta `property_characteristics` заполняется
  - [ ] Load работает: при открытии свойства → характеристики загружаются

- [ ] **AJAX endpoints:**
  - [ ] `wp_ajax_characteristic_icon_search` — returns material-symbols по term
  - [ ] `wp_ajax_characteristic_media_upload` — handles PNG/SVG/JPG upload
  - [ ] `wp_ajax_characteristic_save_order` — updates sort_order
  - [ ] `wp_ajax_get_characteristics_by_group` — returns characteristics of group
  - [ ] `wp_ajax_characteristic_crud` — save/update/delete values (posts)
  - [ ] `wp_ajax_characteristic_group_crud` — save/update/delete groups (terms) + meta
  - [ ] Все endpoints имеют nonce проверку
  - [ ] Все sanitize INPUT, escape OUTPUT
  - [ ] JSON ответы структурированы

### ✅ Фронт готов, когда:

- [ ] **property-features.php обновлён:**
  - [ ] Читает `property_characteristics` meta
  - [ ] Для каждого элемента:
    - [ ] Загружается post characteristic
    - [ ] Извлекаются: title, label, hint, value, icon_type, icon/media_id, style

  - [ ] **Рендер по стилям (с поддержкой медиа-иконок):**
    - [ ] **circle:** icon (material|media) + label + value
    - [ ] **standard:** icon + title [+ hint/value если есть]
    - [ ] **prohibited:** icon + title (strikethrough)
    - [ ] **text:** plain text (без icon div)

  - [ ] Иконки отображаются корректно:
    - [ ] material-symbols: `<span class="material-symbols-outlined">{icon}</span>`
    - [ ] media: `<img src="{url}">` или `<svg>{inline}</svg>`
  - [ ] Порядок на фронте = порядок из sort_order (админка)
  - [ ] На странице одного свойства — несколько characteristics отображаются
  - [ ] На странице архива (если показываем) — characteristics видны

- [ ] **PropertyFilter.js обновлён (НОВОЕ):**
  - [ ] Получает группы с `use_in_filters=1`
  - [ ] Для каждой группы читает `type_ui`
  - [ ] Рендер фильтра в зависимости от type_ui:
    - [ ] **checkbox:** el-checkbox-group (multi-select)
    - [ ] **radio:** el-radio-group (single select)
    - [ ] **select:** el-select (single)
    - [ ] **multi_select:** el-select multiple
    - [ ] **chips:** кастомные chips (multi-select)
    - [ ] **switch:** el-switch для каждого значения (binary)
  - [ ] Иконки отображаются в фильтрах (material|media)
  - [ ] AJAX фильтрация свойств по selected characteristics
  - [ ] Выбранные значения сохраняются в query params (для шеринга ссылок)

### ✅ Бэкэнд-код готов, когда:

- [ ] `pods-registration.php` обновлён:
  - [ ] `register_post_type('characteristic', [...])`
  - [ ] `register_taxonomy('char_group', [...])`
  - [ ] `register_meta(...)` для posts: icon, style, label, hint, value, sort_order, icon_type, media_id
  - [ ] `register_term_meta(...)` для char_group: type_ui, description_full, active, sort_order, use_in_filters, show_in_archive

- [ ] `PropertyFeature` класс расширен новыми AJAX методами:
  - [ ] `ajaxIconSearch()` — material-symbols поиск + кэширование
  - [ ] `ajaxMediaUpload()` — file upload validation + attachment creation
  - [ ] `ajaxCharacteristicCRUD()` — save/update/delete posts
  - [ ] `ajaxGroupCRUD()` — save/update/delete terms + meta
  - [ ] `ajaxSaveOrder()` — drag-drop sort_order
  - [ ] `ajaxGetCharacteristicsByGroup()` — для фильтров

- [ ] Вспомогательные функции:
  - [ ] `get_characteristics_by_group($group_id, $active_only=true)` — getting data
  - [ ] `get_material_symbols_icons($term)` — search + cache (transient 1 day)
  - [ ] `render_characteristic_by_style($data)` — output по стилям + icon_type
  - [ ] `get_characteristic_icon_html($icon_type, $value, $media_id)` — иконка HTML
  - [ ] `validate_uploaded_file($file)` — file validation

- [ ] Код имеет:
  - [ ] Комментарии (что/как/почему)
  - [ ] PHPDoc блоки для функций
  - [ ] Нет синтаксических ошибок (PHP lint OK)
  - [ ] Все функции escaped/sanitized (XSS protection)
  - [ ] Все AJAX endpoints имеют nonce + capability check

### ✅ Интеграция готова, когда:

- [ ] Не сломаны текущие фильтры (location, price, etc.)
- [ ] Не конфликтует с Pods Framework
- [ ] REST API добавлен для characteristic CPT (если нужен)
- [ ] Фильтры интегрированы в PropertyFilter.js компонент
- [ ] Медиа-иконки работают на фронте (всюду)
- [ ] Миграция данных (если было что-то старое)

---

## 9. Архитектура Решения (ЛУЧШИЙ ПОДХОД)

### Почему CPT вместо Pods Pod?

| Функция | CPT | Pods Pod |
|---------|-----|----------|
| Иерархия (post_parent) | ✅ Встроено | ⚠️ Нужна доп. таксономия |
| Taxonomies | ✅ Стандартно | ⚠️ Нужна доп. настройка |
| REST API | ✅ Встроено | ⚠️ Доступно через плагин |
| Performance | ✅ Оптимизировано WP Core | ⚠️ Может быть медленнее |
| Simplicity | ✅ Стандартный WP | ⚠️ Зависит от Pods |

**РЕКОМЕНДАЦИЯ: CPT (`characteristic`) через Pure WP API, иерархия через `post_parent`, таксономия через стандартный `register_taxonomy`**

---

## 10. Файлы участвующие

| Файл | Статус | Задача |
|------|--------|--------|
| `pods-registration.php` | 🟡 Обновить | Регистрация CPT + taxonomy + meta |
| `settings/characteristics-settings.php` | 🆕 Создать | Главная страница управления справочником |
| `PropertyFeature` class | 🟡 Расширить | AJAX endpoints + helper functions |
| `property-features.php` | 🟡 Обновить | Фронт отображение (читать из БД) |
| `admin-function.php` | 🟡 Обновить | Register admin page + metabox |
| `functions.php` | 🟡 Обновить | Хелпер функции для фронта |

---

## 11. Примеры Code Snippets

### Получить характеристики свойства:
```php
function get_property_characteristics($post_id) {
    $data = get_post_meta($post_id, 'property_characteristics', true);
    if (empty($data)) return [];

    $result = [];
    foreach ($data as $item) {
        $char_post = get_post($item['characteristic_id']);
        if (!$char_post) continue;

        $result[] = [
            'id' => $char_post->ID,
            'title' => $char_post->post_title,
            'icon' => get_post_meta($char_post->ID, 'icon', true),
            'style' => get_post_meta($char_post->ID, 'style', true),
            'value' => get_post_meta($char_post->ID, 'value', true),
            'order' => $item['order'] ?? 0,
        ];
    }

    return $result;
}
```

### Material Symbols CDN:
```html
<!-- В header-e -->
<link rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

<style>
    .material-symbols-outlined {
        font-family: 'Material Symbols Outlined';
        font-weight: normal;
        font-style: normal;
        font-size: 24px;
        display: inline-block;
        line-height: 1;
        text-transform: none;
        letter-spacing: normal;
        word-wrap: normal;
        white-space: nowrap;
        direction: ltr;
    }
</style>
```

---

## 12. Сложность & Временные оценки

| Фаза | Дни | Сложность |
|------|-----|-----------|
| 1. CPT регистрация | 0.5 | 🟢 низкая |
| 2. Админ интерфейс | 3-4 | 🟠 средняя |
| 3. Метабокс | 2-3 | 🟠 средняя |
| 4. Фронт | 1-2 | 🟢 низкая |
| 5. Icon search | 1-2 | 🟡 средняя |
| 6. Тестирование | 1-2 | 🟡 средняя |

**ИТОГО: ~10-15 дней** на одного разработчика (при понимании WP + AJAX)

---

## 7. Что Не Хватает (Уточнения)

1. ✅ **Источник иконок:** Material Symbols Outlined (вводить название)
2. ✅ **REST API:** Потом / не сейчас
3. ✅ **Иерархия групп:** Да, может быть (группа → подгруппа → значения)
4. ✅ **Обязательность:** Нет ограничений
5. ✅ **Сортировка:** Да, drag-drop в админке + фронт по порядку

**Дополнительные уточнения (future):**
- Детальный popup — потом
- Bulk operations (import/export) — потом
- Многоязычность (WPML) — если нужна позже

---

## 8. Definition of Done

[все пункты выше]


---

## 9. Архитектура Решения (ЛУЧШИЙ ПОДХОД)

[все пункты выше]

---

## 10. Файлы участвующие

[все файлы выше]

---

## 11. Примеры Code Snippets

[все примеры выше]

---

## 12. Сложность & Временные оценки

[все оценки выше]

---

## 17. Использование Справочника в Системе (Полная Карта Взаимосвязей)

### Этап 1: Администратор создаёт справочник

**Шаг 1: Управление группами**
```
Админка → Недвижимость → Справочник Характеристик → Таб "Группы"
↓
Администратор создаёт группу:
- Название: "Квартира"
- Описание: "Основные параметры квартиры"
- type_ui: "checkbox"  ← Тип UI для фронт-фильтров (новое!)
- use_in_filters: 1    ← Использовать в PropertyFilter.js
- active: 1
- sort_order: 1
↓
Сохраняется в: wp_terms + wp_termmeta
```

**Шаг 2: Добавление значений в группу**
```
Админка → Таб "Характеристики" → Клик на "Квартира" → Форма добавления
↓
Администратор заполняет:
- Название: "GUESTS"
- Лейбл: "GUESTS"
- Хинт: (пусто)
- Значение: "Up to 4"
- Стиль: "circle"
- Иконка:
  » Material Symbol: "group" ← AJAX поиск + preview
  » или загрузить файл: logo.svg ← WP Media, валидация
- Позиция: 1
↓
Сохраняется в: wp_posts (CPT: characteristic) + wp_postmeta (meta fields)
```

### Этап 2: Редактор недвижимости привязывает к объекту

**Шаг 1: Открыть свойство для редактирования**
```
Админка → edit.php?post_type=property&post=123
↓
Видит метабокс "Характеристики"
```

**Шаг 2: Выбрать и привязать характеристики**
```
Метабокс:
Левая колонка:      Правая колонка:            Нижняя левая:
┌─────────────┐    ┌──────────────────┐       ┌────────────────┐
│ Квартира ▼  │ →  │ ☐ GUESTS (Up 4)  │  →   │ 🔄 GUESTS ✕   │
│ Услуги      │    │ ☐ BEDROOMS (2Q)  │  →   │ 🔄 BEDROOMS ✕ │
│ Правила     │    │ ☐ BATHS (1.5)    │       └────────────────┘
└─────────────┘    └──────────────────┘
      ↑                  ↑                           ↑
  Клик на группу   Drag-drop значения       Дополнительная
  (AJAX load)      в нижнюю левую          сортировка (drag)

↓
СОХРАНЯЕТСЯ В: wp_postmeta (property_characteristics)
Значение: [
    {'characteristic_id': 10, 'order': 1},
    {'characteristic_id': 11, 'order': 2},
    {'characteristic_id': 12, 'order': 3}
]
```

### Этап 3: Фронт-энд отображение (на странице свойства)

**Сценарий: Посетитель открывает страницу свойства**
```
Frontend: single-property.php
↓
Получить meta: get_post_meta(123, 'property_characteristics')
↓
Для каждой характеристики:
  ├─ Загрузить post (CPT: characteristic)
  ├─ Извлечь: icon_type (material_symbol или upload)
  ├─ Извлечь: icon или media_id
  ├─ Извлечь: style (circle, standard, prohibited, text)
  ├─ Извлечь: label, value
  └─ Рендер по style:

  ЕСЛИ style='circle':
    <div class="feature-item__com">
      <div class="icon-circle">
        <span class="material-symbols-outlined">group</span>
      </div>
      <div class="feature-text">
        <span class="label">GUESTS</span>
        <span class="value">Up to 4</span>
      </div>
    </div>

  ЕСЛИ style='standard' + icon_type='upload':
    <div class="amenity-item">
      <img src="/uploads/2026/04/pet-icon.svg" alt="Pet">
      <span>Pet Friendly</span>
    </div>
```

### Этап 4: Фильтрация на фронте (новое)

**Сценарий: Посетитель ищет недвижимость с фильтрами**
```
Frontend: PropertyFilter.js (Vue component)
↓
Получить группы: get_terms(['taxonomy' => 'char_group', 'meta_query' => use_in_filters])
↓
Для каждой группы:
  ├─ Получить type_ui (checkbox, radio, select, chips, switch, etc.)
  ├─ Получить characteristics of this group
  ├─ Рендер фильтра:

  TYPE_UI = 'checkbox':
    ☑ GUESTS
    ☐ BEDROOMS
    ☐ BATHS

  TYPE_UI = 'chips':
    [GUESTS] [BEDROOMS] [BATHS]

  TYPE_UI = 'radio':
    ◉ После 15:00
    ○ После 14:00

  TYPE_UI = 'switch':
    ON  Pets allowed
    OFF No pets

  TYPE_UI = 'select':
    [Select one ▼]

↓
Посетитель выбирает: GUESTS + BEDROOMS
↓
AJAX запрос на фильтрацию:
  POST: /wp-admin/admin-ajax.php?action=property_filter
  Data: {
    characteristic_ids: [10, 11],
    ...другие фильтры
  }
↓
Backend (PropertyFeature::ajaxFilter):
  WHERE property meta_query:
    property_characteristics LIKE '%10%' OR LIKE '%11%'

  + другие WHERE условия (location, price, etc.)
↓
Возвращает: отфильтрованные свойства
↓
Фронт: рендер результаты
```

### Архитектура Данных (SQL-like)

```
WordPress Posts (CPT: characteristic):
├── ID: 10
│   post_title: "GUESTS"
│   post_content: "Количество гостей"
│   post_status: "publish"
│   post_parent: 0
│   Meta:
│   ├─ icon: "group" (material-symbols-outlined)
│   ├─ icon_type: "material_symbol"
│   ├─ label: "GUESTS"
│   ├─ hint: ""
│   ├─ value: "Up to 4"
│   ├─ style: "circle"
│   ├─ sort_order: 1
│   └─ media_id: null
└─ Terms: char_group = "Квартира"

WordPress Terms (Taxonomy: char_group):
├── term_id: 5
│   name: "Квартира"
│   slug: "kvartira"
│   Term Meta:
│   ├─ type_ui: "checkbox"          ← Для фронт-фильтров!
│   ├─ description_full: "Основные параметры"
│   ├─ active: 1
│   ├─ sort_order: 1
│   ├─ use_in_filters: 1            ← Показывать в PropertyFilter
│   └─ show_in_archive: 1

WordPress Post Meta (property):
├── post_id: 123 (свойство)
│   meta_key: "property_characteristics"
│   meta_value: [
│       {'characteristic_id': 10, 'order': 1},
│       {'characteristic_id': 11, 'order': 2}
│   ]
```

### Резюме: Кто что использует где

| Компонент | Что использует | Как |
|-----------|---|---|
| **Админка справочник** | Создаёт группы + значения | CRUD интерфейс в /settings/characteristics-settings.php |
| **Метабокс свойства** | Привязывает values | Выбирает из dropdown'ов, сохраняет IDs в meta |
| **Фронт single-property** | Отображает values | Читает meta, рендер по style |
| **Фронт фильтры** | Фильтрует свойства | Использует groups с type_ui (checkbox/radio/select/etc.) |
| **Фронт архив** | Показывает список | Может отображать characteristics (если use_in_archive=1) |

---

## 18. Важные Моменты при Разработке

### Гибкость и Предсказуемость

1. **Структура четкая:** CPT + taxonomy + meta = стандартный WP подход
2. **Не перекомплицировано:** Нет зависимостей от Pods (но можно использовать если нужно)
3. **Предсказуемо:** Разработчик легко понимает, куда сохраняется и откуда читается
4. **Гибко:** type_ui позволяет менять UI фильтра без изменения кода
5. **Медиа-поддержка:** Icon_type позволит в будущем добавлять любые типы файлов

### Security & Performance

**Security:**
- Всё через `wp_verify_nonce()` + `current_user_can()`
- Все INPUT через `sanitize_*()`
- Все OUTPUT через `esc_*()`
- File upload валидация (type + size)

**Performance:**
- Кэширование material-symbols иконок (transient 1 день)
- Кэширование get_characteristics() результатов
- На фронте: одинарный query (не N+1)
- Drag-drop может быть медленным с 100+ элементами — OK для справочника

### Будущие Расширения

Система разрабатывается так, чтобы легко добавить:
- CSV/JSON import-export справочника
- Многоязычность (WPML)
- Версионирование изменений
- Расширенные фильтры (date range, numeric range)
- Собственные типы фильтров (date picker, number input, etc.)

---

## 19. Чек-лист Перед Разработкой

**Разработчик должен проверить:**

- [ ] Pods Framework активен (или использовать Pure WP API)
- [ ] Material Symbols CDN доступен (или локальный шрифт загружен)
- [ ] jQuery UI доступен (встроенный в WP)
- [ ] Element-plus установлен на проекте (для Vue фильтров)
- [ ] WP Media Library работает
- [ ] File upload разрешен в settings.php (upload_max_filesize, etc.)
- [ ] Права доступа: admin может создавать posts/terms
- [ ] База данных: есть wp_postmeta, wp_posts, wp_terms, wp_termmeta таблицы

**При возникновении вопросов:**
1. Обратиться к этому документу (task_characteristics_reference.md)
2. Проверить примеры кода в разделе 11
3. Убедиться что архитектура понятна (раздел 17)
4. Скорректировать Definition of Done если нужно

---

**ДОКУМЕНТ ГОТОВ ДЛЯ РАЗРАБОТЧИКА!** ✅

### Перед началом:
1. **Подготовка:**
   - Создать ветку `feature/characteristics-reference`
   - Убедиться, что Pods Framework активен (или исползовать Pure WP)
   - Установить Material Symbols шрифт (CDN или локально)

2. **Изучить:**
   - `pods-registration.php` — как там регистрируются CPT
   - `PropertyFeature` class — структура AJAX обработчиков
   - `property-features.php` — текущий фронт-энд HTML

3. **Составить список иконок:**
   - Выбрать ~50-100 нужных material-symbols иконок
   - Составить JSON (для AJAX search)
   - Или использовать готовый источник

### Во время разработки:
1. **Таскирование по фазам** (выше)
2. **Commit message формат:**
   ```
   feat: add characteristics reference system

   - Register characteristic CPT
   - Add admin UI for managing reference
   - Add metabox to properties
   - Support 4 display styles
   ```

3. **Code Review checklist:**
   - Security (nonce, sanitize, escape)
   - Performance (no N+1, caching)
   - UX (loading states, error messages)
   - Compatibility (Pods, WP Core versions)

### После разработки:
1. **Миграция (если есть старые данные)**
2. **Документация в README / CLAUDE.md**
3. **User guide для администратора**

---

## 14. UI/UX Мокап Админки

### Страница "Справочник Характеристик"

```
┌────────────────────────────────────────────────────┐
│ ✓ Справочник Характеристик                         │
├──────────────┬──────────────────────────────────────┤
│ Группы       │  ГРУППА: " Квартира               "  │
│              │     │                              │
│ Квартира     │     ├─ + Добавить                │
│ Услуги       │     │                              │
│ Правила      │     └─ [x] Кнопка Редакт./Удаль  │
│              │                                    │
│ ────────     │  ЗНАЧЕНИЯ (фильтр по группе):   │
│ + Новая      │  ┌─────────────────────────────┐  │
│   группа     │  │ # │ 👾 │ Название  │ Стиль   │  │
│              │  ├─────────────────────────────┤  │
│              │  │ 1  │ 👫 │ GUESTS    │ circle │  │
│              │  │ 2  │ 🛏  │ BEDROOMS  │ circle │  │
│              │  │ 3  │ 🛁  │ BATHS     │ circle │  │
│              │  │    │ + ДОБАВИТЬ                  │
│              │  └─────────────────────────────┘  │
│              │                                    │
│              │  [Редактировать] [Удалить]       │
└──────────────┴──────────────────────────────────────┘
```

### Модальное окно "Редактировать Характеристику"

```
┌──────────────────────────────────────────────┐
│ Редактировать: GUESTS                  [✕]   │
├──────────────────────────────────────────────┤
│ Название *                                   │
│ [GUESTS________________]                     │
│                                              │
│ Иконка (material-symbols-outlined)          │
│ [group________________] 👫  [Поиск]        │
│ Предпросмотр: [👫]                         │
│                                              │
│ Стиль *                                      │
│ ◉ circle      ○ standard     ○ prohibited    │
│ ○ text                                      │
│                                              │
│ Дополнительное значение (опционально)       │
│ [Up to 4_________________]                  │
│                                              │
│ Родительская характеристика                │
│ [— Нет —____________] [выбрать]            │
│                                              │
│ ┌─────────────────────────────────────────┐ │
│ │           [Отмена]  [Сохранить]        │ │
│ └─────────────────────────────────────────┘ │
└──────────────────────────────────────────────┘
```

### Метабокс на свойстве

```
┌─── Характеристики ───────────────────────────┐
│ ┌─────────────┬────────────────────────────┐ │
│ │ Группы      │ Значения                   │ │
│ ├─────────────┼────────────────────────────┤ │
│ │ Квартира ▼  │ ☑ GUESTS ← drag to select │
│ │ Услуги      │ ☐ BEDROOMS                 │
│ │ Правила     │ ☐ BATHS                    │
│ │             │ ☐ WiFi                     │
│ │             └────────────────────────────┘ │
│ │                                             │
│ │ ┌──── ВЫБРАННЫЕ (можно drag-drop) ────┐ │
│ │ │ 🔄 ☑ GUESTS (Up to 4)      [✕]    │ │
│ │ │ 🔄 ☑ WiFi500 Mbps         [✕]    │ │
│ │ │                                    │ │
│ │ │ [+ Добавить ещё]                 │ │
│ │ └────────────────────────────────────┘ │
└──────────────────────────────────────────────┘
```

### Фронт-энд отображение

```
CHARACTER STYLES SHOWCASE:

[icon-circle]
├─ 👫 GUESTS: Up to 4
├─ 🛏  BEDROOMS: 2 Queen
└─ 🛁 BATHS: 1.5 Baths

[icon-standard]
├─ 🐾 Pet Friendly
├─ 📶 Wi-Fi
└─ 🍳 Kitchen

[icon-prohibited]
├─ 🚫 Нельзя проводить вечеринки
└─ 🚫 С животными нельзя

[text-only]
├─ Заселение: после 15:00
└─ Выезд: до 12:00
```

---

## 15. Важные Заметки

### Производительность:
- Кэшировать список material-symbols иконок (transient 1 день)
- Кэшировать get_characteristics() результаты
- На фронте — одинарный запрос (не N+1)

### Безопасность:
- **ВСЕГДА** verify nonce на AJAX endpoints
- **ВСЕГДА** sanitize INPUT (GET/POST)
- **ВСЕГДА** escape OUTPUT (в HTML)
- Проверить capabilities (`manage_options`)

### Тестирование Чек-лист:
- ✓ Создать новую группу
- ✓ Добавить 5 характеристик в группу
- ✓ Drag-drop изменить порядок
- ✓ Редактировать характеристику
- ✓ Удалить характеристику
- ✓ Открыть свойство → привязать характеристики
- ✓ Профіль посмотреть → иконки отобразились
- ✓ Все 4 стиля работают
- ✓ Нету JavaScript ошибок
- ✓ Mobile view работает

---

## 16. Контакты & Вопросы

Если возникнут вопросы при разработке:

1. **Иконки материал-symbols:** Список популярных (для whitelis) Да
2. **REST API:** Нужна ли для Vue фронта? Да
3. **Импорт:** Нужна ли возможность импорта из CSV? (Да но несейчас)
4. **Версионирование:** Нужно ли отслеживать изменения? Нет

**Документ готов для передачи dev'у!**

