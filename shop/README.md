# MVC-магазин — практические работы 7 и 8

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

Неизвестный адрес также даёт 404. Router выбирает callable, Controller передаёт данные в View, View компонует шаблон и общий layout. Конструкторы получают реальные зависимости. PHP-классы находятся в `src`, шаблоны — в `views`, исходные данные — в `data`. Работа 8 продолжает тот же магазин; этап 7 сохранён тегом `pr07`.

## Работа 8

Тестовый массив заменён 24 товарами и четырьмя категориями из `data/pr08`. `ProductRepository` читает JSON и возвращает Product, который связан с Category объектной ссылкой. Дополнительное свойство stock — остаток. Цены хранятся в целых копейках, форматируются через `money()` перед отображением. В шаблонах нет чтения файлов и фильтрации.

Маршрут: `GET /catalog?category=1&sort=price` → Router → CatalogController → ProductRepository → Product[] → views/catalog.php → layout. Допустимы name, price, price_desc. Фильтр и сортировка сохраняются в URL. Неизвестная категория даёт пустое состояние. `GET /product/1` показывает карточку; неизвестный/нечисловой id возвращает 404.

Namespaces `Shop\Controller`, `Shop\Model`, `Shop\Repository` загружаются через `spl_autoload_register` из bootstrap. Ручных require классов в контроллерах нет. Product не знает HTTP, repository не знает HTML, а контроллер не открывает JSON.

Проверки из корня: `php shop/tests/catalog.php`. Все строки `catalog_cases.csv` реально выполняются: общий список, книги с сортировкой цены, неизвестная категория. Для ручной проверки откройте главную, каталог, категорию 1 и /product/99999.
