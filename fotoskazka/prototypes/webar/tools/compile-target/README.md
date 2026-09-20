# compile-target — офлайн-компилятор image targets MindAR

Генерирует `targets.mind` из JPEG/PNG **без браузера и без нативного модуля
`canvas`** (последний требует сборки cairo — в CI/на сервере это лишние
проблемы). Компиляция полностью локальная (TensorFlow.js CPU-кернелы +
@msgpack), облако не используется.

## Использование

```bash
npm install            # зависимости ТОЛЬКО этого инструмента
npm run compile        # => ../../targets/targets.mind (из ../../targets/sample-photo.jpg)
npm run compile -- <image.png> <out.mind>   # свой источник/выход
```

Формат выхода — `targets.mind` (тот же, что скачивает браузерный
Image Targets Compiler из официальной документации MindAR).

## Как это работает

В каталоге `vendor/` лежит **нужная часть** исходников MindAR v1.2.5
(официальный репозиторий `hiukim/mind-ar-js`, MIT). Два осознанных патча
относительно оригинала:

1. `detector/detector.js` — импорт `./kernels/webgl/index.js` заменён на
   `./kernels/cpu/index.js` (CPU-кернелы TensorFlow.js). В Node GPU не нужен.
2. `offline-compiler.js` — убран `import { createCanvas } from 'canvas'`.
   Основной объект `createProcessCanvas()` переопределён в `compile.mjs`
   шайбой, которая отдаёт уже декодированные пиксели (jpeg-js / pngjs),
   поэтому нативный canvas не требуется.

`compile.mjs` печатает диагностику: размер, число feature points (max/min) по
каждому уровню пирамиды и tracking points. Рекомендации AR-TARGET-REQUIREMENTS
§14: если на полном масштабе меньше ~60 matching points — изображение слабое
для tracking (мало контраста/деталей или слишком «пустое»); порога у SDK нет,
финальная проверка — на телефоне.

## Условия использования

Исходники MindAR распространяются по MIT (copyright Hiu Kim Yuen). Все файлы в
`vendor/` сохранены с оригинальными заголовками копирайта, лицензия приведена в
`LICENSE.txt` соседнего каталога `public/vendor/` (для скомпилированных
дистрибутивов) и в исходниках.

## Детерминированность

Компиляция детерминированна: повторный прогон одного и того же исходника даёт
байт-в-байт одинаковый `.mind` (проверено md5 для sample-photo.jpg).