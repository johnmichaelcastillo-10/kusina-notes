# Kusina Notes: design direction

Source of truth for how the site looks and sounds. Read before touching the theme or the
recipe card styles. Replaces the first direction (paper, serif, one rust accent), which was
too close to Terra & Thread Studio (parchment, Fraunces, clay).

## How it differs from the sibling projects

| | Portfolio | Terra & Thread | **Kusina Notes** |
|---|---|---|---|
| Mood | Calm, monochrome product polish | Quiet, earthy, crafted | **Loud, warm, funny: a Filipino home kitchen** |
| Colour | No hue at all | Parchment, clay, sage | **Enamel white, cobalt rims, one bright colour per course** |
| Type | Schibsted Grotesk | Fraunces serif + Instrument Sans | **Bricolage Grotesque (chunky, variable width) + Atkinson Hyperlegible Next + Caveat for notes** |
| Shape | Soft cards, shadows | Fine rules, generous space | **Thick rims, round "plates", tilted market tags, gingham** |
| Voice | Professional, factual | Gentle, artisanal | **A tita who cooks: direct, teasing, a little Taglish** |

## Concept: enamelware and the palengke

Two things anyone who grew up in a Filipino kitchen will recognise, used as the system:

1. **Enamelware** (the white peltre plates and basins with blue rims, chipped at the edge).
   This gives the base: crisp off-white surfaces, thick cobalt borders with big radii, and
   photos cropped round like food on a plate.
2. **The palengke** (wet market): hand-lettered cardboard price signs, bright plastic
   basins, gingham oilcloth on the table. This gives the accents: tilted tags for time and
   diet, colour-blocked sections, and a gingham band in the hero and footer.

Personality comes from three places, in this order: **colour per course**, **the voice**,
**a few playful details**. Everything else stays plain so the recipe still reads first.

## Colour

Light mode is enamel white with navy ink; every course owns one bright colour. On a
recipe page the header band takes the course colour, so Ulam pages are tomato red and
desserts are ube purple: the site gets louder without one-off decoration.

| Role | Slug | Light | Dark ("kitchen at night") |
|---|---|---|---|
| Background | `enamel` | #FBFAF6 | #10162B |
| Surface (cards, drawer) | `enamel-2` | #F0EEE6 | #19213D |
| Text | `ink` | #16213E | #F3F1EA |
| Muted text | `ink-2` | #4A5372 | #AEB5CC |
| Rim, links, primary buttons | `cobalt` | #1F3FA8 | #93A9FF |
| Market tag | `cardboard` | #E2C48F | #E2C48F (ink text in both modes) |

Course colours (`course-<slug>` in theme.json; the text colour is fixed per course):

Course names are Tagalog on the site, with the English gloss beside them on the course
chips and as the course page's description (owner's choice, 2026-10-11). Search engines get
English categories via `kusina_courses()` in the plugin.

| Course (slug) | English | Colour | Text on it |
|---|---|---|---|
| Ulam (`ulam`, tomato) | Mains | #C93A26 | white |
| Sabaw (`sabaw`, kangkong) | Soups | #2B7A4B | white |
| Merienda (`merienda`, mango) | Snacks | #F4B62C | ink |
| Panghimagas (`panghimagas`, ube) | Desserts | #6B48B3 | white |
| Almusal (`almusal`, egg yolk) | Breakfast | #EE8A27 | ink |
| Inumin (`inumin`, calamansi) | Drinks | #9CC43E | ink |

Contrast, measured (WCAG 2): ink on enamel 15.2, ink-2 on enamel 7.3, cobalt on enamel 8.6,
white on cobalt 9.0, ink on cardboard 9.5. Course pairs above: 5.1 to 8.8. Dark text 15.9,
dark muted 8.8, dark cobalt 8.0. All pass AA for body text. The home headline's tomato word is 4.9 on enamel but only 3.5 on navy, so dark mode brightens it to #F0644C (5.7). Course colours are fills, never
text colours on enamel (several fail there).

## Type

All OFL, self-hosted woff2, Latin subset (covers ñ).

- **Bricolage Grotesque** (variable: weight, width, optical size) for display. Headlines
  at weight 800, width ~90 for recipe titles; market tags and step numbers at width 75 (condensed,
  like a hand-cut sign). Sentence case, `text-wrap: balance`.
- **Atkinson Hyperlegible Next** for body and UI. Designed for low vision: the right pick
  for reading a recipe from across the counter. Body 1.125rem/1.6, ingredients 1.0625rem.
- **Caveat** only for Tita's notes (the recipe tips) and the occasional handwritten
  annotation on the home page. Never for anything that must be read at speed.

## Shape and detail

- **Rims**: cards and the cook mode panels have a 3px cobalt border and 20px radius, like
  an enamel basin. Buttons are pills with a 2px rim.
- **Plates**: recipe photos crop to circles on index pages (a grid of plates on the table),
  3:2 with rounded corners on the recipe page itself.
- **Market tags**: time, diet and difficulty sit on cardboard-coloured tags in condensed
  Bricolage, uppercase, rotated between -3° and 2° (a fixed rotation per tag type, never
  random per page load, so screenshots and layout stay stable).
- **Gingham**: a two-colour oilcloth check (SVG pattern, cobalt on enamel) in the hero band
  and the footer. Nowhere else.
- **Chip**: the enamel chip, a small irregular dark spot on the rim of the hero plate. Used
  once.
- **Tita's note**: the tips box is a taped paper note in Caveat, rotated 1°, with a strip
  of "tape". This replaces the current grey tips box.
- **Step numbers**: big condensed Bricolage numerals in the course colour.
- Icons: Lucide (ISC), inline SVG, 2px stroke to match the rims.

## Voice

Written like a tita who cooks well and has opinions. Short sentences, second person,
teasing but never mean. Filipino food words stay as they are (ulam, sabaw, merienda,
sawsawan) without italics, with the meaning clear from the sentence.

| Place | Example |
|---|---|
| Home headline | "Ano'ng ulam? Dinner in under an hour, no life story first." |
| Random pick button | "Bahala na" (it's up to fate) |
| Empty pantry result | "Nothing yet. Add rice: there is always rice." |
| Cook mode finish | "Kain na!" (let's eat) |
| 404 | "Naubos na. Someone ate this page." |
| Shopping list empty | "Your list is empty. Suspicious." |

Rules: no "elevate", "journey" or "delve"; no exclamation marks except "Kain na!"; no
invented testimonials, ratings or "our readers love". Recipe copy stays plain and precise:
the joking is in the chrome, not the method.

## Page sketches

- **Home**: gingham band; giant headline; the "Tonight" recipe as a large round plate
  overlapping the band; a "Bahala na" button that jumps to a random recipe; course
  colour chips as quick filters; the latest recipes as a grid of plates with tags; a strip
  for the pantry finder with a handwritten arrow.
- **Recipe**: header band in the course colour with the title in Bricolage and the tags;
  photo below, overlapping the band; then the recipe card (ingredients with tick boxes left,
  method right, Tita's note last).
- **Cook mode**: full screen in the course colour at a very light tint, step text huge in
  Atkinson, step number in condensed Bricolage, timers as big pill buttons. Finish button
  says "Kain na!".
- **Recipes index**: plates grid, filters as course-coloured chips.

## Guard rails

- The recipe stays the most readable thing on the page: no decoration inside the
  ingredient list or method text.
- One playful detail per view, at most two. If a section has a tilted tag, it doesn't also
  get gingham or handwriting.
- No gradients, glassmorphism, emoji icons, stock "chef" illustrations, or
  three-identical-cards rows.
- Motion: none beyond focus and hover; tags don't wiggle. `prefers-reduced-motion` respected
  anyway.
- Photos: **CC0 or public domain only**, overhead shots preferred (they crop well as
  plates). Sources in `CREDITS.md`, no credits on the page.
