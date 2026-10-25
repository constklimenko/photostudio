# WebAR prototype — D1.2 «Первый технический прототип» → D1.3 «видео»

Standalone изолированный прототип для проверки базовой механики
«фотография → распознавание → контент» на смартфоне.

Кадр с камеры отображается на всю страницу, а **распознанная область печатного
таргета динамически заменяется** изображением-контентом (непрозрачная плоскость
ровно по геометрии таргета). Прежде здесь была почти прозрачная рамка «LIVE».
С 2026-10-24 (**D1.3**) поверх таргета проигрывается видео
`targets/demo-video.mp4` (`VIDEO_SRC`): без звука, inline, по кругу; запуск при
`targetFound`, пауза при `targetLost`. Видео разблокируется жестом «Старт»
(требование iOS). Задержка «targetFound → первый кадр видео» пишется в журнал.
Если видео не загрузилось, показывается картинка `replacement.svg`.

Тестовый ролик собран из самого таргета (плавный зум, таймкод, метка `TOP`
для контроля ориентации):

```bash
ffmpeg -y -i targets/sample-photo.jpg -vf "scale=3296:2496,zoompan=z='1+0.15*sin(PI*on/200)':x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':d=200:s=832x630:fps=25,drawtext=fontfile=/usr/share/fonts/truetype/dejavu/DejaVuSansMono.ttf:text='VIDEO %{eif\:t\:d}.%{eif\:mod(n,25)*4\:d\:2}s':x=16:y=16:fontsize=26:fontcolor=white:box=1:boxcolor=black@0.55:boxborderw=8,drawtext=fontfile=/usr/share/fonts/truetype/dejavu/DejaVuSansMono.ttf:text='TOP':x=(w-tw)/2:y=16:fontsize=22:fontcolor=yellow:box=1:boxcolor=black@0.55:boxborderw=6" -c:v libx264 -profile:v main -pix_fmt yuv420p -crf 26 -preset slow -movflags +faststart -an targets/demo-video.mp4
```

Свой ролик: те же пропорции кадра (1648×1248 ≈ 1.32), H.264 + `yuv420p` +
`faststart`, без звука.

Laravel, БД, Filament, авторизация, файловые загрузки и production-код **не
затрагиваются** — это отдельный statics-каталог со своим dev server.

---

## 1. Выбранная первая технология: MindAR v1.2.5 (MIT) — и почему

Выбор сделан **только на основании** исследования
`docs/AR-TECHNOLOGY-RESEARCH.md` (shortlist D1.1, §4) и плана
`docs/AR-PROTOTYPE-TEST-PLAN.md` (§0):

1. **Порядок в shortlist** — MindAR перечислен первым как «самый зрелый
   активный открытый SDK именно под image-to-video» (AR-TECHNOLOGY-RESEARCH.md §4).
   Test-plan выбирает на прототип первые две технологии (MindAR + 8th Wall)
   равноприоритетно; для первого шага D1.2 берётся **MindAR**.
2. **iOS Safari** — работает собственный CV-движок (WebGL + TensorFlow.js),
   WebXR/iOS не требуется, камера доступна из обычного браузера (§1.2, §2).
3. **Лицензия и расходы** — MIT, бесплатно и для коммерческого использования,
   никаких per-view платежей и облачных лицензионных серверов (§1.2).
4. **Self-host** — тестирование не зависит от чужих CDN: `.mind`-таргет и JS
   библиотеки лежат в этом репозитории (§1.2).
5. **Сборка таргета** — Image Targets Compiler (в т.ч. офлайн-версия, см. §6)
   сразу показывает число/распределение feature points — это рекомендованный
   шаг «проверь до печати» из AR-TARGET-REQUIREMENTS.md §14.
6. **Простота страницы** — классический сценарий `MindARThree` + светлый
   `<script>`-подход, минимальный фронтенд без сборщика.

8th Wall Open Source остаётся для D1.3 (вторая технология по плану), AR.js —
B-план при провале критерия iOS (AR-PROTOTYPE-TEST-PLAN.md §0, §4).

> Решение про технологию **для прода** фиксируется не здесь, а на контрольной
> точке D1.5. Этот прототип — только проверка механики.

---

## 2. Структура

```
prototypes/webar/
├── README.md
├── package.json              # только скрипты; зависимостей для запуска НЕТ
├── server.mjs                # локальный static dev server (HTTPS, только встроенный Node)
├── scripts/
│   ├── make-cert.mjs         # генерация локального CA + server-сертификата (openssl, SAN: localhost + LAN IP)
│   ├── start-tunnel.mjs      # опциональный публичный HTTPS-туннель (cloudflared)
│   └── sync-public.mjs       # генерация разворачиваемой копии в public/webar (для публичного сайта)
├── public/                   # фронтенд
│   ├── index.html            # AR-демо: камера (фон) + image tracking + замена таргета контентом + журнал
│   ├── camera.html           # минимальный тест getUsermMedia (без AR)
│   ├── app.js                # логика AR-страницы
│   ├── styles.css
│   └── vendor/               # self-hosted библиотеки (MIT)
│       ├── three.module.js             # three r141 ESM (сопоставляется как «three» через import map)
│       ├── mindar-image-three.prod.js  # MindAR 1.2.5 three-сборка (ESM, входная точка)
│       ├── controller-*.js / ui-*.js   # code-split чанки MindAR dist (self-host, без CDN)
│       ├── three-addons/renderers/CSS3DRenderer.js  # three example (нужен MindAR)
│       └── LICENSE.txt                 # MIT-атрибуция vendored библиотек
├── targets/
│   ├── sample-photo.jpg      # ТЕСТОВАЯ ФОТОГРАФИЯ (исходник; 1648×1248)
│   ├── sample-photo-print.png# та же фотография для печати (20 см @ 300 dpi, кадрирование 1:1 с исходником)
│   ├── replacement.svg       # ИЗОБРАЖЕНИЕ-ЗАМЕНА (1648×1248), которым заменяется область таргета
│   └── targets.mind          # подготовленный image target (MindAR, скомпилирован из sample-photo.jpg)
└── tools/
    └── compile-target/       # офлайн-компилятор .mind (pure JS, без нативного canvas)
        ├── package.json
        ├── compile.mjs
        └── vendor/           # нужная часть исходников MindAR (патч: CPU-кернелы, canvas убран)
```

---

## 3. Быстрый старт

Требования: **Node ≥ 18**, **openssl**, та же Wi-Fi-сеть у смартфона.

```bash
cd prototypes/webar
npm start                 # HTTPS на 0.0.0.0:8443 (certs/ создадутся автоматически)
```

- **Десктоп (без камеры — только превью/код):**
  `https://localhost:8443/`
- **Смартфон (Android Chrome / iOS Safari)** — адрес, который сервер напечатает
  при старте: `https://<IP-компьютера>:8443/`. Указанный IP должен входить в
  SAN сертификата (make-cert добавляет все найденные LAN IP).
- `https://localhost:8443/camera.html` — изолированная проверка камеры без AR.

Остановка: `Ctrl+C`.

### HTTPS — почему и как

Камера `getUserMedia` в браузерах смартфонов доступна **только в secure
context** (HTTPS), локальный `localhost` на телефоне отсутствует, поэтому нужен
HTTPS. Есть два пути:

1. **Локальный CA (по умолчанию, `npm start`):** `scripts/make-cert.mjs`
   генерирует `certs/webar-ca.pem` + подписанный им серверный сертификат с
   SAN для `localhost` и всех LAN IP. Чтобы телефон доверял сертификату и не
   блокировал камеру:
   - **iPhone (Safari):** переслать `certs/webar-ca.pem` (AirDrop/почта) →
     «Разрешить профиль» → «Установить»; затем
     *Настройки → Основные → Об этом устройстве → Доверие сертификатам* →
     включить для «WebAR Prototype Local CA».
   - **Android (Chrome):** Настройки → Безопасность → «Установить сертификат
     CA» → выбрать `webar-ca.pem`.
   - Перезапустить браузер.
2. **Публичный туннель (без установки CA):** в другом терминале
   `npm run tunnel` (понадобится `cloudflared`, см. `scripts/start-tunnel.mjs`).
   Открыть на телефоне полученный `https://<...>.trycloudflare.com/`.

> Подсказка: часто хватает и просто «принять» предупреждение о self-signed
> сертификате в браузере, но на iOS Safari это не гарантирует доступ к камере —
> надёжный путь именно установка CA.

---

## 4. Публичная копия сайта — адрес `/webar`

Прототип можно открывать прямо на публичном сайте (без локального сервера и
установки CA на телефоне) — фрагменты отдаются тем же репозиторием через git:

```bash
npm run sync:public     # cкопирует prototypes/webar/ → public/webar (Laravel webroot)
```

После этого путь **должен** открываться на сайте:

- `https://fotoskazkaufa.ru/webar/` — AR-страница;
- `https://fotoskazkaufa.ru/webar/camera.html` — отдельный тест камеры.

Механика: `scripts/sync-public.mjs` вычищает и заново создаёт
`public/webar/` (HTML/JS/CSS + `vendor/` + `targets/`). Копия **коммитится** в
git — публичный сервер получает её обычным pull-ом. Single source of truth —
`prototypes/webar/`; после изменения прототипа перегенерируйте копию и
коммитьте.

> Прототип использует только относительные пути, поэтому работает и из
> подкаталога `/webar`, и из корня локального dev server.
>
> **Камера:** `getUserMedia` работает только в secure context. Если адрес
> `fotoskazkaufa.ru` отдаётся по HTTP без HTTPS-редиректа — тест камеры на
> телефоне не запустится (остальная разметка откроется). Убедитесь, что сайт
> доступен по HTTPS.

---

## 5. Как тестировать (по AR-PROTOTYPE-TEST-PLAN.md)

Прототип измеряет то, что нужно критерию D1.2.

1. **Распечатайте `sample-photo-print.png`** — матово, «1:1 по кропу», без
   полей/рамок, т.к. именно этот кадр скомпилирован в `targets.mind`
   (геометрия должна совпадать — AR-TARGET-REQUIREMENTS.md §9). Размер на листе
   укажет фото 20 см по ширине; раскутите его **тем же файлом**, из которого
   собран таргет (у нас это исходник `sample-photo.jpg`, а `-print` — лишь
   ресемплинг того же кадра).
2. В обычном браузере (Safari/Chrome, **не** в webview Instagram/VK/Telegram)
   откройте `https://<IP>:8443/`.
3. Нажмите **«Старт»** (жест запрашивает камеру — важно для iOS).
4. Наведите камеру на фото с дистанции ~25–40 см ровным светом.
5. Наблюдайте:
   - **кадр с камеры идёт на всю страницу как есть** (задняя камера не
     зеркалится), а область распознанного таргета **заменяется
     изображением-контентом** (`targets/replacement.svg`) — у него видно
     подписи «верх/лево», по ним легко убедиться, что замена следует за
     распечаткой правильно, без зеркала;
   - зелёный статус `target: found`;
   - журнал внизу пишет `targetFound`, при потере — `targetLost` и длительность
     удержания; **критерий D1.2 = удержание ≥ 5 сек в >50% попыток**;
   - кнопка **«Сетка совмещения»** включает полупрозрачную сетку размером 1:1 —
     проверка, что замена лежит точно на отпечатке (если видны края печати,
     увеличивается `REPLACE_OVERSCAN` в `app.js`).

### Замена контента

Изображение, которым заменяется таргет, задаётся константой
`REPLACEMENT_SRC` в `public/app.js` (по умолчанию `./targets/replacement.svg`).
Достаточно положить рядом свой файл того же кадра (1648×1248 — как у таргета) и
поменять строку. Растровое изображение (`./targets/my-content.jpg`) тоже
подходит. После правки — `npm run sync:public`.

Заполняйте протокольную таблицу (комбо «телефон × свет × размер печати») из
AR-PROTOTYPE-TEST-PLAN.md §3. Результаты — отчёт D1.2.

> **Если кадра с камеры не видно** (только тёмный фон и рамка контента) — это
> старый баг наложения: MindAR ставит `<video>` с `z-index:-2`, и без отдельного
> контекста наложения у `#ar-container` (`z-index:0; isolation:isolate`) видео
> уходит за фон страницы. То же исправление в `styles.css` делает кнопки HUD
> кликабельными (`pointer-events:none` для `.mindar-ui-overlay`).


---

## 6. Пересборка target-файла (`.mind`)

`targets.mind` уже скомпилирован из `sample-photo.jpg` офлайн-компилятором
(без нативного canvas, депозиты только чистый JS). Если меняете фотографию:

```bash
cd prototypes/webar/tools/compile-target
npm install                 # зависимости ставятся ОТДЕЛЬНО от остального прототипа
npm run compile             # => ../../targets/targets.mind (из ../../targets/sample-photo.jpg)
```

`npm run compile -- <какой-файл.jpg> <куда-записать.mind>` — свой источник.
Расширения: `.jpg/.jpeg/.png`. При компиляции печатается число feature points
по пирамиде шкалы — контроль «достаточно ли точек» до печати
(AR-TARGET-REQUIREMENTS.md §14). Патч исходников MindAR описан в
`tools/compile-target/README.md`.

> Печатать после замены надо именно новый файл (1:1 с тем, что собран).

---

## 7. Что уже работает / что намеренно не реализовано

Работает:
- локальный HTTPS dev server (0 npm-зависимостей, Node ≥ 18);
- публичная копия на сайте: `npm run sync:public` → `public/webar` (git);
- минимальный доступ к камере (`camera.html`) и AR-страница (`index.html`);
- image tracking MindAR по подготовленному `targets.mind`;
- **видео поверх распознанного таргета** (`VIDEO_SRC`, запуск/пауза по
  found/lost, задержка запуска в журнале), запасная картинка `REPLACEMENT_SRC`,
  плюс журнал удержания (метрики D1.2);
- пересборка `.mind` офлайн.

Намеренно НЕ сделано (граница D1.2, roadmap):
- БД, Filament, авторизация, загрузка файлов, production routes;
- интеграция с Media/Video проекта;
- звук видео (iOS требует отдельного жеста), выбор ролика без правки кода;
- выбор альтернатив (8th Wall) на этой итерации — D1.3.

---

## 8. Команды

| Действие | Команда |
|---|---|
| Запустить dev server (HTTPS) | `npm start` (или `node server.mjs`) |
| Пересгенерировать сертификаты | `npm run cert` |
| Публичный HTTPS-туннель (опц.) | `npm run tunnel` *(нужен `cloudflared`)* |
| Пересобрать `.mind` из `sample-photo.jpg` | `npm --prefix tools/compile-target run compile` |
| Сгенерировать `public/webar` для сайта | `npm run sync:public` |
| Проверить локальный запуск | `curl -k https://localhost:8443/` |

Проверка без камеры на десктопе: страницы открываются, `camera.html` покажет
ошибку «камера не найдена» — это норма для ноутбука без камеры/разрешённого
термина.