/* WebAR prototype — D1.3 (MindAR image tracking, видео поверх таргета) */

import * as THREE from 'three';
import { MindARThree } from './vendor/mindar-image-three.prod.js';

window.__webarBooted = true;
const scriptsReadyAt = performance.now();

/* Таргеты выбираются параметром ?t=… Пропорции (высота / ширина) должны
   совпадать с изображением, из которого собран .mind, и с кадром видео.
   Картинка-замена — запасной вариант, если видео не загрузилось. */
const TARGETS = {
  sample: {
    label: 'образец FS-001',
    mind: './targets/targets.mind',
    aspect: 1248 / 1648,
    video: './targets/demo-video.mp4',
  },
  class: {
    label: 'фото класса',
    mind: './targets/class.mind',
    aspect: 1064 / 1600,
    video: './targets/class-video.mp4',
  },
};
const requestedTarget = new URLSearchParams(location.search).get('t');
const TARGET_KEY = Object.hasOwn(TARGETS, requestedTarget) ? requestedTarget : 'sample';
const TARGET = TARGETS[TARGET_KEY];

const TARGET_SRC = TARGET.mind;
const TARGET_IMAGE_ASPECT = TARGET.aspect;
const VIDEO_SRC = TARGET.video;
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

function makeContentMaterial(texture) {
  texture.encoding = THREE.sRGBEncoding;
  texture.minFilter = THREE.LinearFilter;
  return new THREE.MeshBasicMaterial({
    map: texture,
    transparent: false,
    depthWrite: false,
    toneMapped: false,
  });
}

/* iOS разрешает inline-автовоспроизведение только muted + playsinline. */
function createVideo() {
  const v = document.createElement('video');
  v.src = VIDEO_SRC;
  v.muted = true;
  v.loop = true;
  v.playsInline = true;
  v.setAttribute('playsinline', '');
  v.setAttribute('webkit-playsinline', '');
  v.preload = 'auto';
  v.addEventListener('playing', () => {
    if (foundAt === null) return;
    log('видео запущено через ' + Math.round(performance.now() - foundAt) + ' мс после targetFound');
    foundAt = null;
  });
  v.addEventListener('error', () => {
    log('ошибка видео — показываю картинку-замену');
    if (overlayMesh) overlayMesh.material = makeContentMaterial(new THREE.TextureLoader().load(REPLACEMENT_SRC));
  });
  return v;
}

/* Видео разблокируется жестом пользователя (кнопка «Старт»), чтобы потом
   запускаться без касания при распознавании таргета. */
function unlockVideo() {
  const p = video.play();
  if (p) p.then(() => { if (!targetVisible) video.pause(); }).catch((e) => log('video unlock: ' + e.name));
}

const video = createVideo();
let targetVisible = false;
let foundAt = null;

/* --- MindAR scene --- */

let mindarThree = null;
let overlayMesh = null;
let gridMesh = null;

/* Замер этапов запуска: «Старт» → камера → .mind → прогрев WebGL →
   сканирование → первое распознавание. Методы MindAR оборачиваются снаружи,
   вендорный код не меняется. */
let stageStart = null;
let stageLast = null;

function markStage(label) {
  if (stageStart === null) return;
  const now = performance.now();
  log(label + ': ' + Math.round(now - stageLast) + ' мс (с «Старт» ' + ((now - stageStart) / 1000).toFixed(1) + ' с)');
  stageLast = now;
}

function mindDownloadInfo() {
  const url = new URL(TARGET_SRC, location.href).href;
  const entry = performance.getEntriesByName(url).pop();
  if (!entry) return '';
  const kb = entry.encodedBodySize ? Math.round(entry.encodedBodySize / 1024) + ' КБ' : 'из кэша';
  return ' [скачивание ' + Math.round(entry.duration) + ' мс, ' + kb + ']';
}

function instrumentStart(m) {
  const startVideo = m._startVideo.bind(m);
  m._startVideo = async () => {
    await startVideo();
    markStage('камера готова');
  };
  const startAR = m._startAR.bind(m);
  m._startAR = () => {
    const done = startAR();
    const controller = m.controller;
    const dummyRun = controller.dummyRun.bind(controller);
    controller.dummyRun = async (v) => {
      markStage('.mind загружен и разобран' + mindDownloadInfo());
      await dummyRun(v);
      markStage('прогрев WebGL');
    };
    return done;
  };
}

function buildScene() {
  mindarThree = new MindARThree({
    container: $('ar-container'),
    imageTargetSrc: TARGET_SRC,
    filterTargetFound: true,
    filterMinCF: 0.1,
    filterBeta: 1000,
    missTolerance: 20,
  });
  instrumentStart(mindarThree);

  const { renderer, scene, camera } = mindarThree;

  const anchor = mindarThree.addAnchor(0);

  const REPLACE_OVERSCAN = 1.02;
  overlayMesh = new THREE.Mesh(
    new THREE.PlaneGeometry(REPLACE_OVERSCAN, REPLACE_OVERSCAN * TARGET_IMAGE_ASPECT),
    makeContentMaterial(new THREE.VideoTexture(video))
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
    targetVisible = true;
    trackStart = performance.now();
    foundAt = video.paused ? trackStart : null;
    video.play().catch((e) => log('video play: ' + e.name + ': ' + e.message));
    markStage('распознано');
    stageStart = null;
    trackTimer = setInterval(() => {
      const s = ((performance.now() - trackStart) / 1000).toFixed(1);
      holdEl.textContent = 'удержание: ' + s + ' с';
    }, 200);
    log('targetFound');
  };

  anchor.onTargetLost = () => {
    setTarget('lost', 'target: lost');
    targetVisible = false;
    foundAt = null;
    video.pause();
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
  if (!window.isSecureContext || !navigator.mediaDevices) {
    setCamera('err', 'camera: error');
    setStatus('Камера доступна только по HTTPS или с адреса localhost / 127.0.0.1. Сейчас: ' + location.origin, 'err');
    log('insecure context: ' + location.origin);
    return;
  }
  btnStart.disabled = true;
  unlockVideo();
  setStatus('Запрашиваю камеру…');
  stageStart = stageLast = performance.now();
  log('Старт');
  try {
    if (!mindarThree) mindarThree = buildScene();
    await mindarThree.start();
    markStage('сканирование запущено');
    setCamera('on', 'camera: on');
    setStatus('Наведите камеру на напечатанное фото — поверх него запустится видео.');
    btnStop.disabled = false;
    btnGrid.disabled = false;
  } catch (err) {
    setCamera('err', 'camera: error');
    setStatus(cameraErrorText(err), 'err');
    log(err ? err.name + ': ' + err.message : 'MindAR start rejected');
    btnStart.disabled = false;
  }
});

btnStop.addEventListener('click', async () => {
  if (!mindarThree) return;
  await mindarThree.stop();
  stageStart = null;
  targetVisible = false;
  video.pause();
  video.currentTime = 0;
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
  if (!err) return 'Не удалось запустить камеру.';
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

document.querySelectorAll('[data-target]').forEach((a) => {
  a.classList.toggle('active', a.dataset.target === TARGET_KEY);
});

log('Loaded (three.module + MindAR three build). Таргет: ' + TARGET.label);
log('скрипты three + MindAR загружены за ' + Math.round(scriptsReadyAt) + ' мс от открытия страницы');
setStatus('Нажмите «Старт»: появится кадр с камеры, а поверх распознанного фото запустится видео.');