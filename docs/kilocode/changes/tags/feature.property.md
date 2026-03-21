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

---

**Обновлено:** 2026-03-21
