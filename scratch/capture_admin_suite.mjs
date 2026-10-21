import puppeteer from 'puppeteer-core';
import path from 'path';

const BASE_URL = 'http://127.0.0.1:8000';
const OUT_DIR = 'd:\\pentapure\\scratch\\playstore_raw';

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

  console.log('Logging in as Admin...');
  await page.goto(`${BASE_URL}/login?logout=1`, { waitUntil: 'networkidle2' });
  await page.waitForSelector('#username-input', { timeout: 5000 });
  await page.type('#username-input', 'admin');
  await page.type('#password-input', 'admin@123');

  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 10000 }),
    page.click('button[type="submit"]')
  ]);

  const screens = [
    { url: '/admin/attendance/dashboard', file: '07_attendance_dashboard.png', label: 'Attendance Dashboard' },
    { url: '/admin/attendance/daily', file: '08_attendance_daily.png', label: 'Daily Attendance' },
    { url: '/admin/po', file: '09_purchase_orders.png', label: 'Purchase Orders' },
    { url: '/admin/cashier-overview', file: '10_cashier_overview.png', label: 'Cashier Financial Overview' },
    { url: '/admin/dispatch-activity', file: '11_dispatch_activity.png', label: 'Dispatch Activity & Fleet' },
    { url: '/admin/users', file: '12_user_management.png', label: 'User & Role Management' }
  ];

  for (const s of screens) {
    console.log(`Navigating to ${s.label}: ${s.url}`);
    await page.goto(`${BASE_URL}${s.url}`, { waitUntil: 'networkidle2' });
    await new Promise(r => setTimeout(r, 1200));

    await page.evaluate(() => {
      document.querySelectorAll('.swal2-container, .modal-overlay, #notif-prompt-overlay').forEach(el => el.remove());
    });

    const dest = path.join(OUT_DIR, s.file);
    await page.screenshot({ path: dest, fullPage: false });
    console.log(`Saved: ${dest}`);
  }

  await browser.close();
  console.log('Admin suite captured successfully!');
}

run().catch(console.error);
