# AJAX Endpoints: документация для фронтенда

Этот документ описывает все AJAX хуки темы для интеграции с Vue/JS.

## Фильтрация недвижимости

### property_filter

Основной AJAX хук для фильтрации объектов недвижимости.

- **Action:** `property_filter`
- **Доступ:** `wp_ajax_property_filter` и `wp_ajax_nopriv_property_filter`
- **Файл:** `inc/features/property/property.php:259`

### Параметры запроса

| Параметр | Тип | Обязательный | Описание |
|----------|-----|:------------:|---------|
| `nonce` | string | да | WordPress nonce (создаётся с `property_filter_nonce`) |
| `location` | string | нет | Название локации |
| `location_slug` | string | нет | Slug локации |
| `price_min` | int | нет | Минимальная цена |
| `price_max` | int | нет | Максимальная цена |
| `adults` | int | нет | Количество взрослых |
| `children` | int | нет | Количество детей |
| `page` | int | нет | Номер страницы (по умолчанию 1) |

### Пример запроса

```javascript
jQuery.ajax({
    url: propertyFilter.ajaxurl,
    type: 'POST',
    data: {
        action: 'property_filter',
        nonce: propertyFilter.nonce,
        location: 'Сочи',
        location_slug: 'sochi',
        price_min: 1000,
        price_max: 10000,
        adults: 2,
        children: 1,
        page: 1
    }
});
```

### Формат ответа

```json
{
    "success": true,
    "data": {
        "html": "<div class=\"properties-grid\">...</div>",
        "count": 25,
        "total_pages": 3,
        "pagination": "<ul class=\"pods-pagination\">...</ul>",
        "coordinates": [
            {"id": 123, "lat": "43.123", "lng": "40.456", "title": "Квартира 1"}
        ]
    }
}
```

При ошибке:

```json
{
    "success": false,
    "data": "Security check failed"
}
```

---

## Автодополнение локаций

### location_autocomplete

AJAX хук для поиска локаций (таксономия `location`).

- **Action:** `location_autocomplete`
- **Доступ:** `wp_ajax_location_autocomplete` и `wp_ajax_nopriv_location_autocomplete`
- **Файл:** `inc/features/property/property.php:333`

### Параметры запроса

| Параметр | Тип | Обязательный | Описание |
|----------|-----|:------------:|---------|
| `nonce` | string | да | WordPress nonce |
| `term` | string | да | Поисковый запрос |

### Пример запроса

```javascript
jQuery.ajax({
    url: propertyFilter.ajaxurl,
    type: 'GET',
    data: {
        action: 'location_autocomplete',
        nonce: propertyFilter.nonce,
        term: 'мос'
    }
});
```

### Формат ответа

```json
[
    {"label": "Москва", "value": "moscow"},
    {"label": "Московская область", "value": "moscow-region"}
]
```

---

## AJAX пагинация

### get_locations_autocomplete

Хук для автодополнения в классическом jQuery (используется в фильтре).

- **Action:** `get_locations_autocomplete`
- **Доступ:** `wp_ajax_get_locations_autocomplete` и `wp_ajax_nopriv_get_locations_autocomplete`
- **Файл:** `functions.php:257`

### Параметры

| Параметр | Тип | Обязательный | Описание |
|----------|-----|:------------:|---------|
| `nonce` | string | да | `property_filter_nonce` |
| `term` | string | да | Поисковый запрос |

### Ответ

```json
{
    "success": true,
    "data": [
        {"name": "Москва", "slug": "moscow"},
        {"name": "Можайск", "slug": "mozhaysk"}
    ]
}
```

---

## Использование на фронтенде

### Получение AJAX URL и nonce

```javascript
// Из localize_script
const ajaxurl = propertyFilter.ajaxurl;
const nonce = propertyFilter.nonce;
```

### Обработка ответов

```javascript
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: formData,
    success: function(response) {
        if (response.success) {
            const html = response.data.html;
            const count = response.data.count;
            const coordinates = response.data.coordinates;
            // Обновить DOM
        }
    },
    error: function(xhr, status, error) {
        console.error('AJAX Error:', error);
    }
});
```

---

## Обратная совместимость

Тема поддерживает старые названия хуков:

| Старый | Новый |
|--------|-------|
| `filter_properties_custom` | `property_filter` |

Оба хука ведут на один обработчик.

---

**Обновлено:** 2026-03-21
