# Brief: fix the fourteen problems of shared components that the stories showed (knowii-oss/knowii#1651)

Front-end only: it runs in a second git worktree on the host (no container), with `node_modules` linked. ONE commit on a branch `fix/1651-component-problems` from `origin/main` (after slice 2b, #1658, is merged). You do not push and you do not open a pull request.

## Read first

1. `AGENTS.md`, `documentation/conventions/rules.md` (front-end rules), ADR 0049, `documentation/architecture/frontend.md` (section "Toolkit").
2. The issue and its comment: `gh issue view 1651 --comments`. Items 1 to 6 are in the body, items 7 to 14 in the comment.
3. The component and its story file for each item, and the stories that work around a problem today (their comments say so).

## Scope: one fix per item, with an assertion in the story

1. `Select`: accept `aria-describedby` (and pass it to the button), so that an error text is linked to the field. Story `WithError` asserts the description.
2. `Select` (item 2) and `DropdownMenu` (item 9): the open list or menu must be inside a landmark (axe `region`). Use the mechanism of Headless UI (a `PortalGroup` with a target inside the application root, or no portal where the layout allows it). It must work in the application (`AppLayout`, the public layout) and in the stories. Remove the "close at the end" workarounds of the stories that only exist for this rule.
3. `Breadcrumb`: "Show the full path" puts the focus on the first revealed item that can take it; when that item has no link, on the list itself (`tabIndex={-1}`), never on `body`. Story `LongPathOnPhoneWithoutLink` asserts the focus.
4. `Avatar`: when the picture fails to load, show the initials. Story `WithBrokenPicture` asserts it.
5. `PasswordInput`: the toggle keeps one name ("Show password") and carries the state in `aria-pressed` only.
6. `PasswordInput`: the strength hint also works without a `value` prop, and its `aria-live` region is in the page (empty) before its first text.
7. `Toaster`: an error toast keeps its `li` as a list item; the alert is announced from an element that does not break the list, and a toast is never announced twice. Remove the workaround of the error story.
8. `MarkdownEditor`: with mentions, the textarea keeps its own role (no `role="combobox"`); use `aria-autocomplete="list"`, `aria-controls` while the list is open, and `aria-activedescendant` for the active member. Add the stories with mentions (the comment at `MarkdownEditor.stories.tsx` near line 141 says why they are missing): "@" opens the list, the arrows move, Enter picks a member, Escape closes the list, and a search with no result.
9. See point 2.
10. `ErrorBoundary`: after "Try again", the focus goes to the start of the region that came back (or to the message when it fails again), never to `body`.
11. `ImageInsertPanel` (description) and `MarkdownEditor` (video address): the error text is in `aria-describedby`, with the hint.
12. `MarkdownEditor`: the `role="toolbar"` follows the toolbar pattern: one Tab stop, the arrows move between the buttons (Home and End too), the focus stays on the last used button.
13. `MarkdownEditor`: "The preview is not available" is announced (`role="status"`).
14. `ImageInsertPanel`: Enter in a description does nothing while `busy`.

## Rules

1. Keep the public props of each component working for every caller; search the callers (`grep -rn "<Select" apps/knowii/src` and so on) and change them only when a fix needs it.
2. No new dependency, no change of the Storybook configuration, no change of ADR 0049: only `color-contrast` stays off.
3. Each fix has an assertion in the story of the component (the stories run as tests). Keep the Vitest specs of the components green; add a spec only where a story cannot show the case.
4. A change that a person can see (the avatar fallback, the toolbar focus ring, the toast) is listed in the report, so that pictures can be made before the merge.
5. Example data: gardening topics or neutral names, `example.com` addresses. Never the name of a third-party community product.

## Checks

`npx nx run-many -t lint typecheck test -p knowii common`, `npm run tsc`, `npm run tsc:spec`, `npx prettier --check <files>`, `npm run storybook:build`. Some specs time out on a busy machine (`Notifications`, `CourseMaterials`, `QuizEditor`, `AccessGroupsManager`, `NotificationsPanel`, `SpaceDialog`): run a failing file alone before you call it a failure.

## Commit

Suggested subject: `fix(a11y): fourteen problems of shared components that the stories showed`. Footer: `Closes #1651`. A wrapped line must not start with a word and a colon; lines under 100 characters. No trailer line.

## Report (max 500 words)

The commit and its stat; for each item, what changed and what its story asserts; every change that a person can see; any item not done and why; the results of the checks.
