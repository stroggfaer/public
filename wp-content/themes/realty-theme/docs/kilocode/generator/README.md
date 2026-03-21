# Генератор объектов недвижимости

Генератор для создания тестовых объектов недвижимости в WordPress.

## Расположение

```
wp-content/themes/realty-theme/docs/kilocode/generator/PropertyGenerator.php
```

## Использование

### Через командную строку

```bash
# Создать 5 объектов
php -r "require_once 'wp-content/themes/realty-theme/docs/kilocode/generator/PropertyGenerator.php'; \$g = new PropertyGenerator(); \$ids = \$g->generate(5);"

# Показать все объекты
php -r "require_once 'wp-content/themes/realty-theme/docs/kilocode/generator/PropertyGenerator.php'; print_r((new PropertyGenerator())->getAll());"

# Очистить все объекты
php -r "require_once 'wp-content/themes/realty-theme/docs/kilocode/generator/PropertyGenerator.php'; echo (new PropertyGenerator())->clearAll() . ' objects deleted';"
```

## Генерируемые данные

Для каждого объекта создаются:

| Поле | Описание |
|------|----------|
| post_title | Название (ЖК "Арбат Парк", Лофт "Сити Вью" и т.д.) |
| post_content | Описание |
| price | Цена (3000-50000 руб) |
| address | Адрес (ул. Арбат, д. 1, стр. 1, кв. 1) |
| guest_count | Количество гостей (1-8) |
| location | Локация (Москва) |
| latitude | Широта (55.5-56.0) |
| longitude | Долгота (37.3-37.9) |
| availability_dates | Дата доступности (начало) |
| availability_dates_end | Дата доступности (конец) |
| area | Площадь (25-150 м²) |
| rooms | Комнаты (1-5) |
| floor | Этаж (1-25) |

### Таксономии

- **Локация**: Москва (справочник `location`)
- **Тип жилья**: Рандомно из списка (Дом, Квартира, Комната, Глэмпинг, Гостевой дом, Отель, Хостел)

## Настройки подключения

Сокет MySQL для Local by Flywheel:
```
/Users/rendzhi/Library/Application Support/Local/run/uJDH2JMK_/mysql/mysqld.sock
```

При необходимости изменить в конструкторе:
```php
$generator = new PropertyGenerator('/path/to/socket.sock');
```

## Пример интеграции с functions.php

```php
require_once get_template_directory() . '/docs/kilocode/generator/PropertyGenerator.php';

add_action('init', function() {
    if (isset($_GET['generate_properties']) && current_user_can('manage_options')) {
        $generator = new PropertyGenerator();
        $count = isset($_GET['count']) ? intval($_GET['count']) : 2;
        $ids = $generator->generate($count);
        wp_die('Создано объектов: ' . count($ids));
    }
});
```

Затем перейти по URL: `/?generate_properties&count=5`
