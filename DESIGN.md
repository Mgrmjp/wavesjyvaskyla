# Waves Menu — Design Direction

## Concept

Nordic waterfront fine-dining meets container restaurant casual. The menu page should feel like a premium editorial spread: dark, refined, calm. Not a fast-food kiosk — a harbour destination worth the trip.

The design system is built on three pillars:

1. **Spatial calm** — generous breathing room, clear hierarchy, nothing cramped
2. **Material refinement** — soft surfaces, subtle borders, no harsh edges
3. **Elegant function** — every element earns its place, information is effortless to scan

---

## Layout Structure

### Page flow

```
[Sticky Header]
  ↓
[Hero — full-width atmospheric image, gradient overlay, title, meta]
  ↓
[Sticky Jump Nav — horizontal scrolling category tabs]
  ↓
[Menu Content — alternating sections + wave dividers]
  ↓
[Dietary Legend]
  ↓
[Footer Destination Block]
```

### Grid system

- Max content width: `min(100%, 1160px)` — centered, consistent
- Horizontal padding: `1.5rem` (mobile: `1.25rem`)
- Section vertical rhythm: `3.5rem` top/bottom padding
- Gap between cards: `0.75rem` (desktop: `0.875rem`)

### Section header layout

- Mobile: stacked, intro below title
- Desktop (≥768px): side-by-side, title left, intro right-aligned
- Section eyebrow → title → intro = top-to-bottom hierarchy within each section

---

## Typography System

### Font stack

| Role    | Font               | Weight  | Notes                       |
| ------- | ------------------ | ------- | --------------------------- |
| Display | Cormorant Garamond | 500     | hero titles, section titles |
| Body    | Inter              | 400–800 | all UI, descriptions, nav   |

### Type scale

| Element          | Size (mobile → desktop)     | Weight | Transform                        |
| ---------------- | --------------------------- | ------ | -------------------------------- |
| Hero title       | `clamp(3rem, 10vw, 5.5rem)` | 500    | none                             |
| Section title    | `clamp(2rem, 6vw, 3.5rem)`  | 500    | none                             |
| Card title       | `0.85rem`                   | 700    | UPPERCASE, letter-spacing 0.06em |
| Price            | `1.0625rem`                 | 800    | tabular nums                     |
| Description      | `0.875rem`                  | 400    | line-height 1.6                  |
| Eyebrow (kicker) | `0.68–0.7rem`               | 700    | UPPERCASE, letter-spacing 0.14em |
| Meta / tags      | `0.6–0.72rem`               | 700    | UPPERCASE                        |
| Body copy        | `0.875–1.0625rem`           | 400    | line-height 1.6                  |

### Principles

- Uppercase only for labels, kickers, and item names — never for descriptions
- No serif in body copy — Inter for readability
- Cormorant Garamond for display moments only (hero, section titles)
- Letter-spacing tuned per element — too tight reads as urgent, too loose reads as cold

---

## Spacing System

| Token | Value   | Usage                      |
| ----- | ------- | -------------------------- |
| xs    | 0.25rem | tag gaps                   |
| sm    | 0.4rem  | tag gaps, meta separators  |
| md    | 0.75rem | card internal gaps         |
| lg    | 1.25rem | section internal spacing   |
| xl    | 2.5rem  | section rhythm             |
| 2xl   | 3.5rem  | section padding top/bottom |

All spacing is based on `0.25rem` increments.

### Card internal spacing

- Padding: `1.25rem 1.375rem`
- Badge → title gap: `0.6rem`
- Title → description gap: `0.4rem`
- Description → tags gap: `0.5rem` (top of tags area)
- Tags padding-top: `0.5rem` (border separator)

---

## Color Refinements

### Palette (CSS variables)

```css
--menu-cream: var(--color-text); /* #f4ead7 — primary text */
--menu-cream-soft: rgba(244, 234, 215, 0.72); /* descriptions, secondary */
--menu-muted: var(--color-muted); /* #b8ad97 — labels, meta */
--menu-gold: var(--color-warm); /* #b97845 — dividers, dots */
--menu-accent: var(--color-accent); /* #c8d86b — prices, badges */
--menu-border: rgba(244, 234, 215, 0.12); /* default card border */
--menu-border-hover: rgba(244, 234, 215, 0.22); /* hover border */
--menu-surface: rgba(244, 234, 215, 0.04); /* card background */
--menu-surface-hover: rgba(244, 234, 215, 0.07); /* card hover */
--menu-surface-accent: rgba(200, 216, 107, 0.08); /* badge bg */
```

### Contrast requirements

- Card title: `menu-cream` on `menu-surface` → ratio ~11:1 ✓
- Price: `menu-accent` on `menu-surface` → ratio ~5.5:1 ✓
- Description: `menu-cream-soft` on `menu-surface` → ratio ~4.2:1 ✓
- All text passes WCAG AA at any size

---

## Card Redesign

### Structure

Each card:

- Border: `1px solid var(--menu-border)`, radius `10px`
- Background: `var(--menu-surface)`
- Padding: `1.25rem 1.375rem`
- Internal stack: badge → title-row → description → tags

### Title-row pattern

```
[CARD TITLE          ] [PRICE]
↑ UPPERCASE          ↑ accent
  0.85rem/700/ls:0.06em  1.0625rem/800
```

Flexbox `justify-content: space-between`, `align-items: baseline`. Price gets `flex-shrink: 0` to never wrap.

### Hover state

```css
.menu-card:hover {
  border-color: var(--menu-border-hover);
  background: var(--menu-surface-hover);
  transform: translateY(-2px);
  box-shadow:
    0 4px 24px rgba(0, 0, 0, 0.12),
    0 1px 4px rgba(0, 0, 0, 0.08);
}
```

Shadow only on hover — never on default. Keeps the resting state calm.

### Featured variant

```css
.menu-card--featured {
  border-color: rgba(200, 216, 107, 0.28);
  background:
    linear-gradient(135deg, rgba(200, 216, 107, 0.06) 0%, transparent 100%), var(--menu-surface);
}
```

Top accent bar via `::before` pseudo-element — not a border-top (which would be clipped by border-radius).

### Dietary tags

```css
.dietary-tag {
  min-height: 1.4rem;
  padding: 0 0.45rem;
  border: 1px solid var(--menu-border);
  border-radius: 4px;
  color: var(--menu-muted);
  font-size: 0.6rem;
  /* ... */
  transition:
    border-color 200ms ease,
    color 200ms ease;
}
```

Subtle hover feedback — border and text shift slightly.

---

## Navigation Improvements

### Sticky header

- `position: sticky; top: 0`
- Frosted glass: `backdrop-filter: blur(20px) saturate(180%)`
- Border: `1px solid rgba(244,234,215,0.08)` — much softer than before
- Gold accent line: centered, not full-width, `rgba(185,120,69,0.45)`

### Desktop nav

- No more pill/pill shape — natural button feel
- Active link: accent background + text, not inverted pill
- Hover: subtle background fill
- Separator dot before language toggle

### Mobile menu

- Scrollable if viewport height is small
- Full tap targets: `min-height: 3.25rem`
- Active state: accent border + tinted background

### Jump nav

- Sticky below header, with its own backdrop blur
- Bottom-border only (no top border)
- Links: text-only tabs, no pill backgrounds
- Active on scroll: accent bottom border via JS
- No padding waste — compact but readable

---

## Hero Section Improvements

### Layout

- Full-width image, positioned center
- Diagonal gradient overlay: dark at bottom-left, transparent at top-right
- Vertical gradient at bottom: fade to dark
- Subtle grid overlay texture (64px grid, very faint)
- Top-left: thin accent line above the kicker

### Hierarchy

```
[Kicker line — muted, small, uppercase]
[Hero title — large, serif, uppercase]
[Intro copy — medium, readable]
[Meta row — small, spaced, dots between]
```

### Mobile hero

- Switch from diagonal to vertical gradient overlay (bottom-heavy)
- Image positioned at 65% horizontal
- Typography scaled down: `font-size: 2.75rem`, `max-width: none`
- Meta dots hidden on mobile (display none after first separator)

---

## Hover & Interaction Ideas

### Cards

- **Rest**: flat surface, `0.12` border, no shadow
- **Hover**: lifted shadow, `0.22` border, slight `translateY(-2px)`
- **Featured badge**: pulsing dot (subtle, CSS only) in `::after` if desired

### Jump links

- **Default**: transparent bottom border
- **Hover**: cream text + accent bottom border
- **Active (scroll)**: JS sets accent bottom border, `aria-current="true"`

### Dietary tags

- **Default**: muted text + `0.12` border
- **Hover**: cream-soft text + `0.22` border

### CTA buttons

- **Default**: accent fill
- **Hover**: `translateY(-2px)` + shadow glow `rgba(200,216,107,0.22)`

---

## Mobile Responsiveness

### Breakpoints

| Breakpoint | Layout changes                                                                      |
| ---------- | ----------------------------------------------------------------------------------- |
| < 768px    | Hero gradient switch, stacked section headers, no meta dots, no hover lift on cards |
| ≥ 768px    | Side-by-side section headers, horizontal hero meta dots, larger hero type           |
| ≥ 900px    | 2-column card grid                                                                  |
| ≥ 1024px   | Full desktop nav replaces mobile menu                                               |

### Mobile priorities

1. **Readable type** — no squashed text, comfortable line lengths
2. **Tap targets** — `min-height: 2.75rem` on all interactive elements
3. **No compression** — padding stays generous even on small screens
4. **Scrollable nav** — jump nav scrolls horizontally, no wrapping
5. **Sticky context** — section nav sticks so users don't lose their place

### Section navigation on mobile

- Jump nav is always visible (sticky below header)
- Active section highlighted via scroll listener
- Smooth scroll: `scroll-behavior: smooth` + `scroll-padding-top` accounts for header + jump nav height

---

## Accessibility

- All interactive elements: `:focus-visible` styled (outline, not default ring)
- `aria-label` on jump nav, card tag groups, dietary legend
- `aria-current="true"` on active jump link (set by JS)
- `prefers-reduced-motion`: card transitions disabled
- Semantic HTML: `<nav>`, `<section>`, `<article>`, `<header>`, `<footer>` within cards
- Color contrast: all text meets WCAG AA minimum
- SVG icons: `aria-hidden="true"` unless decorative

---

## Animation Summary

| Element       | Animation            | Duration | Easing                     |
| ------------- | -------------------- | -------- | -------------------------- |
| Card hover    | translateY + shadow  | 200ms    | ease                       |
| Fade-in els   | opacity + translateY | 600ms    | ease-out (via JS observer) |
| Jump nav link | border-color, color  | 200ms    | ease                       |
| Dietary hover | border-color, color  | 200ms    | ease                       |
| Header scroll | none (instant)       | —        | —                          |

No decorative animations. All motion is functional (hover feedback, scroll position).
