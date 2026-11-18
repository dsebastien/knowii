# Brief: an account without a password can delete itself (knowii-oss/knowii#1521)

An account can exist without a usable password: it signs in only through the OpenID Connect provider of the instance (ADR 0045). The deletion of an account asks for the password, on the web and in the API, so such a member cannot delete the account. That breaks the right to erasure. The owner decided: the proof for such an account is a NEW sign-in at the provider, a short time ago, checked on the server. This is security code: read every rule twice, and build exactly this. ONE commit on a local branch. You do not push and you do not open a pull request. Paths are relative to `~/wks/knowii`.

## Read first

1. `AGENTS.md` (also "Security issues"), `CONTRIBUTING.md`, `documentation/conventions/rules.md`, `documentation/conventions/naming.md`.
2. The issue and its three comments: `gh api repos/knowii-oss/knowii/issues/1521 --jq .body` and `gh api repos/knowii-oss/knowii/issues/1521/comments --jq '.[].body'`. The last two comments hold the rules.
3. ADR 0045 (the OpenID Connect sign-in), ADR 0043 and ADR 0024 (the deletion of an account), ADR 0012 (how an ADR is amended), `documentation/adr/README.md`, `template.md`, `documentation/architecture/roles-and-permissions.md`, the OpenID Connect part of `SELF_HOSTING.md`.
4. Code: `app/Actions/Auth/OpenIdConnectClient.php`, `SignInWithOpenIdConnect.php`, `LinkIdentity.php`, `UnlinkIdentity.php`, the controllers and routes of `/auth/oidc/*` (how the state, the nonce and the PKCE verifier are kept and used once; how "connect an account" reuses the same callback), `app/Support/OpenIdConnect.php`, `config/services.php` (`oidc`), the web deletion (`DeleteAccountController` or the Jetstream action that it calls), the API deletion (`DELETE /api/v1/user`, #1492) with its request, the two-factor check of both, `apps/knowii/src/Pages/Profile/Partials/DeleteUserForm.tsx`, `ConnectedAccountsForm.tsx`, the API client in `libs/common`.
5. Tests: `tests/Feature/OpenIdConnectTest.php` (how a provider is faked: the discovery document, the keys, a signed ID token), the tests of the account deletion on the web and in the API.

## Rules (decided, do not change them)

### Who

1. An account WITH a usable password: nothing changes (the password, and the two-factor code when it is on).
2. An account WITHOUT a usable password that has a connected identity of the provider, on an instance where the provider is configured: the proof of rules 3 to 9.
3. An account without a usable password and without a way to make the proof (no connected identity, or the provider is not configured any more): a clear refusal, with a text that tells the person to write to the operator of the site. Never a deletion without a proof.

### The proof: a new sign-in

4. The person starts the proof from the deletion screen, with a POST (CSRF protected) of a signed-in session. The server keeps the intent in the session, with its own state, nonce and PKCE verifier, each used once, as the sign-in does. The intent holds the id of the account.
5. The authorize request of the proof holds `max_age=0` and `prompt=login`. It uses the callback address that the provider already knows (the platform registers one exact redirect address): the callback tells the three purposes apart (sign-in, connect, proof) by the state that the server stored, never by a parameter that the browser can change.
6. The callback checks the ID token as the sign-in does (issuer, audience, signature, expiry, nonce), and then, for a proof, ALL of this:
   1. the claim `auth_time` is present and is an integer. Absent or of another type: refused. The text says that the sign-in provider did not confirm a new sign-in. This check is the proof: a person can remove `max_age` from the request in their own browser, and a provider can ignore it.
   2. `auth_time` is at most `services.oidc.fresh_sign_in_seconds` old (env `OIDC_FRESH_SIGN_IN_SECONDS`, default 300, kept between 60 and 900) and not more than 60 seconds in the future (the clock of the provider).
   3. `sub` is exactly the identifier of the identity that is connected to THIS account. Another account of the provider: refused.
   4. the person who comes back is the same signed-in account that started the proof. Another session, or no session: refused.
7. A valid proof is recorded on the server: the account, the time, single use. It is valid for `fresh_sign_in_seconds` after `auth_time` (not after the callback). It is NOT a deletion: the callback is a GET and must never delete anything. The person comes back to the deletion screen, which now says that the proof is made and until when it holds.
8. The deletion itself is the POST that exists (web) and the `DELETE` that exists (API). For an account of rule 2 it takes the recorded proof in place of the password: present, of this account, not expired, not used. It is consumed in the same transaction as the deletion, so a second request cannot use it. The two-factor code is still required when two-factor authentication is on, exactly as today. The choice "also delete my posts, comments and messages" works as today.
9. A failed or refused proof leaves the account as it was, and says why in plain words (no detail of the token). Every refusal is logged with the reason and the account, without the token.

### API parity (decided by the owner on 2026-11-18: option (a))

The owner decided that the web screen and the API follow the same rule, and that a token client uses the proof that a browser made a short time before (the comment of 2026-11-18 on the issue). The other option (the API refuses and only the web deletes such an account) was not chosen.

10. `DELETE /api/v1/user` follows the same rule. A token client cannot make the browser round trip, so: for an account of rule 2 the request succeeds when a valid proof is recorded for the account (made in a browser a short time before), with the same token ability as today and the two-factor code when it is on. Without a valid proof it answers a business error (422) whose message says that a new sign-in is needed and gives the address of the page where the person makes it. Keep the proof on the server in a place that both the web session and a token request can read (not only in the session), keyed by the account. Document it: the OpenAPI text, the Bruno request, the API README.
11. No new token ability, and no way to make or to read a proof through the API.

### The screen

12. For an account of rule 2 the deletion dialog has no password field. It explains the step in plain words and has a button "Confirm with <label of the provider>" (the label is the configuration value that the sign-in button uses). After a valid proof the dialog (or the section) says "You confirmed with <label>. You can delete your account until <time>." and offers the final button. The box "Also delete my posts, comments and messages" and its help line stay, and the choice of the box survives the round trip.
13. For an account of rule 3: no button, the refusal text.
14. Texts: simple words, sentence case, no emoji. Accessibility: the result of the proof is announced (a status or an alert as fits), the focus goes to a sensible place after the return, both themes, 360 px.

### Neutral code (issue #1643)

15. Nothing in the new code, tests or documents names one provider or one product. Tests use a neutral label ("Example account") and `example.com` hosts. It must work with any OpenID Connect provider; a provider that does not send `auth_time` gets the clear refusal.

### Documents

16. A new ADR with the next free number (list `documentation/adr` on `origin/main` and on every local branch first): the rule, why `auth_time` is the proof and `max_age` is not, why the callback never deletes, the API rule, the alternatives (a confirmation mail; a deletion at the callback; a proof kept only in the session). It amends ADR 0043 and ADR 0045: "amended by" in their status lines, and the index.
17. `SELF_HOSTING.md`: the new value, and one line that says that the provider must support `max_age` and send `auth_time` for the deletion of an account without a password. `.env.example` and `.env.production.example`: the value with a comment. `roles-and-permissions.md` and `backend.md` where they describe the deletion.
18. The privacy policy and the other texts of `resources/markdown/`: do not change them. If a sentence becomes false, quote it in the report.

## Tests

1. Pest, with the faked provider of `OpenIdConnectTest.php`: a valid proof then a deletion (web and API); no `auth_time`; `auth_time` as a string; an old `auth_time`; an `auth_time` in the future; another `sub`; the proof of another signed-in account; a callback without a session; a replay of the same callback (the state is used once); a proof used twice (the second deletion fails); a proof that expired before the deletion; the two-factor code required and wrong; the deletion with and without "also delete my content"; an account with a password still needs the password and cannot use a proof; an account of rule 3; the authorize request holds `max_age=0` and `prompt=login`; the normal sign-in and "connect an account" still work and do NOT record a proof; the API without a proof answers 422 with the address; nothing is deleted on any refusal.
2. Vitest: the three states of the dialog (password, proof to make, proof made), the refusal state, the box that survives.
3. `OpenApiDocumentTest`, `BrunoCollectionTest`, `ApiTokenAbilitiesTest` stay green. Keep every existing test green.

## NOT in this commit

1. A confirmation by mail. A change of the sign-in or of "connect an account" beyond what the shared callback needs. A registration of a client at a real provider (#1522).

## How to work

1. Branch `fix/1521-delete-account-without-password` from `origin/main`.
2. Docker on this Linux machine, from `~/wks/knowii`: `echo "docker compose exec -T -u sail laravel.test <command> </dev/null" | newgrp docker`. Do not start, stop or restart containers. Do not run `migrate:fresh` or a seeder on the development database.
3. A piped test command hides a failure: use `bash -c "set -o pipefail; ..."`. `php artisan test` takes ONE path: no path for the whole suite, or one run per path.
4. Rules that fail the build: no new use of `mixed` in `app/` (docblocks included), PHPStan at its highest level, Pint, ESLint, the TypeScript build, Prettier, the Storybook stories as tests.
5. Helper function names in Pest files are global: use unique names. No hand-made regular expression escape in a test: compare exact text.
6. Never name a third-party community product. Use `example.com` addresses only.
7. Before the commit, after the last code change: `composer format`, `composer lint`, the whole Pest suite (`php artisan test`), `npm run lint`, `npm run test`, `npm run build` (all inside the container). Leave `public/build` built from your branch.
8. The commit hook allows only the scopes all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build. The subject does not start with a capital letter. A blank line after the subject and before the footer. A wrapped line of the body must not start with a word and a colon. Suggested subject: `fix(auth): an account without a password can delete itself after a new sign-in`. Footer: `Closes #1521`. No trailer line that names a tool or a co-author.
9. If a rule cannot be built as written, skip that rule, build the rest, and say why in the report. Do not invent product rules.
10. If you find a security problem (for example a page that shows data of a private community to an outsider today), do not write it in the commit message or in any file of the repository. Say it only in your final report.

11. This is security code. If you see a way around a rule (in the new code or in the code that exists), do not write it in a file or in the commit message: say it only in your final report.

## Your final report (max 500 words)

1. The commit hash and `git show --stat`.
2. Where the proof is kept and why, and each other choice where the brief left room (the texts included, the ADR number).
3. Any way around a rule that you saw (point 11), and any sentence of rule 18.
4. Results of Pest, PHPStan, Pint, ESLint, the type checks, Vitest and the build, with the exact failure text of anything that failed.
5. Anything of the scope not built, and why.
6. Exact steps for a reviewer to try the screens in a browser without a real provider (if the tests have a fake provider that can serve a browser, say how; if not, say so).
