// Читает data/theme.json и создаёт scss/abstracts/_theme.scss.
// Так значения темы хранятся в одном месте (theme.json) и не дублируются по SCSS-файлам.
import { readFile, writeFile } from 'node:fs/promises';

const SOURCE = new URL('../data/theme.json', import.meta.url);
const TARGET = new URL('../scss/abstracts/_theme.scss', import.meta.url);

// Числовые поля theme.json заданы в пикселях.
const PX_FIELDS = new Set(['radius', 'space']);
const COLOR = /^#[0-9a-f]{3,8}$/i;

const theme = JSON.parse(await readFile(SOURCE, 'utf8'));

const lines = Object.entries(theme).map(([key, value]) => {
  if (PX_FIELDS.has(key) && typeof value === 'number') return `$theme-${key}: ${value}px;`;
  if (typeof value === 'string' && COLOR.test(value)) return `$theme-${key}: ${value};`;
  throw new Error(`theme.json: неожиданное значение поля "${key}": ${JSON.stringify(value)}`);
});

const output = [
  '// Файл создан автоматически: npm run theme (scripts/build-theme.mjs).',
  '// Источник — data/theme.json. Не редактируйте вручную.',
  ...lines,
  '',
].join('\n');

await writeFile(TARGET, output);
console.log(`theme: ${lines.length} переменных -> scss/abstracts/_theme.scss`);
