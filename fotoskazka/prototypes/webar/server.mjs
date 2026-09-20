import { createServer } from 'node:https';
import { readFileSync, statSync, existsSync } from 'node:fs';
import { networkInterfaces } from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { genCerts } from './scripts/make-cert.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const PORT = Number(process.env.PORT ?? process.env.AR_PORT ?? 8443);
const HOST = process.env.HOST ?? '0.0.0.0';

const ROOT = __dirname;
const PUBLIC_DIR = path.join(ROOT, 'public');
const TARGETS_DIR = path.join(ROOT, 'targets');
const CERTS_DIR = path.join(ROOT, 'certs');
const CERT = process.env.AR_CERT ?? path.join(CERTS_DIR, 'webar-cert.pem');
const KEY = process.env.AR_KEY ?? path.join(CERTS_DIR, 'webar-key.pem');

const MIME = {
  '.html': 'text/html; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.mjs': 'text/javascript; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.png': 'image/png',
  '.svg': 'image/svg+xml',
  '.webp': 'image/webp',
  '.mind': 'application/octet-stream',
  '.wasm': 'application/wasm',
  '.mtl': 'text/plain',
  '.obj': 'text/plain',
  '.mp4': 'video/mp4',
  '.webm': 'video/webm',
};

function lanAddresses() {
  const addrs = [];
  for (const infos of Object.values(networkInterfaces())) {
    for (const info of infos ?? []) {
      if (info.family === 'IPv4' && !info.internal) addrs.push(info.address);
    }
  }
  return addrs;
}

function resolveFile(relativeUrl) {
  const urlPath = decodeURIComponent(relativeUrl.split('?')[0]);
  if (urlPath === '/') return path.join(PUBLIC_DIR, 'index.html');
  if (urlPath.startsWith('/targets/') || urlPath === '/targets') {
    const rel = urlPath.replace(/^\/targets\/?/, '');
    const abs = path.join(TARGETS_DIR, rel);
    if (!abs.startsWith(TARGETS_DIR + path.sep)) return null;
    return abs;
  }
  const abs = path.join(PUBLIC_DIR, urlPath);
  if (!abs.startsWith(PUBLIC_DIR + path.sep) && abs !== PUBLIC_DIR) return null;
  return abs;
}

function contentType(file) {
  return MIME[path.extname(file).toLowerCase()] ?? 'application/octet-stream';
}

function loadTls() {
  if (!existsSync(CERT) || !existsSync(KEY)) {
    console.warn('==> No certificate found, generating a local CA + server cert (openssl required)...');
    genCerts(CERTS_DIR);
  }
  return { cert: readFileSync(CERT), key: readFileSync(KEY) };
}

const tls = loadTls();

const server = createServer(tls, (req, res) => {
  const url = req.url ?? '/';
  const file = resolveFile(url);
  if (!file || !existsSync(file) || !statSync(file).isFile()) {
    res.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' });
    res.end('404 — not found (' + url + ')');
    return;
  }
  const body = readFileSync(file);
  res.writeHead(200, {
    'Content-Type': contentType(file),
    'Cache-Control': 'no-store',
    'Access-Control-Allow-Origin': '*',
  });
  res.end(body);
});

server.listen(PORT, HOST, () => {
  const ips = lanAddresses();
  console.log('');
  console.log('WebAR prototype (MindAR image tracking) — dev server');
  console.log('------------------------------------------------------');
  console.log('Local  (desktop, camera works on localhost):');
  console.log('  https://localhost:' + PORT + '/');
  console.log('  https://localhost:' + PORT + '/camera.html');
  console.log('');
  console.log('Phone  (same Wi-Fi, HTTPS via local CA — see README §HTTPS):');
  for (const ip of ips) {
    console.log('  https://' + ip + ':' + PORT + '/');
  }
  console.log('');
  console.log('To open on the phone accept the self-signed cert OR install the CA');
  console.log('(certs/webar-ca.pem) on the device. Alternatively run "npm run tunnel"');
  console.log('for a public HTTPS URL (no cert install needed).');
  console.log('');
  console.log('Press Ctrl+C to stop.');
});