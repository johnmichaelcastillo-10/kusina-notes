// README screenshots (docs/screenshot-*.webp): 1280x800 CSS px at 1.5x = 1920x1200, light
// (plus a dark home page), reduced motion, from the local site on :8092.
//
//   node scripts/readme-screenshots.cjs <outDir>
//
// Writes kn-readme-<name>.png; convert each to docs/screenshot-<name>.webp with GD at q80
// (php -r "imagewebp(imagecreatefrompng('in.png'), 'out.webp', 80);"). Uses the
// playwright-core that ships with the global playwright-cli install, and Edge.
const path = require('path');
const { chromium } = require(path.join(process.env.APPDATA, 'npm/node_modules/@playwright/cli/node_modules/playwright-core'));

const out = process.argv[2];
const base = 'http://localhost:8092';

async function session(browser, colorScheme) {
	const context = await browser.newContext({ viewport: { width: 1280, height: 800 }, deviceScaleFactor: 1.5, colorScheme, reducedMotion: 'reduce' });
	const page = await context.newPage();
	page.settle = async () => {
		await page.evaluate(async () => {
			for (let y = 0; y < document.body.scrollHeight; y += 500) {
				window.scrollTo(0, y);
				await new Promise((r) => setTimeout(r, 60));
			}
			window.scrollTo(0, 0);
		});
		await page.waitForTimeout(700);
	};
	page.scrollToEl = async (sel, top) => {
		await page.evaluate(([s, t]) => {
			const el = document.querySelector(s);
			window.scrollTo(0, el.getBoundingClientRect().top + window.scrollY - t);
		}, [sel, top]);
		await page.waitForTimeout(400);
	};
	page.shot = async (name) => {
		await page.mouse.move(1275, 795);
		await page.screenshot({ path: path.join(out, name + '.png') });
	};
	return { context, page };
}

(async () => {
	const browser = await chromium.launch({ channel: 'msedge' });

	const dark = await session(browser, 'dark');
	await dark.page.goto(base + '/');
	await dark.page.settle();
	await dark.page.shot('kn-readme-home-dark');
	await dark.context.close();

	const { page } = await session(browser, 'light');
	await page.goto(base + '/');
	await page.settle();
	await page.shot('kn-readme-home');

	await page.goto(base + '/recipes/chicken-adobo/');
	await page.settle();
	await page.locator('.recipe-card .ingredient-text').nth(1).click();
	await page.locator('.recipe-card .ingredient-text').nth(2).click();
	await page.evaluate(() => document.activeElement.blur());
	await page.scrollToEl('.recipe-facts', 8);
	await page.shot('kn-readme-recipe');

	await page.click('[data-cook-open]');
	for (let i = 0; i < 4; i++) await page.keyboard.press('ArrowRight');
	await page.click('[data-cook=timer-start]');
	await page.waitForTimeout(2300);
	await page.evaluate(() => document.activeElement.blur());
	await page.shot('kn-readme-cook');
	await page.keyboard.press('Escape');

	await page.goto(base + '/pantry/');
	await page.evaluate(() => localStorage.removeItem('kusina-pantry'));
	await page.reload();
	await page.waitForSelector('.pantry-chip:not([disabled])');
	await page.click('.pantry-chip[data-key=chicken]');
	await page.click('.pantry-chip[data-key=carrot]');
	await page.evaluate(() => document.activeElement.blur());
	await page.settle();
	await page.scrollToEl('.pantry', 24);
	console.log('pantry', await page.textContent('[data-pantry-summary]'));
	await page.shot('kn-readme-pantry');

	await page.goto(base + '/recipes/?course=ulam&max=60');
	await page.waitForSelector('[data-filters]:not([hidden])');
	await page.settle();
	await page.scrollToEl('.page-head .eyebrow', 48);
	console.log('filters', await page.textContent('[data-filter-summary]'));
	await page.shot('kn-readme-filters');

	await browser.close();
})();
