import fs from 'node:fs/promises';
import path from 'node:path';
import { spawn } from 'node:child_process';

const baseUrl = (process.env.SCREENSHOT_BASE_URL ?? 'http://127.0.0.1:8080').replace(/\/$/, '');
const adminPassword = process.env.ADMIN_PASSWORD ?? '';
const outputDir = path.resolve(process.cwd(), process.env.SCREENSHOT_OUTPUT_DIR ?? 'screenshots');
const autoStartServer = process.env.SCREENSHOT_START_SERVER !== '0';

const publicRoutes = [
  '/',
  '/menu',
  '/lounas',
  '/tapahtumat',
  '/yhteystiedot',
  '/kuvat',
  '/en/',
  '/en/menu',
  '/en/lunch',
  '/en/events',
  '/en/contact',
  '/en/gallery',
];

const adminRoutes = [
  '/admin/',
  '/admin/settings.php',
  '/admin/notices.php',
  '/admin/hours.php',
  '/admin/menu.php',
  '/admin/lunch.php',
  '/admin/events.php',
  '/admin/gallery.php',
];

const viewports = [
  { name: 'desktop', width: 1440, height: 1100 },
  { name: 'mobile', width: 390, height: 844 },
];

function routeSlug(route) {
  if (route === '/') {
    return 'home';
  }

  return route
    .replace(/^\/+/, '')
    .replace(/\/+$/, '')
    .replace(/[/.]+/g, '-')
    .replace(/^-|-$/g, '');
}

async function loadPlaywright() {
  try {
    return await import('playwright');
  } catch (error) {
    console.error('Playwright is not installed.');
    console.error('Install it with: npm install -D playwright');
    console.error('Then install a browser with: npx playwright install chromium');
    process.exit(1);
  }
}

async function isReachable(url) {
  try {
    const response = await fetch(url, {
      signal: AbortSignal.timeout(1500),
    });
    return response.ok || response.status < 500;
  } catch {
    return false;
  }
}

function canAutoStartPhpServer(url) {
  const { protocol, hostname, port } = new URL(url);
  return protocol === 'http:' && (hostname === '127.0.0.1' || hostname === 'localhost') && port !== '';
}

async function waitForServer(url, attempts = 20) {
  for (let index = 0; index < attempts; index += 1) {
    if (await isReachable(url)) {
      return true;
    }
    await new Promise((resolve) => setTimeout(resolve, 250));
  }
  return false;
}

async function ensureBaseServer() {
  if (await isReachable(baseUrl)) {
    return null;
  }

  if (!autoStartServer || !canAutoStartPhpServer(baseUrl)) {
    console.error(`Could not reach ${baseUrl}`);
    console.error('Start the site first or set SCREENSHOT_BASE_URL to a running instance.');
    process.exit(1);
  }

  const { hostname, port } = new URL(baseUrl);
  console.log(`No server detected at ${baseUrl}. Starting PHP built-in server...`);

  const serverProcess = spawn(
    'php',
    ['-d', 'session.save_path=/tmp', '-S', `${hostname}:${port}`, '-t', process.cwd()],
    {
      cwd: process.cwd(),
      stdio: 'ignore',
    }
  );

  const ready = await waitForServer(baseUrl);
  if (!ready) {
    serverProcess.kill('SIGTERM');
    console.error(`Started PHP server process, but ${baseUrl} never became reachable.`);
    process.exit(1);
  }

  return serverProcess;
}

async function ensureLoggedIn(page) {
  if (!adminPassword) {
    return false;
  }

  await page.goto(`${baseUrl}/admin/login.php`, { waitUntil: 'networkidle' });
  await page.fill('input[name="password"]', adminPassword);
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');

  const currentUrl = page.url();
  if (currentUrl.includes('/admin/login.php')) {
    throw new Error('Admin login failed. Check ADMIN_PASSWORD.');
  }

  return true;
}

async function captureRoutes(page, routes, viewportName, groupName) {
  const targetDir = path.join(outputDir, viewportName, groupName);
  await fs.mkdir(targetDir, { recursive: true });

  for (const route of routes) {
    const targetUrl = `${baseUrl}${route}`;
    const filePath = path.join(targetDir, `${routeSlug(route)}.png`);

    console.log(`Capturing ${viewportName} ${groupName}: ${route}`);
    await page.goto(targetUrl, { waitUntil: 'networkidle' });
    await page.screenshot({ path: filePath, fullPage: true });
  }
}

const { chromium } = await loadPlaywright();

await fs.mkdir(outputDir, { recursive: true });

const localServerProcess = await ensureBaseServer();
const browser = await chromium.launch({ headless: true });

try {
  for (const viewport of viewports) {
    const page = await browser.newPage({ viewport });
    await page.addInitScript(() => {
      try {
        window.localStorage.setItem('waves_demo_dismissed', '1');
      } catch {}
    });

    await captureRoutes(page, publicRoutes, viewport.name, 'public');

    if (adminPassword) {
      await ensureLoggedIn(page);
      await captureRoutes(page, adminRoutes, viewport.name, 'admin');
    } else {
      console.warn(`Skipping admin routes for ${viewport.name}: ADMIN_PASSWORD is not set.`);
    }

    await page.close();
  }

  console.log(`Screenshots written to ${outputDir}`);
} finally {
  await browser.close();
  if (localServerProcess) {
    localServerProcess.kill('SIGTERM');
  }
}
