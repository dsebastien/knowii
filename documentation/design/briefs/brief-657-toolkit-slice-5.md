# Brief: component toolkit, slice 5: the documentation, the ADR update and the publication of the catalogue (knowii-oss/knowii#657)

You finish the documentation of the component toolkit and you prepare its publication. You add an introduction page to Storybook, you make Storybook load documentation pages, you update five documents, and you add one workflow that builds the Storybook on each push to `main` and deploys it to the page site of the repository (GitHub Pages). No component changes. No PHP, no API change, no new dependency. ONE commit on a local branch. You do not push, you do not open a pull request, and you do not enable the page site: a reviewer reads your diff afterwards, and the steps of the section "Steps that are not code" belong to other people.

Line numbers in this brief are those of the branch `feat/657-toolkit-slice-4c` at `21f172ee`.

## Read first

1. `AGENTS.md` (the section "Security issues"), `CONTRIBUTING.md` (the section "Security", lines 16 to 32), `documentation/conventions/rules.md` (rule 11, the definition of done), `documentation/adr/README.md` (when to write an ADR, how).
2. The issue #657 and its comments (`gh api repos/knowii-oss/knowii/issues/657 --jq .body`, `.../657/comments --jq '.[].body'`): point 2.6 of the proposal (the documentation), decision 1 of the owner (the publication), the comment of 2026-11-17 on the rule pages.
3. ADR 0049 (`documentation/adr/0049-*.md`, all of it), `documentation/design/component-toolkit/proposal.md` (line 3, sections 2 and 4), `documentation/architecture/frontend.md` (the part "Toolkit", lines 37 to 51, points 1 to 11).
4. `apps/knowii/.storybook/main.ts` and `preview.tsx`, `apps/knowii/project.json` (the targets `storybook` and `build-storybook`), `package.json` (lines 43 and 44).
5. In `apps/knowii/src/Components/ui`: `stories.spec.tsx`, `stories-guard.spec.ts`, `boundary.spec.ts`, and three story files as models of a docblock: `Badge.stories.tsx`, `Dialog.stories.tsx`, `ErrorBoundary.stories.tsx`.
6. All files of `.github/workflows` and `.github/dependabot.yml`: how an action is pinned, the permissions blocks, the comments at the top of `image.yml` and `deploy-acceptance.yml`.
7. The documentation of GitHub for a page site that a workflow publishes ("Using custom workflows with GitHub Pages"), and the README of `actions/upload-pages-artifact` and `actions/deploy-pages`.

## The facts today

1. Each component has a docs page already. `preview.tsx:29` sets the tag `autodocs` for all stories, and `main.ts:29` loads `@storybook/addon-docs`. There are 46 story files, so 46 docs pages.
2. The docblock above `const meta` shows on the docs page as the description of the component. Storybook 10.6.7 reads the comment of the statement that declares the meta. So "when to use, when not" and the accessibility contract are on the page today, as Markdown. 45 story files have the docblock directly above the meta. `ErrorBoundary.stories.tsx:91` has a line comment between the docblock and the meta, and its meta has no `component` (no table of props).
3. There is no landing page and no plain documentation page. `main.ts:28` loads only `../src/**/*.stories.tsx`, and the repository holds no `.mdx` file. The catalogue opens on the first component of the list. The titles come from the paths: `Components/ui/<Name>`.
4. `apps/knowii/.storybook/` holds `main.ts` and `preview.tsx` only: no manager file, no `manager-head.html`, no `preview-head.html`.
5. The build: `npm run storybook:build` (`package.json:44`) runs the target `build-storybook` (`apps/knowii/project.json:16-24`) and writes the static site to `dist/storybook`. `dist` is not in git (`.gitignore:22`).
6. CI builds the Storybook on each pull request (`ci.yml:90-94`), after `composer install` (`ci.yml:75`), and throws the result away. `ci.yml` runs only on `pull_request` (`ci.yml:3-4`). Nothing builds the Storybook on a push to `main`.
7. The addresses of the built site are relative. The Vite builder of Storybook sets `base: './'` over the value of `vite.config.mts:17`, and `main.ts:45-48` does not change it. So the site works under a sub-path without a setting. You prove it (rule 21).
8. What the issue and ADR 0049 promise, and what exists.
   1. A docs page per component from the stories and the comments (ADR 0049 decision 2, proposal 2.6): exists.
   2. A toolkit section in `frontend.md` and an ADR: exist (`frontend.md:37-51`, ADR 0049).
   3. The publication on the page site, from `main`, in the last slice (decision 1 of the owner, ADR 0049 context and decision 8): does not exist.
   4. `CONTRIBUTING.md` and `README.md` do not name Storybook or the toolkit at all.
   5. The status line of the proposal (`proposal.md:3`) names only slice 1.
   6. ADR 0049 decision 6 still describes the allow-list of the guard. The list is gone since slice 2.
9. The page site is not enabled (`gh api repos/knowii-oss/knowii/pages` answers 404), and the repository has no environment `github-pages`.
10. The workflows pin each action to a full commit with the version in a comment (`ci.yml:29`, `ci.yml:33`). The repository refuses an action without a full commit, and it allows the actions of GitHub (`actions/*`) and a short list of others. `.nvmrc` holds `26`. `ci.yml:35` writes `26` as a number.
11. The code scanning (CodeQL, default set-up) reads the workflows. Its checks `Analyze (actions)` and `CodeQL` are required on `main`. The repository has no OpenSSF Scorecard workflow, no document about workflow security and no workflow linter.
12. Dependabot keeps the actions current: `.github/dependabot.yml:53-61`, the ecosystem `github-actions`, the group `actions` with the pattern `*`. A new workflow file needs no change there.
13. The stories hold one email address, `rose@example.com` (`DropdownMenu.stories.tsx:157` and `:166`). The pictures of the stories are data addresses. 34 links in 7 story files start with `/`, and `MarkdownEditor.stories.tsx:66` gives a picture address that starts with `/storage/`. No test checks the addresses in a story.

## Rules

### The documentation in Storybook

1. The docs page of a component stays what it is today: the `autodocs` page, with the docblock of the meta as its text. Do not write an `.mdx` file per component, and do not copy a docblock into another file.
2. Read the docs page of each of the 46 components once in the browser. The page must show the three parts of the docblock: when to use, when not, the accessibility contract. Correct only what reads wrong as Markdown, in the docblock (a word in angle brackets outside backticks disappears on the page, a list that became one line). Do not change what a docblock says. Check `ErrorBoundary` first (fact 2). List each docblock that you change in the report.
3. Storybook loads documentation pages. In `main.ts`, `stories` becomes `['../src/**/*.mdx', '../src/**/*.stories.tsx']`, with a comment. The pages live in `apps/knowii/src/Components/ui/docs/<Name>.mdx`, so that they move with the toolkit. Each page starts with `import { Meta } from '@storybook/addon-docs/blocks';` and `<Meta title="..." />`.
4. The order of the list. In `preview.tsx`, `parameters.options.storySort` gets `order: ['Introduction', 'Rules', 'Components']`. The title of the introduction is `Introduction`. The group `Rules` is for slice 4d: it is empty now. The titles of the components do not change (`Components/ui/<Name>`): after the publication they are part of public addresses.
5. The introduction page, `docs/Introduction.mdx`. It is the first page of the catalogue. It is short (two screens at most), and it links to the documents of the repository in place of a copy of them. Use full `https://github.com/knowii-oss/knowii/blob/main/...` addresses for those links. Its parts:
   1. What the toolkit is: the own building blocks of Knowii (ADR 0003), in `apps/knowii/src/Components/ui`, one page per block.
   2. How to read the catalogue: the docs page of a block (when to use, when not, the contract, the props, each state), the theme switch and the two widths of the toolbar, the accessibility panel for the contrast.
   3. How to add a block: the component file with its docblock, the story file with one story per state and a `play` function per point of the contract, the export in `index.ts`, the point in `frontend.md`.
   4. The four rules, each in two or three sentences: the stories are the tests, the guard, the boundary, the accessibility contract.
   5. How to run the catalogue on your machine.
6. A page holds text, links and the doc blocks of Storybook only. A live example in a page is a story of a story file, shown with `<Canvas of={...} />`: it then stays a test. No component code in an `.mdx` file, and no `.tsx` helper in `docs/`.
7. What the tests see. `stories.spec.tsx:14` and `stories-guard.spec.ts:11-14` read only `.tsx` files, the lint configuration reads only `ts`, `tsx`, `js` and `jsx` (`apps/knowii/eslint.config.mjs:28-46`), and the type check does not read `.mdx`. So only the Storybook build checks a page. Do not change the two specs. Add one small spec, `Components/ui/catalogue.spec.ts`, that reads the story files and the pages as text (`import.meta.glob` with `?raw`) and fails when:
   1. A page has no `<Meta title="...">`, or its title is not `Introduction` and does not start with `Rules/`.
   2. A story file or a page holds an email address whose domain is not `example.com`, `example.org` or `example.net`.
8. In `main.ts`, `core` gets `disableProjectJson: true`, with a comment: the built site then holds no file with data about the tools of the build.
9. Search engines do not index the catalogue (question 1). Add `apps/knowii/.storybook/manager-head.html` and `preview-head.html`, each with `<meta name="robots" content="noindex" />`. A `robots.txt` has no effect under a sub-path, so do not add one.

### The documents of the repository

10. `documentation/architecture/frontend.md`, the part "Toolkit". Point 1 gets the published address. One new point 12, "The catalogue": the docs page of a block comes from the docblock of its meta, the introduction page, how to add a page (rules 3, 4 and 6), what the tests see (rule 7), what the catalogue must not hold (rule 16), the workflow and when it runs, the `noindex` rule.
11. `CONTRIBUTING.md`. Two lines in "Useful commands" (`npm run storybook`, `npm run storybook:build`). One new part "Component toolkit" before "Architecture and design", eight lines at most: the published address, how to run the catalogue (`./vendor/bin/sail npm run storybook`, then `http://localhost:6006`), how to read it, the rule that a new block comes with its stories, a link to the part "Toolkit" of `frontend.md`.
12. `README.md`, the section "Contributing": one sentence with a link to `https://knowii-oss.github.io/knowii/`.
13. `documentation/design/component-toolkit/proposal.md`, line 3: the status names what is built. Slice 1 (#1555), slice 2 (#1652, #1658), slice 3 (#1672), slice 4 (#1682, #1687, and the pull request of slice 4c when it has a number), slice 5 (this one). Slice 4d (the rule pages) is open. The rest of the proposal does not change.
14. ADR 0049 gets a dated addition. No new ADR: the publication and the docs page are decisions of ADR 0049 already (its context, decisions 2 and 8), this slice only carries them out, and slice 3 added consequence 6 the same way. The changes:
    1. One line under the date: `- Updated: 2026-11-20 (#657 slice 5: the documentation pages and the publication)`.
    2. Decision 6 says what is true now: the guard has no allow-list any more.
    3. Decision 8 says what is done: the workflow, the address, from `main` only, never from a pull request.
    4. One new decision 9: a documentation page is an `.mdx` file in `Components/ui/docs`, in the group `Introduction` or `Rules`, and a live example is a story.
    5. Two new consequences. 7: the catalogue is public, with what it must not hold (rule 16) and the `noindex` rule. 8: the workflow has a build job that only reads and a deploy job that holds the rights of the page site and runs no code of the repository, and the environment `github-pages` lets only `main` deploy.
    6. The row of the index in `documentation/adr/README.md` does not change.

### The publication

15. One new file, `.github/workflows/toolkit.yml`, with exactly this content. The commits are those of the latest releases on 2026-11-20 (`gh api repos/actions/<name>/releases/latest`, then the commit of the tag). Check them again before the commit, and say in the report if one moved.

```yaml
name: Toolkit catalogue

# Builds the Storybook of the component toolkit (#657, ADR 0049) and publishes it on the page site of the repository:
# https://knowii-oss.github.io/knowii/
# - Push to main: build, then deploy.
# - By hand (workflow_dispatch): the same, from main only. The environment "github-pages" refuses every other branch.
# - Never on a pull request: ci.yml builds the Storybook there, and nothing is published.
# - Never on a fork: both jobs check the name of the repository.
# The build job runs the code of the repository with a token that only reads. The deploy job holds the rights of the
# page site and runs no code of the repository: it gives the artifact of the build job to the page site.
on:
  push:
    branches:
      - main
  workflow_dispatch:

# One deployment at a time. A newer run replaces a run that waits. A deployment that runs is never cancelled.
concurrency:
  group: pages
  cancel-in-progress: false

# A job has only the rights that it names
permissions: {}

jobs:
  build:
    if: github.repository == 'knowii-oss/knowii' && github.ref == 'refs/heads/main'
    runs-on: ubuntu-latest
    timeout-minutes: 15
    permissions:
      contents: read
    steps:
      - uses: actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1 # v7.0.1
        with:
          persist-credentials: false

      - uses: actions/setup-node@949feb2413d6458794dcd2491c4babbbce0c15c1 # v7.1.0
        with:
          node-version-file: '.nvmrc'
          cache: 'npm'
      - name: Install front-end dependencies
        run: npm ci

      # No .env file and no secret: the catalogue holds only what the repository holds
      - name: Build Storybook
        run: npm run storybook:build
        env:
          NX_NO_CLOUD: true

      - name: Upload the catalogue
        uses: actions/upload-pages-artifact@fc324d3547104276b827a68afc52ff2a11cc49c9 # v5.0.0
        with:
          path: dist/storybook

  deploy:
    needs: build
    if: github.repository == 'knowii-oss/knowii' && github.ref == 'refs/heads/main'
    runs-on: ubuntu-latest
    timeout-minutes: 15
    permissions:
      # To publish on the page site
      pages: write
      # The page site checks with this token that the artifact comes from this workflow, on this repository
      id-token: write
    environment:
      name: github-pages
      url: ${{ steps.deployment.outputs.page_url }}
    steps:
      - name: Deploy to the page site
        id: deployment
        uses: actions/deploy-pages@368f82528645a54fb793d4d04e342629a3f51346 # v5.0.1
```

Why it is so:

1.  No path filter. Each push to `main` builds and deploys. A filter that forgets one input (a configuration file, the lock file, the shared library) leaves an old catalogue on the site, and nobody sees it. The cost is one short job per merge.
2.  No `actions/configure-pages`. It gives the base path to a build that needs one. This build has relative addresses (fact 7), so the action is one more thing to trust for no use.
3.  No PHP and no `composer install`. By reading, no story reaches the `ziggy-js` alias of `vendor`. CI does not prove it, because it builds after `composer install`. You prove it (rule 22).
4.  The Node.js version comes from `.nvmrc`. Do not change `ci.yml` in this commit.
5.  The artifact holds no file whose name starts with a dot: the upload action leaves them out. The build writes none that the site needs. No `.nojekyll` file: a site that a workflow publishes does not go through Jekyll.

6.  What the catalogue must not hold. It is public, and it is built from the repository alone. No secret, no token, no key. No data of a real person: no real name, no real email address (only `example.com`, `example.org`, `example.net`), no real picture of a person. No address of an internal server. No call to another origin when a page opens: the fonts and the pictures come from the build, and a video loads only after a click. Nothing checks this today (fact 13): the spec of rule 7 checks the email addresses, the secret scanning of the repository checks the secrets, and the rest is a rule for the review.
7.  The base address is `https://knowii-oss.github.io/knowii/`. The build needs no setting for the sub-path (fact 7). A link in a page to another page of the catalogue is relative (`./?path=/docs/components-ui-button--docs`), never an address that starts with `/`.

### The security of the workflow

18. The triggers are `push` to `main` and `workflow_dispatch`, and nothing else. No `pull_request`, no `pull_request_target`, no `workflow_run`. A pull request, also from a fork, never reaches the deploy job.
19. The least rights. `permissions: {}` at the top, `contents: read` for the build job, `pages: write` and `id-token: write` for the deploy job. No secret, no repository variable, no `.env` file, no `${{ }}` expression inside a `run` script.
20. The deploy job stays separate from the build job. The build job runs `npm ci` and the code of the repository, so it has no right to write anywhere. The deploy job has no checkout and no `npm` step. Each action is pinned to a full commit with its version in a comment. The condition on `main` in the file is not the guard: the rule of the environment is (the section "Steps that are not code", step 2).

### Two proofs before the commit

21. The built catalogue under a sub-path. Copy the build to a folder with the name of the repository and serve its parent: `rm -rf /tmp/toolkit-site && mkdir -p /tmp/toolkit-site && cp -r dist/storybook /tmp/toolkit-site/knowii && python3 -m http.server 8123 --directory /tmp/toolkit-site`. Open `http://localhost:8123/knowii/`. Check, with the network panel open: the introduction opens first, three docs pages show their text and their stories, a story in a frame shows (`Dialog`, `Select`), the font loads, the theme switch works, a link of the introduction to a component page works, each request starts with `/knowii/`, no request goes to another origin, and both HTML files hold the `robots` line. Check that `dist/storybook` holds no `project.json`, no `.env` value and no file that the catalogue does not need: read the list of its files. Stop the server, and delete `/tmp/toolkit-site` and `dist/storybook`.
22. The build without `vendor`. Make a second worktree of your branch in a temp folder, with a link to `node_modules` only, run `npm run storybook:build` there, and remove that worktree. If the build fails without `vendor`, do not add PHP to the workflow on your own: say it in the report with the exact error.

## Scope (in the commit)

1. `apps/knowii/.storybook/main.ts`, `preview.tsx`, `manager-head.html`, `preview-head.html` (rules 3, 4, 8 and 9).
2. `apps/knowii/src/Components/ui/docs/Introduction.mdx` and `Components/ui/catalogue.spec.ts` (rules 5, 6 and 7).
3. The docblocks that read wrong on their docs page (rule 2), and nothing else in a story file.
4. `.github/workflows/toolkit.yml` (rule 15).
5. `documentation/architecture/frontend.md`, `CONTRIBUTING.md`, `README.md`, `documentation/design/component-toolkit/proposal.md`, `documentation/adr/0049-*.md` (rules 10 to 14).

## NOT in this commit

1. The rule pages (slice 4d): the named text styles, the action rules, the width rule, the one settings layout, the word list, and the text styles in `styles.css`.
2. Any change of a component, of its props, of its look, or of what a story shows.
3. A link to the catalogue in the application (question 2), a custom domain (question 3), a preview for a pull request (question 4).
4. A change of `ci.yml`, `release.yml`, `image.yml`, `security-php.yml`, `deploy-acceptance.yml` or `.github/dependabot.yml`.
5. A fix for the links of the stories that start with `/` (fact 13). On the published site, a click on one that the story does not stop leaves the catalogue. List them in the report.
6. A new dependency (`remark-gfm` or any other), a workflow linter, a Scorecard workflow, a visual comparison.
7. Any PHP, any API or schema change.
8. A push, a pull request, a change of a repository setting.

## Ready for slice 4d

1. Storybook loads every `.mdx` file under `apps/knowii/src`. A rule page is one file in `Components/ui/docs`, with the title `Rules/<Name>`. The order of the list has the group `Rules` already, between the introduction and the components.
2. A live example in a rule page is a story, shown with `<Canvas of={...} />`, so that the axe check and the guard cover it.
3. `catalogue.spec.ts` checks the title of each page and the email addresses in it.
4. A Markdown table does not work in an `.mdx` page without the package `remark-gfm`, which is not installed. For the word list, slice 4d writes an HTML `table`, or asks the owner for the package (rule 11 of `rules.md`).
5. Slice 4d adds a link to the group `Rules` in the introduction page, and its own point in `frontend.md`.

## Steps that are not code (who does each)

The builder does none of these steps. Steps 1 and 2 come before the merge, so that the first run on `main` is green.

1. Enable the page site with the source "GitHub Actions". Who: the owner, or the person who runs the slice with the yes of the owner (question 5).
   1. `gh api -X POST repos/knowii-oss/knowii/pages -f build_type=workflow`
   2. Check: `gh api repos/knowii-oss/knowii/pages --jq '{build_type, html_url, https_enforced}'` answers `workflow`, `https://knowii-oss.github.io/knowii/` and `true`. If `https_enforced` is `false`: `gh api -X PUT repos/knowii-oss/knowii/pages -F https_enforced=true`. The address answers 404 until the first deployment.
2. The rule of the environment `github-pages`: only `main` deploys. Who: the same person, at the same time. GitHub makes this environment for a page site that a workflow publishes, at the latest on the first deployment. Check what it holds: `gh api repos/knowii-oss/knowii/environments/github-pages --jq .deployment_branch_policy` and `gh api repos/knowii-oss/knowii/environments/github-pages/deployment-branch-policies --jq '.branch_policies[].name'`. The answer must be custom policies with the one name `main`. If it is not, or if the environment does not exist yet (the first command then makes it):
   1. `gh api -X PUT repos/knowii-oss/knowii/environments/github-pages --input - <<< '{"deployment_branch_policy":{"protected_branches":false,"custom_branch_policies":true}}'`
   2. `gh api -X POST repos/knowii-oss/knowii/environments/github-pages/deployment-branch-policies -f name=main -f type=branch`
   3. No secret and no variable in this environment.
3. The pull request. Who: the person who runs the slice. The checks `Analyze (actions)` and `CodeQL` read the new workflow: they must be green, with no new alert. An alert is not discussed in the pull request (`CONTRIBUTING.md:32`): the maintainer hears of it in private.
4. The first run, after the merge. Who: the person who runs the slice.
   1. `gh run list -R knowii-oss/knowii --workflow toolkit.yml --limit 1`, then `gh run watch <id> -R knowii-oss/knowii`.
   2. `curl -sI https://knowii-oss.github.io/knowii/ | head -n 1` answers 200, and `curl -s https://knowii-oss.github.io/knowii/index.json | head -c 200` shows the list of the stories.
   3. In a browser: the introduction opens first, the docs page of `Button` shows its text, the theme switch works, a story that renders in a frame (`Dialog`) shows, and the network panel shows no failed request and no other origin.
   4. If the deploy job cannot read the artifact, the one right to add is `actions: read` on the deploy job. Nothing else.
5. Dependabot: nothing to do (fact 12). The branch protection of `main`: nothing to do, the new workflow does not run on a pull request, so it is not a required check.

## How to work

1. You work in the git worktree that the person who gave you this brief names, on the branch that they name (on top of slice 4c, or on `main` when slices 4b and 4c are merged). It has links to `node_modules` and `vendor` of the main clone: do not remove or replace them, and do not run `npm install` or `composer install`. Do not touch the main clone `~/wks/knowii`.
2. Everything runs on the host, from the root of the worktree. No Docker, no Pest (no PHP changes).
3. Before the commit, after the last change: `npx nx format --all` (then check with `git status` that it changed only your files, and revert any other file it touched), `npm run lint:knowii`, `npm run lint:common`, `npm run tsc`, `npm run tsc:spec`, `npm run test:all` (Vitest: the stories, their guard, the boundary and the new spec), `npm run storybook:build`. A piped command hides a failure: use `bash -c "set -o pipefail; ..."`. If the formatter breaks an `.mdx` page, say it in the report and keep the page as the build accepts it. Then do the two proofs of rules 21 and 22.
4. Never name a third-party community product. No real personal email address in code, tests or documents.
5. The commit hook allows only the scopes all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build. The subject does not start with a capital letter. Suggested: `docs(ui): toolkit, an introduction page, documentation pages, and the catalogue goes on the page site`. Footer: `Refs #657`. No trailer line that names a tool or a co-author.
6. If a rule cannot be built as written, skip it, build the rest, and say why in the report. Do not invent product rules.

## Definition of done

1. The catalogue opens on the introduction, and each of the 46 components has a docs page that shows when to use it, when not, and its contract.
2. The build works under `/knowii/` (rule 21) and without `vendor` (rule 22).
3. The workflow file is the text of rule 15, with the commits checked again on the day of the commit.
4. The five documents are current (rules 10 to 14), and each one says the same thing as the code.
5. All commands of "How to work" point 3 pass.
6. One commit, with the convention of point 5. Nothing pushed, no setting changed.

## Your final report (max 350 words)

1. The commit hash and the last line of `git show --stat`.
2. The docblocks that you changed (rule 2), and what the docs page of `ErrorBoundary` shows.
3. The result of rule 21: the list of the files at the top of `dist/storybook`, the size of the folder, each request that did not start with `/knowii/`, and each link of a story that leaves the catalogue (the story file and the line).
4. The result of rule 22, with the exact error if it failed.
5. The version and the commit of each action, and each one that moved since this brief.
6. The result of each command of "How to work" point 3, with the exact text of anything that failed.
7. Each choice where the brief left room, and anything of the scope not built, with the reason.

## Questions for the owner

Until the owner answers, the builder builds what each recommendation says. The page site is enabled only with the yes of the owner.

Until the owner answers, the builder builds what each recommendation says.

1. Do search engines index the catalogue? Recommendation: no (`noindex` on both pages, rule 9). The catalogue is a tool for contributors, who reach it from the README and the contributor guide. Its pages hold sample texts and sample communities, which must not show in a search for the product. One line of two files turns indexing on later.
2. Where is the link to the catalogue? Recommendation: in `README.md`, in `CONTRIBUTING.md` and in `frontend.md` only. Not in the footer of the application: the footer belongs to each instance, and a member of a community has no use for a tool of the developers.
3. Which address? Recommendation: the default one, `https://knowii-oss.github.io/knowii/`. It needs no DNS record, it has HTTPS at once, and the relative addresses of the build mean that a move to a custom domain later needs a setting and no code.
4. Does a pull request get a preview of the catalogue? Recommendation: no. A preview needs a deployment right in a run that a pull request starts, and that is the risk that rule 18 removes. CI builds the Storybook on each pull request already, and a reviewer runs `npm run storybook` on the branch.
5. Who enables the page site, and when? Recommendation: the owner, or the person who runs the slice with your yes, before the merge of this slice, with the two commands of step 1 and the check of step 2. The address answers 404 until the first deployment, so nothing is public before the merge.
