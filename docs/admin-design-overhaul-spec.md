# Spec: Admin Visual Design Overhaul

Date: 2026-05-03
Source screenshots: `screenshots/desktop/admin/` and `screenshots/mobile/admin/`
Related product spec: [admin-cms-saas-overhaul-spec.md](admin-cms-saas-overhaul-spec.md)

## Objective

Redesign the admin visual system so it feels like a professional CMS/SaaS back office rather than a custom HTML form tool. The overhaul should improve hierarchy, spacing, polish, mobile ergonomics, and trust without changing the public site.

## Screenshot Diagnosis

The current admin is functional and has the start of an app shell, but the visual execution still reads unfinished. The interface relies heavily on default-looking controls, thin outlines, cramped rows, entity/emoji icons, dense labels, and repeated white cards.

Visible issues from the screenshots:

- **Brand/header:** The brand appears as a normal underlined link. Replace with a compact product lockup: mark, product name, site context, no underline.
- **Sidebar:** Section headings are too small and the active item is just a pale pill. Use a structured rail with grouped nav, consistent icons, active indicator, and a user/account footer.
- **Top bar:** Current pills (`Konttiravintola Waves`, `Esikatselu`, `Live`) are visually weak. Treat them as a real environment/status cluster with preview and publish affordances.
- **Cards:** Cards are oversized and repetitive. Use cards for meaningful objects; use unframed sections for page structure.
- **Dashboard widgets:** Current widgets are generic bordered cards. Use compact health/action tiles with severity, metric, short explanation, and action link.
- **Menu list:** Rows are too loose horizontally but cramped typographically. Category headers lack proper count spacing. Status badges compete with content. Native small buttons make the row look unstyled.
- **Lunch editor:** Mobile repeats full empty states and full forms for every weekday, creating a long, heavy page. Replace with collapsed day sections and add-on-demand item forms.
- **Settings tabs:** Mobile tabs overflow horizontally and the sticky save bar hides fields. Use scrollable tabs with fade affordance or stacked section navigation, plus bottom padding for sticky actions.
- **Typography:** The current font sizing and weights are inconsistent. Headings are sometimes too large inside compact surfaces and labels are too heavy everywhere.
- **Iconography:** Entity symbols and emoji-style icons are inconsistent. Use one icon set or remove icons where text is clearer.
- **Status color:** Red/green badges are useful but too visually loud in dense lists. Use softer status tokens and reserve strong colors for urgent states.
- **Control styling:** Some buttons look like browser defaults, some look custom, and form controls do not share a coherent visual language.
- **Text rhythm:** Examples in screenshots include joined labels such as `Summer Tacos5`, `Hinta18,00`, and `TagitL`.

## Design Goal

The admin should feel like a restrained, operational CMS for a restaurant team: calm, dense enough for repeated work, visually trustworthy, and clearly branded without looking decorative.

Target qualities:

- Quiet, confident, information-dense.
- Fewer bordered boxes, stronger alignment, better use of whitespace.
- Clear hierarchy between navigation, page header, toolbars, content lists, editors, and destructive actions.
- Consistent control styling across buttons, inputs, selects, checkboxes, tabs, badges, and upload areas.
- Mobile layouts that look intentionally designed, not squeezed desktop forms.

## Design Direction

Use a compact SaaS layout with a muted neutral base and one primary blue accent.

Recommended palette:

```css
:root {
  --admin-bg: #f6f7f9;
  --admin-surface: #ffffff;
  --admin-surface-muted: #f1f4f7;
  --admin-surface-raised: #ffffff;
  --admin-border: #d9e1ea;
  --admin-border-strong: #b8c5d2;
  --admin-text: #122033;
  --admin-text-muted: #526174;
  --admin-text-faint: #738093;
  --admin-primary: #0f5f86;
  --admin-primary-hover: #0b4d6d;
  --admin-primary-soft: #e7f2f8;
  --admin-success: #147a46;
  --admin-success-soft: #eaf7ef;
  --admin-warning: #9a5b00;
  --admin-warning-soft: #fff4df;
  --admin-danger: #b42318;
  --admin-danger-soft: #fff0ee;
}
```

Typography:

- Use one system stack for the admin: `Inter`, `system-ui`, `Segoe UI`, sans-serif. If no webfont is added, use `system-ui`.
- Page title: 24-28px desktop, 22px mobile, weight 750.
- Section title: 18-20px, weight 750.
- Row title: 14-16px, weight 700.
- Body text: 14px desktop, 15-16px mobile for form readability.
- Labels: 12-13px, weight 650, no forced all-caps except small metadata headings.
- Letter spacing: 0 except small uppercase navigation group labels.

Radii and elevation:

- Controls: 8px.
- Object cards/panels: 10-12px.
- Avoid large rounded pills except compact status chips.
- Use borders by default; use shadows only for sticky bars, popovers, drawers, and top navigation.

Spacing:

- Base grid: 4px.
- Page gutters: 24px desktop, 14-16px mobile.
- Card padding: 20-24px desktop, 14-16px mobile.
- List rows: 12-16px vertical, not oversized blank space.
- Add bottom padding to pages with sticky actions: at least sticky bar height plus 24px.

## Component Requirements

### App Shell

- Desktop uses a 240px sidebar with fixed position or sticky full-height behavior.
- Sidebar sections: Overview, Content, Operations, Media, Settings.
- Active nav item has a left accent bar or stronger filled background, not only a pale hover state.
- Sidebar footer contains user/account actions and logout in a styled menu or compact footer block.
- Mobile uses a top bar with menu button, page title, preview action, and live/draft status. Navigation opens in a drawer.

### Page Header

- Header contains title, one-sentence description, primary action, and secondary actions.
- Do not repeat the page title in both top bar and content area unless mobile needs the top bar title.
- Add optional status summary under the title for content pages: counts, warnings, missing translations, hidden items.

### Toolbar

- Lists need a toolbar before content: search, filters, sort, bulk actions, and primary add action.
- Toolbars should be a single horizontal row on desktop and wrap into stacked controls on mobile.
- Avoid tiny native buttons. All toolbar controls use the same button/select/input styling.

### Object Lists

- Replace default-looking row actions with consistent icon/text buttons.
- Rows should have this structure: leading media/status/drag handle, title and compact metadata, status chips, last updated/ordering metadata, primary action.
- Category headers must show clear spacing: `Summer Tacos` and count chip `5`, never joined text.
- Price/tag labels need spacing: `Hinta 18,00 €`, `Tagit L, G`.

### Forms

- Form fields use consistent height, border, focus, error, disabled, and help text states.
- Group bilingual fields with language tabs or paired cards. On mobile, avoid side-by-side FI/EN.
- Raw HTML textareas should be replaced with rich text or plain text. If raw HTML remains, mark it as advanced.
- Required fields show required state without relying only on asterisks.
- Validation errors appear inline and in a summary at the top of the form.

### Sticky Save Bar

- Sticky bars appear only after changes or when the page's main job is editing.
- Desktop bar is aligned to the content column, not the whole viewport if a sidebar exists.
- Mobile bar uses one primary action plus a compact secondary action if needed.
- Sticky bar must not cover focused content; page content includes bottom spacer.
- Copy should state the real state: `Unsaved changes`, `Draft saved`, `Ready to publish`, not generic reminders.

### Buttons

Button hierarchy:

- Primary: filled blue for save/publish/create.
- Secondary: white with border for preview, duplicate, add secondary item.
- Ghost: transparent for low-emphasis navigation/actions.
- Danger: red soft or filled depending on severity.

Button rules:

- No browser-default button styling.
- Minimum touch target: 40px desktop, 44px mobile.
- Icon-only buttons require accessible labels and tooltips on desktop.

### Tabs

- Tabs should be visually connected to the content they switch.
- Mobile tabs must scroll horizontally without clipping content; provide enough padding and visible active state.
- Do not allow sticky save bars to obscure tab content.

### Empty States

- Empty states should be compact and action-oriented.
- Avoid repeating large empty boxes five times, as seen on lunch mobile.
- For repeated sections, use one-line section empty states and one clear add action.

### Media Upload

- Upload zones need a polished dashed container, upload icon, accepted formats, max size, and selected file preview.
- Native file input should be visually replaced with a styled trigger and filename display.
- Gallery and menu uploads share one component.

## Screen-Specific Targets

### Dashboard

- Use a two-column desktop layout:
  - left: today/site health and priority tasks,
  - right: recent activity and quick actions.
- Health tiles use severity styling: neutral, warning, danger, success.
- Avoid count-only cards unless the count is actionable.

### Menu

- Add a sticky list toolbar under the page header.
- Use denser rows with cleaner typography and softer chips.
- Move order controls into a menu or compact reorder mode; current `Ylös/Alas` buttons dominate rows.
- Add right-side editor drawer for desktop.
- Mobile item cards collapse details and use a bottom sheet/full-screen editor.

### Lunch

- Use weekday accordion cards.
- Default state: collapsed day summary plus `Add lunch`.
- Expanded state: compact fields and preview.
- Avoid rendering full new-item forms for every weekday by default.

### Opening Hours

- Desktop can use a structured grid, but mobile must use day cards.
- Add a preview strip above the form showing current public result.
- Closed toggle should visually disable time fields.

### Notices

- Replace table row with notice card and preview.
- Status chips: Draft, Scheduled, Active, Expired.
- Use style preview so `Info`, `Warning`, and `Closed` are visually distinguishable.

### Events

- Create form should not dominate the page if events exist.
- Use tabs for Upcoming, Draft, Past.
- Event cards need date badge, title, visibility, featured state, and edit action.

### Gallery

- Use media grid with thumbnail cards.
- Empty state should show upload action and what images are used for.
- Captions/alt text edit in drawer/modal.

### Settings

- Settings should be a sectioned settings product page, not one long card.
- Desktop can use left subnavigation inside settings.
- Mobile should use segmented section tabs or accordion sections.
- SEO preview card should look like a search result, not another textarea block.

## Acceptance Criteria

- Admin no longer shows browser-default button or file input styling in primary workflows.
- Sidebar/header/brand read as one coherent app shell on desktop and mobile.
- No joined metadata labels such as `Hinta18,00` or `Summer Tacos5` remain.
- Sticky save bars never cover focused fields or final form content.
- Mobile editor screens look intentionally stacked, not clipped or horizontally squeezed.
- Dashboard, menu, lunch, hours, notices, gallery, and settings share the same visual language.
