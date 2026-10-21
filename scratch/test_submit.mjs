import puppeteer from 'puppeteer-core';
import path from 'path';

async function testSubmit(username, password, label) {
  const browser = await puppeteer.launch({
    executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
  });

  const page = await browser.newPage();
  await page.setViewport({
    width: 412,
    height: 915,
    deviceScaleFactor: 2.625,
    isMobile: true,
    hasTouch: true
  });

  await page.goto('https://pentapureadmin.in/public/login', { waitUntil: 'networkidle2' });

  await page.type('#username-input', username);
  await page.type('#password-input', password);

  console.log(`Submitting form for ${label} (${username})...`);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 10000 }).catch(e => console.log('Navigation timeout or stayed on page:', e.message)),
    page.click('button[type="submit"]')
  ]);

  const currentUrl = page.url();
  console.log(`Current URL for ${label}:`, currentUrl);

  const outputPath = path.resolve(`d:\\pentapure\\scratch\\after_login_${label}.png`);
  await page.screenshot({ path: outputPath, fullPage: false });
  console.log(`Saved screenshot to: ${outputPath}`);

  // check page text
  const bodyText = await page.evaluate(() => document.body.innerText.substring(0, 300));
  console.log(`Page snippet:`, bodyText.replace(/\n+/g, ' '));

  await browser.close();
}

async function run() {
  await testSubmit('admin', 'admin@123', 'admin');
}

run().catch(console.error);
