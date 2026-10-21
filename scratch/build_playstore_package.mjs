import puppeteer from 'puppeteer-core';
import path from 'path';
import fs from 'fs';

const OUT_BASE = 'd:\\pentapure\\playstore_assets';
const ICON_DIR = path.join(OUT_BASE, 'icon');
const FEATURE_DIR = path.join(OUT_BASE, 'feature_graphic');
const RAW_DIR = path.join(OUT_BASE, 'screenshots_raw');
const SHOWCASE_DIR = path.join(OUT_BASE, 'screenshots_showcase');

[ICON_DIR, FEATURE_DIR, RAW_DIR, SHOWCASE_DIR].forEach(dir => {
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
});

// Load Logo
const logoBuf = fs.readFileSync('d:\\pentapure\\public\\logo.png');
const logoBase64 = `data:image/png;base64,${logoBuf.toString('base64')}`;

// Define the 8 main screenshot mappings
const screens = [
  {
    rawSrc: 'd:\\pentapure\\scratch\\playstore_raw\\01_login.png',
    rawName: '01_login_portal.png',
    tag: 'SECURE AUTHENTICATION',
    title: 'Role-Based Enterprise Access',
    subtitle: 'Unified portal for Admin, Sales, Dispatch, Cashier & HR teams'
  },
  {
    rawSrc: 'd:\\pentapure\\scratch\\playstore_raw\\02_admin_dashboard.png',
    rawName: '02_admin_dashboard.png',
    tag: 'EXECUTIVE OVERVIEW',
    title: 'Live Operations Dashboard',
    subtitle: 'Monitor manufacturing, active orders, dispatches & stock at a glance'
  },
  {
    rawSrc: 'd:\\pentapure\\scratch\\playstore_raw\\03_inventory_stock.png',
    rawName: '03_inventory_stock_ledger.png',
    tag: 'DYNAMIC INVENTORY',
    title: 'Multi-Stage Stock Ledger',
    subtitle: 'Live balance tracking across Raw, Semi-Finished & Finished materials'
  },
  {
    rawSrc: 'd:\\pentapure\\scratch\\playstore_raw\\04_sales_orders.png',
    rawName: '04_sales_orders.png',
    tag: 'ORDER MANAGEMENT',
    title: 'Sales & Fulfillment Hub',
    subtitle: 'Track B2B customer orders, pricing, fulfillment status and pending items'
  },
  {
    rawSrc: 'd:\\pentapure\\scratch\\playstore_raw\\05_dispatch_logistics.png',
    rawName: '05_dispatch_logistics.png',
    tag: 'SUPPLY CHAIN & LOGISTICS',
    title: 'Fleet Dispatch & Delivery',
    subtitle: 'Vehicle assignment, driver records, LR documents & gate pass tracking'
  },
  {
    rawSrc: 'd:\\pentapure\\scratch\\playstore_raw\\06_cashier_transactions.png',
    rawName: '06_cashier_transactions.png',
    tag: 'FINANCIAL CONTROL',
    title: 'Cashier & Expense Ledger',
    subtitle: 'Manage daily cash IN/OUT, receipt attachments & automated PDF audits'
  },
  {
    rawSrc: 'd:\\pentapure\\scratch\\playstore_raw\\07_attendance_dashboard.png',
    rawName: '07_workforce_attendance.png',
    tag: 'HUMAN RESOURCES',
    title: 'Workforce & Attendance',
    subtitle: 'Daily worker shifts, department headcounts & automated payroll logs'
  },
  {
    rawSrc: 'd:\\pentapure\\scratch\\playstore_raw\\09_purchase_orders.png',
    rawName: '08_purchase_orders.png',
    tag: 'PROCUREMENT',
    title: 'Purchase Order Approvals',
    subtitle: 'Direct factory requisition, inventory replenishment & admin sign-offs'
  }
];

async function buildAll() {
  const browser = await puppeteer.launch({
    executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
  });

  const page = await browser.newPage();

  // 1. App Icon (512x512)
  console.log('Rendering 512x512 App Icon...');
  await page.setViewport({ width: 512, height: 512, deviceScaleFactor: 1 });
  await page.setContent(`
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
          width: 512px; height: 512px;
          background: linear-gradient(135deg, #090e17 0%, #1e293b 100%);
          display: flex; align-items: center; justify-content: center;
          font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
          position: relative; overflow: hidden;
        }
        .glow {
          position: absolute; width: 340px; height: 340px;
          background: radial-gradient(circle, rgba(14, 165, 233, 0.35) 0%, rgba(14, 165, 233, 0) 70%);
          border-radius: 50%;
        }
        .container {
          width: 440px; height: 440px;
          background: rgba(255, 255, 255, 0.05);
          border: 2px solid rgba(255, 255, 255, 0.12);
          border-radius: 96px;
          display: flex; flex-direction: column; align-items: center; justify-content: center;
          box-shadow: 0 20px 50px rgba(0,0,0,0.5);
          position: relative; z-index: 2;
        }
        .logo {
          width: 270px; height: 270px; object-fit: contain;
          filter: drop-shadow(0 10px 20px rgba(0,0,0,0.4));
        }
        .label {
          margin-top: 10px; color: #38bdf8; font-weight: 800; font-size: 26px;
          letter-spacing: 2px; text-transform: uppercase;
        }
      </style>
    </head>
    <body>
      <div class="glow"></div>
      <div class="container">
        <img class="logo" src="${logoBase64}" alt="Logo">
        <div class="label">PENTAPURE</div>
      </div>
    </body>
    </html>
  `, { waitUntil: 'domcontentloaded' });
  await page.screenshot({ path: path.join(ICON_DIR, 'playstore_icon_512x512.png') });

  // 2. Feature Graphic (1024x500)
  console.log('Rendering 1024x500 Feature Graphic...');
  await page.setViewport({ width: 1024, height: 500, deviceScaleFactor: 1 });
  await page.setContent(`
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
          width: 1024px; height: 500px;
          background: linear-gradient(135deg, #090e17 0%, #0f172a 45%, #0284c7 100%);
          display: flex; align-items: center; justify-content: space-between;
          padding: 0 60px;
          font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
          color: #ffffff; position: relative; overflow: hidden;
        }
        .content { max-width: 580px; position: relative; z-index: 2; }
        .tagline {
          display: inline-block;
          background: rgba(14, 165, 233, 0.25);
          border: 1px solid rgba(56, 189, 248, 0.5);
          padding: 6px 14px; border-radius: 20px;
          font-size: 13px; font-weight: 700; color: #38bdf8;
          text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 16px;
        }
        h1 { font-size: 44px; font-weight: 800; line-height: 1.15; margin-bottom: 12px; }
        h1 span { color: #38bdf8; }
        p { font-size: 16px; line-height: 1.5; color: #cbd5e1; margin-bottom: 24px; }
        .pills { display: flex; gap: 12px; }
        .pill {
          background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2);
          padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; color: #f1f5f9;
        }
        .art {
          position: relative; z-index: 2; width: 300px; height: 300px;
          background: radial-gradient(circle, rgba(14, 165, 233, 0.25) 0%, transparent 70%);
          display: flex; align-items: center; justify-content: center;
        }
        .art img {
          width: 230px; height: 230px; object-fit: contain;
          filter: drop-shadow(0 15px 30px rgba(0,0,0,0.5));
        }
      </style>
    </head>
    <body>
      <div class="content">
        <div class="tagline">Enterprise ERP & Logistics</div>
        <h1>Pentapure <span>Operations</span></h1>
        <p>Unified real-time platform for manufacturing, inventory ledger, sales fulfillment, logistics dispatches & cashier accounting.</p>
        <div class="pills">
          <div class="pill">📦 Live Inventory</div>
          <div class="pill">🚚 Fleet Dispatch</div>
          <div class="pill">💵 Cashier Ledger</div>
          <div class="pill">👥 Attendance HR</div>
        </div>
      </div>
      <div class="art">
        <img src="${logoBase64}" alt="Logo">
      </div>
    </body>
    </html>
  `, { waitUntil: 'domcontentloaded' });
  await page.screenshot({ path: path.join(FEATURE_DIR, 'playstore_feature_graphic_1024x500.png') });

  // 3. Process the 8 Screenshots
  console.log('Processing Screenshots...');
  for (let i = 0; i < screens.length; i++) {
    const s = screens[i];
    console.log(`Processing [${i + 1}/8]: ${s.title}`);

    // Copy to raw directory
    const rawDest = path.join(RAW_DIR, s.rawName);
    fs.copyFileSync(s.rawSrc, rawDest);

    // Read screenshot image as base64 for embedding in showcase card
    const screenBuf = fs.readFileSync(s.rawSrc);
    const screenBase64 = `data:image/png;base64,${screenBuf.toString('base64')}`;

    // Render Showcase Promotional Card (1080 x 2400)
    await page.setViewport({ width: 1080, height: 2400, deviceScaleFactor: 1 });
    await page.setContent(`
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="utf-8">
        <style>
          * { margin:0; padding:0; box-sizing:border-box; }
          body {
            width: 1080px;
            height: 2400px;
            background: linear-gradient(180deg, #090e17 0%, #0f172a 35%, #0284c7 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 110px;
            overflow: hidden;
            position: relative;
          }
          .glow-top {
            position: absolute;
            top: -100px;
            width: 900px;
            height: 450px;
            background: radial-gradient(ellipse at center, rgba(14, 165, 233, 0.3) 0%, transparent 70%);
            z-index: 1;
          }
          .header {
            text-align: center;
            max-width: 920px;
            margin-bottom: 75px;
            position: relative;
            z-index: 2;
          }
          .tag {
            display: inline-block;
            background: rgba(14, 165, 233, 0.2);
            border: 1.5px solid rgba(56, 189, 248, 0.6);
            color: #38bdf8;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: 3px;
            text-transform: uppercase;
            padding: 12px 32px;
            border-radius: 40px;
            margin-bottom: 28px;
          }
          .title {
            font-size: 64px;
            font-weight: 900;
            line-height: 1.15;
            letter-spacing: -1px;
            margin-bottom: 20px;
          }
          .subtitle {
            font-size: 32px;
            line-height: 1.4;
            color: #94a3b8;
            font-weight: 500;
            padding: 0 20px;
          }
          .phone-frame {
            width: 900px;
            height: 1850px;
            background: #000000;
            border-radius: 64px;
            border: 12px solid #1e293b;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.7), 0 0 40px rgba(14, 165, 233, 0.3);
            overflow: hidden;
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
          }
          .phone-notch {
            height: 38px;
            background: #000000;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
          }
          .camera-dot {
            width: 14px;
            height: 14px;
            background: #1e293b;
            border-radius: 50%;
          }
          .phone-screen {
            flex: 1;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: top;
          }
        </style>
      </head>
      <body>
        <div class="glow-top"></div>
        <div class="header">
          <div class="tag">${s.tag}</div>
          <h1 class="title">${s.title}</h1>
          <p class="subtitle">${s.subtitle}</p>
        </div>
        <div class="phone-frame">
          <div class="phone-notch">
            <div class="camera-dot"></div>
          </div>
          <img class="phone-screen" src="${screenBase64}" alt="Screen">
        </div>
      </body>
      </html>
    `, { waitUntil: 'domcontentloaded' });

    const showcaseDest = path.join(SHOWCASE_DIR, s.rawName.replace('.png', '_showcase.png'));
    await page.screenshot({ path: showcaseDest });
    console.log(`Saved showcase: ${showcaseDest}`);
  }

  await browser.close();
  console.log('\n--- ALL PLAY STORE ASSETS COMPILED SUCCESSFULLY! ---');
}

buildAll().catch(console.error);
