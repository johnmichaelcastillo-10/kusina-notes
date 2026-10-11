# Recipe photos

One photo per recipe: `<slug>.webp`, 1200×800 (3:2), WebP quality 80. `seed-content.php`
attaches it as the recipe's featured image (the plate crops it round in the theme).

**Owner's rule: CC0 or public domain only.** No CC BY, no CC BY-SA, no "free to use"
stock sites with their own licences. No credits are shown on the site; every photo's
source is recorded in the `*-credits.json` files here so the licence can be checked.

A photo must show **that dish**, cooked and served, recognisably. A close cousin, a market
stall, a menu board, a raw ingredient or a photo you're unsure about is a skip: the recipe
keeps its course-coloured plate. Never reuse one photo for two different recipes.

## Credits file entry

```json
{
  "slug": "sinigang-na-baboy",
  "file": "sinigang-na-baboy.webp",
  "title": "File:Sinigang na baboy.jpg",
  "source": "https://commons.wikimedia.org/wiki/File:Sinigang_na_baboy.jpg",
  "author": "Example Name",
  "license": "Public domain",
  "retrieved": "2026-10-11"
}
```

## Sources that work from this network

- Wikimedia Commons API (`commons.wikimedia.org/w/api.php`), with a descriptive
  `User-Agent` and at most one request per second (it rate-limits with an HTML "You are
  making too many requests" page). `LicenseShortName` in `extmetadata` must be CC0 or
  public domain. Download a standard thumbnail width (1280); odd widths return errors.
- Openverse API with `source=stocksnap` (all CC0); only
  `cdn.stocksnap.io/img-thumbs/960w/<ID>.jpg` downloads from here.
- Crop and convert with host PHP GD (`%LOCALAPPDATA%\Programs\PHP\8.5\php.exe`,
  `imagewebp`).
