# Brief: component toolkit, slice 4c: the field label, the field error and the card grid move into the toolkit (knowii-oss/knowii#657)

You move three shared components from `apps/knowii/src/Components` into the component toolkit (`apps/knowii/src/Components/ui`), give each one its stories, and remove a fourth one that only wraps a toolkit block. The move is mechanical: no screen changes its look, its markup or its words. Front end only: no PHP, no API change, no new dependency. ONE commit on a local branch. You do not push, you do not open a pull request: a reviewer reads your diff afterwards.

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md` (the front-end rules, the definition of done), `documentation/conventions/naming.md`.
2. ADR 0049 (`documentation/adr/0049-*.md`, consequences 1, 2 and 6), `documentation/architecture/frontend.md` (the part "Toolkit", all its points).
3. In `apps/knowii/src/Components/ui`: `index.ts`, `stories.spec.tsx`, `stories-guard.spec.ts`, `boundary.spec.ts`, `cn.ts`. The models of a small block with its stories: `Badge.tsx`, `Divider.tsx`, `EmptyState.tsx`, `TextInput.tsx`, each with its `.stories.tsx`.
4. The four files that this slice touches: `Components/InputError.tsx`, `Components/InputLabel.tsx`, `Components/CardGroup.tsx`, `Components/Separator.tsx`, and any spec of them.

## Inventory (at the start of this slice)

1. `InputError` (23 lines): a `div` with a `p role="alert"` in `text-sm text-danger-text`. It renders nothing without a message and without children. 32 files import it.
2. `InputLabel` (15 lines): a `label` in `block text-sm font-bold text-primary`. 23 files import it.
3. `CardGroup` (14 lines): a `ul` grid for cards. 1 file imports it.
4. `Separator` (5 lines): `<Divider className="my-10" />` and nothing else. 4 files import it.

Count them again before you start (`grep -rn "Components/InputError'"` and the others). If a count differs much, say it in the report and go on.

## Rules

1. `InputError`, `InputLabel` and `CardGroup` move to `Components/ui/<Name>.tsx` with `git mv`, so that the history follows. Each one becomes a named export, as the other toolkit blocks, and `index.ts` exports it. The props, the markup, the classes and the behavior do not change. Keep each name.
2. Each of the three gets a docblock in the style of the toolkit (what it is for, what it is not for, its accessibility contract) and strict prop types. Do not add a prop. Do not remove a prop that a caller uses. If a prop has no caller at all, say it in the report and keep it: a removal changes the public shape, and this slice is a move.
3. Each of the three gets a story file. The stories are the tests (`stories.spec.tsx` runs each one with the automated accessibility check, and the guard fails without a story file).
   1. `InputError`: with `message`; with children; without both (nothing renders); with an `id` that a `TextInput` names in `aria-describedby` (the `play` proves that the description of the field is the message); a long message at the phone width. `play`: the message has the role `alert`.
   2. `InputLabel`: with `value`; with children; with `htmlFor` beside a `TextInput` (the `play` proves that the field has the label as its name); with an `id` that a group names in `aria-labelledby`.
   3. `CardGroup`: three `Card` blocks; one card; at the phone width (one column). `play`: the root is a list. Check what its children must be so that the list is valid (list items): write the stories as the one caller uses it, and if that caller puts something else than list items into the `ul`, do not fix it here: say it in the report.
4. Every import of the three changes to the toolkit entry (`import { InputError } from '@/Components/ui'`, merged into an import of `@/Components/ui` that the file already has). No other line of a caller changes. A spec that mocks one of the old paths follows the new path.
5. `Separator` goes away. Its four callers use `<Divider className="my-10" />` from `@/Components/ui`. The file `Components/Separator.tsx` is deleted.
6. The boundary holds: a file of `Components/ui` imports only React, Headless UI, the icons and `@knowii/common` (`boundary.spec.ts` and the lint rule check it).
7. The look does not change. For each caller, the HTML that reaches the browser is the same as before, class for class. The specs of the feature components pass without a change of what they expect. If a spec needs a change other than an import path or a mock path, stop on that file, leave it as it was, and say it in the report.
8. Documents, same commit: `documentation/architecture/frontend.md`, in the part "Toolkit": one new point that names the three blocks with what each is for and its contract, and that says that `Separator` is gone (use `Divider`). Correct any other document or code comment that names one of the four old paths (`grep -rn "Components/InputError\|Components/InputLabel\|Components/CardGroup\|Components/Separator" documentation apps libs`).

## NOT in this commit

1. The layout shells one level up (`SectionTitle`, `FormSection`, `ActionSection`, `PageTitle`, `PageWrapper`, `PageContentWrapper`): they wait for the one settings layout (#1590) and the section header (#1601).
2. The components that belong to the application (`PageHeader`, `Footer`, `MainMenu` and the others): they stay where they are.
3. A new name, a new prop, a new look, a merge of `InputError` with `SaveStatus` or of `InputLabel` into the field blocks: none of it. The status text is #1591.
4. The rule pages (slice 4d) and the documentation and publication (slice 5).
5. Any PHP, any API or schema change, any new dependency.

## How to work

1. You work in the git worktree that the person who gave you this brief names, on the branch that they name. It has links to `node_modules` and `vendor` of the main clone: do not remove or replace them, and do not run `npm install` or `composer install`. Do not touch the main clone `~/wks/knowii`.
2. Everything runs on the host, from the root of the worktree. No Docker, no Pest (no PHP changes).
3. Do the import change with a small script or by hand, file by file, and read the diff of each file: an import line must not lose another name, and a file must not get two imports of `@/Components/ui`.
4. Before the commit, after the last change: `npx nx format --all` (then check with `git status` that it changed only your files, and revert any other file it touched), `npm run lint:knowii`, `npm run lint:common`, `npm run tsc`, `npm run tsc:spec`, `npm run test:all` (Vitest: it runs the stories, their guard and the boundary), `npm run storybook:build`. A piped command hides a failure: use `bash -c "set -o pipefail; ..."`. Delete the folder of the Storybook build after the check.
5. Never name a third-party community product. No real personal email address in code, tests or documents.
6. The commit hook allows only the scopes all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build. The subject does not start with a capital letter. Suggested: `refactor(ui): toolkit, the field label, the field error and the card grid move in, and the separator goes`. Footer: `Refs #657`. No trailer line that names a tool or a co-author.
7. If a rule cannot be built as written, skip it, build the rest, and say why in the report. Do not invent product rules.

## Your final report (max 300 words)

1. The commit hash and the last line of `git show --stat`.
2. The counts: callers changed per component, and specs changed with the reason for each.
3. Each place where a line other than an import or a mock path changed in a caller.
4. Each choice where the brief left room (rule 2, rule 3.3).
5. The result of each command of "How to work" point 4, with the exact text of anything that failed.
6. Anything of the scope not built, and why.
