# realty-theme

WordPress тема для сайта недвижимости с поддержкой webpack и SCSS.

## Используемые технологии

### Backend/CMS
- **WordPress** — CMS система управления контентом
- **Pods** — плагин для создания кастомных типов записей (CPT) и таксономий

### Frontend инструменты
- **Webpack** — сборщик модулей для JS и SCSS
- **SCSS/SASS** — препроцессор CSS
- **Babel** — транспиляция JavaScript (ES6+)
- **PostCSS + Autoprefixer** — трансформация CSS
- **CSSnano** — минификация CSS

### JavaScript фреймворки и библиотеки
- **Vue.js 3** — прогрессивный JavaScript фреймворк
- **Vuex** — библиотека для управления состоянием
- **Element Plus** — UI-компоненты для Vue 3
- **jQuery** — для совместимости
- **Moment.js** — работа с датами
- **DateRangePicker** — виджет выбора диапазона дат

### Сторонние сервисы
- **Google Maps API** — интерактивные карты
- **Font Awesome** — иконографическая библиотека
- **Splide** — слайдер для галерей

## Структура проекта

```
realty-theme/
├── assets/
│   ├── sass/           # SCSS файлы стилей
│   ├── vue_module/     # Vue.js компоненты
│   ├── images/         # Изображения и иконки
│   └── font-awesome/   # Иконки Font Awesome
├── js/                 # JavaScript файлы
├── inc/
│   ├── admin/          # Админ-функции, регистрация Pods, настройки
│   └── frontend/       # Функции для фронтенда, фильтры
├── template-parts/     # Переиспользуемые части шаблонов
├── webpack.config.js   # Конфигурация Webpack для стилей
└── webpack.vue.config.js # Конфигурация Webpack для Vue
```

## Возможности

- Кастомный тип записи «property» для объектов недвижимости
- Фильтрация объектов по параметрам (ajax)
- Интерактивные карты Google Maps
- Галерея изображений объектов
- AJAX пагинация
- Адаптивный дизайн
- Поддержка русского языка

## Установка зависимостей

```bash
npm install
```

## Скрипты

- `npm run sass` — компиляция SCSS с отслеживанием изменений
- `npm run build:css` — production сборка CSS
- `npm run watch:css` — dev сборка CSS с отслеживанием
- `npm run vue-build` — production сборка Vue компонентов
- `npm run vue-watch` — dev сборка Vue с отслеживанием
