/* Публичная страница «оживающей фотографии» /ar/{token}.
   Перенесено из прототипа prototypes/webar (D1.2–D1.3): предзагрузка .mind
   параллельно с камерой, видео поверх распознанного фото, пауза при потере.
   Звук: попытка включить жестом «Старт», иначе кнопка «Включить звук». */

import * as THREE from 'three';
import { MindARThree } from '/vendor/mindar/1.2.5/mindar-image-three.prod.js';

const config = JSON.parse(document.getElementById('ar-config').textContent);

const $ = (id) => document.getElementById(id);
const intro = $('intro');
const btnStart = $('start');
const statusEl = $('status');
const hint = $('hint');
const btnSound = $('sound');

const PLANE_OVERSCAN = 1.02;

const mindPrefetch = fetch(config.target)
    .then((r) => {
        if (! r.ok) {
            throw new Error('HTTP ' + r.status);
        }

        return r.blob();
    })
    .then((blob) => URL.createObjectURL(blob))
    .catch(() => config.target);

const video = document.createElement('video');
video.src = config.video;
video.loop = true;
video.playsInline = true;
video.setAttribute('playsinline', '');
video.setAttribute('webkit-playsinline', '');
video.preload = 'auto';
video.muted = ! config.hasSound;

let targetVisible = false;

function setStatus(text, isError = false) {
    statusEl.hidden = ! text;
    statusEl.textContent = text;
    statusEl.classList.toggle('err', isError);
}

function updateSoundButton() {
    btnSound.hidden = ! (config.hasSound && targetVisible);
    btnSound.textContent = video.muted ? '🔊 Включить звук' : '🔇 Выключить звук';
}

/* Первый play() вызывается синхронно в обработчике нажатия — так браузер
   засчитывает жест. Если звук запрещён, видео будет играть без звука, а
   включить его можно кнопкой. */
async function unlockVideo() {
    if (config.hasSound) {
        video.muted = false;
        try {
            await video.play();
            if (! targetVisible) {
                video.pause();
            }

            return;
        } catch (e) {
            video.muted = true;
        }
    }

    try {
        await video.play();
        if (! targetVisible) {
            video.pause();
        }
    } catch (e) {
        // Видео запустится при распознавании фото.
    }
}

function cameraErrorText(err) {
    const name = err && err.name;
    if (name === 'NotAllowedError' || name === 'SecurityError') {
        return 'Нет доступа к камере. Разрешите камеру для этого сайта в настройках браузера и нажмите «Старт» ещё раз.';
    }
    if (name === 'NotFoundError' || name === 'OverconstrainedError') {
        return 'Камера не найдена. Откройте ссылку на телефоне.';
    }
    if (name === 'NotReadableError') {
        return 'Камера занята другим приложением. Закройте его и нажмите «Старт» ещё раз.';
    }

    return 'Не удалось запустить камеру. Откройте ссылку в Chrome или Safari.';
}

function buildScene() {
    const mindar = new MindARThree({
        container: $('ar'),
        imageTargetSrc: config.target,
        uiLoading: 'no',
        uiScanning: 'no',
        uiError: 'no',
        filterMinCF: 0.1,
        filterBeta: 1000,
        missTolerance: 20,
    });

    const startAR = mindar._startAR.bind(mindar);
    mindar._startAR = async () => {
        mindar.imageTargetSrc = await mindPrefetch;

        return startAR();
    };

    const { renderer, scene, camera } = mindar;
    const anchor = mindar.addAnchor(0);

    const texture = new THREE.VideoTexture(video);
    texture.encoding = THREE.sRGBEncoding;
    texture.minFilter = THREE.LinearFilter;
    const plane = new THREE.Mesh(
        new THREE.PlaneGeometry(PLANE_OVERSCAN, PLANE_OVERSCAN * config.aspect),
        new THREE.MeshBasicMaterial({ map: texture, depthWrite: false, toneMapped: false }),
    );
    anchor.group.add(plane);

    anchor.onTargetFound = () => {
        targetVisible = true;
        hint.hidden = true;
        video.play().catch(() => {
            video.muted = true;
            video.play().catch(() => {});
        }).finally(updateSoundButton);
        updateSoundButton();
    };

    anchor.onTargetLost = () => {
        targetVisible = false;
        video.pause();
        hint.hidden = false;
        updateSoundButton();
    };

    renderer.setAnimationLoop(() => renderer.render(scene, camera));

    return mindar;
}

let mindar = null;

btnStart.addEventListener('click', async () => {
    if (! window.isSecureContext || ! navigator.mediaDevices) {
        setStatus('Камера работает только по защищённой ссылке (https). Откройте ссылку из QR-кода ещё раз.', true);

        return;
    }

    const unlocked = unlockVideo();
    btnStart.disabled = true;
    setStatus('Запускаю камеру…');

    try {
        mindar ??= buildScene();
        await mindar.start();
        await unlocked;
        intro.hidden = true;
        hint.hidden = targetVisible;
        setStatus('');
    } catch (err) {
        setStatus(cameraErrorText(err), true);
        btnStart.disabled = false;
    }
});

btnSound.addEventListener('click', () => {
    video.muted = ! video.muted;
    if (! video.muted) {
        video.play().catch(() => {
            video.muted = true;
        }).finally(updateSoundButton);
    }
    updateSoundButton();
});
