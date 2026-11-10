<section class="heading"><span class="eyebrow">Учебная коллекция</span><h1>Каталог</h1><p>Тестовые товары каркаса MVC. Данные JSON подключаются на следующем этапе.</p></section><section class="cards">
<?php foreach ($products as $product): ?><article class="panel"><h2><?= e($product['name']) ?></h2><p class="price"><?= money($product['price']) ?></p></article><?php endforeach ?></section>
