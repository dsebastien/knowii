# Brief: component toolkit, slice 3: the boundary lint rule (knowii-oss/knowii#657)

Front-end only: it runs in a second git worktree on the host (no container), with `node_modules` and `vendor` linked from the main tree. ONE commit on a branch `feat/657-toolkit-boundary` from `origin/main`. You do not push and you do not open a pull request.

## Read first

1. `AGENTS.md`, `documentation/conventions/rules.md` (front-end rules), ADR 0003, ADR 0049, `documentation/design/component-toolkit/proposal.md` (points 2.5, 1.3 and 3.3), `documentation/architecture/frontend.md` (section "Toolkit").
2. The ESLint configuration of the workspace (`eslint.config.mjs` at the root and in `apps/knowii`).

## The rule

1. Files of `apps/knowii/src/Components/ui/` (not their `*.spec.*` and `*.stories.*` files) import only: `react`, `react-dom` (only where a component needs it, today `react-dom/client` in one file), `@headlessui/react`, `react-icons/*`, `@knowii/common`, the small helpers that build class names (`classnames`, `tailwind-merge`), `date-fns`, and the other files of `Components/ui` (relative imports in the folder). Anything else is an error: `@inertiajs/*`, `ziggy-js`, any `@/...` path, any relative path out of the folder.
2. Write it with the means of ESLint that the workspace already has (`no-restricted-imports` with patterns, or `@nx/enforce-module-boundaries` if that fits better), in the flat config, scoped to the folder. The message of the error says what to do: "Components/ui is the toolkit: it imports only React, Headless UI, the icons and @knowii/common. Pass the part of the application as a property or through the toolkit context." No new dependency.
3. A spec proves the rule: lint a small fixture in a temporary place, or call ESLint through its API on a string, and assert one error for each forbidden kind and none for the allowed ones.

## The files that break the rule today (on `main`)

1. `Button.tsx` and `Breadcrumb.tsx` import `Link` of `@inertiajs/react`.
2. `MarkdownContent.tsx` imports `../Video/VideoPlayer`.
3. `RelativeTime.tsx` and `Toaster.tsx` import from `@inertiajs/react` (read what for).

For each, the part of the application comes from outside, without a change for the callers:

1. A small toolkit context in `Components/ui` (for example `ToolkitProvider` with `linkComponent`, `videoRenderer` and what `RelativeTime` and `Toaster` need), with plain defaults (an `<a>` for a link, a plain link for a video). The application sets the values once, where the Inertia app is created (`apps/knowii/src/app.tsx` or the root layout); read how the app boots.
2. A property can override the context where one place needs another value.
3. The stories and specs work with the defaults, or with a decorator that sets the context. Read the stories of `Button` (their link stories stop the Inertia visit today): they must keep working, and the visit must still be stopped.
4. Behaviour in the application stays the same: Inertia visits for links, the video player in member content, the times and the toasts.

## Checks

`npx nx run-many -t lint typecheck test -p knowii common`, `npm run tsc`, `npm run tsc:spec`, `npx prettier --check <files>`, `npm run storybook:build`. A Vitest file that times out under load: run it alone. Then `grep` that no file of `Components/ui` (outside specs and stories) imports `@inertiajs` or a path out of the folder.

## Documents

`documentation/architecture/frontend.md`, section "Toolkit": the boundary, the context, and how a component gets an application part. ADR 0049 gets one line ("the boundary of point 2.5 is a lint rule since slice 3").

## Commit

Suggested subject: `feat(ui): the toolkit imports nothing of the application (a lint rule and a context)`. Footer: `Refs #657`. A wrapped line must not start with a word and a colon; lines under 100 characters. No trailer line.

## Report (max 400 words)

The commit and its stat; the rule and where it lives; each component changed and how the application now gives its part; the spec of the rule; anything a person can see differently (nothing is expected); the results of the checks.
