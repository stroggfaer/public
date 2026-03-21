# Тег: core.realtycore

Базовый класс темы недвижимости.

## Файл

`inc/core/RealtyCore.php`

## Класс

`RealtyCore`

## Методы

- `getInstance()` — синглтон
- `loadView($name, $data)` — подгрузка view файлов
- `getPosts($post_type, $params)` — универсальный WP_Query
- `createNonce($action)` — создание nonce
- `verifyNonce($nonce, $action)` — проверка nonce
- `getAjaxUrl()` — получение AJAX URL
- `getPostMeta($id, $key, $default)` — метаполя с fallback

## История изменений

### 2026-03-21

- Создан базовый класс RealtyCore
- Добавлены универсальные методы для работы с постами
- Добавлены методы для nonce и AJAX

---

**Обновлено:** 2026-03-21
