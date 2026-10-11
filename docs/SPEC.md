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

Look: a modern Filipino cookbook (rice paper, banana-leaf greens, Instrument Serif, drawn
banana leaves where photos are missing) with the voice of a tita who cooks. It must look nothing like the portfolio or
Terra & Thread. Full direction in [`DESIGN.md`](DESIGN.md).

## 2. Pages

| URL | Template | Content |
|---|---|---|
| `/` | `front-page.html` | Gingham hero with headline, "Tonight" pick (latest recipe) as a big plate, "Bahala na" random-recipe button, course chips as quick filters, latest 6 recipes as plates, pantry finder teaser |
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
- **Taxonomies** (hierarchical off, REST on): `course` (Tagalog: Ulam, Sabaw, Merienda,
  Panghimagas, Almusal, Inumin; list and English glosses in `kusina_courses()`), `diet` (Vegetarian, Pescatarian, Gluten-free, Dairy-free), `key_ingredient`
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

Progressive enhancement: with JS off the recipe is still complete and readable at its
default servings, and the controls stay hidden. Each feature is one self-contained ES
module with no imports (the static exporter only collects files the HTML references, and
no dependencies means WordPress prints no inline import map for the CSP to hash). The
recipe card's lives at `blocks/recipe-card/view.js` (block.json `viewScriptModule`).

1. **Scaler and units** (built, tested in Edge): `−`/`+` and a number input on servings,
   1 to 99. A three-way switch: As written / Metric / US. Rules: 1 cup = 240 ml; US mass
   goes to oz/lb, volume to cups/tbsp/tsp; piece/clove/pinch never convert. Scaled amounts
   snap to kitchen fractions and move between spoons and cups (¼ tbsp → ¾ tsp,
   8 tbsp → ½ cup). At the default servings in "As written", the server's own text is
   restored, so it always matches `kusina_format_qty()`. Unit choice remembered in
   `localStorage` (try/catch). Live region announces "Ingredients updated for 6 servings".
2. **Cook mode** (built, **not yet browser-tested**): full-screen `<dialog>` cloned from a
   server-rendered `<template>` (labels go through `__()`), one step per screen, swipe /
   arrow keys / big Next button, ingredients drawer (always visible from 64rem). Screen
   Wake Lock where supported, requested again when the tab returns. Timers: start/pause/
   reset per step, several at once (chips jump to other running timers), timestamps so
   throttled tabs don't drift, a Web Audio chime plus vibration, remaining time in the tab
   title.
3. **Ingredient tick boxes**: tap an ingredient to tick it off while cooking, in the card
   and the cook mode drawer (same state). Per recipe in `sessionStorage`, cleared on
   leaving.
4. **Bahala na**: a button on the home page and recipe index that opens a random recipe
   from the JSON index (never the one you're on).
5. **Recipe index (JSON)**: the plugin serves `GET /wp-json/kusina/v1/index` (slug, title,
   URL, thumbnail, course, diet, key ingredients, total minutes). The static export writes
   the same payload to `/recipes.json`; the JS fetches `recipes.json` on the static site and
   the REST route locally (same switch pattern as Terra & Thread's `staticApi()`).
6. **Filters** on `/recipes/`: course-coloured chips over the index, results update without
   reload, state mirrored in the query string, "n recipes" announced via `aria-live`.
7. **Pantry finder**: chips for key ingredients (grouped: proteins, vegetables, pantry);
   rank = matched ÷ needed, ties by total time; shows "missing: x, y".
8. **Shopping list**: "Add to list" stores `{slug, servings}`; `/list/` rebuilds lines from
   the index plus each recipe's ingredient data (add `ingredients` to the index), merges
   same name + unit, groups by aisle (a fixed map in the plugin), checkboxes, print button.
9. **Print**: `@media print` hides header, footer, buttons, photo optional; fits one A4
   page for typical recipes. Black on white: no course colours, gingham or tilted tags.

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

Moved to [`DESIGN.md`](DESIGN.md): enamelware and palengke, a colour per course,
Bricolage Grotesque + Atkinson Hyperlegible Next + Caveat, and the tita voice. The layout
rule stays: one column on phones; on desktop the recipe card is two columns (ingredients
left, method right). Ingredients are not sticky: a long list scrolled inside itself and got
cut off on short screens.

## 7. Static hosting

Same model as the other two projects: crawl local WordPress, export to `dist/`, deploy with
`vercel deploy dist --prod`. Reuse the exporter from `jmcastillo-portfolio/scripts/export-static.php`
(CSP hashing of inline scripts, localhost-link and PHP-warning checks) and adapt:

- Seed URLs: `/`, `/recipes/`, `/pantry/`, `/list/`, `/about/`, sitemap.
- Write `recipes.json` from the REST route into `dist/`, and rewrite the pantry block's
  `data-index` attribute (the REST URL) to `/recipes.json`.
- CSP: `default-src 'self'`, scripts by hash, `img-src 'self' data:`, `font-src 'self'`,
  `connect-src 'self'`, `form-action 'self'` (no forms yet), `frame-ancestors 'none'`.
- Nothing may need PHP at request time. Search uses the JSON index, not `?s=`.
- WordPress currently prints an inline emoji-loader `<script type="module">` plus a
  `wp-emoji-settings` JSON block on every page. Remove them in the theme (Terra & Thread did
  the same) rather than hashing them.

## 8. Launch content

**50 recipes**, the best-known dishes across every course: ulam 26, sabaw 6, merienda 7,
almusal 4, panghimagas 4, inumin 3; 25 have photos. Owner's call (2026-10-11): 1,022
were written and seeded first, then cut to these 50. The full 1,022 are in git at commit
`4e01e02` (`git show 4e01e02:scripts/recipes/<file>.json`) if any are wanted back: restore
the JSON, validate, re-seed.

- Data lives in `scripts/recipes/*.json` (shape and writing rules in that folder's
  README), file names kept from the original dish-family batches (01 adobo ... 21 inumin).
- `node scripts/validate-recipes.mjs` must pass before seeding: field rules, unit
  whitelist, key-ingredient vocabulary, diet claims checked against the ingredient list,
  duplicate slugs and titles across files. `--own <files>` checks only those files.
- `scripts/seed-content.php` (WP-CLI `eval-file`, `--user=admin`) seeds courses, diets,
  the About page and every recipe, idempotent by slug, about 30 s for all of them.
  Publish dates are one hour apart in a stable hash order so "latest" lists mix courses;
  chicken adobo is pinned newest (it's the home page's "Tonight").
- Diet labels are only vegetarian and pescatarian: no allergen claims.
- **Photos**: CC0/public-domain only (`scripts/images/`, sources in `*-credits.json`), each
  checked by eye to show that dish. 25 of 50 have one; the rest show the course-tinted
  plate. Add photos as matching ones are found; never a mismatched dish. The photo search
  was stopped by the owner on 2026-10-11.

## 9. Build order

1. **Done**: scaffold (Docker, theme that activates, content model).
2. **Done**: recipe card block (editor UI, server render, JSON-LD), recipe details panel,
   chicken adobo seed.
3. **Done**: scaler and unit switch; cook mode (browser-tested in Edge: keyboard through all
   steps, two timers at once with chips, one finishing via a skewed clock, finish closes and
   returns focus, Escape, phone layout and ingredients drawer). Wake lock: the API exists in
   Edge, but whether a lock is granted wasn't verifiable headless; check on a phone.
4. **Done**: theme in the new direction: theme.json tokens and course colours, dark mode,
   self-hosted fonts, header/footer patterns, gingham, market tags (`kusina/recipe-tags`),
   course chips (`kusina/course-chips`), templates for home, recipe, recipes index, course,
   other taxonomies, page and 404, recipe card and cook mode restyled, emoji loader removed,
   About page seeded. Checked at 1440 and 390 px, light and dark.
5. **Done**: pantry finder (`/pantry/`, block `kusina/pantry-finder`, index at
   `/wp-json/kusina/v1/index`) and shopping list (`/list/`, block `kusina/shopping-list`,
   "Add to shopping list" on every recipe, header count). Theme redesigned calmer (DESIGN.md).
6. JS still open: ingredient tick boxes, recipe index filters, Bahala na.
7. **Done**: 50 recipes seeded, 25 with CC0/PD photos (section 8). Open: photos for the
   other 25 where matching ones exist.
8. **Done**: exporter, CSP, Vercel project, `Deploy.cmd`. Live at https://kusina-notes.vercel.app
   (2026-10-11). Open: Open Graph / Twitter meta tags (WordPress prints none; section 5).
9. Add the project to the portfolio (screenshots 1920×1200 WebP).

## 10. Done means

- No PHP warnings/notices with `WP_DEBUG=1` on any page.
- Every feature works on the live WordPress and on the static build.
- Lighthouse mobile: performance ≥ 95, accessibility 100, SEO 100, light and dark.
- Recipe JSON-LD passes the Rich Results Test with no errors.
- Checked at 390 px, 768 px and 1440 px; keyboard-only run through cook mode.
