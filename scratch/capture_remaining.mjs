import puppeteer from 'puppeteer-core';
import path from 'path';

const BASE_URL = 'http://127.0.0.1:8000';
const OUT_DIR = 'd:\\pentapure\\scratch\\playstore_raw';

async function safeLogin(page, username, password) {
  await page.goto(`${BASE_URL}/login?logout=1`, { waitUntil: 'networkidle2' });
  await page.waitForSelector('#username-input', { timeout: 5000 });
  await page.type('#username-input', username);
  await page.type('#password-input', password);
  
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 15000 }).catch(() => {}),
    page.click('button[type="submit"]')
  ]);
  
  await new Promise(r => setTimeout(r, 2000));

  await page.evaluate(() => {
    document.querySelectorAll('.swal2-container, .modal-overlay, #notif-prompt-overlay').forEach(el => el.remove());
  });
}

async function captureScreen(page, url, filename) {
  if (url) {
    await page.goto(`${BASE_URL}${url}`, { waitUntil: 'networkidle2' }).catch(() => {});
    await new Promise(r => setTimeout(r, 1500));
  }
  await page.evaluate(() => {
    document.querySelectorAll('.swal2-container, .modal-overlay, #notif-prompt-overlay').forEach(el => el.remove());
  });
  const dest = path.join(OUT_DIR, filename);
  await page.screenshot({ path: dest, fullPage: false });
  console.log(`Saved: ${dest} | Current URL: ${page.url()}`);
}

async function run() {
  const browser = await puppeteer.launch({
    executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
  });

  const page = await browser.newPage();
  await page.setViewport({
    width: 412,
    height: 915,
    deviceScaleFactor: 2.621359,
    isMobile: true,
    hasTouch: true
  });

  // 6. Cashier
  console.log('Capturing Cashier...');
  await safeLogin(page, 'cashier', 'cashier@123');
  await captureScreen(page, null, '06_cashier_transactions.png');

  // 7. Attendance
  console.log('Capturing Attendance...');
  await safeLogin(page, 'attendance', 'attendance@123');
  await captureScreen(page, null, '07_workforce_attendance.png');

  // 8. Purchase Orders (admin)
  console.log('Capturing Purchase Orders...');
  await safeLogin(page, 'admin', 'admin@123');
  await captureScreen(page, '/admin/po', '08_purchase_orders.png');

  // Bonus: Production / Manufacturing Action
  console.log('Capturing Production / Manufacturing...');
  await captureScreen(page, '/admin/users', '09_admin_team_management.png');

  await browser.close();
  console.log('Finished capturing remaining screens!');
}

run().catch(console.error);
