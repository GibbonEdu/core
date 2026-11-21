import { spawn } from 'node:child_process';
import { mkdtemp, readFile, rm } from 'node:fs/promises';
import { createServer } from 'node:http';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';
import { once } from 'node:events';

const root = resolve(import.meta.dirname, '../..');
const php = process.env.PHP_BINARY || '/www/server/php/85/bin/php';
const chromium = process.env.CHROMIUM_BINARY || '/usr/bin/chromium';
const temp = await mkdtemp(join(tmpdir(), 'gibbon-passkey-'));
const sessionPath = join(temp, 'session.json');
const profilePath = join(temp, 'chromium-profile');

const runPhp = (mode, origin, input = '') => new Promise((resolveRun, reject) => {
    const child = spawn(php, [join(root, 'tests/browser/passkey-harness.php'), mode, sessionPath, origin], {
        cwd: root,
        stdio: ['pipe', 'pipe', 'pipe'],
    });
    let stdout = '';
    let stderr = '';
    child.stdout.setEncoding('utf8').on('data', (chunk) => { stdout += chunk; });
    child.stderr.setEncoding('utf8').on('data', (chunk) => { stderr += chunk; });
    child.on('error', reject);
    child.on('close', (code) => {
        if (code !== 0) {
            reject(new Error(stderr || `PHP harness exited with ${code}`));
            return;
        }
        resolveRun(stdout);
    });
    child.stdin.end(input);
});

const waitForJson = async (url, attempts = 100) => {
    for (let attempt = 0; attempt < attempts; attempt++) {
        try {
            const response = await fetch(url);
            if (response.ok) return response.json();
        } catch (error) {
            // Chromium is still starting.
        }
        await new Promise((resolveWait) => setTimeout(resolveWait, 100));
    }
    throw new Error(`Timed out waiting for ${url}`);
};

const connectCdp = async (url) => {
    const socket = new WebSocket(url);
    await once(socket, 'open');
    let nextId = 1;
    const pending = new Map();
    socket.addEventListener('message', (event) => {
        const message = JSON.parse(event.data);
        if (!message.id || !pending.has(message.id)) return;
        const { resolveCommand, rejectCommand } = pending.get(message.id);
        pending.delete(message.id);
        if (message.error) rejectCommand(new Error(message.error.message));
        else resolveCommand(message.result || {});
    });

    return {
        send(method, params = {}) {
            return new Promise((resolveCommand, rejectCommand) => {
                const id = nextId++;
                pending.set(id, { resolveCommand, rejectCommand });
                socket.send(JSON.stringify({ id, method, params }));
            });
        },
        close() {
            socket.close();
        },
    };
};

let browser;
let cdp;
let server;

try {
    server = createServer((request, response) => {
        response.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        response.end('<!doctype html><html><body>Passkey virtual authenticator test</body></html>');
    });
    server.listen(0, 'localhost');
    await once(server, 'listening');
    const origin = `http://localhost:${server.address().port}`;

    const debugPort = 19000 + Math.floor(Math.random() * 1000);
    browser = spawn(chromium, [
        '--headless=new',
        '--no-sandbox',
        '--disable-gpu',
        '--disable-dev-shm-usage',
        `--remote-debugging-port=${debugPort}`,
        `--user-data-dir=${profilePath}`,
        'about:blank',
    ], { stdio: 'ignore' });

    await waitForJson(`http://127.0.0.1:${debugPort}/json/version`, 100);
    const targetResponse = await fetch(
        `http://127.0.0.1:${debugPort}/json/new?${encodeURIComponent(origin)}`,
        { method: 'PUT' }
    );
    if (!targetResponse.ok) {
        throw new Error(`Unable to create Chromium target (HTTP ${targetResponse.status})`);
    }
    const target = await targetResponse.json();
    cdp = await connectCdp(target.webSocketDebuggerUrl);
    await cdp.send('WebAuthn.enable');
    await cdp.send('WebAuthn.addVirtualAuthenticator', {
        options: {
            protocol: 'ctap2',
            ctap2Version: 'ctap2_1',
            transport: 'internal',
            hasResidentKey: true,
            hasUserVerification: true,
            isUserVerified: true,
            automaticPresenceSimulation: true,
        },
    });
    await cdp.send('Page.enable');
    await cdp.send('Page.navigate', { url: origin });
    await new Promise((resolveWait) => setTimeout(resolveWait, 250));

    const options = JSON.parse(await runPhp('options', origin));
    const expression = `(async () => {
        function fromBase64url(value) {
            let input = String(value || '').replace(/-/g, '+').replace(/_/g, '/');
            while (input.length % 4) input += '=';
            const binary = atob(input);
            return Uint8Array.from(binary, ch => ch.charCodeAt(0)).buffer;
        }
        function toBase64url(buffer) {
            const bytes = new Uint8Array(buffer);
            let binary = '';
            for (const byte of bytes) binary += String.fromCharCode(byte);
            return btoa(binary).replace(/\\+/g, '-').replace(/\\//g, '_').replace(/=+$/, '');
        }
        function toBase64(buffer) {
            const bytes = new Uint8Array(buffer);
            let binary = '';
            for (const byte of bytes) binary += String.fromCharCode(byte);
            return btoa(binary);
        }
        const publicKey = ${JSON.stringify(options.options)};
        publicKey.challenge = fromBase64url(publicKey.challenge);
        publicKey.user.id = fromBase64url(publicKey.user.id);
        if (publicKey.excludeCredentials) {
            publicKey.excludeCredentials = publicKey.excludeCredentials.map(item => ({
                type: item.type,
                id: fromBase64url(item.id),
                transports: item.transports
            }));
        }
        const credential = await navigator.credentials.create({ publicKey });
        const response = credential.response;
        return JSON.stringify({
            response: {
                id: toBase64url(credential.rawId),
                rawId: toBase64(credential.rawId),
                type: credential.type,
                response: {
                    clientDataJSON: toBase64url(response.clientDataJSON),
                    attestationObject: toBase64url(response.attestationObject),
                    transports: response.getTransports ? response.getTransports() : []
                }
            },
            nonce: ${JSON.stringify(options.nonce)}
        });
    })()`;
    const evaluated = await cdp.send('Runtime.evaluate', {
        expression,
        awaitPromise: true,
        returnByValue: true,
    });
    if (evaluated.exceptionDetails) {
        const description = evaluated.exceptionDetails.exception?.description
            || evaluated.exceptionDetails.text
            || 'Browser passkey ceremony failed';
        throw new Error(description);
    }

    const verified = JSON.parse(await runPhp('verify', origin, evaluated.result.value));
    if (!verified.credentialId || String(verified.personID) !== '1') {
        throw new Error(`Unexpected verification result: ${JSON.stringify(verified)}`);
    }
    console.log(`Passkey virtual authenticator E2E passed (${verified.credentialId.length / 2} byte credential)`);
} finally {
    if (cdp) cdp.close();
    if (browser) {
        browser.kill('SIGTERM');
        await Promise.race([
            once(browser, 'exit'),
            new Promise((resolveWait) => setTimeout(resolveWait, 2000)),
        ]);
    }
    if (server) {
        server.close();
        await once(server, 'close');
    }
    await rm(temp, { recursive: true, force: true, maxRetries: 5, retryDelay: 100 });
}
