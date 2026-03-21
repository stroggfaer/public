# Тег: feature.property

Фича недвижимости - основной компонент темы.

## Файл

`inc/features/property/property.php`

## Класс

`PropertyFeature`

## Методы

- `getList($params)` — получение списка с фильтрацией
- `getListFromGet()` — получение из GET параметров
- `getCurrentFilters()` — активные фильтры
- `formatRequestParams($type)` — унификация параметров GET/POST
- `ajaxFilter()` — AJAX обработка
- `ajaxLocationAutocomplete()` — автодополнение локаций
- `shortcodeFilter($atts)` — шорткод [property_filter]
- `renderHomeForm()` — форма главной страницы
- `renderList()` — рендер списка

## История изменений

### 2026-03-21

- Создан класс PropertyFeature
- Перенесена логика из frontend/filter-functions.php
- Добавлена поддержка AJAX фильтрации
- Добавлен шорткод [property_filter]

### 2026-03-21

- Создан метод formatRequestParams($type) для унификации параметров фильтров
- Обновлены getCurrentFilters(), getListFromGet(), ajaxFilter()

### 2026-03-21

- Исправлена проблема с игнорированием GET-параметров на странице архива
- Удалён дублирующийся код в property-card-list.php
- Теперь используется централизованный вызов property_filter_render_list()

---

**Обновлено:** 2026-03-21
