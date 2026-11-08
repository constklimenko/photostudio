import { execFileSync } from 'node:child_process';
import { mkdirSync, existsSync, writeFileSync } from 'node:fs';
import { networkInterfaces } from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const CA_KEY = 'webar-ca-key.pem';
const CA_CERT = 'webar-ca.pem';
const SERVER_KEY = 'webar-key.pem';
const SERVER_CERT = 'webar-cert.pem';

function lanAddresses() {
  const addrs = [];
  for (const infos of Object.values(networkInterfaces())) {
    for (const info of infos ?? []) {
      if (info.family === 'IPv4' && !info.internal) addrs.push(info.address);
    }
  }
  return addrs;
}

function run(...args) {
  execFileSync('openssl', args, { stdio: 'ignore' });
}

export function genCerts(certsDir) {
  mkdirSync(certsDir, { recursive: true });

  const caKey = path.join(certsDir, CA_KEY);
  const caCert = path.join(certsDir, CA_CERT);
  const srvKey = path.join(certsDir, SERVER_KEY);
  const srvCsr = path.join(certsDir, 'webar.csr');
  const srvCert = path.join(certsDir, SERVER_CERT);
  const extFile = path.join(certsDir, 'webar.ext');

  const dns = ['localhost', 'webar.local'];
  const ips = ['127.0.0.1', '::1', ...lanAddresses()];
  const sans = [
    ...dns.map((d) => 'DNS:' + d),
    ...ips.map((ip) => 'IP:' + ip),
  ];

  run('req', '-x509', '-newkey', 'rsa:2048', '-nodes',
    '-keyout', caKey, '-out', caCert, '-days', '3650',
    '-subj', '/CN=WebAR Prototype Local CA');
  run('req', '-newkey', 'rsa:2048', '-nodes',
    '-keyout', srvKey, '-out', srvCsr,
    '-subj', '/CN=webar-prototype');

  writeFileSync(extFile, [
    'basicConstraints=CA:FALSE',
    'subjectKeyIdentifier=hash',
    'authorityKeyIdentifier=keyid,issuer',
    'keyUsage=digitalSignature,keyEncipherment',
    'extendedKeyUsage=serverAuth',
    'subjectAltName=' + sans.join(', '),
  ].join('\n') + '\n');

  run('x509', '-req', '-in', srvCsr, '-CA', caCert, '-CAkey', caKey,
    '-CAcreateserial', '-out', srvCert, '-days', '825', '-sha256',
    '-extfile', extFile);

  return { caCert, serverCert: srvCert, serverKey: srvKey, sans };
}

function main() {
  if (!existsSync(path.join(__dirname, '..', 'certs', SERVER_CERT))) {
    const r = genCerts(path.join(__dirname, '..', 'certs'));
    console.log('Certificates created:');
    console.log('  CA    : ' + r.caCert + '   (install on the phone)');
    console.log('  Server: ' + r.serverCert);
    console.log('  SANs  : ' + r.sans.join(', '));
  } else {
    console.log('Certificates already exist — nothing to do.');
  }
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  main();
}