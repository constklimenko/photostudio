/* Компиляция таргета «оживающей фотографии» в браузере (форма Filament).
   Фото уменьшается до maxSide по длинной стороне, компилятор MindAR собирает
   .mind, оба файла загружаются во временные загрузки Livewire. */

function loadImage(file) {
    return new Promise((resolve, reject) => {
        const img = new Image()
        img.onload = () => resolve(img)
        img.onerror = () => reject(new Error('Не удалось прочитать изображение'))
        img.src = URL.createObjectURL(file)
    })
}

function downscale(img, maxSide) {
    const scale = Math.min(1, maxSide / Math.max(img.naturalWidth, img.naturalHeight))
    const canvas = document.createElement('canvas')
    canvas.width = Math.round(img.naturalWidth * scale)
    canvas.height = Math.round(img.naturalHeight * scale)
    canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height)

    return canvas
}

function upload($wire, path, file) {
    return new Promise((resolve, reject) => {
        $wire.upload(path, file, resolve, () => reject(new Error('Не удалось загрузить файл на сервер')))
    })
}

export default function arMarkerCompiler({ compilerUrl, statePath, maxSide, weakPoints }) {
    return {
        busy: false,
        progress: 0,
        status: '',
        error: '',
        result: null,
        weakPoints,

        async compile(file) {
            if (! file) {
                return
            }

            this.busy = true
            this.progress = 0
            this.error = ''
            this.result = null

            try {
                const started = performance.now()
                this.status = 'Подготовка фото…'
                const canvas = downscale(await loadImage(file), maxSide)

                this.status = 'Сборка таргета… не закрывайте страницу'
                const { a: Compiler } = await import(compilerUrl)
                const compiler = new Compiler()
                await compiler.compileImageTargets([canvas], (p) => {
                    this.progress = Math.round(p)
                })

                const buffer = compiler.exportData()
                const mind = new File([buffer], 'target.mind', { type: 'application/octet-stream' })
                const jpeg = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.92))
                const marker = new File([jpeg], 'marker.jpg', { type: 'image/jpeg' })
                const points = compiler.data[0].matchingData[0].maximaPoints.length
                const seconds = ((performance.now() - started) / 1000).toFixed(1)

                this.status = 'Загрузка на сервер…'
                await upload(this.$wire, `${statePath}.marker_upload`, marker)
                await upload(this.$wire, `${statePath}.mind_upload`, mind)
                this.$wire.set(`${statePath}.marker_width`, canvas.width, false)
                this.$wire.set(`${statePath}.marker_height`, canvas.height, false)
                this.$wire.set(`${statePath}.feature_points`, points, false)

                this.progress = 100
                this.result = {
                    seconds,
                    width: canvas.width,
                    height: canvas.height,
                    kb: Math.round(buffer.byteLength / 1024),
                    points,
                }
                this.status = 'Таргет собран. Нажмите «Сохранить».'
            } catch (e) {
                this.progress = 0
                this.status = ''
                this.error = 'Не удалось собрать таргет: ' + (e && e.message ? e.message : e)
                    + '. Нужен браузер с поддержкой WebGL (Chrome, Edge, Firefox).'
            } finally {
                this.busy = false
            }
        },
    }
}
