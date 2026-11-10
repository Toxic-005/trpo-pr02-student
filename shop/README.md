# MVC-магазин — практическая работа 7

Единая точка входа, Router, контроллеры и отдельные представления без фреймворка. Основой главной страницы служит `data/pr07/pages.json`, логотип скопирован из dataset.

Из корня репозитория, PHP 8.1+:

```bash
php -S localhost:8007 -t shop/public shop/public/router.php
```

Откройте http://localhost:8007. Для остановки нажмите Ctrl+C. `router.php` — адаптер встроенного сервера; динамические запросы проходят через `index.php`. Он не содержит разметки. Только styles.css и shop-logo.svg выдаются как статические файлы.

| Маршрут из routes.csv | Контроллер | View | Статус |
| --- | --- | --- | --- |
| GET / | HomeController::index | home | 200 |
| GET /catalog | CatalogController::index | catalog | 200 |
| GET /about | HomeController::about | about | 200 |
| GET /missing | ErrorController::notFound | error | 404 |

Неизвестный адрес также даёт 404. Router выбирает callable, Controller передаёт данные в View, View компонует шаблон и общий layout. Конструкторы получают реальные зависимости. Каталог пока содержит два тестовых товара; это намеренный промежуточный этап. PHP-классы находятся в `src`, шаблоны — в `views`, исходные данные — в `data`. На следующем этапе тестовый массив заменяется ProductRepository.
