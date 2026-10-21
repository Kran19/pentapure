import puppeteer from 'puppeteer-core';
import path from 'path';
import fs from 'fs';

const BASE_URL = 'http://127.0.0.1:8000';
const OUT_DIR = 'd:\\pentapure\\scratch\\playstore_raw';

if (!fs.existsSync(OUT_DIR)) {
  fs.mkdirSync(OUT_DIR, { recursive: true });
}

async function loginAndCapture(page, username, password, targetUrl, filename, label) {
  console.log(`\n--- Capturing ${label} (${username}) ---`);
  await page.goto(`${BASE_URL}/login?logout=1`, { waitUntil: 'networkidle2' });

  await page.waitForSelector('#username-input', { timeout: 5000 });
  await page.type('#username-input', username);
  await page.type('#password-input', password);

  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 10000 }),
    page.click('button[type="submit"]')
  ]);

  if (targetUrl) {
    await page.goto(`${BASE_URL}${targetUrl}`, { waitUntil: 'networkidle2' });
  }

  // Dismiss any SweetAlert if visible
  await page.evaluate(() => {
    const swalConfirm = document.querySelector('.swal2-confirm');
    if (swalConfirm) swalConfirm.click();
    const swalClose = document.querySelector('.swal2-close');
    if (swalClose) swalClose.click();
    // Also remove notifications overlay if blocking
    const notifModal = document.getElementById('notif-prompt-overlay') || document.getElementById('modal-overlay');
    if (notifModal) notifModal.style.display = 'none';
  });

  await new Promise(r => setTimeout(r, 1000));

  const dest = path.join(OUT_DIR, filename);
  await page.screenshot({ path: dest, fullPage: false });
  console.log(`Saved: ${dest} | URL: ${page.url()}`);
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
    deviceScaleFactor: 2.621359, // 412 * 2.621359 = 1080 px, 915 * 2.621359 = 2400 px
    isMobile: true,
    hasTouch: true
  });

  // 1. Login Page
  console.log('\n--- Capturing 01_login.png ---');
  await page.goto(`${BASE_URL}/login?logout=1`, { waitUntil: 'networkidle2' });
  await page.screenshot({ path: path.join(OUT_DIR, '01_login.png'), fullPage: false });

  // 2. Admin Dashboard
  await loginAndCapture(page, 'admin', 'admin@123', '/admin/home', '02_admin_dashboard.png', 'Admin Dashboard');

  // 3. Admin Stock Ledger
  await page.goto(`${BASE_URL}/admin/stock`, { waitUntil: 'networkidle2' });
  await new Promise(r => setTimeout(r, 1000));
  await page.screenshot({ path: path.join(OUT_DIR, '03_inventory_stock.png'), fullPage: false });

  // 4. Sales Orders
  await loginAndCapture(page, 'sales', 'sales@123', '/sales/home', '04_sales_orders.png', 'Sales Orders');

  // 5. Dispatch Hub
  await loginAndCapture(page, 'dispatch', 'dispatch@123', '/dispatch/home', '05_dispatch_logistics.png', 'Dispatch Hub');

  // 6. Cashier Portal
  await loginAndCapture(page, 'cashier', 'cashier@123', '/cashier/home', '06_cashier_transactions.png', 'Cashier Portal');

  // 7. Attendance HR
  await loginAndCapture(page, 'attendance', 'attendance@123', '/attendance/dashboard', '07_workforce_attendance.png', 'Attendance HR');

  // 8. Purchase Orders (Admin)
  await loginAndCapture(page, 'admin', 'admin@123', '/admin/po', '08_purchase_orders.png', 'Purchase Orders');

  await browser.close();
  console.log('\nALL SCREENSHOTS CAPTURED SUCCESSFULLY!');
}

run().catch(console.error);
