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

Personality: a loud, warm Filipino home kitchen (enamelware, palengke signs, gingham
oilcloth) with the voice of a tita who cooks. It must look nothing like the portfolio or
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
sticky left, method right).

## 7. Static hosting

Same model as the other two projects: crawl local WordPress, export to `dist/`, deploy with
`vercel deploy dist --prod`. Reuse the exporter from `jmcastillo-portfolio/scripts/export-static.php`
(CSP hashing of inline scripts, localhost-link and PHP-warning checks) and adapt:

- Seed URLs: `/`, `/recipes/`, `/pantry/`, `/list/`, `/about/`, sitemap.
- Write `recipes.json` from the REST route into `dist/`.
- CSP: `default-src 'self'`, scripts by hash, `img-src 'self' data:`, `font-src 'self'`,
  `connect-src 'self'`, `form-action 'self'` (no forms yet), `frame-ancestors 'none'`.
- Nothing may need PHP at request time. Search uses the JSON index, not `?s=`.
- WordPress currently prints an inline emoji-loader `<script type="module">` plus a
  `wp-emoji-settings` JSON block on every page. Remove them in the theme (Terra & Thread did
  the same) rather than hashing them.

## 8. Launch content

12 recipes, at least 3 vegetarian, at least 4 under 30 minutes, spread over all courses.
Suggested: chicken adobo, pork sinigang, tortang talong, ginisang monggo, pancit canton,
arroz caldo, tokwa't baboy, ginataang kalabasa at sitaw, garlic fried rice (sinangag),
turon, leche flan, calamansi juice. Each needs a matching CC0 photo; if none exists for a
dish, swap the dish rather than use a mismatched photo.

Seeded by a WP-CLI `eval-file` script (`scripts/seed-content.php`), idempotent by slug.

## 9. Build order

1. **Done**: scaffold (Docker, theme that activates, content model).
2. **Done**: recipe card block (editor UI, server render, JSON-LD), recipe details panel,
   chicken adobo seed.
3. **Mostly done**: scaler and unit switch (tested); cook mode built, needs a browser test
   pass (step navigation, swipe, timers incl. one finishing, wake lock, focus return on close,
   390 px).
4. **Next: theme in the new direction** (`DESIGN.md`): theme.json tokens (enamel, ink,
   cobalt, course colours, dark mode), self-hosted fonts, header and footer (Custom HTML
   patterns, no Navigation block), gingham pattern, market tags, `single-recipe.html` with
   the course-colour band. Then **restyle the recipe card** (its `style.css` still uses the
   old paper/annatto look) and cook mode to match. Course colour reaches the card via a
   `course-<slug>` class on the page.
5. Home, recipes index, taxonomy, pantry, list, about and 404 templates, written in the
   voice.
6. JS: tick boxes, then the index route, filters, Bahala na, pantry, list.
7. Seed the other 11 recipes and CC0 photos (overhead shots, for the plate crops).
8. Exporter, CSP, emoji loader removal, Vercel project, `Deploy.cmd`.
9. Add the project to the portfolio (screenshots 1920×1200 WebP).

## 10. Done means

- No PHP warnings/notices with `WP_DEBUG=1` on any page.
- Every feature works on the live WordPress and on the static build.
- Lighthouse mobile: performance ≥ 95, accessibility 100, SEO 100, light and dark.
- Recipe JSON-LD passes the Rich Results Test with no errors.
- Checked at 390 px, 768 px and 1440 px; keyboard-only run through cook mode.
