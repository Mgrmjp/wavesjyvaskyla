# Spec: Admin CMS/SaaS Overhaul

Date: 2026-05-03
Source screenshots: `screenshots/desktop/admin/` and `screenshots/mobile/admin/`

## Assumptions

1. This remains a web-based admin for a single restaurant site before it becomes a true multi-tenant SaaS.
2. The first implementation can stay in PHP with JSON storage, but the UX and domain model should be designed so a later database-backed SaaS migration is straightforward.
3. The primary admin users are restaurant operators who need to update content quickly on desktop and mobile, not technical site maintainers.
4. Finnish and English content are both first-class editing requirements.
5. The public site should remain stable while the admin is rebuilt behind the same `/admin` routes or a compatible route set.

## Objective

Turn the current admin panel into a professional CMS-style product experience: predictable, responsive, safe to edit, easy to scan, and structured around real publishing workflows rather than page-sized forms.

Success means an operator can log in, understand site health, edit menu/lunch/events/hours/media/settings, preview the effect, and publish changes with confidence from desktop or a phone.

## Current Screenshot Audit

The current admin is functional and has the start of an app shell, but the visual execution still reads unfinished rather than professional CMS/SaaS. The interface relies heavily on default-looking controls, thin outlines, cramped rows, entity/emoji icons, dense labels, and repeated white cards. It looks like an internal form tool, not a polished content product.

The screenshots show the current ceiling:

- **Navigation is horizontal and wraps on mobile.** At 390px the header consumes significant vertical space and the nav becomes a dense button cluster.
- **The latest sidebar shell is better on desktop but still underdesigned.** The left rail uses cramped labels, tiny section headings, mixed symbols, an underlined brand link, and a lonely logout link at the bottom. It needs stronger product-grade hierarchy.
- **Dashboard is too passive.** It reports counts and shortcuts, but not freshness, warnings, draft changes, upcoming events, expiring notices, missing translations, or operational tasks.
- **Tables remain the default editor pattern.** Lunch, notices, and hours use wide tabular editing that clips or horizontally scrolls on mobile.
- **Save model is page-level and ambiguous.** Sticky save bars help, but they do not clearly distinguish draft, saved, published, unsaved, validation, and preview states.
- **Long forms lack internal wayfinding.** Settings and menu pages become long vertical documents, especially on mobile.
- **Bilingual editing is side-by-side everywhere.** Useful on desktop, but heavy on mobile and error-prone when translations are incomplete.
- **Content workflow is not role-aware.** Admin has no audit trail, revision history, author, last updated timestamp, or confirmation for risky changes.
- **Media management is split.** Menu image upload and gallery upload are separate experiences instead of one media library with reuse.
- **Visual hierarchy is card-heavy.** The interface is calm but the repeated card/table/list surfaces make pages feel like forms, not a CMS product.
- **Controls look native and inconsistent.** Some buttons look like browser defaults, some look custom, and form controls do not share a coherent visual language.
- **Spacing and text rhythm are weak.** Examples in the screenshots include joined labels such as `Summer Tacos5`, `Hinta18,00`, and `TagitL`, plus sticky bars covering content on mobile.
- **Professional SaaS affordances are missing.** Search, filters, bulk actions, empty-state guidance, import/export, content status, preview, validation summaries, and activity history are not visible.

## Visual Design

Visual design requirements now live in [admin-design-overhaul-spec.md](admin-design-overhaul-spec.md). Keep this document focused on product model, content workflow, and implementation sequencing.

## Target Product Principles

- **App shell over document pages.** Use a fixed/sidebar-first admin layout on desktop and a compact drawer/tab shell on mobile.
- **Content objects over forms.** Menu item, lunch entry, event, notice, gallery image, and setting group should each have a clear object lifecycle.
- **Draft, preview, publish.** Editing should make state explicit before changes affect the public site.
- **Mobile editors become stacked forms.** Do not rely on horizontal table scrolling for core workflows.
- **Fast scanning first, deep editing second.** Lists should expose status, title, schedule, language completeness, visibility, and primary action.
- **Bilingual without duplication fatigue.** Editors should support language tabs, translation completeness indicators, and copy-from-FI actions.
- **Safe destructive actions.** Delete, hide, replace image, password change, and publish actions require appropriate confirmation.
- **CMS-quality validation.** Required fields, invalid URLs, conflicting opening hours, missing translations, image constraints, and stale content should be caught inline and in a page summary.

## Information Architecture

Recommended top-level admin areas:

1. **Overview**
   - Site status, tasks, recent activity, public preview, quick actions.
2. **Content**
   - Menu, lunch, events, notices, pages/settings copy.
3. **Operations**
   - Opening hours, exceptions, seasonal status, contact messages if enabled.
4. **Media**
   - Uploads, gallery, menu images, alt text, captions, usage.
5. **Settings**
   - Brand/site info, SEO, social links, users/password/security.

Desktop layout:

- Left sidebar with grouped navigation and active state.
- Top bar with site switcher/name, preview link, publish status, user menu.
- Content header with title, description, primary action, secondary actions.
- Main content with list/detail split where useful.

Mobile layout:

- Compact top bar with brand, status, and menu button.
- Drawer navigation grouped by area.
- Sticky bottom action bar only when there are unsaved changes or primary actions.
- Editors use full-width stacked fields, not squeezed tables.

## Core Screens

### Overview

Replace the current count dashboard with an operational command center.

Required content:

- Public site status: live, last published, unpublished changes count.
- Today panel: current opening status, kitchen closing time, active notices, events today.
- Content health: missing translations, menu items without images, empty SEO descriptions, expired notices/events.
- Recent activity: who changed what and when. If user accounts do not exist yet, show system-level timestamps.
- Quick actions: add menu item, update hours, add notice, upload image, preview site.

Acceptance:

- Operator can see the next most important admin action without opening another page.
- Dashboard has no decorative metrics that do not lead to an action.

### Menu CMS

Current screenshot shows the best-developed admin page, with categorized cards, reorder controls, image picker, and sticky save. It should become the reference pattern.

Required changes:

- Add toolbar with search, category filter, visibility filter, translation status filter, and `Add item`.
- Use a list/detail model: selecting `Edit` opens an inline drawer or side panel on desktop and a full-screen editor on mobile.
- Replace `Up/Down` as primary reorder with drag handles on desktop and explicit `Move to category/order` controls on mobile.
- Show item status: visible/hidden, missing EN copy, missing price, missing image, dietary tags, last edited.
- Add category management as first-class, not hidden at the bottom.
- Add bulk actions: hide, show, change category, duplicate, delete.
- Add preview of the public menu item inside the editor.
- Introduce reusable dietary tag presets with labels, not only comma text.

Data model target:

```json
{
  "id": "item_123",
  "status": "published",
  "visible": true,
  "category_id": "summer-tacos",
  "sort_order": 10,
  "price": 18.0,
  "dietary_tags": ["L", "G"],
  "image_id": "media_456",
  "content": {
    "fi": { "name": "Kuhataku", "description": "" },
    "en": { "name": "", "description": "" }
  },
  "updated_at": "2026-05-03T08:00:00+03:00"
}
```

### Lunch

The current lunch screenshot exposes a mobile blocker: the table clips at 390px and hides later fields.

Required changes:

- Replace the table with weekday sections.
- Each day has repeatable lunch cards with compact summary and edit expansion.
- Add `Duplicate from previous day`, `Clear week`, `Hide lunch this week`, and `Publish week`.
- Support multiple lunch entries per day.
- Add validity window for weekly lunch menus if needed.
- Use price input with currency formatting and dietary tag picker.

Acceptance:

- At 390px all lunch fields are reachable without horizontal scrolling.
- Adding a full week of lunches is possible with fewer than 25 focused field interactions after first entry.

### Opening Hours

Current desktop table is readable, but the mobile screenshot clips columns and sticky save covers content.

Required changes:

- Replace mobile table with one card per weekday.
- Each day has open/closed toggle, open time, close time, kitchen close time, note.
- Add validation for impossible ranges: close before open, kitchen after close, missing times when open.
- Add exception calendar/list with upcoming exceptions first.
- Add templates: regular week, summer hours, closed day, private event.
- Add computed preview: `Today public site shows: Open 11:00-22:00, kitchen until 21:00`.

Acceptance:

- Closed days disable irrelevant time inputs.
- Exceptions clearly override weekly hours.

### Notices

Current notices are a sparse table with no live preview.

Required changes:

- Use notice cards with text, style, date range, active state, and public preview.
- Add scheduling states: scheduled, active, expired, draft.
- Add notice types with semantic meaning: info, warning, closed, event, private booking.
- Add optional dismissible flag if public site supports dismissal.
- Show date conflict warnings if multiple high-priority notices overlap.

Acceptance:

- Operator can tell whether a notice is visible today without mentally comparing dates.

### Events

Current event page has a solid create form but empty-state and list handling are basic.

Required changes:

- Split into `Upcoming`, `Draft`, and `Past` tabs.
- Add event image/media, external ticket/link field, location, featured toggle, visibility, and language completeness.
- Add duplicate event and archive actions.
- Add public card preview.
- Add default sort by date, with manual featured ordering where needed.

Acceptance:

- Past events do not compete with upcoming operational work.
- Empty state explains the exact next action and why events may not be public.

### Gallery And Media

Current gallery and menu image uploads are separate. Professional CMS behavior needs one media system.

Required changes:

- Create a media library for all uploads.
- Store image metadata: file, dimensions, size, alt text FI/EN, caption FI/EN, usage, uploaded_at.
- Allow upload from media screen, gallery screen, and menu editor using the same component.
- Add grid/list toggle, search, filters, and unused-media view.
- Add usage warnings before deleting media.
- Add image validation and resizing strategy.

Acceptance:

- Uploaded media can be reused across menu and gallery.
- Deleting a used image requires confirmation showing where it is used.

### Settings

Current settings page is too long and mixes brand copy, contact info, social links, SEO, and password.

Required changes:

- Split into tabs/sections: Site identity, Contact, Social links, SEO, Security.
- Use language tabs for bilingual fields on mobile.
- Replace raw HTML textareas with either:
  - a constrained rich-text editor, or
  - plain text fields rendered safely as paragraphs.
- Add URL validation for social links.
- Move password change into Security with current password confirmation if possible.
- Add SEO preview for FI and EN.

Acceptance:

- Settings page is navigable without scrolling through unrelated sections.
- Raw HTML editing is removed or clearly labeled as advanced.

## Publishing And State

Minimum viable workflow:

- Editing writes to draft state.
- `Save draft` persists changes without publishing.
- `Preview` opens public site using draft data or a preview parameter.
- `Publish` promotes draft data to live JSON.
- `Discard changes` reverts draft to live state.

If full draft/live storage is too large for phase one, implement explicit unsaved-change detection, validation, and last-saved timestamps first.

State labels:

- Draft
- Published
- Hidden
- Scheduled
- Expired
- Needs translation
- Missing required data

## Design System Requirements

- Use semantic tokens: `surface`, `surface-muted`, `border`, `text`, `muted`, `primary`, `danger`, `success`, `warning`.
- Keep radius at 8px for controls and 12px maximum for major panels unless existing cards require compatibility.
- Replace emoji and entity icons with a consistent icon set or pure text labels.
- Buttons use clear hierarchy: primary, secondary, ghost, danger.
- Forms use consistent field height, help text, errors, and disabled states.
- Tables are allowed only for read-heavy desktop views, not as the only editor UI.
- Sticky action bars must not cover focused fields; add bottom padding equal to bar height.

## Accessibility Requirements

- Every control has a programmatic label.
- Icon-only controls have accessible names.
- Focus states meet WCAG 2.1 AA.
- Dialogs/drawers trap focus and restore focus on close.
- Validation errors are announced and linked to fields.
- Drag-and-drop reorder has keyboard alternatives.
- Color is never the only signal for status.

## Technical Architecture

Current stack:

- PHP 8.3+
- Flat JSON in `data/`
- Vanilla JS
- CSS split between `admin/includes/header.php` inline styles and `admin/assets/admin.css`
- Playwright screenshot capture through `npm run screenshots`

Recommended near-term architecture:

- Move inline admin CSS out of `admin/includes/header.php` into `admin/assets/admin.css`.
- Add reusable PHP render helpers or partials for admin shell, page header, toolbar, empty state, form field, language tabs, media picker, status badge, sticky action bar.
- Add an admin-specific JS module layer for drawers, dirty-state tracking, validation summaries, list filtering, and mobile navigation.
- Keep JSON writes atomic with `LOCK_EX`, but add timestamps and stable IDs everywhere.
- Add schema validation functions before saving each content type.

Potential future SaaS migration:

- Replace JSON files with database tables for tenants, users, roles, media, revisions, content objects, and publish snapshots.
- Introduce role-based permissions: owner, editor, viewer.
- Add audit log and content revisions.
- Add background image processing and object storage for media.

## Commands

Current available commands:

```bash
php -d session.save_path=/tmp -S 127.0.0.1:8080
ADMIN_PASSWORD='your-admin-password' npm run screenshots
```

Recommended commands to add:

```bash
npm run lint
npm run format
npm run test
npm run screenshots
```

## Implementation Plan

### Phase 1: Admin Foundation

- Extract admin shell CSS from inline header styles.
- Create shared admin components/partials.
- Replace horizontal mobile nav with drawer or grouped menu.
- Add dirty-state tracking and safer sticky action behavior.
- Add common status badges, empty states, page toolbars, and validation summary.

### Phase 2: Mobile-Safe Editors

- Rebuild lunch editor as weekday cards.
- Rebuild hours editor as day cards plus exception list.
- Rebuild notices as cards with schedule state.
- Ensure every core workflow works at 390px without horizontal scrolling.

### Phase 3: Content Workflow

- Add last-updated timestamps and stable object IDs.
- Add validation per content type.
- Add preview cards for menu items, events, notices, SEO, and hours.
- Add missing translation indicators.
- Add search/filter/sort to menu, media, events, and notices.

### Phase 4: Media Library

- Create centralized upload/media metadata.
- Integrate media picker into menu and gallery.
- Add captions, alt text, usage tracking, deletion protection.

### Phase 5: Publish Model

- Introduce draft/live separation or revision snapshots.
- Add preview mode.
- Add publish/discard actions.
- Add dashboard activity and health cards.

## Task Breakdown

- [ ] Audit current admin CSS and move inline styles into `admin/assets/admin.css`.
- [ ] Define admin component partials for shell, nav, page header, card, toolbar, field, status badge, empty state, and sticky action bar.
- [ ] Implement responsive admin navigation with mobile drawer.
- [ ] Implement dirty-state tracking and sticky action bottom padding.
- [ ] Redesign lunch page into weekday card editor.
- [ ] Redesign hours page into weekday card editor and exception cards.
- [ ] Redesign notices page into scheduled notice cards.
- [ ] Add dashboard health cards and action-oriented recent status.
- [ ] Add validation helpers for settings, lunch, hours, notices, events, menu, and media.
- [ ] Add media library metadata and reusable picker.
- [ ] Add menu filters, translation status, and bulk visibility actions.
- [ ] Add settings tabs and SEO preview.
- [ ] Add screenshot regression coverage for desktop and 390px mobile admin screens.

## Boundaries

Always:

- Preserve existing public site routes and data compatibility during phase-one admin work.
- Keep all admin forms CSRF-protected.
- Validate and escape user content before rendering.
- Verify desktop and 390px mobile screenshots after each screen overhaul.

Ask first:

- Replacing JSON storage with a database.
- Adding a frontend framework.
- Adding paid services, object storage, or background workers.
- Changing authentication model or adding user accounts.

Never:

- Commit real admin passwords or secrets.
- Remove existing data fields without migration.
- Make public publishing behavior ambiguous.
- Depend on horizontal scrolling for primary mobile editing workflows.

## Success Criteria

- Admin navigation remains usable under 390px width with less header clutter than current screenshots.
- No primary admin editor requires horizontal scrolling on mobile.
- Every content object shows status, visibility, language completeness, and last updated state.
- Operators can preview changes before publishing or saving live content.
- Empty states and validation errors tell the user the next concrete action.
- Media upload and selection are unified.
- Screenshot capture covers all admin screens after the overhaul.

## Open Questions

- Should the first overhaul keep direct live saves, or should draft/live publishing be part of the initial release?
- Is this admin expected to become multi-tenant SaaS, or should SaaS-quality mean product polish for a single-site CMS?
- Should restaurant staff edit raw HTML, constrained rich text, or plain text for long-form copy?
- Are user roles needed now, or is single-password admin acceptable for this project phase?
- Should public preview support unpublished draft data in phase one?
