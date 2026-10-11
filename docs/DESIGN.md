# Kusina Notes: design direction ("banana leaf editorial")

Source of truth for how the site looks and sounds. Read before touching the theme or any
plugin styles.

History (all 2026-10-11): paper/serif/rust was too close to Terra & Thread; "enamelware and
palengke" (solid course colours, thick rims, tilted tags) was too chaotic; "quiet enamel"
was calmer but not aesthetic enough. The owner picked this direction from three options.

## Idea

A modern Filipino cookbook. Rice-paper white pages, banana-leaf greens, an elegant serif
for everything you read as a title, a clean sans for everything you cook from. Photos are
large and rounded; where a dish has no photo yet, a drawn banana leaf takes its place, so
the page never looks unfinished. Restraint everywhere else.

| | Portfolio | Terra & Thread | **Kusina Notes** |
|---|---|---|---|
| Mood | Monochrome product polish | Earthy, crafted | **Calm, green, editorial cookbook** |
| Colour | No hue | Parchment, clay, sage | **Rice paper, banana-leaf greens, nothing else** |
| Type | Schibsted Grotesk | Fraunces + Instrument Sans | **Instrument Serif + Figtree, Caveat for one note** |
| Signature | Code hero photo | Product customiser | **Drawn banana leaf, green italic word** |

## Colour

One family of greens on rice paper. No per-course colours any more (course is a small
uppercase label in green).

| Role | Slug | Light | Dark |
|---|---|---|---|
| Page | `paper` | #F7F7F2 | #0F1712 |
| Panels (ingredients, pantry, placeholders) | `paper-2` | #ECEFE5 | #17221B |
| Text | `ink` | #17221C | #ECEFE7 |
| Muted text | `ink-2` | #55615A | #A9B5AC |
| Accent: links, buttons, italics, step numbers | `leaf` | #2D5A41 | #9CC79A |
| Text on leaf | `on-leaf` | #FFFFFF | #0F1712 |
| Footer, teaser, hover on primary | `leaf-deep` | #1E3B2C | #0B120E |
| Chips, time pills, selected states | `leaf-soft` | #DCE6D2 | #22332A |

Contrast (WCAG 2): ink on paper 15.2, ink-2 on paper 6.0 (5.6 on paper-2), leaf on paper
7.4, white on leaf 7.9, paper on leaf-deep 11.4, #B9CCB0 on leaf-deep 7.2; dark mode text
15.7, muted 8.6, leaf 9.6. All AA for body text.

## Type

All OFL, self-hosted (`assets/fonts/README.md`).

- **Instrument Serif** (one weight, plus italic) for headings, titles, the wordmark, the
  intro paragraph, facts values and step numbers. Never bold it (faux bold smears).
  Headlines can end in one **green italic word**: "Ano'ng ulam *tonight?*",
  "Fresh from the *kusina*". One per heading, at most.
- **Figtree** for body, UI and ingredient amounts (it has ⅓ ⅔ ⅛; the serif doesn't).
  Labels: 0.6875 to 0.75rem, weight 600, 0.16em tracking, uppercase.
- **Caveat** only for Tita's note.

## Shape and detail

- Photos: 4:5 portrait in tiles and the recipe hero, radius 18px (28px for the big ones),
  one soft shadow on hero images only. Slight zoom on hover.
- No photo: `paper-2` with the drawn banana leaf (`assets/art/leaf-*.svg`, three
  colourways). On a recipe page without a photo the media column goes away and the leaf
  sits faintly behind the title.
- Tiles: photo, green course label, serif title, a quiet meta line ("1 hr · Vegetarian").
  No pills, no boxes.
- Buttons: pills. Primary = leaf fill; secondary = 1px neutral outline.
- Panels: `paper-2`, radius 26px (ingredients, pantry picker).
- Deep-green blocks (footer, pantry teaser) carry a large faint leaf in a corner.
- Hairlines (11% ink) separate sections; nothing else draws borders.
- Tita's note: paper note with tape, Caveat, rotated -0.8°.

## Voice

Written like a tita who cooks well and has opinions. Short sentences, second person,
teasing but never mean. Filipino food words stay as they are (ulam, sabaw, merienda).

| Place | Line |
|---|---|
| Home headline | "Ano'ng ulam *tonight?*" |
| Pantry, nothing ticked | "Nothing yet. Add rice: there is always rice." |
| Cook mode finish | "Kain na!" (the only exclamation mark) |
| 404 | "Naubos *na.* Someone ate this page." |
| Empty list | "Your list is empty. Suspicious." |

No "elevate", "journey" or "delve"; no invented testimonials, ratings or "readers love";
no history or health claims. Jokes live in the chrome, never in the method.

## Guard rails

- Greens and neutrals only; photos bring the other colours.
- No gradients, glass, emoji icons, cartoon chefs, rotated tags.
- Motion: colour and underline transitions, a 3.5% photo zoom on hover; nothing with
  reduced motion.
- Photos: AI-generated in one house style (`scripts/images/README.md`), one per recipe,
  each checked to show that dish. The site says they are AI-generated.
  Recipes with photos are dated newest so lists lead with them.
