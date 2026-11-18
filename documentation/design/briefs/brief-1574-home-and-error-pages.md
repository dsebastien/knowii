# Brief: the home page says what Knowii is, the waitlist goes, and the error page helps (knowii-oss/knowii#1574)

ONE commit on a local branch. You do not push and you do not open a pull request: a reviewer reads your diff afterwards. Paths are relative to `~/wks/knowii`.

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md` (architecture rules, front-end rules, definition of done), `documentation/conventions/naming.md`, ADR 0049 (stories run as tests).
2. The issue and the two decisions: `gh api repos/knowii-oss/knowii/issues/1574 --jq .body` and `gh api repos/knowii-oss/knowii/issues/1574/comments --jq '.[].body'`.
3. Code: `apps/knowii/src/Pages/Welcome.tsx`, the waitlist form (`WaitlistForm.tsx`), its API route, controller, action, contract, request, tests, Bruno request, OpenAPI entry and README line; the code that talks to the mailing list provider; `config/retention.php`, `app/Actions/Retention/PruneExpiredData.php` and its tests (the retention rule of the waitlist addresses); `config/legal.php`; `resources/markdown/policy.md` and the other texts of `resources/markdown/` that name the waitlist; `apps/knowii/src/Pages/Error.tsx` and `bootstrap/app.php` (where it is rendered); the `EmptyState`, `PageTitle` and `AppLayout` building blocks; how the application knows whether registration is open (Fortify features, a configuration value).

## Rules (decided, do not change them)

### The home page

1. No "Coming soon", no "revolutionary", no waitlist card, no emoji.
2. The `h1`: "A place for your community's knowledge, ideas and inspiration". Under it, two short lines on what a person can do (talk in spaces, share and keep resources, learn with courses, meet at events only if that exists in the product today: say only what exists). Sentence case everywhere, the same in the footer.
3. A visitor sees "Sign up" (the main button) and "Sign in". A signed-in person sees "Open your dashboard". If the application has a way to close registration and it is closed: no "Sign up", and one line "Sign-up is closed for now." (no form).
4. Both themes, 360 px, one `h1`, the document title set.

### The waitlist

5. Remove the form, its API route, its controller, request, action and contract, their tests, the Bruno request, the OpenAPI entry, the README line, the client method in `libs/common`, and the rate limiter and constants that only they use.
6. KEEP everything that concerns the addresses that are already at the mailing list provider: the retention rule, its prune step and command, their tests, the configuration of the provider that the prune needs, and the sub-processor line in `config/legal.php`. Nothing is deleted at the provider and nothing is sent. If the prune needs code that you were about to remove, keep that code.
7. The policy texts (`resources/markdown/`): find every sentence that names the waitlist. Change each one in the smallest way so that it says: no new address is taken for the waitlist; the addresses that were given before stay until the end of the period that the text already names, then they are deleted. Change the `updated` date of a text that you change. These words go to the owner before the merge: quote each old and new sentence in your report.

### The error page

8. Rendered inside `AppLayout` for a signed-in person (with the normal header), and in the guest layout for a visitor. It uses the `EmptyState` building block (or the building block that fits best), one sentence and one button.
9. Texts, no emoji, no number in the visible title (the status code stays the HTTP status, and the document title names the case in words): 404 "This page does not exist, or it moved."; 500 "Something went wrong on our side. Try again in a minute."; 503 "Knowii is under maintenance. Come back in a few minutes."; 403 "You do not have access to this page."
10. The button: "Go to my communities" for a signed-in person (the same target as the no-access page of #1561 uses), "Go to the home page" for a visitor.
11. The no-access page of community content (#1561) is not changed.

## Tests

1. Vitest: the home page for a visitor, for a member, and with registration closed (if rule 3 applies); the error page for each status, signed in and not. The stories of a building block that you change.
2. Pest: the removed route answers 404; the retention tests of the waitlist stay green; `OpenApiDocumentTest`, `BrunoCollectionTest`, `ApiTokenAbilitiesTest` stay green; the error page renders the right component for each status (where the code allows a test).
3. Keep every other test green; a test of removed code goes with it.

## NOT in this commit

1. A marketing page, pictures, pricing, a list of communities.
2. Any delete or any message to the addresses at the mailing list provider.
3. A change of the registration rules.

## How to work

1. Branch `fix/1574-home-and-error-pages` from `origin/main`.
2. Docker on this Linux machine, from `~/wks/knowii`: `echo "docker compose exec -T -u sail laravel.test <command> </dev/null" | newgrp docker`. Do not start, stop or restart containers. Do not run `migrate:fresh` or a seeder on the development database.
3. A piped test command hides a failure: use `bash -c "set -o pipefail; ..."`. `php artisan test` takes ONE path: no path for the whole suite, or one run per path.
4. Rules that fail the build: no new use of `mixed` in `app/` (docblocks included), PHPStan at its highest level, Pint, ESLint, the TypeScript build, Prettier, the Storybook stories as tests.
5. Helper function names in Pest files are global: use unique names. No hand-made regular expression escape in a test: compare exact text.
6. Never name a third-party community product. Use `example.com` addresses only.
7. Before the commit, after the last code change: `composer format`, `composer lint`, the whole Pest suite (`php artisan test`), `npm run lint`, `npm run test`, `npm run build` (all inside the container). Leave `public/build` built from your branch.
8. The commit hook allows only the scopes all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build. The subject does not start with a capital letter. A blank line after the subject and before the footer. A wrapped line of the body must not start with a word and a colon. Suggested subject: `fix(ux): the home page says what Knowii is, without a waitlist`. Footer: `Closes #1574`. No trailer line that names a tool or a co-author.
9. If a rule cannot be built as written, skip that rule, build the rest, and say why in the report. Do not invent product rules.
10. If you find a security problem (for example a page that shows data of a private community to an outsider today), do not write it in the commit message or in any file of the repository. Say it only in your final report.

## Your final report (max 400 words)

1. The commit hash and `git show --stat`.
2. Each choice where the brief left room (the exact texts included).
3. The old and the new sentence of each policy text that you changed (rule 7).
4. Results of Pest, PHPStan, Pint, ESLint, Vitest and the build, with the exact failure text of anything that failed.
5. Anything of the scope not built, and why.
