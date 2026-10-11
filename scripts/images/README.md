# Recipe photos

One photo per recipe: `<slug>.webp`, 1200×1500 (4:5, the theme's photo shape), WebP quality
80. `seed-content.php` attaches it as the recipe's featured image, re-importing only when
the file changes.

**Source: AI-generated with Higgsfield** (owner's choice, 2026-10-12, replacing the earlier
CC0/public-domain-only rule, which left half the recipes without photos and the rest
inconsistent). The site says so in the footer and on the About page. `generated.json`
records each image's Higgsfield job file and date.

## How they were made

- Model `z_image` (the only photoreal model on the free plan; 0.15 credits per image).
  `gpt_image_2_5` needs a paid plan.
- Square (1:1) output, then `php scripts/images/crop.php <png> <slug>` crops the centre
  4:5. Tall 3:4 output tended to draw the dish twice.
- The service accepts **one submission at a time**: a batch of more than one gets
  `429 rate_limit_reached` for the extras. Submit one, wait for it, submit the next.
- Every prompt uses the same house style so the set looks like one shoot:

  > Three-quarter overhead food photograph of Filipino <dish>: <what is in it, as the
  > recipe makes it>, in one <vessel>. Exactly one <vessel>, centered. Pale linen
  > tablecloth, a piece of fresh banana leaf, soft natural window light from the left,
  > gentle shadows, muted green and cream palette, shallow depth of field, editorial
  > cookbook style. No text, no hands, no people, no logos.

  Vessels vary by dish: white enamel bowl with a blue rim (soups, stews), dark clay pot
  (kare-kare, laing, pinakbet), white ceramic plate (fried and grilled), woven rattan plate
  on banana leaf (grilled, street food, kakanin), cast-iron sizzling plate (sisig), clear
  glass (drinks).
- Describe the dish from the recipe's own ingredient list; the model invents garnishes
  (shrimp in pinakbet, a second bowl) if the prompt leaves room. Review every image before
  using it, and regenerate any that shows the wrong dish.

`crop.php` also takes a ratio (`3:2`, `1:1`) if another shape is ever needed.
