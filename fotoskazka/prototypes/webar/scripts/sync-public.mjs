import { cpSync, existsSync, mkdirSync, rmSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptDir = path.dirname(fileURLToPath(import.meta.url));
const webarDir = path.resolve(scriptDir, '..');
const repoRoot = path.resolve(scriptDir, '../../..');

const SRC_PUBLIC = path.join(webarDir, 'public');
const SRC_TARGETS = path.join(webarDir, 'targets');
const DEST = path.join(repoRoot, 'public', 'webar');

if (!existsSync(SRC_PUBLIC) || !existsSync(SRC_TARGETS)) {
  console.error('Не найдены исходники:', SRC_PUBLIC, SRC_TARGETS);
  process.exit(1);
}

rmSync(DEST, { recursive: true, force: true });
mkdirSync(DEST, { recursive: true });

cpSync(SRC_PUBLIC, DEST, { recursive: true });
cpSync(SRC_TARGETS, path.join(DEST, 'targets'), { recursive: true });

console.log('public/webar -> сгенерировано из prototypes/webar/');
console.log('  откуда:', SRC_PUBLIC, ' + ', SRC_TARGETS);
console.log('  куда:  ', DEST);
console.log();
console.log('Сайт должен открывать: https://<site>/webar/  (camera.html по адресу /webar/camera.html)');
console.log('ВАЖНО: камера (getUserMedia) работает только в защищённом контексте — код должен отдаваться по HTTPS.');
console.log('После изменения prototypes/webar/public или targets/ повторно запускаете: npm run sync:public');