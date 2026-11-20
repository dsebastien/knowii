# Brief: component toolkit, slice 4b: skeleton lines, "Load more", the banner with a question, the count and the dot (knowii-oss/knowii#657)

You add five small building blocks to the component toolkit (`apps/knowii/src/Components/ui`): `Skeleton`, `LoadMoreButton`, `QuestionBanner`, `CountBadge` and `Dot`. Each one replaces code that the feature components write by hand today. The screens keep their look, with two exceptions that this brief names. Front end only: no PHP, no API change, no new dependency. ONE commit on a local branch. You do not push, you do not open a pull request: a reviewer reads your diff afterwards.

Line numbers in this brief are those of `main` at `583701ad`.

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md` (Front-end rules 2, 3, 4, 9 and 12, the definition of done), `documentation/conventions/naming.md`.
2. The issue #657 and its comments (`gh api repos/knowii-oss/knowii/issues/657 --jq .body`, `.../657/comments --jq '.[].body'`): the slice plan, the decisions of the owner, the progress notes.
3. ADR 0049 (`documentation/adr/0049-*.md`, consequences 1, 2 and 6), `documentation/design/component-toolkit/proposal.md` (section 3, slice 4), `documentation/architecture/frontend.md` (the part "Toolkit", points 1 to 9).
4. In `apps/knowii/src/Components/ui`: `index.ts`, `stories.spec.tsx`, `stories-guard.spec.ts`, `boundary.spec.ts`, `cn.ts`. The models of a small block with its stories: `Badge.tsx`, `SaveStatus.tsx`, `EmptyState.tsx`, `NoAccessPanel.tsx`, `Spinner.tsx`, each with its `.stories.tsx`. `Button.tsx` (the `loading` state, lines 62 to 142).
5. The code that the blocks replace: the files of the section "Inventory", at the lines it gives, each with its spec.
6. The issues of the UI review that name these blocks (`gh api repos/knowii-oss/knowii/issues/<n> --jq .body`): #1588 (skeleton), #1579 (the dot), #1600 (buttons built by hand). They say where the look goes later. You do not build their fixes.

## Inventory (what is written by hand today)

The search covered `apps/knowii/src/Components` (without `ui`), `Pages` and `Layouts`, without the specs.

### In this slice

1. Skeleton lines. 2 blocks, 1 file, no toolkit block today. `Components/Notifications/NotificationsPanel.tsx:696-708` (4 rows: a round shape and two lines) and `:766-777` (2 rows: a round shape and one line). Each shape is a `span` with `bg-overlay/10 motion-safe:animate-pulse`.
2. "Load more". 9 buttons, 7 files. `Button` covers the button, nothing covers the row around it, and the busy state is built in two ways.
   1. `Components/Resources/ResourceList.tsx:130-136`: "Load more resources", centered, `loading`.
   2. `Components/Posts/PostList.tsx:186-192`: "Load more posts", centered, `loading`.
   3. `Components/Events/EventList.tsx:293-299`: "Load older" or "Load more" (`event-words.ts:14-16`), centered, `loading`.
   4. `Components/Communities/CommunityMembersManager.tsx:337-343`: "Load more", centered, `loading`.
   5. `Components/Communities/AccessGroupsManager.tsx:341-347`: "Show more", small, centered, no busy state.
   6. `Components/Chat/ChatRoom.tsx:353-359`: "Load older messages", small, centered, above the list, `loading`.
   7. `Components/Notifications/NotificationsPanel.tsx:728-739`: "Load older", small, at the start, `aria-busy` and `aria-disabled` by hand, no spinner.
   8. `Components/Notifications/NotificationsPanel.tsx:796-809`: "Load more" with the name "Load more in Following", the same hand-made busy state.
   9. `Components/Notifications/NotificationsPanel.tsx:844-855`: "Load more", the same hand-made busy state, `mt-2 ml-2`.
3. The banner with a question. 1 use. `Components/TimeZone/TimeZoneAsk.tsx:101-120`: a named region at the top of the page, the question, two small buttons, an alert when the save fails.
4. The count. 4 uses, 4 files, no toolkit block today (`Badge` says in its docblock that it is not a count).
   1. `Components/Spaces/SpaceNavigation.tsx:106-109`, `Components/Spaces/CommunityShell.tsx:161-164`, `Components/Messages/ConversationList.tsx:60-63`: the same `span` three times (`shrink-0 rounded-full bg-secondary px-1.5 text-xs font-bold text-white`), with the hidden word " unread".
   2. `Components/Notifications/NotificationBell.tsx:137-144`: the count in the corner of the bell, hidden from screen readers (the name of the button holds the number), "9+" above nine (`notificationBadge()` in `libs/common/src/lib/types/notification.schema.ts:109`).
5. The dot. 3 uses, 3 files, no toolkit block today.
   1. `Components/Spaces/SpaceNavigation.tsx:99-102`: "new content", with the hidden text ", New content".
   2. `Components/Presence/PresenceAvatars.tsx:36` and `Components/Presence/OnlineListPanel.tsx:59`: "online", in the corner of an avatar, decoration only.

### Found, and not in this slice

6. The status text. `SaveStatus` exists and 4 files use it (`OnlineStatusForm.tsx:37`, `JoinPolicySetting.tsx:75`, `IndexingSetting.tsx:52`, `DirectMessagesSetting.tsx:35`). Still by hand: "Not saved. Retry." as `<p role="alert">` 7 times in 6 files (`TimeZoneForm.tsx:78`, `SpaceLevelsForm.tsx:202` and `:221`, `PopupsForm.tsx:39`, `TimeZoneAsk.tsx:115`, `ThreadFollowControl.tsx:139`, `SpaceLevelControl.tsx:109`), a hidden "Saving" twice (`TimeZoneForm.tsx:76`, `SpaceLevelsForm.tsx:200`), "Saved" in a status once (`SpaceLevelControl.tsx:105`), and the "Saved" bar of the notification settings (`Pages/Profile/Notifications.tsx:56-64`).
7. Pills and labels. `Badge` exists and 2 files of the events use it (4 uses). Still by hand: 3 outline chips in `Pages/Spaces/Show.tsx:147-152`, the chips of `Components/Resources/ResourceDetails.tsx:23` (4 uses), the role pill in `Pages/Communities/About.tsx:164` and `Components/Communities/CommunityMembersManager.tsx:317`, "Your answer" in `Components/SpacePages/LessonQuiz.tsx:283`, the chip with a remove button in `Components/Messages/MemberPicker.tsx:98`.
8. The error of a list with "Retry". 3 times in `NotificationsPanel.tsx` (`:710-717`, `:779-786`, `:828-835`).
9. The shared components one level up. `Components/` holds 21 component files. 10 respect the boundary today: `InputError` (99 uses, 32 files), `InputLabel` (75 uses, 23 files), `Separator` (21 uses, 4 files, a wrapper of `Divider`), `ActionSection` (8), `FormSection` (2), `SectionTitle` (2), `CardGroup` (1), `PageTitle` (1), `PageWrapper` (5), `PageContentWrapper` (5). The other 11 belong to the application: they import Inertia, `ziggy-js`, `axios` or `zod`, or they hold its name, its mark or its wiring (`PageHeader`, `Footer`, `MainMenu`, `NewsletterForm`, `ConfirmsPassword`, `AuthenticationCard`, `AuthenticationCardLogo`, `BreadcrumbHome`, `ApplicationToolkit`, `ApplicationMark`, `ApplicationName`).
10. The rule pages (the comment of 2026-11-17 on #657). Storybook loads only `*.stories.tsx` today (`apps/knowii/.storybook/main.ts`), and no `.mdx` file exists. The named text styles of R-S69 are not in `styles.css`.

## Rules

### All five blocks (ADR 0049)

1. Each block lives in `Components/ui`, is exported from `index.ts`, and imports nothing of the application (`boundary.spec.ts` and the lint rule check it).
2. Each block has a story file: one story per state, a story at the phone width where the block has text that wraps (`globals: { viewport: { value: 'phone', isRotated: false } }`, as `Badge.stories.tsx` does it), and a `play` function for each point of its accessibility contract. The comment of the meta says when to use the block, when not, and its contract (name, role, states, focus). The stories run as tests with axe (`stories.spec.tsx`). The guard (`stories-guard.spec.ts`) fails for a component without stories.
3. Brand tokens only (no raw color), no state by color alone, visible focus, targets of 44 px on touch where the code of today has them, no sideways scroll at 360 px, usable at 400% zoom, forced colors.
4. Props are typed strictly, with a docblock that says what the block is for and what it is not for. No prop that nothing uses.
5. The look does not change. For each replacement, the classes that reach the browser are the classes of today, in any order. The two exceptions are rules 12 and 13. If a replacement cannot keep the look, leave that place as it is and say it in the report.
6. The specs of the feature components pass without a change of what they expect. If a spec needs a change, the markup changed: keep the change small and name it in the report.

### `Skeleton`

7. The grey lines that hold the place of a list while it loads. Props: `label` (a string, required: "Loading your notifications"), `rows` (a number, default 1), `lines` (1 or 2, default 2), `avatar` (a boolean: a round shape before the lines), `className`.
8. Contract: the root has `role="status"` and holds the label as text for screen readers only (the model is `Spinner`). The shapes are `aria-hidden`. Nothing takes the focus. The pulse runs only with `motion-safe`. In forced colors each shape has a border, so that it stays visible (`forced-colors:border`): this changes nothing outside that mode.
9. It replaces the two blocks of inventory point 1, with their wrapper: a `ul` cannot hold the status. Keep `aria-busy="true"` on a `div` around the block. The two constants `SKELETON_LINES` and `FOLLOWING_SKELETON_LINES` give `rows`. New for a screen reader: it hears "Loading your notifications" and "Loading Following". Nothing new on the screen.
10. Stories: one row with two lines, with the round shape, one line, four rows, the phone width. `play`: the status has the label, and the shapes add no text to it.

### `LoadMoreButton`

11. The button under a list (or above it, for the chat) that loads the next part. Props: `onClick`, `loading` (a boolean), `children` (the words of the button, required), `size` (`sm` or `md`, default `md`), `align` (`center` or `start`, default `center`), `aria-label`, `className` (for the row). It renders a row and a `Button variant="secondary"` with `loading`. Contract: a `button` with the words as its name. While it loads it has `aria-busy` and `aria-disabled`, keeps the focus, and a second click, Enter or Space does nothing (rule 12 of the front-end rules: `Button` does it). It is not for the result: the feature component shows the block only while there is more, and says what loaded in its own status region.
12. It replaces the nine buttons of inventory point 2, with the words of today. Look change 1: the three buttons of `NotificationsPanel.tsx` get the busy look of `Button` (a small spinner before the words) in place of no visible change. Their rows keep `self-start` and `mt-2 ml-2`.
13. Look change 2: "Show more" of `AccessGroupsManager.tsx:343` gets a busy state. Add a state around the call of `load` and give it to `loading`. Its words stay "Show more".
14. Stories: default, small, at the start, busy, a long text at the phone width, with an `aria-label`. `play`: Tab reaches the button and Enter calls `onClick` once. In the busy story a click and Enter do not call `onClick`, and the focus stays on the button.

### `QuestionBanner`

15. One plain band at the top of a page that asks one question with two answers ("Show times in Europe/Brussels?"). Not a dialog, not a toast, not for a message without a question. Props: `question` (the text), `actions` (the buttons, the answer that changes something first), `error` (a string or `undefined`), `className`.
16. Contract: a `div` with `role="region"`, named by the question through `aria-labelledby` (the block makes the id with `useId`). The question is a `p`, not a heading. The error shows after the buttons as `role="alert"`. The banner never takes the focus when it shows. It has no close button: each answer closes it, and the feature component removes it.
17. It replaces the markup of `TimeZoneAsk.tsx:101-120`. The logic, the texts, the buttons and their busy state stay in `TimeZoneAsk`. Do not change what the focus does after an answer: if the focus falls on the page body when the banner leaves, say it in the report.
18. Stories: default, the first answer busy, with the error, long zone names at the phone width. `play`: the region has the question as its name, Tab reaches the two buttons in their order, the alert holds the error text, and the focus is not inside the banner after the first render.

### `CountBadge`

19. The number of new things on a link or on a button ("3 unread"). Not a label (`Badge`), not a state. Props: `count` (a number), `max` (a number, optional: above it the text is the maximum and "+"), `label` (a string, optional: the words after the number for screen readers, "unread"), `placement` (`inline` or `corner`, default `inline`), `className`.
20. Contract: a `span`, never a control. A count of 0 or less renders nothing. With `label`, the accessible text is the number and the words ("3 unread"), and it becomes part of the name of the link around it. Without `label`, the badge is `aria-hidden`, and the control around it must hold the number in its own name: the docblock says so.
21. It replaces the four uses of inventory point 4. The three inline counts keep "unread" and have no maximum, as today. The bell uses `placement="corner"`, `max={9}` and no `label` (`notificationBellName()` gives the name). Remove `notificationBadge()` and its spec cases from `libs/common` when nothing uses it any more. `notificationBellName()` stays.
22. Stories: one, a number of two digits, above the maximum, zero (nothing renders), with the words inside a link, hidden inside a button with a name, in the corner of an icon button. `play`: the name of the link is "General 3 unread", the name of the button is "Notifications, 3 new", and the hidden badge adds nothing to it.

### `Dot`

23. A small round mark next to an item. Props: `tone` (`new` or `online`), `label` (a string, optional: the text for screen readers), `className` (the place, for the corner of an avatar). Each tone has the classes of today: `new` is `size-2.5 rounded-full border-2 border-current bg-secondary`, `online` is `size-2.5 rounded-full bg-success ring-2 ring-surface`.
24. Contract: the round shape is always `aria-hidden`. With `label`, the text sits next to it for screen readers only, and becomes part of the name of the link around it (", New content"). Without `label` the dot is decoration: the docblock says that the item must then say the same thing in words elsewhere. Never a control, never the only sign of a state for all people.
25. It replaces the three uses of inventory point 5. #1579 changes the look of the `new` tone later, in this one place.
26. Stories: `new` with its label inside a link, `online` in the corner of an `Avatar`, each tone alone. `play`: the name of the link is "General, New content", and the dot without a label adds nothing to the accessibility tree.

## Scope (in the commit)

1. The five components, their stories, their exports in `index.ts`.
2. The replacements of rules 9, 12, 13, 17, 21 and 25 in the 14 feature files of the inventory, and `notification.schema.ts` with its spec (rule 21).
3. A Vitest spec only for a case that a story cannot hold. The stories are the tests.
4. Documents: `documentation/architecture/frontend.md`, a new point 10 in the part "Toolkit" that names the five blocks, what each one is for and its contract, in the style of point 9. ADR 0049 does not change. The screen atlas does not change.

## NOT in this commit

1. The status text (inventory point 6). `SaveStatus` in the six files changes an alert into a polite status with an icon, and the "Saved" bar goes away: that is the fix of #1591, with pictures for the owner.
2. Pills and labels (inventory point 7). `Badge` has square corners and a border, the chips of today are round: that is the fix of #1602 (point 3), with pictures for the owner.
3. The error of a list with "Retry", and a skeleton in the lists that have none today (`CatchUpCard`, `CommunityMembersManager`, `AccessGroupsManager`, `ThreadPanel`): #1588. Each one is a new look.
4. The move of the shared components (inventory point 9). It is slice 4c: `InputError` and `InputLabel` move into `Components/ui` with their stories, `Separator` goes away for `Divider`, `CardGroup` moves. About 60 files change their imports, so that diff stays alone. `SectionTitle`, `FormSection`, `ActionSection`, `PageTitle`, `PageWrapper` and `PageContentWrapper` wait for the one settings layout (R-S54, #1590) and the section header (R-S69, #1601): a move now writes down two layouts as variants. The 11 components of the application stay where they are.
5. The rule pages (inventory point 10): the named text styles (R-S69), the action rules (R-S73), the width rule (R-S68), the one settings layout (R-S54), the word list and the style sheet. They are slice 4d, a slice of its own, as the comment of 2026-11-17 on #657 allows. It adds the documentation pages to the Storybook configuration and the text styles to `styles.css`.
6. For slice 5: the "when to use, when not" page of each component in the published catalogue, the update of ADR 0049 and of the status line of `proposal.md` (it names only slice 1), the publication of the built Storybook on GitHub Pages.
7. A new word on a button, a new look of the dot (#1579), a `link` variant of `Button` (#1600), any other fix of the UI review.
8. Any PHP, any API or schema change, any new dependency.

## How to work

1. You work in the git worktree that the person who gave you this brief names, on the branch `feat/657-toolkit-slice-4b` (already created from `origin/main`). It has links to `node_modules` and `vendor` of the main clone: do not remove or replace them, and do not run `npm install` or `composer install`. Do not touch the main clone `~/wks/knowii`.
2. Everything runs on the host, from the root of the worktree. No Docker, no Pest (no PHP changes).
3. Before the commit, after the last change: `npx nx format --all` (then check with `git status` that it changed only your files, and revert any other file it touched), `npm run lint:knowii`, `npm run lint:common`, `npm run tsc`, `npm run tsc:spec`, `npm run test:all` (Vitest: it runs the stories, their guard and the boundary), `npm run storybook:build`. A piped command hides a failure: use `bash -c "set -o pipefail; ..."`.
4. You make no pictures. The person who gave you this brief makes the before and after pictures of the two look changes for the owner, from your report.
5. Never name a third-party community product. No real personal email address in code, tests or documents.
6. The commit hook allows only the scopes all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build. The subject does not start with a capital letter. Suggested: `feat(ui): toolkit, skeleton lines, a load more button, a question banner, a count and a dot`. Footer: `Refs #657`. No trailer line that names a tool or a co-author.
7. If a rule cannot be built as written, skip it, build the rest, and say why in the report. Do not invent product rules.

## Your final report (max 350 words)

1. The commit hash and `git show --stat`.
2. Each choice where the brief left room.
3. Each place where the look or the markup changed, with the file and the line (rules 5, 6, 12, 13 and 17).
4. The result of each command of "How to work" point 3, with the exact text of anything that failed.
5. Anything of the scope not built, and why.

## Questions for the owner

Until the owner answers, the builder builds what each recommendation says. The owner approves the two look changes from pictures.

1. The busy look of "Load older" and "Load more" in the notifications panel (rule 12). Today nothing shows while they load. Recommendation: the spinner of `Button`, as in the six other lists. Without your approval, these three buttons stay as they are.
2. `QuestionBanner` has one use today (the time zone question). Recommendation: build it now, because the issue names it and the block is small. The other choice: wait for a second use.
3. The count has two rules today: "9+" on the bell, no limit in the navigation and in the messages. Recommendation: keep both in this slice (no look change), and choose one limit with the word list of slice 4d.
4. The words of the buttons: "Load more", "Load more posts", "Load more resources", "Show more", "Load older", "Load older messages". Recommendation: no change in this slice. For the word list of slice 4d: "Load more" for a list that grows down, "Load older" for a list by time, no noun, and "Show more" becomes "Load more".
5. The order after this slice. Recommendation: slice 4c (the move of `InputError` and `InputLabel`) at once, and slice 4d (the rule pages) after your decisions on #1590, #1600 and #1601, so that each page states a rule that the code can follow.
