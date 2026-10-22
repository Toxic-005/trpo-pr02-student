// Каталог «Учебная лавка».
// Маршрут данных: data/catalog.csv -> fetch -> parseCatalog() -> state.products
//                 -> applyFilters() -> render() -> карточки в #catalog-grid.
'use strict';

const CATALOG_URL = 'data/catalog.csv';
const REQUIRED_COLUMNS = ['id', 'name', 'category', 'price', 'rating'];

// Категория из CSV -> модификатор badge и изображение из dataset/assets.
const CATEGORY_META = {
  'Книги': { slug: 'books', image: 'images/product-01.svg' },
  'Курсы': { slug: 'courses', image: 'images/product-02.svg' },
  'Инструменты': { slug: 'tools', image: 'images/product-03.svg' },
};
const FALLBACK_META = { slug: 'other', image: 'images/product-01.svg' };

const priceFormat = new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 });

const el = {
  form: document.getElementById('filters-form'),
  search: document.getElementById('filter-search'),
  categories: document.getElementById('filter-categories'),
  price: document.getElementById('filter-price'),
  priceValue: document.getElementById('filter-price-value'),
  rating: document.getElementById('filter-rating'),
  sort: document.getElementById('filter-sort'),
  grid: document.getElementById('catalog-grid'),
  count: document.getElementById('catalog-count'),
  empty: document.getElementById('state-empty'),
  emptyReset: document.getElementById('state-empty-reset'),
  error: document.getElementById('state-error'),
  errorText: document.getElementById('state-error-text'),
  stats: document.getElementById('info-stats'),
  template: document.getElementById('product-card-template'),
};

const state = { products: [], skipped: 0 };

/**
 * Разбирает CSV с разделителем «;».
 * Строки с пустыми или нечисловыми значениями пропускаются и учитываются в state.skipped.
 */
function parseCatalog(text) {
  const lines = text.replace(/^﻿/, '').trim().split(/\r?\n/).filter(Boolean);
  if (lines.length < 2) throw new Error('В catalog.csv нет строк с товарами.');

  const header = lines.shift().split(';').map((h) => h.trim());
  const missing = REQUIRED_COLUMNS.filter((col) => !header.includes(col));
  if (missing.length) throw new Error(`В catalog.csv нет колонок: ${missing.join(', ')}.`);

  const products = [];
  let skipped = 0;
  for (const line of lines) {
    const cells = line.split(';');
    const row = Object.fromEntries(header.map((key, i) => [key, (cells[i] ?? '').trim()]));
    const product = {
      id: Number(row.id),
      name: row.name,
      category: row.category,
      price: Number(row.price),
      rating: Number(row.rating),
    };
    const valid = Number.isInteger(product.id) && product.name && product.category
      && Number.isFinite(product.price) && product.rating >= 0 && product.rating <= 5;
    if (valid) products.push(product); else skipped += 1;
  }
  return { products, skipped };
}

function createCard(product) {
  const card = el.template.content.firstElementChild.cloneNode(true);
  const meta = CATEGORY_META[product.category] ?? FALLBACK_META;

  card.dataset.id = product.id;
  const image = card.querySelector('.product-card__image');
  image.src = meta.image;
  image.alt = `Иллюстрация категории «${product.category}»`;

  const badge = card.querySelector('.badge');
  badge.textContent = product.category;
  badge.classList.add(`badge--${meta.slug}`);

  card.querySelector('.product-card__title').textContent = product.name;
  card.querySelector('.rating').style.setProperty('--rating', product.rating);
  card.querySelector('.product-card__rating-value').textContent = `${product.rating.toFixed(1)} / 5`;
  card.querySelector('.product-card__rating').title = `Рейтинг ${product.rating.toFixed(1)} из 5`;
  card.querySelector('.product-card__price').textContent = priceFormat.format(product.price);
  return card;
}

/** Строит чекбоксы категорий и границы ползунка цены по данным CSV. */
function buildFilters(products) {
  const categories = [...new Set(products.map((p) => p.category))];
  el.categories.replaceChildren(...categories.map((category) => {
    const label = document.createElement('label');
    label.className = 'filters__option';
    const input = document.createElement('input');
    input.type = 'checkbox';
    input.name = 'category';
    input.value = category;
    input.checked = true;
    input.defaultChecked = true; // чтобы form.reset() возвращал отметку
    label.append(input, ` ${category}`);
    return label;
  }));

  const prices = products.map((p) => p.price);
  el.price.min = Math.min(...prices);
  el.price.max = Math.max(...prices);
  el.price.value = el.price.max;
  el.price.defaultValue = el.price.max;
  updatePriceLabel();
}

function updatePriceLabel() {
  el.priceValue.textContent = priceFormat.format(Number(el.price.value));
}

function readFilters() {
  const data = new FormData(el.form);
  return {
    search: String(data.get('search') || '').trim().toLowerCase(),
    categories: new Set(data.getAll('category')),
    maxPrice: Number(data.get('maxPrice')),
    minRating: Number(data.get('minRating')),
    sort: data.get('sort'),
  };
}

const SORTERS = {
  id: (a, b) => a.id - b.id,
  'price-asc': (a, b) => a.price - b.price,
  'price-desc': (a, b) => b.price - a.price,
  'rating-desc': (a, b) => b.rating - a.rating || a.price - b.price,
};

function applyFilters(products, f) {
  return products
    .filter((p) => f.categories.has(p.category))
    .filter((p) => p.price <= f.maxPrice)
    .filter((p) => p.rating >= f.minRating)
    .filter((p) => !f.search || p.name.toLowerCase().includes(f.search))
    .sort(SORTERS[f.sort] ?? SORTERS.id);
}

function render() {
  const visible = applyFilters(state.products, readFilters());
  el.grid.replaceChildren(...visible.map(createCard));
  el.empty.hidden = visible.length > 0;
  el.count.textContent = `Показано ${visible.length} из ${state.products.length}`;
}

function renderStats(products) {
  const avg = (list, key) => list.reduce((sum, p) => sum + p[key], 0) / list.length;
  const byCategory = Object.groupBy
    ? Object.groupBy(products, (p) => p.category)
    : products.reduce((acc, p) => ((acc[p.category] ??= []).push(p), acc), {});

  const stats = [
    ['Всего товаров', products.length],
    ...Object.entries(byCategory).map(([name, list]) => [name, list.length]),
    ['Цена', `${priceFormat.format(Math.min(...products.map((p) => p.price)))} – ${priceFormat.format(Math.max(...products.map((p) => p.price)))}`],
    ['Средний рейтинг', avg(products, 'rating').toFixed(2)],
  ];
  if (state.skipped) stats.push(['Пропущено строк CSV', state.skipped]);

  el.stats.replaceChildren(...stats.map(([term, value]) => {
    const item = document.createElement('div');
    item.className = 'info__stat';
    const dt = document.createElement('dt');
    dt.textContent = term;
    const dd = document.createElement('dd');
    dd.textContent = value;
    item.append(dt, dd);
    return item;
  }));
}

function showError(message) {
  el.error.hidden = false;
  el.errorText.textContent = message;
  el.count.textContent = 'Данные недоступны';
  el.form.querySelectorAll('input, select, button').forEach((control) => { control.disabled = true; });
}

async function init() {
  try {
    const response = await fetch(CATALOG_URL);
    if (!response.ok) throw new Error(`Сервер вернул ${response.status} для ${CATALOG_URL}.`);
    const { products, skipped } = parseCatalog(await response.text());
    if (!products.length) throw new Error('В catalog.csv нет корректных строк.');

    state.products = products;
    state.skipped = skipped;
    buildFilters(products);
    renderStats(products);
    render();
  } catch (error) {
    // fetch() не работает при открытии index.html двойным щелчком (file://).
    const hint = location.protocol === 'file:'
      ? ' Страница открыта как файл — запустите локальный сервер (см. README).'
      : '';
    const message = error instanceof TypeError ? `Браузер не смог загрузить ${CATALOG_URL}.` : error.message;
    showError(`${message}${hint}`);
  }
}

el.form.addEventListener('submit', (event) => event.preventDefault());
el.form.addEventListener('input', () => { updatePriceLabel(); render(); });
el.form.addEventListener('reset', () => {
  // reset срабатывает до сброса значений — перерисовываем на следующем тике.
  setTimeout(() => { updatePriceLabel(); render(); });
});
el.emptyReset.addEventListener('click', () => el.form.reset());

init();
