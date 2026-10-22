// Каталог «Учебная лавка»: загрузка data/catalog.csv и вывод карточек.
'use strict';

const CATALOG_URL = 'data/catalog.csv';

// Категория из CSV -> модификатор badge и изображение из dataset/assets.
const CATEGORY_META = {
  'Книги': { slug: 'books', image: 'images/product-01.svg' },
  'Курсы': { slug: 'courses', image: 'images/product-02.svg' },
  'Инструменты': { slug: 'tools', image: 'images/product-03.svg' },
};

const priceFormat = new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 });

/** Разбирает CSV с разделителем «;» в массив объектов товаров. */
function parseCatalog(text) {
  const lines = text.replace(/^﻿/, '').trim().split(/\r?\n/);
  const header = lines.shift().split(';').map((h) => h.trim());
  return lines.map((line) => {
    const cells = line.split(';');
    const row = Object.fromEntries(header.map((key, i) => [key, (cells[i] || '').trim()]));
    return {
      id: Number(row.id),
      name: row.name,
      category: row.category,
      price: Number(row.price),
      rating: Number(row.rating),
    };
  });
}

function createCard(product) {
  const template = document.getElementById('product-card-template');
  const card = template.content.firstElementChild.cloneNode(true);
  const meta = CATEGORY_META[product.category];

  card.dataset.id = product.id;
  card.querySelector('.product-card__image').src = meta.image;
  const badge = card.querySelector('.badge');
  badge.textContent = product.category;
  badge.classList.add(`badge--${meta.slug}`);
  card.querySelector('.product-card__title').textContent = product.name;
  card.querySelector('.product-card__rating-value').textContent = product.rating.toFixed(1);
  card.querySelector('.product-card__price').textContent = priceFormat.format(product.price);
  return card;
}

async function init() {
  const grid = document.getElementById('catalog-grid');
  const count = document.getElementById('catalog-count');
  const response = await fetch(CATALOG_URL);
  const products = parseCatalog(await response.text());
  grid.replaceChildren(...products.map(createCard));
  count.textContent = `Товаров: ${products.length}`;
}

init();
