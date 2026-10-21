import puppeteer from 'puppeteer-core';
import path from 'path';
import fs from 'fs';

const OUT_BASE = 'd:\\pentapure\\playstore_assets';
const ICON_DIR = path.join(OUT_BASE, 'icon');
const FEATURE_DIR = path.join(OUT_BASE, 'feature_graphic');

[ICON_DIR, FEATURE_DIR].forEach(dir => {
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
});

// Read logo as base64
const logoBuf = fs.readFileSync('d:\\pentapure\\public\\logo.png');
const logoBase64 = `data:image/png;base64,${logoBuf.toString('base64')}`;

async function generateGraphics() {
  const browser = await puppeteer.launch({
    executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
  });

  const page = await browser.newPage();

  // 1. Generate 512x512 App Icon
  console.log('Generating 512x512 App Icon...');
  await page.setViewport({ width: 512, height: 512, deviceScaleFactor: 1 });
  await page.setContent(`
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
          width: 512px;
          height: 512px;
          background: linear-gradient(145deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
          display: flex;
          align-items: center;
          justify-content: center;
          font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
          overflow: hidden;
          position: relative;
        }
        .glow {
          position: absolute;
          width: 320px;
          height: 320px;
          background: radial-gradient(circle, rgba(14, 165, 233, 0.3) 0%, rgba(14, 165, 233, 0) 70%);
          border-radius: 50%;
        }
        .card {
          width: 440px;
          height: 440px;
          background: rgba(255, 255, 255, 0.04);
          border: 2px solid rgba(255, 255, 255, 0.12);
          border-radius: 96px;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          box-shadow: 0 24px 48px -12px rgba(0, 0, 0, 0.6);
          position: relative;
          z-index: 2;
        }
        .logo {
          width: 280px;
          height: 280px;
          object-fit: contain;
          filter: drop-shadow(0 12px 24px rgba(0, 0, 0, 0.4));
        }
        .badge {
          margin-top: 12px;
          color: #38bdf8;
          font-weight: 800;
          font-size: 26px;
          letter-spacing: 3px;
          text-transform: uppercase;
        }
      </style>
    </head>
    <body>
      <div class="glow"></div>
      <div class="card">
        <img class="logo" src="${logoBase64}" alt="Logo">
        <div class="badge">PENTAPURE</div>
      </div>
    </body>
    </html>
  `, { waitUntil: 'networkidle0' });

  const iconPath = path.join(ICON_DIR, 'playstore_icon_512x512.png');
  await page.screenshot({ path: iconPath, omitBackground: false });
  console.log('Saved App Icon to:', iconPath);

  // 2. Generate 1024x500 Feature Graphic
  console.log('Generating 1024x500 Feature Graphic...');
  await page.setViewport({ width: 1024, height: 500, deviceScaleFactor: 1 });
  await page.setContent(`
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
          width: 1024px;
          height: 500px;
          background: linear-gradient(135deg, #0b1329 0%, #0f172a 45%, #0369a1 100%);
          display: flex;
          align-items: center;
          justify-content: space-between;
          padding: 0 60px;
          font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
          color: #ffffff;
          overflow: hidden;
          position: relative;
        }
        .bg-pattern {
          position: absolute;
          top: 0; left: 0; right: 0; bottom: 0;
          background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
          background-size: 28px 28px;
          opacity: 0.6;
        }
        .content {
          max-width: 580px;
          position: relative;
          z-index: 2;
        }
        .tagline-chip {
          display: inline-flex;
          align-items: center;
          gap: 8px;
          background: rgba(14, 165, 233, 0.2);
          border: 1px solid rgba(56, 189, 248, 0.4);
          padding: 6px 14px;
          border-radius: 20px;
          font-size: 13px;
          font-weight: 700;
          color: #38bdf8;
          text-transform: uppercase;
          letter-spacing: 1.5px;
          margin-bottom: 20px;
        }
        h1 {
          font-size: 44px;
          font-weight: 800;
          line-height: 1.15;
          letter-spacing: -0.5px;
          margin-bottom: 14px;
        }
        h1 span {
          background: linear-gradient(90deg, #38bdf8, #818cf8);
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
        }
        p {
          font-size: 16px;
          line-height: 1.5;
          color: #cbd5e1;
          margin-bottom: 24px;
        }
        .features-row {
          display: flex;
          gap: 16px;
        }
        .pill {
          background: rgba(255, 255, 255, 0.08);
          border: 1px solid rgba(255, 255, 255, 0.15);
          padding: 8px 14px;
          border-radius: 8px;
          font-size: 13px;
          font-weight: 600;
          color: #e2e8f0;
        }
        .visual {
          position: relative;
          z-index: 2;
          display: flex;
          align-items: center;
          justify-content: center;
        }
        .visual-circle {
          width: 320px;
          height: 320px;
          background: linear-gradient(135deg, rgba(14, 165, 233, 0.2), rgba(99, 102, 241, 0.1));
          border: 2px solid rgba(56, 189, 248, 0.3);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          box-shadow: 0 0 60px rgba(14, 165, 233, 0.25);
        }
        .visual img {
          width: 220px;
          height: 220px;
          object-fit: contain;
          filter: drop-shadow(0 16px 32px rgba(0, 0, 0, 0.5));
        }
      </style>
    </head>
    <body>
      <div class="bg-pattern"></div>
      <div class="content">
        <div class="tagline-chip">Enterprise ERP & Logistics</div>
        <h1>Pentapure <span>Operations</span></h1>
        <p>Complete multi-role platform for manufacturing, inventory ledger, sales fulfillment, logistics dispatches & cashier accounting.</p>
        <div class="features-row">
          <div class="pill">📦 Real-time Stock</div>
          <div class="pill">🚚 Fleet Dispatch</div>
          <div class="pill">💵 Cashier Ledger</div>
          <div class="pill">👥 Attendance HR</div>
        </div>
      </div>
      <div class="visual">
        <div class="visual-circle">
          <img src="${logoBase64}" alt="Pentapure Logo">
        </div>
      </div>
    </body>
    </html>
  `, { waitUntil: 'networkidle0' });

  const featurePath = path.join(FEATURE_DIR, 'playstore_feature_graphic_1024x500.png');
  await page.screenshot({ path: featurePath, omitBackground: false });
  console.log('Saved Feature Graphic to:', featurePath);

  await browser.close();
  console.log('Graphics generated successfully!');
}

generateGraphics().catch(console.error);
