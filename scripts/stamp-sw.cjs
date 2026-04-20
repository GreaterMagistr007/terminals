/**
 * Копирует resources/sw.template.js в public/sw.js, подставляя текущий BUILD_ID.
 * Используется в npm-скриптах build и dev.
 *
 * Зачем: Service Worker перерегистрируется только при изменении байтов
 * /sw.js. Зашитый в шаблоне BUILD_ID = timestamp гарантирует, что каждая
 * сборка даст новый файл → браузер подхватит новую версию и очистит кеш.
 */
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const source = path.join(root, 'resources', 'sw.template.js');
const target = path.join(root, 'public', 'sw.js');

const buildId = String(Date.now());
const template = fs.readFileSync(source, 'utf8');
const output = template.replace(/__BUILD_ID__/g, buildId);

fs.writeFileSync(target, output);
console.log(`[stamp-sw] BUILD_ID=${buildId} -> ${path.relative(root, target)}`);
