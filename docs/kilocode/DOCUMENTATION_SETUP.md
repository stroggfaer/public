# Настройка системы документирования

## Обзор

Система документирования адаптирована под WordPress тему недвижимости (realty-theme).

## Структура

```
docs/kilocode/
├── realty-theme-rules.md       # Основные правила для агента
├── AJAX_ENDPOINTS.md           # Документация AJAX хуков
├── DOCUMENTATION_SETUP.md      # Этот файл
├── changes/
│   ├── README.md               # Описание системы
│   ├── SCOPES.md               # Справочник скоупов
│   ├── diary/                  # Дневники изменений
│   │   └── 2026/
│   │       └── 03/
│   └── tags/                   # Теги по скоупам
└── rules/                      # Дополнительные правила (будущее)
```

## Скоупы

| Тип | Формат | Пример |
|-----|--------|--------|
| Feature | `feature.<name>` | `feature.property` |
| Core | `core.<name>` | `core.realtycore` |
| Helper | `helper.<name>` | `helper.gallery` |
| Ajax | `ajax.<name>` | `ajax.property_filter` |
| Shortcode | `shortcode.<name>` | `shortcode.property_filter` |
| Template | `template.<name>` | `template.archive-property` |
| Component | `component.<name>` | `component.property-card` |
| JS | `js.<name>` | `js.property-filter` |
| Vue | `vue.<name>` | `vue.property-filter` |
| Admin | `admin.<name>` | `admin.settings` |

## Основные домены

1. **Property** — недвижимость (основная фича)
2. **Filter** — фильтрация
3. **Map** — Google Maps
4. **Gallery** — галереи
5. **Admin** — админ-функции

## Как работает

### Перед началом работы

Агент анализирует задачу:
1. Определяет релевантные скоупы
2. Читает историю из тегов
3. Использует контекст при реализации

### После завершения работы

1. Создаёт запись в дневнике: `docs/kilocode/changes/diary/YYYY/MM/YYYY-MM-DD.md`
2. Обновляет теги: `docs/kilocode/changes/tags/<scope>.md`
3. Сообщает список обновлённых скоупов

## Примеры скоупов

### PropertyFeature

```
wp-content/themes/realty-theme/inc/features/property/property.php
→ feature.property
```

### AJAX хук

```
Хук: property_filter
→ ajax.property_filter
```

### Шорткод

```
Шорткод: [property_filter]
→ shortcode.property_filter
```

### Vue компонент

```
assets/vue_module/src/components/PropertyFilter.js
→ vue.property-filter
```

## Правила именования файлов

- **SCOPES.md** — всегда в корне changes/
- **Теги:** `<scope>.md` (например, `feature.property.md`)
- **Дневники:** `YYYY-MM-DD.md` (например, `2026-03-21.md`)

---

**Обновлено:** 2026-03-21
