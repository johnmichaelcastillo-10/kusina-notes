# Kusina Notes: design direction ("quiet enamel")

Source of truth for how the site looks and sounds. Read before touching the theme or any
plugin styles.

History: the first direction (paper, serif, rust accent) was too close to Terra & Thread.
The second ("enamelware and palengke": solid course-colour bands, thick 3px rims, tilted
cardboard tags, gingham behind the hero) had personality but the owner found it too
chaotic (2026-10-11). This version keeps the same ingredients and turns them down.

## How it differs from the sibling projects

| | Portfolio | Terra & Thread | **Kusina Notes** |
|---|---|---|---|
| Mood | Monochrome product polish | Earthy, crafted | **Calm, warm, a little funny: a Filipino home kitchen** |
| Colour | No hue at all | Parchment, clay, sage | **Enamel white, cobalt, a hint of colour per course** |
| Type | Schibsted Grotesk | Fraunces + Instrument Sans | **Bricolage Grotesque + Atkinson Hyperlegible Next, Caveat for one note** |
| Shape | Cards and shadows | Fine rules | **Round enamel plates, hairlines, pills** |
| Voice | Professional | Gentle, artisanal | **A tita who cooks: direct, teasing, a little Taglish** |

## Principles

1. **One interface colour.** Cobalt does links, buttons, focus, selected states and step
   numbers. Nothing else competes with it.
2. **Course colour is a hint, never a block.** It shows up as a small dot (course chips,
   crumbs, "Tonight", list rows), a soft 9% wash behind page heads, and the tinted disc on
   a plate without a photo. Never a solid band, solid chip or text colour on enamel.
3. **Photos carry the colour.** Food photos are the loudest thing on a page; the chrome
   stays neutral around them.
4. **Hairlines, not boxes.** 1px lines at 12% ink separate things. Fills (enamel-2) mark
   the few tool panels (scaler, pantry teaser, footer). No 3px borders anywhere.
5. **One playful detail per view.** The enamel plate, Tita's handwritten note, the "notes"
   wordmark, the gingham strip: each appears in its place and nowhere else. Nothing tilts
   except Tita's note (under 1°) and the wordmark.
6. **The recipe reads first.** No decoration inside ingredients or method text.

## Colour

| Role | Slug | Light | Dark ("kitchen at night") |
|---|---|---|---|
| Background | `enamel` | #FBFAF6 | #10162B |
| Surface (panels, footer) | `enamel-2` | #F0EEE6 | #19213D |
| Text | `ink` | #16213E | #F3F1EA |
| Muted text | `ink-2` | #4A5372 | #AEB5CC |
| Interface accent | `cobalt` | #1F3FA8 | #93A9FF |
| Text on cobalt | `on-cobalt` | #FFFFFF | #10162B |

Course hues (`course-<slug>` sets `--course`; used as dots, washes and plate discs only):

| Course (slug) | English | Hue |
|---|---|---|
| Ulam (`ulam`) | Mains | tomato #C93A26 |
| Sabaw (`sabaw`) | Soups | kangkong #2B7A4B |
| Merienda (`merienda`) | Snacks | mango #F4B62C |
| Panghimagas (`panghimagas`) | Desserts | ube #6B48B3 |
| Almusal (`almusal`) | Breakfast | egg yolk #EE8A27 |
| Inumin (`inumin`) | Drinks | calamansi #9CC43E |

Course names are Tagalog with the English gloss beside them (owner's choice). Search
engines get English categories via `kusina_courses()`.

Contrast (WCAG 2): ink on enamel 15.2, ink-2 on enamel 7.3, cobalt on enamel 8.6, white on
cobalt 9.0; dark text 15.9, dark muted 8.8, dark cobalt 8.0. The headline's tomato word is
4.9 on enamel and is brightened to #F0644C (5.7) on navy. Text never sits on a course hue.

## Type

All OFL, self-hosted woff2 (`assets/fonts/README.md`).

- **Bricolage Grotesque** for headings, weight 700, normal width, -0.02 to -0.035em
  tracking. Also ingredient amounts and step numbers (Atkinson has no ⅓ ⅔ ⅛).
- **Atkinson Hyperlegible Next** for body and UI. Body 1.125rem/1.6. Labels are small caps
  style: 0.75 to 0.8125rem, weight 700, 0.1em tracking, ink-2.
- **Caveat** only for Tita's note and the "notes" in the wordmark.

## Shape and detail

- **Plates**: photos crop round inside a white enamel rim with one thin cobalt hairline
  and a soft shadow. No photo: a course-tinted disc.
- **Tags**: flat pills, enamel-2 fill, ink text; diets get a faint green wash. No tilt, no
  shadow.
- **Chips**: outline pills with a course dot; selected = soft cobalt wash, cobalt border
  and a check.
- **Buttons**: pills, 1.5px cobalt outline or cobalt fill for the one primary action.
- **Page heads**: a 9% course wash (cobalt on non-course pages) with a hairline below.
- **Gingham**: one 14px oilcloth strip along the top of the footer.
- **Tita's note**: paper note in Caveat with a strip of tape, rotated 0.6°.
- Radius 14px for panels and photos; pills elsewhere.

## Voice

Written like a tita who cooks well and has opinions. Short sentences, second person,
teasing but never mean. Filipino food words stay as they are (ulam, sabaw, merienda).

| Place | Line |
|---|---|
| Home headline | "Ano'ng ulam?" |
| Pantry, nothing ticked | "Nothing yet. Add rice: there is always rice." |
| Cook mode finish | "Kain na!" (the only exclamation mark) |
| 404 | "Naubos na. Someone ate this page." |
| Empty list | "Your list is empty. Suspicious." |

No "elevate", "journey" or "delve"; no invented testimonials, ratings or "readers love";
no history or health claims. Jokes live in the chrome, never in the method.

## Guard rails

- No gradients (other than the plate disc), glass, emoji icons, cartoon chefs,
  three-identical-cards rows.
- Motion only on hover and focus (colour, a 4% photo zoom); none with reduced motion.
- Photos: **CC0 or public domain only**, matched to the dish (`scripts/images/README.md`).
