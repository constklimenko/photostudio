import { readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import jpeg from 'jpeg-js';
import { PNG } from 'pngjs';
import { OfflineCompiler } from './vendor/image-target/offline-compiler.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const args = process.argv.slice(2);
const srcPath = path.resolve(__dirname, args[0] ?? '../../targets/sample-photo.jpg');
const outPath = path.resolve(__dirname, args[1] ?? '../../targets/targets.mind');
const ext = path.extname(srcPath).toLowerCase();

let width;
let height;
let data;

if (ext === '.jpg' || ext === '.jpeg') {
  const decoded = jpeg.decode(readFileSync(srcPath), { useTArray: true, formatAsRGBA: true });
  width = decoded.width;
  height = decoded.height;
  data = decoded.data;
} else if (ext === '.png') {
  const png = PNG.sync.read(readFileSync(srcPath));
  width = png.width;
  height = png.height;
  data = png.data;
} else {
  console.error('Unsupported format: ' + ext + ' (use .jpg / .jpeg / .png)');
  process.exit(1);
}

class NodeCompiler extends OfflineCompiler {
  createProcessCanvas(img) {
    return {
      width: img.width,
      height: img.height,
      getContext() {
        return {
          drawImage() {},
          getImageData(x, y, w, h) {
            return { data: img.rgba };
          },
        };
      },
    };
  }
}

const compiler = new NodeCompiler();
await compiler.compileImageTargets([{ rgba: data, width, height }], (p) => {
  process.stdout.write('\rcompile progress: ' + Math.round(p) + '%   ');
});
process.stdout.write('\n');

const buffer = compiler.exportData();
writeFileSync(outPath, buffer);

const d = compiler.data[0];
const trackingTotal = d.trackingData.reduce((a, t) => a + (t.points.length || 0), 0);
console.log('sources : ' + srcPath);
console.log('output  : ' + outPath + ' (' + (buffer.length / 1024).toFixed(1) + ' KB)');
console.log('target  : ' + width + 'x' + height);
console.log('matching feature points (per pyramid scale):');
d.matchingData.forEach((k) => {
  console.log('  x' + k.width + ' x' + k.height + ': max=' + k.maximaPoints.length + ' min=' + k.minimaPoints.length);
});
console.log('tracking feature points (2 scales): ' + d.trackingData.map((t) => t.points.length + 'pts@' + t.width + 'x' + t.height).join(' | ') + ' (total ' + trackingTotal + ')');
console.log('');
console.log('Heuristic: fewer than ~60 matching points at full scale -> reconsider the image');
console.log('(add contrast/edges, avoid large flat areas). No hard SDK threshold - verify on the phone.');