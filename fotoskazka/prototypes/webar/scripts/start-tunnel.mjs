/**
 * Optional convenience: expose the local HTTPS server through a free public
 * HTTPS tunnel (cloudflared "quick tunnel") so a phone can open the prototype
 * without installing the local CA.
 *
 * Before first use install cloudflared:
 *   - macOS:  brew install cloudflared
 *   - Linux:  download from https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/
 *             or: npx @cloudflare/quick-tunnel (alternative, no binary install)
 *
 * Run it together with "npm start" in another terminal. The URL printed by
 * this script (https://<random>.trycloudflare.com) replaces the localhost URL.
 */
import { execFile, execFileSync } from 'node:child_process';

const PORT = process.env.PORT ?? process.env.AR_PORT ?? '8443';

function which(cmd) {
  try {
    execFileSync('which', [cmd], { stdio: 'ignore' });
    return true;
  } catch {
    return false;
  }
}

function main() {
  const cloudflared = which('cloudflared');
  if (cloudflared) {
    console.log('Starting cloudflared quick tunnel for https://localhost:' + PORT + ' ...');
    const child = execFile('cloudflared', ['tunnel', '--url', 'https://localhost:' + PORT],
      { stdio: 'inherit' });
    child.on('error', (err) => console.error('cloudflared failed:', err.message));
  } else {
    console.log('');
    console.log('cloudflared is not installed. Options:');
    console.log('  1) install cloudflared (see README §HTTPS) and run "npm run tunnel" again');
    console.log('  2) node --experimental-... not needed — just run:');
    console.log('     npx @cloudflare/quick-tunnel -- https://localhost:' + PORT);
    console.log('');
  }
}

main();