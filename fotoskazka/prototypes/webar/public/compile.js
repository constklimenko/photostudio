/* WebAR prototype — D1.4: компиляция .mind в браузере (прототип UX для Filament
   и замер времени). Компилятор MindAR берётся из того же вендорного чанка,
   что и страница AR. */

import { a as Compiler } from './vendor/controller-mGt1s8dJ.js';

const $ = (id) => document.getElementById(id);

const fileInput = $('file');
const maxSideSelect = $('max-side');
const btnCompile = $('btn-compile');
const download = $('download');
const progress = $('progress');
const preview = $('preview');
const statusEl = $('status');
const result = $('result');

let sourceFile = null;

function setStatus(text, kind) {
  statusEl.textContent = text;
  statusEl.className = 'status' + (kind ? ' ' + kind : '');
}

function loadImage(file) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => resolve(img);
    img.onerror = () => reject(new Error('не удалось прочитать изображение'));
    img.src = URL.createObjectURL(file);
  });
}

/* Уменьшение до заданной длинной стороны: так же, как перед офлайн-компиляцией
   (5668 px → 1600 px). Компилятор MindAR сам строит пирамиду масштабов. */
function downscale(img, maxSide) {
  const scale = maxSide > 0 ? Math.min(1, maxSide / Math.max(img.naturalWidth, img.naturalHeight)) : 1;
  const canvas = document.createElement('canvas');
  canvas.width = Math.round(img.naturalWidth * scale);
  canvas.height = Math.round(img.naturalHeight * scale);
  canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
  return canvas;
}

function gpuName() {
  const gl = document.createElement('canvas').getContext('webgl');
  if (!gl) return 'WebGL недоступен';
  const ext = gl.getExtension('WEBGL_debug_renderer_info');
  return ext ? gl.getParameter(ext.UNMASKED_RENDERER_WEBGL) : gl.getParameter(gl.RENDERER);
}

function showResult(rows) {
  const tbody = result.querySelector('tbody');
  tbody.replaceChildren(...rows.map(([k, v]) => {
    const tr = document.createElement('tr');
    tr.innerHTML = '<td></td><td></td>';
    tr.children[0].textContent = k;
    tr.children[1].textContent = v;
    return tr;
  }));
  result.hidden = false;
}

fileInput.addEventListener('change', () => {
  sourceFile = fileInput.files[0] || null;
  btnCompile.disabled = !sourceFile;
  download.hidden = true;
  result.hidden = true;
  progress.value = 0;
  if (sourceFile) {
    preview.src = URL.createObjectURL(sourceFile);
    preview.hidden = false;
    setStatus('Готово к компиляции: ' + sourceFile.name);
  }
});

btnCompile.addEventListener('click', async () => {
  if (!sourceFile) return;
  btnCompile.disabled = true;
  fileInput.disabled = true;
  download.hidden = true;
  progress.value = 0;
  setStatus('Компиляция… не закрывайте страницу.');

  try {
    const t0 = performance.now();
    const img = await loadImage(sourceFile);
    const canvas = downscale(img, Number(maxSideSelect.value));
    const tPrepared = performance.now();

    const compiler = new Compiler();
    await compiler.compileImageTargets([canvas], (p) => {
      progress.value = p;
    });
    const tCompiled = performance.now();

    const buffer = compiler.exportData();
    const blob = new Blob([buffer], { type: 'application/octet-stream' });
    download.href = URL.createObjectURL(blob);
    download.download = sourceFile.name.replace(/\.[^.]+$/, '') + '.mind';
    download.hidden = false;
    progress.value = 100;

    const data = compiler.data[0];
    const full = data.matchingData[0];
    const totalSec = ((tCompiled - t0) / 1000).toFixed(1);
    showResult([
      ['Итого', totalSec + ' с'],
      ['Подготовка изображения', Math.round(tPrepared - t0) + ' мс'],
      ['Компиляция', ((tCompiled - tPrepared) / 1000).toFixed(1) + ' с'],
      ['Исходник', img.naturalWidth + '×' + img.naturalHeight],
      ['Таргет', canvas.width + '×' + canvas.height],
      ['Размер .mind', Math.round(buffer.byteLength / 1024) + ' КБ'],
      ['Опорные точки (полный масштаб)', full.maximaPoints.length + ' max / ' + full.minimaPoints.length + ' min'],
      ['Точки трекинга', data.trackingData.map((t) => t.points.length).join(' + ')],
      ['Видеокарта', gpuName()],
      ['Потоков CPU', String(navigator.hardwareConcurrency || '—')],
      ['Браузер', navigator.userAgent],
    ]);
    window.__compileResult = { totalSec: Number(totalSec), bytes: buffer.byteLength };
    setStatus('Готово за ' + totalSec + ' с. Перепишите таблицу в бланк замера.', 'ok');
  } catch (err) {
    setStatus('Ошибка компиляции: ' + (err && err.message ? err.message : String(err)), 'err');
    window.__compileResult = { error: String(err) };
  } finally {
    btnCompile.disabled = false;
    fileInput.disabled = false;
  }
});

setStatus('Выберите фото (JPEG/PNG). Компиляция идёт локально, файл никуда не отправляется. Видеокарта: ' + gpuName());
