/* WebAR prototype — D1.2 → первый шаг к D1.3 (MindAR image tracking, target replacement) */

import * as THREE from 'three';
import { MindARThree } from './vendor/mindar-image-three.prod.js';

window.__webarBooted = true;

const TARGET_SRC = './targets/targets.mind';
const TARGET_IMAGE_ASPECT = 1248 / 1648; // sample-photo.jpg (kept in sync by the compile tool)

/* Изображение, которым заменяется распознанный таргет. Замените файл (или эту
   строку) своим контентом. Формат кадра должен совпадать с таргетом (1648×1248). */
const REPLACEMENT_SRC = './targets/replacement.svg';

const $ = (id) => document.getElementById(id);

const btnStart = $('btn-start');
const btnStop = $('btn-stop');
const btnGrid = $('btn-grid');
const cameraState = $('camera-state');
const targetState = $('target-state');
const holdEl = $('hold');
const statusEl = $('status');
const logEl = $('log');

let trackTimer = null;
let trackStart = null;
let targetLog = [];
let gridVisible = false;

window.addEventListener('error', (e) => log('window error: ' + e.message));

/* --- HUD helpers --- */

function setStatus(text, kind) {
  statusEl.textContent = text;
  statusEl.className = 'status' + (kind ? ' ' + kind : '');
}

function log(text) {
  const time = new Date().toLocaleTimeString('ru-RU');
  const li = document.createElement('li');
  li.textContent = time + ' — ' + text;
  logEl.prepend(li);
  while (logEl.children.length > 40) logEl.lastChild.remove();
}

function setTarget(kind, text) {
  targetState.textContent = text;
  targetState.className = 'pill target ' + kind;
}

function setCamera(kind, text) {
  cameraState.textContent = text;
  cameraState.className = 'pill ' + kind;
}

/* --- Canvas textures for the stub overlay --- */

function makeCanvasTexture(size, draw) {
  const c = document.createElement('canvas');
  c.width = size;
  c.height = size;
  draw(c.getContext('2d'));
  return new THREE.CanvasTexture(c);
}

function makeGridTexture(size, cells) {
  return makeCanvasTexture(size, (ctx) => {
    ctx.clearRect(0, 0, size, size);
    ctx.strokeStyle = 'rgba(255,255,255,0.85)';
    ctx.lineWidth = 2;
    for (let i = 1; i < cells; i++) {
      ctx.beginPath();
      ctx.moveTo((size / cells) * i, 0);
      ctx.lineTo((size / cells) * i, size);
      ctx.stroke();
      ctx.beginPath();
      ctx.moveTo(0, (size / cells) * i);
      ctx.lineTo(size, (size / cells) * i);
      ctx.stroke();
    }
  });
}

/* Изображение-замена: непрозрачная текстура ровно по таргету. */
function makeReplacementMaterial() {
  const texture = new THREE.TextureLoader().load(REPLACEMENT_SRC);
  texture.encoding = THREE.sRGBEncoding;
  texture.minFilter = THREE.LinearFilter;
  return new THREE.MeshBasicMaterial({
    map: texture,
    transparent: false,
    depthWrite: false,
    toneMapped: false,
  });
}

/* --- MindAR scene --- */

let mindarThree = null;
let overlayMesh = null;
let gridMesh = null;

function buildScene() {
  mindarThree = new MindARThree({
    container: $('ar-container'),
    imageTargetSrc: TARGET_SRC,
    filterTargetFound: true,
    filterMinCF: 0.1,
    filterBeta: 1000,
    missTolerance: 20,
  });

  const { renderer, scene, camera } = mindarThree;

  const anchor = mindarThree.addAnchor(0);

  const REPLACE_OVERSCAN = 1.02;
  overlayMesh = new THREE.Mesh(
    new THREE.PlaneGeometry(REPLACE_OVERSCAN, REPLACE_OVERSCAN * TARGET_IMAGE_ASPECT),
    makeReplacementMaterial()
  );
  anchor.group.add(overlayMesh);

  const gridMat = new THREE.MeshBasicMaterial({
    map: makeGridTexture(1024, 8),
    transparent: true,
    depthWrite: false,
    opacity: 0.7,
  });
  gridMesh = new THREE.Mesh(new THREE.PlaneGeometry(1, TARGET_IMAGE_ASPECT), gridMat);
  gridMesh.visible = false;
  anchor.group.add(gridMesh);

  anchor.onTargetFound = () => {
    setTarget('found', 'target: found ✓');
    trackStart = performance.now();
    trackTimer = setInterval(() => {
      const s = ((performance.now() - trackStart) / 1000).toFixed(1);
      holdEl.textContent = 'удержание: ' + s + ' с';
    }, 200);
    log('targetFound');
  };

  anchor.onTargetLost = () => {
    setTarget('lost', 'target: lost');
    if (trackStart) {
      const held = ((performance.now() - trackStart) / 1000).toFixed(1);
      log('targetLost — удержание ' + held + ' с');
      appendMeasurement(held);
    }
    clearInterval(trackTimer);
    trackTimer = null;
    holdEl.textContent = 'удержание: —';
  };

  renderer.setAnimationLoop(() => {
    renderer.render(scene, camera);
  });

  return mindarThree;
}

function appendMeasurement(heldSeconds) {
  const h = $('measurements');
  if (!h) return;
  const span = document.createElement('span');
  span.textContent = heldSeconds + ' с · ';
  h.appendChild(span);
}

/* --- Start / stop --- */

btnStart.addEventListener('click', async () => {
  btnStart.disabled = true;
  setStatus('Запрашиваю камеру…');
  try {
    if (!mindarThree) mindarThree = buildScene();
    await mindarThree.start();
    setCamera('on', 'camera: on');
    setStatus('Кадр с камеры — как есть. Наведите на образец: область таргета заменится изображением-контентом.');
    btnStop.disabled = false;
    btnGrid.disabled = false;
  } catch (err) {
    setCamera('err', 'camera: error');
    setStatus(cameraErrorText(err), 'err');
    log(err.name + ': ' + err.message);
    btnStart.disabled = false;
  }
});

btnStop.addEventListener('click', async () => {
  if (!mindarThree) return;
  await mindarThree.stop();
  setCamera('off', 'camera: off');
  setTarget('idle', 'target: —');
  setStatus('Остановлено.');
  btnStop.disabled = true;
  btnGrid.disabled = true;
  btnStart.disabled = false;
});

btnGrid.addEventListener('click', () => {
  if (!gridMesh) return;
  gridVisible = !gridVisible;
  gridMesh.visible = gridVisible;
  btnGrid.classList.toggle('active', gridVisible);
  setStatus(gridVisible ? 'Сетка совмещения включена.' : 'Сетка совмещения выключена.');
});

function cameraErrorText(err) {
  if (err.name === 'SecurityError' || err.name === 'NotAllowedError') {
    return 'Доступ к камере запрещён. Разрешите камеру для этого сайта и повторите (HTTPS обязателен).';
  }
  if (err.name === 'NotFoundError' || err.name === 'OverconstrainedError') {
    return 'Камера не найдена (десктоп?). Нужен смартфон с камерой.';
  }
  if (err.name === 'NotReadableError') {
    return 'Камера занята другим приложением. Закройте его и повторите.';
  }
  return err.message || String(err);
}

log('Loaded (three.module + MindAR three build).');
setStatus('Нажмите «Старт»: появится кадр с камеры, а распознанная область таргета заменится контентом.');