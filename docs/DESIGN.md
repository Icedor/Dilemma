# Visual direction and participant experience

## Owner correction, 2026-11-02

The first design was rejected. This replaces its layout, colors, illustration and
visitor copy instead of adding another theme layer. The owner's references were
opened and inspected from Material/Inspiration design:

- Dinner illustration: saturated yellow field, dominant subject, sparse navigation.
- Artichoke image: one meaningful object overlapping oversized typography.
- North Face composition: large image, confident scale, yellow/coral contrast.
- Game-store image: graphic color at large scale rather than decorative UI frames.

The resulting system uses sunflower yellow, coral and near-black, and a large
unpunctuated wordmark. Following the owner's next correction, the photographic
flag is removed entirely. The yellow uses warm gradients with subtle static grain.
A small CSS flag animates briefly only in the debate result and confirmation;
reduced-motion preferences disable it. Reading areas use neutral paper;
dark mode is charcoal, never green. Participant sides use coral/blue consistently.
Forms and controls use large square edges. No decorative slogan badges, stock
people illustration, repeated colored cards or artificial statistics.

## Main experience

1. State an opinion and a reason. Topic, introduction and position scale are optional.
2. Share an invitation with someone who disagrees; connect each person's own AI.
3. AI assistants exchange arguments. An AI that changes its assessment explains
   why to its own human in their private chat. The human can bring new objections.
4. Only the human can pause, stop without persuasion, or raise the white flag.

The introduction uses ordinary Swedish/English. Connection specifics belong only
in the connection flow. Public explanatory pages do not discuss email delivery,
model execution, tokens, clients or manual fallback. Fallback remains available
inside the debate. No claim that AI assessments establish objective truth.

Email notifications are an unchecked per-debate choice at creation and acceptance,
and can be changed in debate settings. Optional push is offered next to the debate
and in the profile, replacing debate emails for the account after explicit consent.
Installation is offered there, with no automatic site install or permission prompt.
The global email profile setting can disable chosen email notifications.
Migration 005 defaults existing participant email choices to off.
Login verification remains separate from optional debate notifications.

The extra registration gate added by the assistant was removed: the original brief
allows legal text marked for final review. The review notice stays on legal pages.
Authentication, human-only actions and processing/publication consent are unchanged.

## Asset provenance

Built-in image_gen generated the original transparent PNG. PHP GD exported WebP
with alpha, quality 86; final asset public_html/assets/white-flag.webp, 102608 bytes.
No external image service, runtime dependency or image request is used by the site.
Original: C:/Users/hello/.codex/generated_images/01a19c23-f646-7402-9879-c50d7458328b/exec-1189d8ab-d900-4979-a32b-8fd0a6679e46.png.

Generation prompt:

> Use case: product-mockup. Create a premium editorial photograph cutout for Dilemma,
> an art project about people changing their minds. Subject: one large immaculate
> white fabric flag, caught in a gentle full wave, attached to a slender polished
> chrome pole leaning slightly to the right. The free cloth flies towards the right
> and fills most of the upper half of a portrait 4:5 composition; the pole extends
> down to the lower edge, no base. The flag must be sculptural and beautifully tactile
> with luminous ivory-white folds, fine cotton weave, a stitched hem, credible gravity
> and dimensional shadows. Studio photography, soft directional lighting, art
> exhibition campaign quality, physically real material rather than cartoon or
> plastic 3D. Isolated on a truly transparent background, no floor, no surrounding
> scene. Generous clear margins around the cloth. Pure white cloth without any
> writing, marks, emblems or logos. No people, no hands, no confetti, no sparkles,
> no generic UI shapes, no decorative objects, no text. Intended to overlap enormous
> black typography on a saturated sunflower-yellow webpage. Asset only, not a
> website mockup. Save the image as a local generated asset if possible.

## Verification limits

Functional/rendering checks do not prove visual quality. The native Chrome window
was positively identified as Dilemma, but Computer Use then stopped because it
could not verify the current URL. Browser-host inventory also remains unavailable.
Do not bypass this restriction or call desktop/mobile/keyboard visual QA complete.
