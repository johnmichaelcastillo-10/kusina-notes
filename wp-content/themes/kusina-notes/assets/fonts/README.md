# Fonts

All SIL Open Font License 1.1 (licence texts alongside). Self-hosted because the static
site's CSP allows no third-party hosts. Downloaded 2026-10-11 from Google Fonts' CSS2 API
(woff2, variable), Latin subset only (U+0000-00FF plus common punctuation; covers ñ).

| File | Family | Axes | Use |
|---|---|---|---|
| `bricolage-grotesque.woff2` | Bricolage Grotesque | opsz 12–96, wdth 75–100, wght 200–800 | Display, tags, step numbers, ingredient amounts |
| `bricolage-grotesque-fractions.woff2` | Bricolage Grotesque | same | Only U+2150–215E (⅓ ⅔ ⅛ ⅜ ⅝ ⅞ …) |
| `atkinson-hyperlegible-next.woff2` | Atkinson Hyperlegible Next | wght 200–800 | Body and UI |
| `atkinson-hyperlegible-next-italic.woff2` | Atkinson Hyperlegible Next Italic | wght 200–800 | Body italic |
| `caveat.woff2` | Caveat | wght 400–700 | Tita's notes only |

Atkinson Hyperlegible Next has ½ ¼ ¾ but **no ⅓ ⅔ ⅛ ⅜ ⅝ ⅞** (Google Fonts returns 400 for a
`text=` request with them). That is why ingredient amounts are set in Bricolage, which has
them: the Latin file leaves them out, so the fractions file adds them under the same family
name with a `unicode-range`.

To refresh: request `https://fonts.googleapis.com/css2?family=<family>:<axes>` with a
current Chrome user agent, take the `/* latin */` block's `url()`, and for the fractions
file add `&text=⅐⅑⅒⅓⅔⅕⅖⅗⅘⅙⅚⅛⅜⅝⅞` (URL-encoded).
