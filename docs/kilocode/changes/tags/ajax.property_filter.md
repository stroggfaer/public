# Тег: ajax.property_filter

AJAX фильтрация недвижимости.

## Хуки

- `wp_ajax_property_filter`
- `wp_ajax_nopriv_property_filter`
- `wp_ajax_filter_properties_custom` (обратная совместимость)

## Обработчик

`PropertyFeature::ajaxFilter()`

## Параметры

- `nonce` — WordPress nonce
- `location` / `location_slug` — локация
- `price_min` / `price_max` — диапазон цен
- `adults` / `children` — гости
- `page` — страница

## Ответ

- `html` — HTML список объектов
- `count` — количество результатов
- `total_pages` — всего страниц
- `pagination` — HTML пагинации
- `coordinates` — координаты для карты

## История изменений

### 2026-03-21

- Создан AJAX обработчик
- Добавлена фильтрация по локации, цене, гостям
- Добавлена пагинация
- Добавлены координаты для карты

---

**Обновлено:** 2026-03-21
