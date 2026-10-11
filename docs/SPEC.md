# Kusina Notes: build spec

The source of truth for what to build. Change it when a decision changes.

## 1. Concept

A recipe journal for Filipino everyday cooking and weeknight staples. The recipe is the
page: title, photo, times, servings, then ingredients and steps. Intro text is at most two
short paragraphs. The site is fictional (a personal portfolio piece) but every recipe must be
genuinely cookable, with realistic quantities and times.

Audience: someone cooking on a phone propped against the counter, often with wet hands.
That drives every interaction decision: big tap targets, no hover-only controls, nothing
that moves the page while they read.

## 2. Pages

| URL | Template | Content |
|---|---|---|
| `/` | `front-page.html` | Short intro, "Tonight" pick (latest recipe), quick filters (≤30 min, Vegetarian, Merienda), latest 6 recipes, pantry finder teaser |
| `/recipes/` | `archive-recipe.html` | All recipes, client-side filters (course, diet, time), URL query state (`?course=ulam&max=30`) |
| `/recipes/<slug>/` | `single-recipe.html` | Recipe card, cook mode, add to list, print, related recipes (same course) |
| `/course/<slug>/`, `/diet/<slug>/` | `taxonomy-*.html` | Filtered index, server-rendered |
| `/pantry/` | page + pattern | Pantry finder |
| `/list/` | page + pattern | Shopping list |
| `/about/` | `page.html` | What the site is, how recipes are tested |
| 404 | `404.html` | Search plus three recipe links |

Blog posts are off: no `post` archive, no comments anywhere.

## 3. Content model (plugin `kusina-core`)

- **Post type `recipe`**: public, archive `recipes`, REST on, supports title, editor,
  excerpt, thumbnail, revisions. Scaffolded.
- **Taxonomies** (hierarchical off, REST on): `course` (Breakfast, Ulam, Soup, Merienda,
  Dessert, Drink), `diet` (Vegetarian, Pescatarian, Gluten-free, Dairy-free), `key_ingredient`
  (chicken, pork, egg, tofu, rice, coconut milk, ...; feeds the pantry finder). Scaffolded.
- **Meta** (registered, REST on, single): `prep_minutes` int, `cook_minutes` int,
  `servings` int (default 4), `yield_label` string (e.g. "pieces"), `difficulty`
  enum `easy|medium|involved`. Scaffolded.
- **Block `kusina/recipe-card`** (dynamic, server-rendered, one per recipe). Attributes:
  - `groups`: `[{ title, items: [{ qty, unit, name, note }] }]`. `qty` is a number or a
    `[min, max]` range; `unit` comes from a fixed list (g, kg, ml, l, tsp, tbsp, cup,
    piece, clove, thumb, pinch, to taste) so conversion is reliable.
  - `steps`: `[{ text, minutes }]`. `minutes` is optional and creates a timer.
  - `tips`: rich text.
  Editor UI in plain JS using the `wp.*` globals (no build step): repeatable rows, unit
  select, number inputs. Times and servings stay in post meta (sidebar panel) so the archive
  and JSON index can read them without parsing blocks.

## 4. Interactive features

All in `assets/js/` as small ES modules, progressive enhancement: with JS off the recipe is
still complete and readable at its default servings.

1. **Scaler and units**: `−`/`+` and a number input on servings. Quantities rendered as
   `data-qty`/`data-unit` spans; JS rewrites the text. Display rounds to kitchen fractions
   (⅛ steps for tsp/tbsp/cup, whole grams over 50 g). US ↔ metric for mass and volume only;
   piece/clove/pinch never convert. Choice remembered in `localStorage` (try/catch).
2. **Cook mode**: full-screen dialog, one step per screen, swipe / arrow keys / big
   Next button, ingredient list in a drawer. Screen Wake Lock API where supported (silent
   fallback). Timers: start/pause/reset per step, several can run; a chime from the Web
   Audio API (no audio file) and the tab title shows the remaining time.
3. **Recipe index (JSON)**: the plugin serves `GET /wp-json/kusina/v1/index` (slug, title,
   URL, thumbnail, course, diet, key ingredients, total minutes). The static export writes
   the same payload to `/recipes.json`; the JS fetches `recipes.json` on the static site and
   the REST route locally (same switch pattern as Terra & Thread's `staticApi()`).
4. **Filters** on `/recipes/`: checkbox groups over the index, results update without
   reload, state mirrored in the query string, "n recipes" announced via `aria-live`.
5. **Pantry finder**: chips for key ingredients (grouped: proteins, vegetables, pantry);
   rank = matched ÷ needed, ties by total time; shows "missing: x, y".
6. **Shopping list**: "Add to list" stores `{slug, servings}`; `/list/` rebuilds lines from
   the index plus each recipe's ingredient data (add `ingredients` to the index), merges
   same name + unit, groups by aisle (a fixed map in the plugin), checkboxes, print button.
7. **Print**: `@media print` hides header, footer, buttons, photo optional; fits one A4
   page for typical recipes.

## 5. SEO and sharing

- `Recipe` JSON-LD on each recipe from the same block data: name, image, description,
  prepTime/cookTime/totalTime (ISO 8601), recipeYield, recipeIngredient, recipeInstructions
  (HowToStep), recipeCategory (Ulam and Merienda mapped to English for search engines),
  keywords, suitableForDiet (vegetarian, gluten-free only). No recipeCuisine: there is no
  cuisine taxonomy. **No `aggregateRating` or reviews**:
  there are none, and inventing them breaks the content rule.
- Open Graph and Twitter tags, canonical, sitemap (users sitemap disabled).
- Validate with Google's Rich Results Test (paste the HTML: `*.vercel.app` is blocked on the
  owner's network).

## 6. Design direction

Editorial cookbook, warm and quiet. Paper background, ink text, one strong accent.

- Palette (theme.json slugs): `paper` #F7F2E8, `paper-2` #EFE7D7, `ink` #221C15,
  `ink-2` #5A5046, `annatto` #B4441C (accent, links, buttons), `calamansi` #5E7A2C
  (vegetarian/diet badges, timer running). Dark mode: `paper` #18140F, `ink` #EDE5D6,
  `annatto` #E07A4F. Check all text pairs for WCAG AA in both modes.
- Type: **Newsreader** (display, headings, optical sizes) and **Public Sans** (body, UI),
  both OFL, self-hosted woff2, subset to Latin + Filipino characters (ñ, ng is plain).
- Layout: single column on phones; on desktop the recipe card is two columns
  (ingredients sticky left, steps right). Step numbers set large in Newsreader.
- Photos: overhead or 45° food shots, warm light, cropped 3:2. **CC0 or public domain only**,
  no credits on the page (sources listed in `CREDITS.md` in the repo).
- Avoid the generic AI look: no gradient blobs, no glassmorphism, no emoji icons, no
  three-identical-cards rows, no "Elevate your cooking" copy. Icons: Lucide (ISC), inline SVG.
- Motion: none needed beyond focus/hover states; respect `prefers-reduced-motion`.

## 7. Static hosting

Same model as the other two projects: crawl local WordPress, export to `dist/`, deploy with
`vercel deploy dist --prod`. Reuse the exporter from `jmcastillo-portfolio/scripts/export-static.php`
(CSP hashing of inline scripts, localhost-link and PHP-warning checks) and adapt:

- Seed URLs: `/`, `/recipes/`, `/pantry/`, `/list/`, `/about/`, sitemap.
- Write `recipes.json` from the REST route into `dist/`.
- CSP: `default-src 'self'`, scripts by hash, `img-src 'self' data:`, `font-src 'self'`,
  `connect-src 'self'`, `form-action 'self'` (no forms yet), `frame-ancestors 'none'`.
- Nothing may need PHP at request time. Search uses the JSON index, not `?s=`.

## 8. Launch content

12 recipes, at least 3 vegetarian, at least 4 under 30 minutes, spread over all courses.
Suggested: chicken adobo, pork sinigang, tortang talong, ginisang monggo, pancit canton,
arroz caldo, tokwa't baboy, ginataang kalabasa at sitaw, garlic fried rice (sinangag),
turon, leche flan, calamansi juice. Each needs a matching CC0 photo; if none exists for a
dish, swap the dish rather than use a mismatched photo.

Seeded by a WP-CLI `eval-file` script (`scripts/seed-content.php`), idempotent by slug.

## 9. Build order

1. Scaffold (done): Docker, theme that activates, content model.
2. Recipe card block: editor UI, server render, JSON-LD.
3. Theme: tokens, fonts, header/footer, all templates.
4. JS: scaler/units, cook mode, then index route + filters, pantry, list.
5. Seed content and photos.
6. Exporter, CSP, Vercel project, `Deploy.cmd`.
7. Add the project to the portfolio (screenshots 1920×1200 WebP).

## 10. Done means

- No PHP warnings/notices with `WP_DEBUG=1` on any page.
- Every feature works on the live WordPress and on the static build.
- Lighthouse mobile: performance ≥ 95, accessibility 100, SEO 100, light and dark.
- Recipe JSON-LD passes the Rich Results Test with no errors.
- Checked at 390 px, 768 px and 1440 px; keyboard-only run through cook mode.
