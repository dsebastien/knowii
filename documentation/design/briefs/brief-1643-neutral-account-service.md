# Brief: the platform depends on no service of one instance: neutral names, a contract page, a guard (knowii-oss/knowii#1643)

ONE commit on a local branch `refactor/1643-neutral-account-service` from `origin/main` (after #1521 is merged: it adds tests in the same files). You do not push and you do not open a pull request. Pest needs the main working tree `~/wks/knowii` (the container mounts only it).

## Read first

1. `AGENTS.md`, `documentation/conventions/rules.md`, `documentation/conventions/naming.md`.
2. The issue and its comment: `gh issue view 1643 --comments`. The other values of one instance (legal operator, `metadata.json`, the seeder, the legal texts, the structured data of `app.blade.php`, `humans.txt`, the footer links) are #1653: NOT in this commit.
3. ADR 0010, 0025, 0045, 0047 (and the ADR of #1521 when it is merged), `documentation/architecture/high-level-design.md`, `roles-and-permissions.md`, the OpenID Connect sections of `SELF_HOSTING.md` ("Sign in with an OpenID Connect provider", "Entitlements of the provider feed the access groups").
4. Code: `app/Actions/Auth/OpenIdConnectClient.php`, `app/Support/OpenIdConnect.php`, `app/Support/OidcEntitlements.php`, the webhook controller `app/Http/Controllers/Webhooks/OidcEntitlementsWebhookController.php`, `config/services.php` (`oidc`).

## Scope

1. Neutral names, everywhere outside the ADRs and the legal texts: the code comments (`AddAccessGroupMember.php`, `AccessGroupApiController.php`), the test data (`tests/Pest.php`, `OpenIdConnectTest.php`, the front-end specs: `OpenIdConnectButton.spec.tsx`, `Login.spec.tsx`, `ConnectedAccountsForm.spec.tsx`), the architecture documents and `SELF_HOSTING.md`. Use "the external account service" for the whole contract (sign-in, entitlements, events) and "the sign-in provider" where only the sign-in is meant. The test label is "Example account" and the hosts are `example.com` hosts. Today's list of places is the output of `git grep -n -i -E 'knowii account|my\.knowii|auth\.knowii'` on `main`.
2. The ADRs are history: no rewrite. ADR 0010, 0025 and 0045 get one line under their status: "Names (2026-11-18, #1643): the provider is any external account service; the Knowii account is one instance of it."
3. A new page `documentation/architecture/external-account-service.md`: the contract that a provider implements, written so that another provider can implement it from this page alone. Sections: what the platform needs (and that every part is optional), the sign-in (discovery, the client, the redirect address, the claims, the algorithms, PKCE, the fresh sign-in of #1521: `max_age=0` honored and `auth_time` sent), the entitlements read (the client credentials grant, the scope, the address with `{subject}`, the answer: read the code and the tests for the exact JSON shape, and say what the platform does with each field), the change events (the address, the signed JWT, its header and claims, the answers of the platform), the errors and what the platform does with each, and the configuration values on the side of the platform. Link it from `SELF_HOSTING.md` (both OpenID Connect sections) and from ADR 0045 and 0047 ("see also").
4. Links to the outside service: no link exists yet. Write in the page that a future link ("manage my account") is a configuration value (an address and a label), hidden when not set. No code for it now.
5. A guard test (Pest): it fails when a host or a product name of one instance comes back in `app/`, `config/`, `routes/`, `apps/knowii/src/`, `libs/common/src/` or `tests/`. The list: `knowii account` (any case), `my.knowii.net`, `auth.knowii.net`. Allow-list the files of #1653 that still hold other values of one instance only if the guard reads them (it must not read `resources/markdown/`, the ADRs or `libs/common/src/lib/metadata.json`; say each exception in the test with its issue).
6. The legal texts (`resources/markdown/policy.md`, `dpa.md`) keep their words: they describe the hosted instance and change only with the owner. Quote in the report each sentence that names the product, with a proposal of neutral words, for the owner.

## Rules

1. No change of behavior: names, comments, documents and tests only, plus the guard.
2. Pest: `php artisan test` in the container, the whole suite before the commit (`docker compose exec -T -u sail laravel.test php artisan test --parallel --processes=8 </dev/null`, read the line `Tests:`). Vitest, lint, typecheck, `npm run tsc`, `npm run tsc:spec`, `composer lint`, `composer format`.
3. Example data: `example.com` addresses. Never the name of a third-party community product.

## Commit

Suggested subject: `refactor(auth): neutral names for the external account service, its contract and a guard`. Footer: `Refs #1643` (the issue closes when the owner answers on the legal words). A wrapped line must not start with a word and a colon; lines under 100 characters. No trailer line.

## Report (max 500 words)

The commit and its stat; the list of places changed; the sections of the contract page and anything of the contract that the code does not make clear (a gap is a finding for the owner, not a change of code); the guard and its exceptions; the sentences of the legal texts with a proposal; the results of the checks.
