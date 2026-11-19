# Brief: component toolkit, slice 4a: four building blocks, and the time zone picker that filters (knowii-oss/knowii#657, #1666)

You add four building blocks to the component toolkit (`apps/knowii/src/Components/ui`): `Combobox`, `Badge`, `LinkTabs` and `CoverImage`. The screens of the events (slice E1b, #1514, NOT your work) need them. You also use the new `Combobox` for the time zone picker of the profile (#1666). Front end only: no PHP, no API change. ONE commit on a local branch. You do not push, you do not open a pull request: a reviewer reads your diff afterwards.

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md` (Front-end rules, definition of done), `documentation/conventions/naming.md`.
2. The issue #1666 (`gh api repos/knowii-oss/knowii/issues/1666 --jq .body`): its five "Wanted" points are rules of this brief.
3. ADR 0049 (`documentation/adr/0049-*.md`), `documentation/design/component-toolkit/proposal.md` (section on slice 4), `documentation/architecture/frontend.md` (the parts "Toolkit" and "Tests").
4. In `apps/knowii/src/Components/ui`: `index.ts`, `Select.tsx` and `Select.stories.tsx` (the model for a Headless UI list in the landmark portal, its stories and its `play` functions), `ToolkitContext.tsx` (`useToolkit().linkComponent`), `boundary.spec.ts`, `stories.spec.tsx`, `stories-guard.spec.ts`, `Breadcrumb.tsx` (links through the toolkit context), `Avatar.tsx` (an image with a fallback), `cn.ts`, the contrast spec.
5. `apps/knowii/src/Pages/Profile/Partials/TimeZoneForm.tsx` and its spec, `apps/knowii/src/Components/TimeZone/*`, `libs/common/src/lib/utils/time-zone.fn.ts` (`timeZoneOptions()`) and its spec, `apps/knowii/src/styles.css` (the brand tokens).

## Rules

### All four blocks (ADR 0049)

1. Each block lives in `Components/ui`, is exported from `index.ts`, and imports nothing of the application (`boundary.spec.ts` and the lint rule check it). A link goes through `useToolkit().linkComponent`.
2. Each block has a story file: one story per state, in both themes where the other stories do so, with a `play` function for the keyboard path where the block has one. The stories run as tests with axe (`stories.spec.tsx`); the guard (`stories-guard.spec.ts`) fails for a component without stories.
3. Brand tokens only (no raw colour), no state by colour alone, visible focus, targets of 44 px on touch (`touch-target`), no sideways scroll at 360 px, usable at 400% zoom, forced colours. Text contrast holds in both themes (the contrast spec).
4. Props are typed strictly, with a docblock that says what the block is for and what it is not for. No prop that nothing uses.

### `Badge`

5. A short text label ("Draft", "Cancelled", "Happening now"). A `span`, never a button or a link. Variants by meaning: `neutral`, `info`, `success`, `warning`, `danger`. The text always carries the meaning: the colour only supports it. An optional leading icon is `aria-hidden`.

### `LinkTabs`

6. A row of links for views of one page that each have a URL ("Upcoming", "Past"). A `nav` with an `aria-label` (a required prop) and a list of links; the current one has `aria-current="page"` and a marker that is not only a colour (for example a thicker underline and a heavier weight). It is NOT an ARIA tab widget: no `role="tablist"`, no arrow keys. Props: `items` (`label`, `href`, `current`), `ariaLabel`. At 360 px the row scrolls inside itself or wraps: the page never scrolls sideways.

### `CoverImage`

7. A 16:9 box (`aspect-video`) with `object-cover`, rounded like `Card`. Props: `src` (nullable), `alt` (a string; empty means decoration and gives `alt=""`), `loading` (default `lazy`), `className`. Without a `src`, or when the image fails to load, the box shows a neutral placeholder with the same size (no layout shift) and no text. `width` and `height` attributes or the aspect box keep the place before the load.

### `Combobox`

8. Headless UI `Combobox` (the version of `package.json`), generic over the value type like `Select`. Its list renders in the landmark portal, like `Select`. Props in the spirit of `Select`: `value`, `onChange`, `options` (`value`, `label`, an optional `description`, an optional `keywords` list that the filter reads too), `label` or the id of a label, `disabled`, `placeholder`, an optional `pinned` list of values that stay at the top when the query is empty, an optional `filter` function, `noResultsText`.
9. Typing filters the list at each key. The default filter is forgiving (#1666 point 2): any part of the label or of a keyword, case and accents ignored, spaces, underscores and slashes the same ("new york" finds "America/New_York", "brus" finds "Europe/Brussels").
10. Keyboard: the arrows move, Enter picks, Escape closes and keeps the old value, Tab leaves and keeps the old value. A click outside keeps the old value. The text field shows the label of the picked value when it is closed.
11. A screen reader hears the number of results after the list changes (a polite status region: "12 results", "1 result", "No results"), and the option that is active.
12. A long list stays fast: about 420 options must filter without a visible delay. Render at most a fixed number of options (for example 50) with a last line "Type to see more" when more match, or use the virtual list of Headless UI. Choose one, test it, and say which in your report.

### The time zone picker (#1666)

13. `TimeZoneForm.tsx` uses the `Combobox` instead of the `Select`. It still saves at once with its status (#1569): nothing else of the form changes.
14. With an empty query, the zone of the browser and the current zone of the account are at the top (`pinned`), then the others in their order of today.
15. Each entry reads "Brussels, Europe (UTC+1)": the city (underscores as spaces), the region, the offset now. The `description` is the long name of the zone when `Intl` gives one ("Central European Standard Time"). The `keywords` hold the raw identifier ("Europe/Brussels"), so a person can type it. Build these texts in a new function of `time-zone.fn.ts` (in `libs/common`), with `Intl` only, and test it there. The value sent to the server stays the identifier.
16. If another screen uses the same `Select` of zones (search for `timeZoneOptions`), move it to the `Combobox` too, with the same entries.

## Scope (in the commit)

1. The four components, their stories, their exports.
2. The function of rule 15 with its Vitest spec; the change of `TimeZoneForm.tsx` (and of rule 16) with the specs updated: filter by a part of the name, by the city alone, with spaces for underscores, the pinned zones, Escape keeps the old value, the save at once, axe.
3. Documents: `documentation/architecture/frontend.md` (the toolkit list), ADR 0049 only if a rule of it changes (it should not), the screen atlas (`tools/screen-atlas/screens.json`) only if it lists the states of the time zone form.

## NOT in this commit

1. Any screen of the events, any use of `Badge`, `LinkTabs` or `CoverImage` in a page (slice E1b uses them).
2. The other candidates of slice 4 (status text, skeleton lines, "Load more", the banner with a question).
3. Any PHP, any API or schema change, any new dependency.

## How to work

1. You work in the git worktree that the person who gave you this brief names, on the branch `feat/657-toolkit-blocks` (already created from `origin/main`). It has links to `node_modules` and `vendor` of the main clone: do not remove or replace them, and do not run `npm install` or `composer install`. Do not touch the main clone `~/wks/knowii`.
2. Everything runs on the host, from the root of the worktree. No Docker, no Pest (no PHP changes).
3. Before the commit, after the last change: `npx nx format --all` (then check with `git status` that it changed only your files; revert any other file it touched), `npm run lint:knowii`, `npm run lint:common`, `npm run tsc`, `npm run tsc:spec`, `npm run test:all` (Vitest: it runs the stories, their guard and the boundary), `npm run storybook:build`. A piped command hides a failure: use `bash -c "set -o pipefail; ..."`.
4. Never name a third-party community product. No real personal email address in code, tests or documents.
5. The commit hook allows only the scopes all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build. The subject does not start with a capital letter. Suggested: `feat(ui): toolkit, a combobox that filters, a badge, link tabs and a cover image`. Footer: `Closes #1666` and `Refs #657`. No trailer line that names a tool or a co-author.
6. If a rule cannot be built as written, skip it, build the rest, and say why in the report. Do not invent product rules.

## Your final report (max 350 words)

1. The commit hash and `git show --stat`.
2. Each choice where the brief left room (rule 12 included).
3. The result of each command of "How to work" point 3, with the exact text of anything that failed.
4. Anything of the scope not built, and why.
