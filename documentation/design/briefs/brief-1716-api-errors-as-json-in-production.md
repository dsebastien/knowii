# Brief: in production, an API error stays JSON (knowii-oss/knowii#1716)

State: DECIDED on 2026-11-23 by the main session (the issue fixes the rule; rules 7 and 8 were added from the reading of the code: the same goal, "every API error is JSON"). Nothing is built.

## The goal

Outside the environments `local` and `testing`, the response hook of `bootstrap/app.php` turns every answer with the status 403, 404, 500 or 503 into the Inertia page `Error`, also for a request that expects JSON. So a client of the API in production gets a page in place of the JSON error of ADR 0011, and the tests do not see it because they run in `testing`. This slice keeps the page `Error` for a page request and an Inertia visit only, and keeps the page without access of #1561 as it is.

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md`.
2. The issues: #1716, #1561 (the page without access), #1574 (the error page), #1515 (the calendar file and its refusals in JSON).
3. ADR 0011 (decision 6 and deviation 1). `documentation/architecture/backend.md:164-169`.

## What exists today (facts, with the evidence)

Line numbers are those of `origin/main` on 2026-11-23 (commit `21542639`).

1. The one place that makes the page `Error`: the response hook `$exceptions->respond(...)` (`bootstrap/app.php:343-374`). At lines 357-365 it renders `Error` with the status, `instance` and `legalPages` when the environment is not `local` or `testing` and the status is 500, 503, 404 or 403. It does not read `$request->expectsJson()`. No other code renders `Error` (`git grep "'Error'" origin/main -- app bootstrap routes` finds only line 360).
2. Before it, the hook handles `AlreadyAuthenticatedException` for a request that expects JSON (lines 344-350), and returns the page without access as it is (lines 352-355). After it, a 419 becomes a redirect back (lines 367-371).
3. The render callbacks (lines 168-340) already give the JSON of ADR 0011 for a request that expects JSON: `notFound` (177-190), `authorizationIssue` (231-249), `httpIssue` for the other HTTP statuses, 503 too (304-327), `internalError` for any other exception (330-340). The hook then replaces that JSON in production. The shapes are in `app/Traits/ApiResponses.php`.
4. How a request is told apart:
   1. `expectsJson()` is true when the request asks for JSON in `Accept`, or is an XHR that accepts any type. The front end calls the API with axios, which sends `X-Requested-With` (`apps/knowii/src/bootstrap.ts:7`) and the JSON `Accept` of axios. The Bruno collection sends `Accept: application/json` (233 of the 237 `.bru` files of `documentation/api`).
   2. An Inertia visit sends `X-Inertia: true` and `Accept: text/html, application/xhtml+xml`, so `expectsJson()` is false (the headers of the test at `tests/Feature/NoAccessPageTest.php:249-254`). Its error page is an Inertia answer with the component `Error`.
   3. The path: the visitor redirect to the sign-in page (`bootstrap/app.php:168-174`) and the page without access (`app/Support/NoAccess.php:24-30`) test both `! expectsJson()` and `! $request->is('api/*')`. The web fallback route leaves out `api/` (`routes/web.php:216-219`).
   4. The middleware `json-refusals` sets `Accept: application/json` on a route of the API that a browser opens with a plain link, the calendar file of an event (`app/Http/Middleware/AnswerRefusalsInJson.php:21-26`, `bootstrap/app.php:114,122`, `routes/api.php:237`). Its refusals expect JSON.
5. The page without access (#1561). `NoAccessException` extends `AccessDeniedHttpException` and renders `Communities/NoAccess` with the status 403 (`app/Exceptions/NoAccessException.php:23-41`). It is thrown only for a signed-in person who is not a member, on a page: never for a request that expects JSON or for `api/*` (`NoAccess.php:24-30`). The hook returns it before the line of `Error` (fact 2), in every environment.
6. A 503 of an instance that is not configured is plain text from a middleware, not an exception (`app/Http/Middleware/RefuseUnconfiguredInstance.php:43`): the hook never sees it.
7. The tests. `tests/Feature/HomeAndErrorPagesTest.php:39-67` checks the page `Error` in production for a page and an address with no route; lines 69-72 check the JSON 404 of the API, but in `testing`. `NoAccessPageTest.php:238-245` checks the JSON 403 of a request that expects JSON, in `testing`; lines 263-275 check the page without access and `Error` in production. A test sets production with `app()->detectEnvironment(fn () => 'production')` after `configuredInstance()`.

## Rules

Each rule is a test, in production (`configuredInstance()`, then `app()->detectEnvironment(fn () => 'production')`).

1. A request that expects JSON gets the same answer in production as in `testing`: the JSON of ADR 0011, with its status and `Content-Type: application/json`, and no Inertia page. Test each status:
   1. 404: `getJson('/api/v1/no-such-endpoint')` and an API route with a cuid that does not exist: `type` `notFound`.
   2. 403: an API route that the policy refuses, and a token without the ability of the route: `type` `authorizationIssue`.
   3. 500: a test route of the `api` group that throws a `RuntimeException`: `type` `internalError`, the message `Internal Server Error`, and no text of the exception.
   4. 503: a test route of the `api` group that calls `abort(503)`: `type` `httpIssue`.
2. The hook renders `Error` only for a request that does not expect JSON (`! $request->expectsJson()`). A page request keeps the page `Error` for 403, 404, 500 and 503: the tests at `HomeAndErrorPagesTest.php:39-67` pass with no change.
3. An Inertia visit (the headers of fact 4.2) to a route that answers 404 gets an Inertia answer with the component `Error` and `status` 404.
4. A `getJson()` to a web route in production also keeps JSON: an address with no route gives `type` `notFound`, a members-only page of an open community gives `type` `authorizationIssue` (as `NoAccessPageTest.php:238-245`, now in production).
5. The calendar file of an event (`json-refusals`), opened with a plain `get()` and no `Accept` header, gives the JSON 404 for an event that does not exist, in production.
6. The page without access does not change: every test of `NoAccessPageTest.php` passes with no change, and an Inertia visit of an outsider in production still gets `Communities/NoAccess` with 403.

7. Every request to `api/*` gets the JSON of ADR 0011 for an error, whatever its `Accept` header says (a client such as curl sends `*/*`): use `$exceptions->shouldRenderJsonWhen(...)` with `$request->is('api/*') || $request->expectsJson()`. So an `api/*` request without the JSON `Accept` gets a JSON 401 (not a redirect to the sign-in page), a JSON 400 of a validation (not a redirect back), and the JSON 404, 403, 500 and 503, in `testing` and in production. Test each with a plain `get()`/`post()` and `Accept: */*`. The visitor redirect to the sign-in page and the page without access keep their `! $request->is('api/*')` checks.
8. A 419 (an expired form token) of a request that expects JSON or of `api/*` is the JSON of ADR 0011 with the status 419 (`type` `httpIssue`), not a redirect back; a page request keeps the redirect back. A test of each.

## Scope (in the commit)

1. `bootstrap/app.php`: the condition of the page `Error` (line 357) reads `! $request->expectsJson()` too, with a comment that names #1716 and ADR 0011; `shouldRenderJsonWhen` of rule 7; the 419 of rule 8. Nothing else of the hook changes.
2. The tests of the rules: `tests/Feature/HomeAndErrorPagesTest.php` for rules 1 to 4, `tests/Feature/NoAccessPageTest.php` for rule 4 (the members-only page) and rule 6, `tests/Feature/EventCalendarFileTest.php` for rule 5. A test route goes in the test file, as `homeErrorRoute()` does (`HomeAndErrorPagesTest.php:17-24`), with `Route::middleware('api')`.
3. ADR 0011, decision 6: "one structure for every error, in every environment (#1716); the page `Error` is for a page request and an Inertia visit". Edit in place.
4. `documentation/architecture/backend.md:169`: the same sentence after "when the client requests a JSON response".
5. `documentation/api/README.md`, section "Use it": one line that a client sends `Accept: application/json`, and then every error is the JSON of ADR 0011 in every environment.
6. `SELF_HOSTING.md` and `.env.example`: no change. No new setting.

## NOT in this commit

1. A change of the render callbacks of `bootstrap/app.php:168-340` or of the shapes of `ApiResponses`.
2. A change of the 419 answer of a page request (lines 367-371), or of `AlreadyAuthenticatedException` (lines 344-350).
3. A change of the page `Error`, of the page without access, or of the visitor redirect to the sign-in page.
4. A change of the plain 503 of an instance that is not configured.
5. A change of the front end.

## How to work

1. `git fetch origin`, then branch `fix/1716-api-errors-as-json-in-production` from `origin/main`. ONE commit, no push, no pull request. If the working tree holds changes of another person, stop and ask: no stash, no reset. Find the text when a line number moved.
2. Docker, from `~/wks/knowii`: `docker compose exec -T -u sail laravel.test <command> </dev/null`. Do not start, stop or restart a container. No `migrate:fresh` and no seeder on the development database.
3. Run `npm run build` on the host before the first Pest run: the Inertia answers need the build.
4. Write the tests of rules 1, 4, 5, 7 and 8 first, run them with a path (`php artisan test tests/Feature/HomeAndErrorPagesTest.php`), and see them fail on the page `Error`. Then change `bootstrap/app.php`.
5. In the container: `composer format`, `composer lint`. Then the whole Pest suite ONE time, after the last change: `bash -c "set -o pipefail; php artisan test --parallel 2>&1 | tail -n 40"`, with its summary written to a file outside the repository (for example `/tmp/knowii-1716-pest.txt`).
6. No front-end file changes. If one does: `npx nx format --all`, `npm run lint:common`, `npm run tsc`, `npm run tsc:spec`, and the full front-end run `npx nx test common --skip-nx-cache && npx nx test knowii --skip-nx-cache`. Never at the same time as the Pest suite.
7. Never name a third-party community product, in code, tests or documents.
8. The commit: a conventional header of at most 100 characters, with a scope of `commitlint.config.js` (`all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build`), not starting with a capital letter. Suggested: `fix(api): an API error stays JSON in production, the error page is for pages only`. Footer, after a blank line: `Closes #1716`. No trailer that names a tool or a co-author.
9. If a rule cannot be built as written, skip it, build the rest, and say why in the report. Do not invent product rules.
10. If you find a weakness in the code while you work, do not write it in the commit, a test name, the ADR or an issue. Tell the reviewer in private.

## Questions for the owner

None.

## Your final report (max 250 words)

1. The commit hash and `git show --stat`.
2. The tests of rules 1, 4, 5, 7 and 8: their failure before the change and their pass after it.
3. The summary of the full Pest run (the file of step 5), and the results of `composer format` and `composer lint`.
4. The diff of `bootstrap/app.php`.
5. Each choice where the brief left room, and anything of the scope not built, with the reason.
