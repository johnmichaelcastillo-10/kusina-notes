# Fonts

All SIL Open Font License 1.1 (licence texts alongside). Self-hosted because the static
site's CSP allows no third-party hosts. Downloaded 2026-10-11 from Google Fonts' CSS2 API
(woff2), Latin subset only.

| File | Family | Axes | Use |
|---|---|---|---|
| `instrument-serif.woff2` | Instrument Serif | regular only | Headlines, wordmark, card titles |
| `instrument-serif-italic.woff2` | Instrument Serif Italic | regular only | The green italic word in headlines |
| `figtree.woff2` | Figtree | wght 300–800 | Body, UI, ingredient amounts |
| `figtree-italic.woff2` | Figtree Italic | wght 300–800 | Body italic |
| `figtree-fractions.woff2` | Figtree | wght 300–800 | Only U+2150–215E (⅓ ⅔ ⅛ ⅜ ⅝ ⅞ …) |
| `caveat.woff2` | Caveat | wght 400–700 | Tita's note only |

Instrument Serif has no ⅓-style fraction glyphs (Google Fonts returns 400 for a `text=`
request with them), so ingredient amounts are set in Figtree. Figtree has them, but its
Latin file leaves them out, so the fractions file adds them under the same family name
with a `unicode-range`.

To refresh: request `https://fonts.googleapis.com/css2?family=<family>:<axes>` with a
current Chrome user agent, take the `/* latin */` block's `url()`, and for the fractions
file add `&text=⅐⅑⅒⅓⅔⅕⅖⅗⅘⅙⅚⅛⅜⅝⅞` (URL-encoded).
