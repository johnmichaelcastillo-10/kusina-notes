# Recipe data

Launch content as JSON, one array of recipes per file. `scripts/seed-content.php` loads every
`*.json` here (idempotent by slug); `node scripts/validate-recipes.mjs` checks them. Run the
validator before seeding; the seed refuses files that fail it.

## Shape

```json
{
  "slug": "adobong-pusit",
  "title": "Adobong pusit",
  "course": "ulam",
  "diets": ["pescatarian"],
  "key_ingredients": ["squid", "vinegar", "soy sauce", "garlic"],
  "prep": 15,
  "cook": 25,
  "servings": 4,
  "yield_label": "",
  "difficulty": "easy",
  "intro": "Squid simmered in vinegar, soy sauce and its own ink until the sauce turns inky and glossy.",
  "groups": [
    { "title": "", "items": [
      { "qty": 1, "max": null, "unit": "kg", "name": "small squid", "note": "cleaned, ink sacs kept" }
    ] }
  ],
  "steps": [
    { "text": "Simmer the squid in the vinegar without stirring.", "minutes": 3 }
  ],
  "tips": "Overcooked squid turns rubbery: keep it to a few minutes, or go past 30."
}
```

| Field | Rule |
|---|---|
| `slug` | lowercase ASCII, words joined by `-`, unique across all files |
| `title` | sentence case, the name people use (Filipino name when that's what it's called), max 60 chars |
| `course` | `ulam`, `sabaw`, `merienda`, `panghimagas`, `almusal` or `inumin` |
| `diets` | only `vegetarian` (no meat, fish or seafood, **and no fish sauce, shrimp paste, bagoong, oyster sauce or meat broth**) and `pescatarian` (fish/seafood, no meat). Leave empty when unsure. No other diet claims |
| `key_ingredients` | 1 to 6 names from the vocabulary in `validate-recipes.mjs` (feeds the pantry finder) |
| `prep`, `cook` | whole minutes, realistic. Overnight marinating goes in the tips, not in `prep` |
| `servings` | whole number; with `yield_label` (e.g. `"pieces"`, `"glasses"`) it reads "Makes 12 pieces" |
| `difficulty` | `easy`, `medium` or `involved` |
| `intro` | 1 or 2 sentences: what it is, how it tastes, when people eat it |
| `groups` | ingredient groups; `title` may be `""` for a single group |
| item `qty` / `max` | positive number or `null`; `max` only for ranges and must be bigger. Thirds as `0.333`, `0.667` |
| item `unit` | `""` (a count), `g`, `kg`, `ml`, `l`, `tsp`, `tbsp`, `cup`, `piece`, `clove`, `thumb`, `pinch`, `to-taste` |
| `steps` | 3 to 14; `minutes` (whole, max 480) only where the cook waits: simmer, boil, bake, steam, rest |
| `tips` | optional, one short practical note ("Tita's note"), `""` if none |

Writing rules: American English; plain, precise method text; inline HTML only `<strong>`
and `<em>`; no em dashes; no history or origin claims, no health claims, no invented people
or anecdotes; amounts and times a home cook would actually use.
