# Brief: a person who signs up from a community lands in that community after the address check (knowii-oss/knowii#1612)

## Decided by the owner on 2026-11-20

1. Every recommendation of "Questions for the owner" is the decision: the destination travels inside the signed link, with nothing stored; from a private community the person lands on its page without access; the session wins over the link; the sign-in link in another browser is fixed in the same commit; no confirmation text here (#1571).
2. Since this brief was drafted, `main` got "the application answers only for its own host names" (#1690): every address that the application builds takes its host from the configuration. Read `app/Http/Middleware/AnswerOnlyForOwnHostNames.php` before the build, and keep rule 3 of this brief (a landing is a path that the router gives to a named route, never a text of the request).
3. The slice can be built, after #1688 and #148 slice 1.

State: DECIDED on 2026-11-20 (see "Decided by the owner"). The issue asks for a design first. The design is in "The design" below, and the owner answers the "Questions for the owner" before the build starts.

You build #1612. A visitor reads a page of a community, signs up, and opens the link of the address check. Today the person comes back to that page only when the link opens in the same browser, with the same session. In another browser or on another device, the person lands on the dashboard. After this slice, the link of the mail carries the page, so the person lands in the community from any browser. The sign-in link by email gets the same. Nothing is stored in the database, and no screen changes. ONE commit on a local branch: a reviewer reads your diff afterwards. Paths are relative to `~/wks/knowii`.

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md` (structure rule 2, data rules 8 and 10, definition of done 6 and 10), `documentation/conventions/naming.md`.
2. The issue (`gh api repos/knowii-oss/knowii/issues/1612 --jq .body`) and its references: #1571 (the sign-in and sign-up pages), #1561 (the page without access, closed), #1575 (the first dashboard), #1398 (onboarding). The report `documentation/reviews/2026-11-17-ui-review/report.md`: R-M1, R-S1, R-S6, R-S10, R-L1.
3. ADRs: 0044 (points 15 and "Who can join" point 4, and "Alternatives" point 2), 0019 (point 3), 0023, 0045 (point 6), 0054 (point 1: the address verification mail is never held).
4. Code of the flow: `app/Http/Middleware/AuthenticateIfPresent.php`, `bootstrap/app.php` (lines 133 to 141), `routes/web.php`, `app/Providers/FortifyServiceProvider.php`, `config/fortify.php`, `app/Actions/Fortify/CreateNewUser.php`, `app/Models/User.php`, `app/Http/Controllers/Inertia/MagicLinkController.php`, `app/Actions/Auth/MagicLinks.php` and its two contracts, `app/Http/Controllers/Inertia/OpenIdConnectController.php`, `app/Http/Controllers/Inertia/CommunityInvitationController.php`, `app/Http/Controllers/Inertia/CommunityController.php`, `app/Support/NoAccess.php`, `app/Support/PublicCommunity.php`, `app/Actions/CommunityMembers/JoinCommunity.php`.
5. Code of the framework, in `vendor/laravel`: `fortify/src/Http/Responses/` (`RegisterResponse`, `LoginResponse`, `TwoFactorLoginResponse`, `VerifyEmailResponse`), `fortify/src/Http/Controllers/RegisteredUserController.php` and `VerifyEmailController.php`, `fortify/routes/routes.php` (lines 86 to 100), `framework/src/Illuminate/Auth/Notifications/VerifyEmail.php`, `framework/src/Illuminate/Auth/Middleware/EnsureEmailIsVerified.php`, `framework/src/Illuminate/Routing/Redirector.php` (`guest()` and `intended()`).
6. Tests: `tests/Feature/CommunityJoinTest.php` (line 548), `PublicSpacesTest.php` (lines 374 to 386), `EmailVerificationTest.php`, `MagicLinkTest.php`, `OpenIdConnectTest.php` (line 963), `CommunityMemberManagementTest.php` (the tests of #1251), `NoAccessPageTest.php`, `PublicCommunityPageTest.php`.

## What happens today (facts, with the evidence)

Line numbers are those of `main` on 2026-11-20 (commit `583701ad`).

1. A visitor opens one of the six pages that visitors can read (`routes/web.php:84-100`, the group `auth.optional`). The middleware stores the full address in the session, as `url.intended` (`AuthenticateIfPresent.php:55-58`). A visitor on an address that they cannot read (a members-only space, a private community, an unknown address) goes to the sign-in page, and `redirect()->guest()` stores that address too (`bootstrap/app.php:133-141`).
2. "Sign up to join", "Sign up" and "Sign in" are plain links to `/register` and `/login` (`apps/knowii/src/Components/Communities/JoinCommunity.tsx:30-37`, `Pages/Communities/Public.tsx:47-50`, `Components/Reader/Reader.tsx:31-41`). They carry no parameter. ADR 0044 refused a `return` parameter ("Alternatives", point 2).
3. The sign-up: `POST /register` calls `CreateNewUser` (`app/Actions/Fortify/CreateNewUser.php:32-67`), which reads no destination. Fortify then sends the verification mail in the same request (the `Registered` event), signs the person in, and answers `redirect()->intended('/dashboard')` (`RegisterResponse`, `config/fortify.php:76`). The person goes back to the page of the community.
4. That page refuses an account without a verified address (`AuthenticateIfPresent.php:43-45`). `EnsureEmailIsVerified` sends the person to `/email/verify` with `Redirect::guest()`, which stores the address of the page again.
5. The link of the mail is the route `verification.verify`. It is signed, it lives 60 minutes (`config/auth.php:99`), it holds only `id` and `hash`, and it needs a signed-in session (`auth`, `signed` and `throttle`, in `fortify/routes/routes.php:94-96`). Its answer is `redirect()->intended('/dashboard?verified=1')` (`VerifyEmailResponse`).
6. So, in the same browser and with the same session, the person already comes back to the page of the community. `tests/Feature/CommunityJoinTest.php:548-568` proves it. The sentence of the issue "lands on the dashboard" is true only in the cases of point 7.
7. The address is lost in four cases:
   1. The link opens in another browser or on another device (for example the browser of a mail application on a phone). The person signs in first, the sign-in uses up `url.intended` (it held the verification link), and the verification answers with the dashboard.
   2. The person presses "Log out" on the page of the address check (`apps/knowii/src/Pages/Auth/VerifyEmail.tsx:57-70`). The session ends.
   3. The session ends before the click (120 minutes, `config/session.php:35`).
   4. Before the click, the person opens another guarded address (for example the dashboard). `Redirect::guest()` replaces the stored address.
8. A sign-in with a password comes back to the page: `LoginResponse` and `TwoFactorLoginResponse` answer `redirect()->intended(...)`. `tests/Feature/PublicSpacesTest.php:374-386` proves it.
9. A sign-in with a provider comes back to the page: the flow lives in one session, and the callback answers `redirect()->intended(route('dashboard'))` (`OpenIdConnectController.php:205`). A new account of the provider has a verified address from the start (`CreateNewUser.php:78,91-93`), so it has no address check. `tests/Feature/OpenIdConnectTest.php:963-970` proves that the address survives the second factor.
10. A sign-in link by email comes back to the page only in the same browser. `MagicLinks::send()` keeps only the id of the account, in the cache, for 15 minutes (`app/Actions/Auth/MagicLinks.php:40`). `MagicLinkController::consume()` verifies the address of the account (`MagicLinkController.php:51-53`) and answers `redirect()->intended(route('dashboard'))` (line 66). In another browser the person lands on the dashboard.
11. An invitation (ADR 0019) has its own signed link (`routes/web.php:68`). A guest goes to the sign-in page, and the link is the stored address. After the sign-in or the sign-up, the link adds the member and lands in the community with "Welcome to <name>" (`CommunityInvitationController.php:22-32`). It has the limits of point 7.
12. Joining: `KnowiiCommunityJoinPolicy` has two values, `invitation` and `open`. A join on request does not exist. A join needs a verified address and an open community (`JoinCommunity.php:30-40`). Nothing joins by itself after a sign-up (ADR 0044, "Who can join", point 4).
13. The home of a community for a person who is not a member (`CommunityController.php:38-45,86-101`): the public page when the community has a public space that is not a chat space, with "Join" when the community is open. Else the page without access, with the status 403 (`NoAccess.php:50-57`), with "Join" when the community is open and the words about an invitation when it is not (`Pages/Communities/NoAccess.tsx`).
14. The dashboard lists the communities of the account. A new account has only its personal space (`CreateNewUser.php:114-130`, `Pages/Dashboard.tsx:98-139`). No code reads `?verified=1`.

## The design (the options and the choice)

The question of the issue: where does the destination live between the sign-up and the address check, when the link opens in another browser?

1. Option A, the session only (today). No new code. It fails in the four cases of fact 7. Refused as the only place, kept as the first source.
2. Option B, columns on the account (a community and a page, set at the sign-up, cleared at the first landing). It works from any browser and for a mail that is sent again from anywhere. Its cost: a migration, a new piece of personal data at rest (data rule 10: a change of the privacy policy, which is an approved text), the member data export, an action behind a contract for the write (structure rule 2), and a rule for a value that is never used. Refused.
3. Option C, inside the link of the mail (recommended). The verification link is signed by the instance, so its parameters cannot be changed. The sign-in link has a token, and the server keeps its data beside the token in the cache. The destination travels with the link that the person opens, and it has the life of that link (60 minutes, 15 minutes). No table, no column, no personal data at rest, no change of a legal text, no change of the export. One limit, accepted: a mail that is sent again from a browser whose session has no community page carries no destination, and that person lands on the dashboard as today.
4. Option D, a `return` parameter on the sign-up and sign-in forms. ADR 0044 refused it: it needs an allow-list in each form, and it leaves the other paths without it.

The choice is option C on top of option A: the session stays the first source, and the link fills the gap when the session has nothing useful.

No open redirect, by construction:

1. A landing is never a full address. It is a path, and only a path that the router of this instance gives to one of six named routes.
2. The code never redirects to a text that it received. It asks the router for the route name and the parameters of the path, and it builds the address again with `route()`. The host, the scheme, the query and the fragment of the input are never used.
3. A value that fails any check is dropped without an error, and the person gets the landing of today (the address of the session, else the dashboard).

## Rules

These rules are the recommended design. They become "decided" when the owner answers the questions at the end.

### The landing value

1. New `app/Support/Landing.php`: a final class with static functions. It holds no state and writes nothing to the database.
2. `Landing::ROUTES` is the list of the six route names of the group `auth.optional`: `communities.show`, `spaces.show`, `posts.show`, `pages.show`, `resources.show`, `events.show`. A WARNING comment on the group in `routes/web.php` and on the constant says that they stay aligned. A test compares the list with the routes that carry the middleware (scope 3.1).
3. `Landing::pathOf(?string $address): ?string` reads any text: the address of the session, the parameter of the link, the value of the cache. It works in this order:
   1. Null, an empty text, or more than 2048 characters: null.
   2. It keeps only the path part of the text. No path, or a path that does not start with `/`: null.
   3. It asks the router which route answers a GET of that path. No route, a route without a name, or a name that is not in `ROUTES`: null. The fallback route has no name, so it is refused.
   4. Each parameter of the route holds only the characters of a slug (`a-z`, `0-9`, `-`). Else null. Check this against the slugs that the models make, and say in the report if a slug can hold another character.
   5. It returns `route($name, $parameters, false)`: the path that the router builds from the name and the parameters. No character of the input goes to the result by another way.
4. `Landing::fromSession(): ?string` returns `pathOf()` of the `url.intended` value when the request has a session. Else null (a command, a job).
5. `Landing::remember(Request $request, User $user, ?string $address): void` applies the rules of "Where the person lands". It writes an address to `url.intended` of the session, or it does nothing. The address that it writes always comes from `route()`.
6. No new `mixed`. A value of the session, of the query or of the cache is narrowed with `is_string` where it is read.

### Where the landing travels

7. Nothing changes in `AuthenticateIfPresent`, in `bootstrap/app.php`, in the answers of Fortify for the sign-up, the sign-in and the second factor, and in `OpenIdConnectController`. The session works as today.
8. New `app/Notifications/VerifyEmailAddress.php`, which extends the `VerifyEmail` notification of the framework. Its constructor takes `?string $landing`. It builds the same signed, temporary route as today, with one more parameter, `landing`, when the value is not null. The text of the mail, its queue and the life of the link do not change (ADR 0054, point 1).
9. `User::sendEmailVerificationNotification()` sends `new VerifyEmailAddress(Landing::fromSession())`. This one place covers the sign-up, "Resend verification email" (the framework controller calls the same method) and a change of address in the profile (`UpdateUserProfileInformation.php:126`).
10. New `app/Http/Responses/VerifyEmailResponse.php`, which implements `Laravel\Fortify\Contracts\VerifyEmailResponse`. `FortifyServiceProvider::register()` binds it. A request that wants JSON gets 204, as today. Else it calls `Landing::remember($request, $user, <the "landing" query value>)`, then it answers `redirect()->intended(Fortify::redirects('email-verification').'?verified=1')`, as today. A second click on the link (the address is already verified) gets the same answer.
11. A link with a changed `landing` fails the `signed` middleware: 403, and the address is not verified, as for any changed link today. A link without `landing` works as today. The check of rule 3 still runs on a value with a good signature.
12. The sign-in link by email:
    1. `SendsMagicLinks::send(array $input, ?string $landing = null)`. `MagicLinkController::store()` passes `Landing::fromSession()`.
    2. For an account that exists, and a landing that is not null, `MagicLinks` puts the landing in a second cache entry, `magic-link-landing:<the same hash>`, with the same life as the token. The first entry does not change, so a link that was sent before the update still works.
    3. `ConsumesMagicLinks` gets `pullLanding(string $token): ?string`: read and delete at once. `consume()` deletes the entry too, so that nothing stays after a link is used.
    4. `MagicLinkController::consume()` reads the landing before `consume()`. When the account is known, it calls `Landing::remember()` before the branch of the second factor. The challenge of Fortify then answers with `redirect()->intended()`, so the landing survives it.
    5. An unknown address stores nothing, as today.
13. The sign-in with a provider needs no code: its flow lives in one session (fact 9). It gets tests (scope 3.6).

### Where the person lands

14. `Landing::remember()` works in this order, and stops at the first point that applies:
    1. The session already holds a community page (`pathOf()` of `url.intended` is not null), or the link of an invitation (the router gives `community-invitations.accept`): do nothing. The session wins: it is the newer intent of this browser, and the flow of ADR 0019 stays as it is.
    2. `pathOf($address)` is null: do nothing.
    3. No community has the slug of the path, or the community is a personal one: do nothing.
    4. The path is the home of the community: the target is `communities.show`.
    5. The path is a page in a space. When the space no longer exists in that community, or `Gate::forUser($user)->denies('view', $space)`: the target is `communities.show`. Else the target is the page itself.
    6. Write the target to `url.intended`, as an address built with `route()`.
15. "Do nothing" means the landing of today: the address of the session when it has one, else the dashboard.
16. The target page answers with its own rules, as today. `Landing` only chooses an address: it never changes who can read what, and it never calls `JoinCommunity`.
    1. A member already (for example an invitation that was accepted in between): the page, or the home of the community.
    2. An open community with a public page: the public page, or the public space page, with the "Join" button. The person presses it: nothing joins by itself (ADR 0044, "Who can join", point 4, and point 2 of the issue).
    3. A community by invitation with a public page: the public page, without "Join".
    4. A members-only space of a community with a public page: the public page of the community (rule 14.5), with "Join" when the community is open.
    5. A community without a public page (a private community, or one that was made private in between): the page without access of #1561, with "Join" when the community is open, and the words about an invitation when it is not.
    6. A community that was deleted, or whose slug changed: the dashboard (rule 14.3).
    7. A post, a page, a resource or an event that was deleted while its space still exists: the 404 of today. Accepted: the link lives 60 minutes.
17. A join on request does not exist in the code (fact 12). When it comes, its action shows on the same public page, and this slice needs no change.
18. An invitation in play: the link of the invitation in the session wins (rule 14.1). The landing of a verification link never replaces it. The invitation flow gets no new code.

### The personal space (point 3 of the issue) and the sign-in (point 4)

19. Point 3 needs no code here. With a landing, the first screen is the community, and the dashboard comes only as the fallback. The personal space stays where it is: in "My Communities" on the dashboard, behind the "Dashboard" entry of the header. `Dashboard.tsx`, `DashboardController` and `config/fortify.php` do not change. The first dashboard of a person without a community (R-S10) is #1575.
20. Point 4 works today for a password, a second factor and a provider (facts 8 and 9). This slice adds the sign-in link in another browser (rule 12) and tests for each way.

## Scope (in the commit)

1. Rules 1 to 20.
2. Files:
   1. New: `app/Support/Landing.php`, `app/Notifications/VerifyEmailAddress.php`, `app/Http/Responses/VerifyEmailResponse.php`.
   2. Changed: `app/Models/User.php`, `app/Providers/FortifyServiceProvider.php`, `app/Actions/Auth/MagicLinks.php`, `app/Contracts/Auth/SendsMagicLinks.php`, `app/Contracts/Auth/ConsumesMagicLinks.php`, `app/Http/Controllers/Inertia/MagicLinkController.php`, `routes/web.php` (the WARNING comment only).
   3. No migration. No new configuration value. No front-end file.
3. Pest. New `tests/Feature/LandingTest.php`, and additions to the existing files where a test belongs to them:
   1. `Landing::ROUTES` is equal to the names of the GET web routes that carry `auth.optional`.
   2. `pathOf()` accepts the path of each of the six routes. A full address of this instance gives its path. A query and a fragment are dropped.
   3. `pathOf()` and the open redirect, as one data set. Each input gives null, or a path that starts with `/community/`: an address of another host with a community path, `//other.example/community/x`, `/\other.example`, `javascript:alert(1)`, a path with `..`, a path with an encoded slash or an encoded line break in a slug, `/dashboard`, `/login`, `/email/verify`, the address of an invitation, `/api/v1/communities`, `/community/x/settings`, `/community/x/about`, an address that only the fallback route answers, an empty text, a text of 3000 characters. For each input, the address that `remember()` writes is null or starts with the root of the application. Use `example` hosts only.
   4. The mail of the address check: with a community page in the session, the link holds `landing` and its signature is valid. With the dashboard, the link of an invitation, or nothing in the session: no `landing`. The same for "Resend verification email" and for a change of address in the profile.
   5. The address check:
      1. Another browser (a new session, a sign-in, then the link): the person lands on the page of the community. This is the test that fails on `main`.
      2. The same browser: the test of `CommunityJoinTest.php:548` passes without a change.
      3. The session holds a page of community B and the link holds community A: B. The session holds the link of an invitation: the invitation. The session holds the dashboard: A.
      4. A changed `landing`: 403, and the address is not verified. A link without `landing`: `/dashboard?verified=1` (the test of `EmailVerificationTest.php:21` passes without a change). A request that wants JSON: 204. A second click on the link: the same landing.
      5. Each case of rule 16: a member already, an open community with a public page (and the person is not a member after the landing), a members-only space, a space that was deleted, a community without a public page (the next request answers 403 with the page without access), a community that was deleted, the slug of a personal community.
   6. Each sign-in way from a community page: a password (exists, `PublicSpacesTest.php:381`), a password with the second factor, a provider with a new account, a provider with an account that exists, a sign-in link in the same browser, a sign-in link in another browser, a sign-in link with the second factor.
   7. The sign-in link: an unknown address stores no landing. The landing entry is gone after `consume()` and after 15 minutes. A token that was stored without a landing signs in and lands on the dashboard. A session that holds a community page wins over the landing of the token.
   8. The invitation tests of #1251 pass without a change.
4. Vitest: nothing. No component and no page changes. Say so in the report.
5. Bruno and OpenAPI: nothing. No API route changes. `BrunoCollectionTest` and `OpenApiDocumentTest` stay green.
6. `composer security:php`: when the scan reports the redirect or the session write, put `@psalm-taint-escape` at the check of rule 3, with the reason in the same docblock (definition of done 10). Never silence it at another place.
7. Documents:
   1. New ADR, the next free number (0056 on 2026-11-20: check it): "The landing after the address check travels in the link". Context: facts 6 and 7. Decision: the choice, rules 1 to 18, the three points of "No open redirect". Alternatives: options A, B and D with their reasons. Consequences: no personal data at rest and no change of a legal text, the limit of option C, the 404 of rule 16.7, and that a slug which another community takes inside the life of a link leads to that community. Add its row to `documentation/adr/README.md`. ADR 0044 is not edited: the new ADR names its point 15 and its "Who can join" point 4.
   2. `documentation/architecture/backend.md`: in "Visitors", one paragraph on `Landing`, the two links that carry it, and the order of rule 14.
8. The browser check, in the Docker development environment (definition of done 1). No screen changes, so check the path, not the look:
   1. As a visitor, open the public page of an open community of the demo data, press "Sign up to join", and sign up with an `example.com` address.
   2. Read the mail in the mail catcher of the development environment. Copy the link, and open it in a private window. Sign in there. You land on the public page of the community, with "Join". Press "Join".
   3. Do it again from a public post. You land on the post.
   4. Do it again from the address of a members-only space. You land on the public page of the community.
   5. As a member, ask for a sign-in link from a public page, and open it in a private window. You land on that page.
   6. Do step 2 in the same window. It works as before.

If the reviewer wants a smaller diff, the one clean cut is rule 12 with scope 3.7 (the sign-in link in another browser), as a second commit. The rest does not split.

## NOT in this commit

1. The titles, the words and the community line of the sign-in, sign-up and address check pages (#1571). `Landing::fromSession()` is the value that #1571 can show later.
2. The first dashboard, its welcome card and its filters (#1575).
3. A first page for new members and a welcome message per community (#1398). When #1398 exists, `Landing::remember()` is the place that asks it.
4. A join that happens by itself, and a join on request.
5. A column, a table, a cookie, or a `return` parameter on a form.
6. A landing on the other member pages (the details page, a member profile, the messages, the settings).
7. The link of an invitation in another browser: the person opens the invitation mail again, as today.
8. The page of a verification link that expired (403 today), and the 404 page (R-L1).
9. The API, and the sign-in of the API.

## How to work

1. `git fetch origin`, then branch `feat/1612-land-in-the-community` from `origin/main`. ONE commit, no push, no pull request. Find the text when a line number moved.
2. Docker on this Linux machine, from `~/wks/knowii`: `docker compose exec -T -u sail laravel.test <command> </dev/null`. Do not start, stop or restart containers. No `migrate:fresh` and no seeder on the development database.
3. PHP commands in the container: `composer format`, `composer lint`, `composer security:php`, and the whole Pest suite with `php artisan test --parallel`. Front-end commands on the host, to prove that nothing moved: `npx nx format --all`, `npm run tsc`, `npm run tsc:spec`. Never run the whole Pest suite and the whole Vitest suite at the same time.
4. A piped test command hides a failure: use `bash -c "set -o pipefail; ..."`. Without `--parallel`, `php artisan test` takes ONE path.
5. Write the first test of scope 3.5 before the code, and see it fail on `main`.
6. A test of another browser needs a new session and a guard that forgets its user: see `$this->app['auth']->forgetGuards()` in `CommunityJoinTest.php:553`. A wrong method on a web route answers 404, not 405.
7. Never name a third-party community product. Use `example.com` addresses and `example` hosts.
8. The commit: a conventional type (`feat`), one of the allowed scopes, a header of at most 100 characters that does not start with a capital letter. Suggested: `feat(auth): the address check and the sign-in link land in the community that the person came from`. Footer, after a blank line: `Closes #1612`. No trailer line that names a tool or a co-author.
9. If a rule cannot be built as written, skip that rule, build the rest, and say why in the report. Do not invent product rules.
10. If you find a weakness in the code of `main` while you work, do not write it in the commit, in a test name, in the ADR or in an issue. Handle it as `SECURITY.md` says, and tell the reviewer in private.

## Questions for the owner

1. The place of the destination. Option C (inside the signed link, nothing stored) or option B (columns on the account)? Recommendation: C. It needs no change of the privacy policy, and its only limit is a mail that is sent again from another browser.
2. A person who signed up from the address of a community without a public page (a private community). After the address check: the page without access of that community, which says "by invitation" (the answer of today in the same browser), or the dashboard? Recommendation: the page without access. It names the next step, and the person typed that address themselves.
3. A person signs up from community A, then opens a page of community B in the same browser before the address check. Land on B (the session wins, as today) or on A (the link wins)? Recommendation: B.
4. The sign-in link by email in another browser: in this commit, or later? Recommendation: in this commit. It is the same gap, and it is about 40 lines.
5. A short confirmation after the address check (for example "Your email address is verified.")? Today nothing says it, on the dashboard too. Recommendation: not here. The words of the address check belong to #1571, point 3.

## Your final report (max 400 words)

1. The commit hash and `git show --stat`.
2. The test of scope 3.5.1: its failure on `main` and its pass on the branch.
3. The data set of scope 3.3, with the result of each input.
4. Each choice where the brief left room, and the answer to rule 3.4 (the characters of a slug).
5. Results of Pest, PHPStan, Pint, the security scan, the type check and the format check, with the exact failure text of anything that failed.
6. The six steps of the browser check, each with what you saw.
7. Anything of the scope not built, and why.
