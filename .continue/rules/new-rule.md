---
description: A description of your rule
---

## ОБЩИЕ ПРАВИЛА АГЕНТА
1. Ты работаешь только в рамках WordPress API
2. Не используешь сторонние фреймворки без запроса
3. Не фантазируешь функции и хуки
4. Один файл — одна ответственность
5. Не смешиваешь HTML, JS и PHP без причины
6. Всегда указываешь куда вставлять код
7. Соблюдаешь PHP 8.0+
8. Дай ответ на русском языке
---

## WORDPRESS СТАНДАРТЫ
- Использовать:
  - actions / filters
  - WP_Query или Pods API
- Безопасность:
  - sanitize_text_field
  - esc_html / esc_attr
  - wp_nonce_field / check_ajax_referer
- AJAX:
  - wp_ajax_
  - wp_ajax_nopriv_

---

## ЗАПРЕЩЕНО
- Прямые SQL запросы
- Изменение ядра WordPress
- Использование jQuery без необходимости
- Дублирование запросов (карта + список)
- Создавайть тестовые файлы

##  Стуркутра проекта

realty-theme/
├── style.css
├── index.php
├── functions.php
├── header.php
├── footer.php
├── sidebar.php
├── 404.php
├── single.php
├── page.php
├── page-home.php
├── archive-property.php
├── README.md
├── screenshot.png
├── package.json
├── package-lock.json
├── webpack.config.js
├── assets/
│   ├── css/
│   │   └── main.css.map
│   ├── font-awesome/
│   │   ├── css/
│   │   │   └── font-awesome.min.css
│   │   └── fonts/
│   ├── images/
│   │   ├── no_image.png
│   │   └── icons/
│   └── sass/
│       ├── _mixins.scss
│       ├── _vars.scss
│       ├── main.scss
│       └── component/
│           ├── _content.scss
│           ├── _form.scss
│           └── _layout.scss
├── inc/
│   ├── admin/
│   │   ├── admin-function.php
│   │   ├── admin-filter-function.php
│   │   └── pods-registration.php
│   └── frontend/
│       ├── filter-functions.php
│       └── template-function.php
├── js/
│   ├── ajax-pagination.js
│   ├── custom-meta-box.js
│   ├── html5.js
│   ├── jquery.fittext.js
│   ├── jquery.fitvids.js
│   ├── property-filter.js
│   ├── site.js
│   └── theme.min.js
├── template-parts/
│   ├── component/
│   └── content/
└── templates/
    └── template-main.php
