# Warm reference-led textures

Owner correction, 2026-11-02: use the supplied turquoise image's organic ribbons,
dark pools and visible photographic grain, recolored fire yellow and warm gold.
No white/pale washes, purple or pink. Keep a warmer orange/red secondary section.
The first generated pair was rejected and is not used.

The shared home background spans header and hero. Header grid and reserved
scrollbar space keep navigation positions stable between public pages. Mobile
uses explicit logo/actions/navigation rows. No actual browser visual QA completed.

Visitor copy is substantially shorter and starts with opinions and conviction.
AI is explained briefly in the process. Sharing headline: What would change your mind?
Duplicate home introduction removed; the experiment is described in one sentence.

## Assets

Generated through built-in imagegen, with the owner's first turquoise image as
the visual reference. Exported with native PHP/GD to 1440×720 WebP:

- public_html/assets/gradient-yellow.webp (45,932 bytes)
- public_html/assets/gradient-red.webp (25,740 bytes)
- bin/export-textures.php reproduces the export from the generated PNGs.
- bin/brand-assets.php applies the yellow texture to sharing artwork and icons.

Local originals, outside the repository:
C:/Users/hello/.codex/generated_images/01a19c23-f646-7402-9879-c50d7458328b/
exec-13ef838a-4ee2-4661-adbe-203b797cc986.png (yellow),
exec-a0bda583-9da7-44ae-8037-6458da326a52.png (ember).

Yellow prompt:
> Use the attached turquoise textured gradient as the exact visual direction: its flowing, softly blurred, irregular ribbons, deep organic pockets and clearly visible photographic grain. Create a WIDE 2:1 color reinterpretation for a website background. Replace all cyan/turquoise with intense FIRE YELLOW and warm golden yellow; replace navy shadows with rich warm amber/gold. Keep the reference's elegant serpentine organic composition and substantial fine grain, do not simplify it to a plain left-to-right gradient or generic circular blobs. Palette strictly golden yellow, fire yellow, deep warm gold, with tiny amber accents. NO white or cream or pale washed-out areas, NO pink, purple, magenta, burgundy, green, blue or cyan. NO dominant orange. Make it saturated, luminous and warm with noticeable depth. Black website text must remain readable across top-left and lower portion, so these areas should be luminous warm yellow rather than dark. Full bleed, no text, no logo, no objects.

Ember prompt:
> Use the attached turquoise textured gradient as visual direction for its soft serpentine ribbons, blurred organic color pockets and substantial fine photographic grain. Create a WIDE 2:1 website section background, a coordinated warm EMBER variant. Color it glowing dark orange-red, deep fire red and burnt umber shadows. Let the red flow organically into a very dark warm reddish-brown. Absolutely NO purple, pink, magenta, burgundy, blue, cream, white or pastel highlights. Retain the distinctive flowing organic shapes and visible grain of the reference, NOT a flat stock gradient. Keep luminosity dark throughout so ivory paragraph text will be legible. Full bleed, no text, no logos, no objects.

CSS adds a warm dark translucent layer to the process section for readable text.
SW v3 replaces the old grain asset with the yellow WebP in the offline shell.

## Verification and remaining work

40 HTTP and 7 service-worker checks pass; touched PHP and worker syntax pass.
Generated sharing bitmap inspected. Real desktop/mobile page rendering and
installation screenshots remain unverified because Computer Use stopped on URL
verification. Richer Install UI screenshots are still missing, not completed.
No measured PageSpeed score; its earlier 429 remains a stop condition.

Owner explicitly requested GitHub publication; main was verified at 2a197d5
after this correction. Known-secret/history scan: 199 blobs, no matches.
Production release d9ccbb7ce9ac3b73: all 76 installed hashes verified; three
serial HTTPS checks verified English/Swedish copy and the yellow WebP response.
