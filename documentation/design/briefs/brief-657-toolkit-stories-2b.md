# Brief: component toolkit, slice 2b: stories for the last eleven components (knowii-oss/knowii#657)

Front-end only: it can run in a second git worktree on the host (no container), with `node_modules` linked. ONE commit on a branch `feat/657-toolkit-stories-2b` from `origin/main` (after slice 2a, #1652, is merged; else stacked on its branch). You do not push and you do not open a pull request.

## Read first

1. `AGENTS.md`, `documentation/conventions/rules.md` (front-end rules), ADR 0049, `documentation/design/component-toolkit/proposal.md`, the issue #657 with its comments.
2. The story files that exist, as the pattern to follow exactly: `Dialog.stories.tsx` (a portal and the focus), `Select.stories.tsx` and `RelativeTime.stories.tsx` (a list, a fixed clock), `Breadcrumb.stories.tsx` (a link that must not visit, the phone viewport), `stories.spec.tsx` and `stories-guard.spec.ts` (the guard and its allow-list `COMPONENTS_WITHOUT_STORIES`).

## Scope

One story file for each of: `AccountTimeZone`, `Drawer`, `DropdownMenu`, `ErrorBoundary`, `ImageInsertPanel`, `ImageViewer`, `MarkdownContent`, `MarkdownEditor`, `ThemeToggle`, `Toaster`, `Turnstile`. Remove each name from the allow-list: it must be empty at the end, and code of the guard that exists only for a list that is not empty goes.

## Rules

1. One story per variant and per state of the component (read the component and its spec), and a `play` function with assertions where the component has behaviour: the keyboard path, open and close, Escape, where the focus goes after a close, a change of value, an error state, a message linked with `aria-describedby`.
2. Do NOT change a component. If a story shows a bug or an accessibility problem, keep the story honest, leave out an assertion that would fail, and list the problem in the report with the file and the line (the problems of slice 2a are in #1651).
3. No network, no third-party script, no router of the server, no clock of the machine without control. `Turnstile` loads an outside script: replace it with a local fake as its spec does. `MarkdownEditor`, `ImageInsertPanel` and `MarkdownContent` get fakes for the preview, the upload and the mention source, like their specs. `Toaster` uses the toast context with fixed toasts, also one with an action link (it stays until it is closed). An `ErrorBoundary` story throws on purpose: silence the expected error inside the story only.
4. Both themes and a small screen as the existing stories do. No new mechanism, no new dependency, no change of the Storybook configuration unless a story cannot work without it.
5. Example data: gardening topics or neutral names, `example.com` addresses. Never the name of a third-party community product.

## Checks

`npx nx run-many -t lint typecheck test -p knowii common`, `npm run tsc`, `npm run tsc:spec`, `npx prettier --check <files>`, `npm run storybook:build`. `Notifications.spec.tsx` can time out on a busy machine: run it alone then.

## Commit

Suggested subject: `test(ui): stories for the last eleven components of the toolkit`. Footer: `Refs #657`. No trailer line.

## Report

The commit and its stat; for each component the number of stories and what the `play` functions assert; every problem of a component that a story showed; what changed in the guard; the results of the checks.
