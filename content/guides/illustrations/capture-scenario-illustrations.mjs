import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath, pathToFileURL } from 'node:url';

// Playwright is a local authoring tool, not an application dependency.
const require = createRequire(import.meta.url);
const { chromium } = require(process.argv[3] || 'playwright');
const output = path.resolve(process.argv[2] || 'storage/app/illustrations-next');
const publicRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../../public');
const manifest = JSON.parse(fs.readFileSync(path.join(output, 'manifest.json'), 'utf8'));
function jpegSize(buffer) {
  for (let offset = 2; offset < buffer.length;) {
    const marker = buffer.readUInt16BE(offset);
    const length = buffer.readUInt16BE(offset + 2);
    if ([0xffc0, 0xffc1, 0xffc2].includes(marker)) {
      return { height: buffer.readUInt16BE(offset + 5), width: buffer.readUInt16BE(offset + 7) };
    }
    offset += 2 + length;
  }
  throw new Error('JPEG dimensions not found');
}
const browser = await chromium.launch({ headless: true, channel: process.env.ILLUSTRATION_BROWSER || 'msedge' });
try {
  for (const item of manifest) {
    const page = await browser.newPage({ viewport: { width: item.width, height: 1000 }, deviceScaleFactor: 1 });
    await page.route(/^https?:/, route => route.abort());
    await page.goto(pathToFileURL(path.join(output, item.filename)).href);
    await page.evaluate(() => document.fonts.ready);
    const overflow = await page.evaluate(() => [...document.querySelectorAll('.box,.field,.setting')]
      .filter(e => e.scrollWidth > e.clientWidth + 1).map(e => e.textContent));
    if (overflow.length) throw new Error(`Overflow in ${item.filename}: ${overflow.join('; ')}`);
    const canvas = page.locator('.canvas');
    item.src = `/images/guides/${item.slug}/${item.name}${item.mobile ? '-mobile' : ''}.jpg`;
    const file = path.join(publicRoot, item.src);
    fs.mkdirSync(path.dirname(file), { recursive: true });
    const jpeg = await canvas.screenshot({ path: file, type: 'jpeg', quality: 90 });
    const size = jpegSize(jpeg);
    if (size.width !== item.width) throw new Error(`Unexpected screenshot width: ${file}`);
    item.height = size.height;
    item.bytes = fs.statSync(file).size;
    console.log(`${item.src}: ${item.width}x${item.height}, ${Math.round(item.bytes / 1024)} KiB`);
    await page.close();
  }
} finally {
  await browser.close();
}
fs.writeFileSync(path.join(output, 'exported.json'), JSON.stringify(manifest, null, 2) + '\n');
