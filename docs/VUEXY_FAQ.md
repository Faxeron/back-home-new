# Vuexy FAQ (кратко для проекта)

Источник: https://demos.pixinvent.com/vuexy-html-admin-template/documentation/faq.html

## Лого/брендинг
- Лого можно заменить на SVG или PNG/JPG.
- Бренд‑текст можно менять (цвет, размер, отступы и т.д.).
- В HTML это блок `.app-brand` с логотипом и текстом бренда.

## Добавление пункта меню
- Добавить `<li class="menu-item">` с `data-i18n` и ссылкой.
- Обновить i18n JSON в `/assets/json/locales/*`.
- Если нужен поиск — добавить пункт в `search-vertical.json` или `search-horizontal.json`.

## Кастомный шрифт
- Рекомендуется через SCSS: правки в `scss/_custom-variables/_bootstrap-extended.scss`.
- Альтернатива (не рекомендовано): заменить `--bs-font-sans-serif` в `assets/vendors/css/core.css`.

## Интеграция в существующий проект
- Vuexy — это стартовый шаблон, его не подключают как библиотеку.
- Рекомендация — стартовать проект на Vuexy или переносить свой проект поверх него.

## Точка сворачивания меню (breakpoint)
- Меняется через SCSS переменную `scss/_custom-variables/_components.scss`.
- JS‑брейкпоинт синхронизируется в `js/helpers.js` (LAYOUT_BREAKPOINT).
- После смены брейкпоинта надо поправить классы navbar/menu.

## Убрать Auto из переключателя темы
- Удалить пункт из HTML.
- Удалить `auto` из `TemplateCustomizer.THEMES` в `template-customizer.js`.

## Поиск по страницам
- Редактировать `search-vertical.json` / `search-horizontal.json`.
- Важно: `data-i18n` и ключи в JSON должны совпадать.

## Разные изображения для light/dark
- Использовать `data-app-light-img` / `data-app-dark-img`.
- Переключение происходит через `switchImage` в `main.js`.

## Ошибки при открытии HTML локально
- Нельзя открывать `index.html` напрямую из файла.
- Нужен локальный сервер (XAMPP/MAMP/WAMP) или `npm/yarn serve`.

## Убрать Ripple/Waves
- Удалить `node-waves` из зависимостей.
- Удалить CSS/JS waves из HTML.
- Удалить инициализацию в `main.js`/`front-main.js`.

## Ошибки зависимостей npm
- Для npm 7+ помогает `npm i --legacy-peer-deps`.
- Альтернатива: использовать Yarn.

## Обновление Vuexy
- Варианты: форк репозитория или ручной merge.
- Перед обновлением сделать бэкап.
- После обновления: `npm/yarn install`, `npm run build`, проверить вручную.

## Интеграция с backend‑фреймворками
- Официальных туториалов нет, интеграция зависит от проекта.

## Карты (Mapbox)
- Нужен access token.
- Токен вставляется в `app-logistics-fleet.js`.
