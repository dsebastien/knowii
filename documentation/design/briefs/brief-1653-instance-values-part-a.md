# Brief: the values of one instance leave the code, part A: configuration and the start check (knowii-oss/knowii#1653)

The work of #1653 is too large for one commit. It has two parts, in this order. Part A (this brief, in full): the operator, the contact address, the publisher, the footer and social links, the structured data, `humans.txt` and the seeder come from configuration, and production refuses to start without the two required values. Part B (outline at the end): the legal texts. Part A leaves `main` working and changes no legal text. ONE commit on a local branch: a reviewer reads your diff afterwards. Paths are relative to `~/wks/knowii`.

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md`, `documentation/conventions/naming.md`.
2. The issue and the decisions of the owner: `gh api repos/knowii-oss/knowii/issues/1653 --jq .body` and `gh api repos/knowii-oss/knowii/issues/1653/comments --jq '.[].body'`. "Decision N" below is a point of that comment.
3. `documentation/design/instance-values/comparison.md`, "Recommendation for Knowii" and "Trade-offs".
4. What #1643 did: `git show --stat aff885d1 00677881`, `documentation/architecture/external-account-service.md` (the introduction), `tests/Unit/InstanceNeutralNamesTest.php` (all of it).
5. Code: `config/legal.php`, `config/security_txt.php` and `app/Support/SecurityTxt.php` (the model: values from configuration, empty hides, a wrong value is left out and logged), `config/robots.php` (a default that depends on `APP_ENV`), `config/communities.php` (`$list`), `app/Support/Newsletter.php` (`pageProp`), `app/Http/Middleware/HandleInertiaRequests.php`, `bootstrap/app.php`, `app/Listeners/PruneNotificationsAfterAccessChange.php`, `app/Mail/CommunityInvitation.php`, `app/Support/AccountDeletion.php` (`UNAVAILABLE_MESSAGE`), `app/Constants.php` (`CAPTURE_USER_AGENT`, `SOCIAL_MEDIA_LINK_PROPERTIES`), `app/Traits/FetchUrl.php`, `resources/views/app.blade.php`, `database/seeders/DatabaseSeeder.php`, `routes/web.php` (line 138), `public/humans.txt`, `docker/production/entrypoint.sh`, `compose.production.yaml`.
6. Front end: `libs/common/src/lib/metadata.json`, `footer-links.ts`, `common.ts` (line 59), `types/jetstream/jetstream-inertia.intf.ts`, `types/social-media-link.schema.ts`; `apps/knowii/src/Components/Footer.tsx`, `Pages/Contact.tsx`, `Pages/Profile/Partials/DeleteUserForm.tsx`, `Pages/Legal/Show.tsx`, and their specs.
7. Documents: `SELF_HOSTING.md` (step 4, step 8, "Docker"), `.env.example`, `.env.production.example`, `documentation/adr/README.md`, `template.md`, ADR 0046.

## Rules (decided, do not change them)

### The two required values

1. `config/legal.php` holds `operator` (env `LEGAL_OPERATOR`) and the new `contact_email` (env `LEGAL_CONTACT_EMAIL`). No default names an operator or an address (decision 1). The sub-processors stay in this file: part B moves them. Its header comment says "the operator", not a company.
2. A value that is empty or blank counts as not set (`KEY=` in `.env` gives an empty text, not the default). When a value is not set and `env('APP_ENV', 'production') !== 'production'`, the configuration uses the placeholder: `Constants::INSTANCE_PLACEHOLDER_OPERATOR = 'Example Operator'` and `Constants::INSTANCE_PLACEHOLDER_CONTACT_EMAIL = 'contact@example.com'` (decision 1). In production a value that is not set stays empty.
3. New `App\Support\InstanceValues` (final, static methods, like `Newsletter`). `missing(): list<string>` returns the env names to set. It returns an empty list when `! app()->isProduction()`. In production a value is missing when it is empty, when it equals its placeholder, or, for the address, when `filter_var(..., FILTER_VALIDATE_EMAIL)` refuses it. [Q] The owner did not say that a placeholder counts as empty in production. Recommended and built: yes, so a copied example file does not pass.
4. The message, one constant text with the missing names joined by " and ": `This instance is not configured. Set LEGAL_OPERATOR and LEGAL_CONTACT_EMAIL in the environment. See SELF_HOSTING.md, section "Instance values".`

### The start check

5. "Production" means `app()->isProduction()`. The image sets `APP_ENV=production`, so an acceptance instance needs the two values too (comparison, trade-off 1).
6. HTTP: new global middleware `App\Http\Middleware\RefuseUnconfiguredInstance`, first in the global stack (`$middleware->prepend(...)` in `bootstrap/app.php`). When `InstanceValues::missing()` is not empty, it writes `Log::critical` with the message and returns `response($message, 503)` as `text/plain; charset=UTF-8`. Every address answers so, `/up` too: the health check of Docker fails, `docker compose up --wait` fails, and the other services never start (`depends_on` of `compose.production.yaml`).
7. Console: new `App\Listeners\RefuseUnconfiguredInstance` with `handleCommandStarting(Illuminate\Console\Events\CommandStarting $event)`. When values are missing and the command is one of `queue:work`, `queue:listen`, `schedule:work`, `schedule:run`, `reverb:start`, it throws `App\Exceptions\InstanceNotConfiguredException` (extends `RuntimeException`) with the message: artisan prints it and exits with 1. The list is `Constants::INSTANCE_CHECKED_COMMANDS`.
8. Every other artisan command runs: `key:generate` (documented without any environment), `storage:link`, `package:discover`, `config:cache`, `route:cache`, `view:cache`, `event:cache`, `optimize`, `migrate`, `app:health`, `about`, the export commands. Do not change `HealthCheckCommand` (another branch edits it).

### The optional values

9. New `config/instance.php`. Each value is trimmed text; empty means hidden (decision 4).

| Key                | Env                       | Used by                                         | When empty                         |
| ------------------ | ------------------------- | ----------------------------------------------- | ---------------------------------- |
| `publisher.name`   | `INSTANCE_PUBLISHER_NAME` | Footer line `© <year> <name>.`, structured data | No `©` text, no `publisher` member |
| `publisher.url`    | `INSTANCE_PUBLISHER_URL`  | Structured data                                 | No `url` of the publisher          |
| `footer.note`      | `INSTANCE_FOOTER_NOTE`    | Text after the `©` text                         | Not shown                          |
| `footer.links`     | `INSTANCE_FOOTER_LINKS`   | Links of the "Community" column                 | No extra link                      |
| `social_links`     | `INSTANCE_SOCIAL_LINKS`   | Icon links of the footer, `sameAs`              | No icon, no `sameAs`               |
| `seed.admin_email` | `SEED_ADMIN_EMAIL`        | `DatabaseSeeder`                                | Default `admin@example.com`        |

10. `INSTANCE_FOOTER_LINKS`: entries separated by commas, each `Label=https://address` (split at the first `=`). `INSTANCE_SOCIAL_LINKS`: entries `key=https://address`, where `social_link_<key>` is in `Constants::SOCIAL_MEDIA_LINK_PROPERTIES` (for example `github=https://github.com/example`). `InstanceValues` parses both. It leaves out an entry with an empty label, an unknown key or an address that is not `https://`, and writes one `Log::warning` (like `SecurityTxt::contacts()`). Narrow every configuration value with `is_string`: no `mixed`.
11. `InstanceValues::pageProp()` returns `contactEmail` (text), `publisher` and `footerNote` (text or null), `footerLinks` (list of `label`, `href`) and `socialLinks` (list of `property`, `href`). `HandleInertiaRequests` shares it lazily as `instance`. `InertiaSharedProps` gets `instance?: InstancePageProp`.

### The front end

12. `metadata.json` keeps only `repositoryUrl` (decision 4). No code reads another key after this commit.
13. `footer-links.ts`: `buildFooterLinkGroups(instance)` returns the groups in the order of today. "Community" holds the configured links, then "Contact us" when `contactEmail` is not empty. A group without a link is left out. The two links of one instance in that column are removed: the hosted instance sets them in `INSTANCE_FOOTER_LINKS`.
14. `Footer.tsx`: the bottom line shows `© {year} {publisher}.` when the publisher is set, then the note when it is set, and no `<p>` when both are missing. [Q] The line of today ends with a country sentence: recommended, it becomes `INSTANCE_FOOTER_NOTE`. The mail icon shows only with a contact address. The repository icon stays. One icon link per `socialLinks` entry, with `SOCIAL_MEDIA_LINK_ICONS[property]` and `aria-label={SOCIAL_MEDIA_LINK_NAMES[property]}`. Without the `instance` prop the footer hides all of these.
15. `/contact` becomes `Route::get` with a closure: 404 when `legal.contact_email` is empty, else the `Contact` page, which reads the address from the prop.
16. `DeleteUserForm.tsx` reads the address from the prop. Without one, the two sentences say "the operator of this site" in place of the address (the words of `AccountDeletion::UNAVAILABLE_MESSAGE`).

### Server pages and files

17. `app.blade.php`: the JSON-LD block is built by `InstanceValues::structuredData()` and printed with `json_encode` and the flags `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR`. Members: `@context`, `@type` `WebSite`, `name` (`config('app.name')`), `description` (the text of today), `url` (`config('app.url')`), and `publisher` only with a publisher name: `@type` `Organization`, `name`, `url` when set, `sameAs` when social links exist. [Q] Today the publisher is a person: recommended, an organisation.
18. `public/humans.txt` names the project (decision 4). The TEAM part becomes two lines: `The Knowii open source project -- https://github.com/knowii-oss/knowii` and `Contributors -- https://github.com/knowii-oss/knowii/graphs/contributors`. Leave the other parts.
19. `DatabaseSeeder`: the first user is `Example Admin` with `config('instance.seed.admin_email')` (decision 4). `documentation/api/.env.example` gets the same default.
20. `CAPTURE_USER_AGENT` ends with `Knowii/1.0 (+https://github.com/knowii-oss/knowii)`: the project, not one instance.
21. `CommunityInvitation` keeps `config('legal.operator')`. The comments of `ContentSecurityPolicySources.php`, `Analytics.php` and `config/services.php` use `example.com` hosts. The comment of `Show.tsx` (line 16) says "the owner of the instance".

### The guard

22. `InstanceNeutralNamesTest` gets these patterns (issue, "Done when" 2): `/develo\s*passion/i` (the operator), `/s[eé]bastien/iu` (the founder), `/0717[.\s]?561[.\s]?755/` (the company number), `/route\s+de\s+mons|\bbinche\b/i` (the street address), `/\bknowii\.net\b/i` (a host of one instance).
23. It reads `app`, `config`, `database`, `lang`, `routes`, `resources/views`, `apps/knowii/src`, `libs/common/src`, `tests`, and the files `public/humans.txt`, `.env.example`, `.env.production.example`. The list of skipped files is empty. It never reads `deploy/` (decision 3): a test checks that no entry starts with `deploy`.
24. One exception until part B: `tests/Feature/LegalPagesTest.php` (it checks the words of the hosted texts). `resources/markdown/` is not read yet.

## Scope (in the commit)

1. Rules 1 to 24, with the two constants of rule 2 and the list of rule 7 (server only).
2. `.env.example`: no `LEGAL_OPERATOR` line with a value (a comment shows `# LEGAL_OPERATOR="Example Ltd (Ireland)"` and `# LEGAL_CONTACT_EMAIL=contact@example.com`), `MAIL_FROM_ADDRESS="contact@example.com"`, the statistics example with `example.com` hosts, the five `INSTANCE_*` keys empty, `SEED_ADMIN_EMAIL=admin@example.com`. `.env.production.example`: `LEGAL_OPERATOR=` and `LEGAL_CONTACT_EMAIL=` empty, with "Required: the application refuses to start without them", and the five `INSTANCE_*` keys.
3. Tests that name a value of one instance get neutral values: `AnalyticsTest`, `ContentSecurityPolicySourcesTest`, `ReservedCommunityNamesTest` (lines 18 and 47), `CommunityMemberManagementTest` (line 403: set `legal.operator` in the test), `Show.spec.tsx`, `DeleteUserForm.spec.tsx`.
4. Pest, new `tests/Feature/InstanceValuesTest.php`. Set production with `$this->app->detectEnvironment(fn () => 'production')`.
   1. Rules 1 to 3: the placeholders in the test environment; in production, `missing()` for each case (empty, blank, the placeholder, a wrong address, both set).
   2. Rule 6: 503 with the exact message on `/`, `/up` and an API address; 200 with both values; no refusal outside production with empty values.
   3. Rules 7 and 8: dispatch `CommandStarting` for each checked command (exception) and for `migrate`, `config:cache`, `key:generate`, `app:health` (no exception).
   4. Rules 10 and 11: valid entries, each kind of wrong entry left out, the `instance` prop of `/login`.
   5. Rule 15: 404 and 200. Rule 17: decode the JSON of `/`; no `publisher` when empty; a value with `</script>` stays inside the block. Rule 19: with `$this->seed()` on the test database, the user has the configured address. The invitation mail names the configured operator.
   6. The guard: each new pattern is found (build each value from two parts) and neutral text is left alone (`Example Operator`, `contact@example.com`, the repository address).
5. Vitest: `Footer.spec.tsx` (publisher and note shown and hidden, configured links, "Contact us", social icons, nothing without the prop), a spec for `buildFooterLinkGroups`, a new `Contact.spec.tsx`, `DeleteUserForm.spec.tsx` (the address of the prop, and the text without one).
6. Documents:
   1. `SELF_HOSTING.md`: a part "Instance values" in step 4 with ONE table of every instance value (decision 4). Columns: value, required, default outside production, effect when empty. Rows: `APP_NAME`, `APP_URL`, `LEGAL_OPERATOR`, `LEGAL_CONTACT_EMAIL`, `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME`, the five `INSTANCE_*` values, `SECURITY_TXT_CONTACTS`, `OIDC_LABEL`, `SESSION_COOKIE`, `COMMUNITY_RESERVED_NAMES`, and "the legal texts" (a link to the paragraph of today). Remove these rows and the `LEGAL_OPERATOR` sentence from where they are now. Step 8 and "Docker, First start" get a note: set the two required values before the update.
   2. A new ADR from `template.md`: "The values of one instance come from configuration". Status Accepted, the date of the build, Issue #1653, "Pull request: not yet opened". It records decisions 1 to 4, rules 1 to 24, that part B follows, and the alternatives of the comparison (a setup wizard, texts in the database, a neutral default in production). Number: list `documentation/adr` on `origin/main` and on every local branch (`git for-each-ref refs/heads`) and take the next free one. Add it to the index.
   3. `documentation/architecture/backend.md` (a part "Instance values") and `frontend.md` ("Global data": the `instance` prop). `external-account-service.md`: the sentence about the guard also names the values of #1653.

## NOT in this commit

1. Part B: any file of `resources/markdown/`, `app/Support/LegalPages.php`, the legal routes, the sub-processors, `tests/Feature/LegalPagesTest.php`, the legal links of the footer, the sign-up consent, the deployment workflows.
2. One word of a legal text, of `SECURITY.md` or of the security page.
3. `package.json`, `composer.json`, `funding.json`, `.github/`, the runbooks: data of the project.
4. A setup wizard, an administration screen, a database table (decision 4).
5. `HealthCheckCommand`, the entrypoint, the image, the Compose file.

## How to work

1. `git fetch origin`, then branch `feat/1653-instance-values` from `origin/main`. ONE commit, no push, no pull request. The working tree can be on another branch with mail code: leave that branch as it is. Line numbers are those of 2026-11-19: find the text, do not trust the number.
2. Docker on this Linux machine, from `~/wks/knowii`: `docker compose exec -T -u sail laravel.test <command> </dev/null`. Do not start, stop or restart containers. No `migrate:fresh` and no seeder on the development database.
3. A piped test command hides a failure: use `bash -c "set -o pipefail; ..."`. `php artisan test` takes ONE path: no path for the whole suite, or one run per path.
4. A wrong method on a web route answers 404, not 405 (the fallback route).
5. `InstanceNeutralNamesTest` reads `tests/` too: a test that checks that a name is absent builds that name from two parts. Never name a third-party community product. Use `example.com` addresses only.
6. Rules that fail the build: no new `mixed` in `app/` (`MixedTypeUsageTest`), PHPStan at its highest level, Pint, ESLint, the type check.
7. Before the commit, after the last change, each in the container: `npm run format` (Prettier and Pint), `npm run lint` (ESLint and PHPStan), `npm run tsc` and `npm run tsc:spec` (the TypeScript build), `npm run test:all` (Vitest), `composer security:php`, and the whole Pest suite (`php artisan test`).
8. The commit hook allows only the scopes all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build. The subject does not start with a capital letter. A blank line after the subject and before the footer. Suggested: `feat(all): instance values come from configuration, and production needs an operator and a contact`. Footer: `Refs #1653` (part B, the last part, carries `Closes #1653`). No trailer line that names a tool or a co-author.
9. If a rule cannot be built as written, skip that rule, build the rest, and say why in the report. Do not invent product rules.

## Your final report (max 400 words)

1. The commit hash and `git show --stat`.
2. Each choice where the brief left room (the ADR number included), and each [Q] as built.
3. Results of Pest, Vitest, PHPStan, ESLint, the type check and Pint, with the exact failure text of anything that failed.
4. The values that the hosted instance must set before its next deploy, to look as it does today.
5. Anything of the scope not built, and why.

## Outline of part B: the legal texts (own brief, branch `feat/1653-legal-texts`, after the owner approves the move)

1. Slugs and files, unchanged: `privacy-policy` `policy.md`, `terms-of-service` `terms.md`, `dpa` `dpa.md`, `imprint` `imprint.md`, `content-policy` `content-policy.md`, `security` `security.md`. The only source is `config('legal.path')`, default `storage_path('app/legal')`, with no env name (decision 2). The routes stay registered; the controller answers 404 per request when the file is missing.
2. `git mv` of the six files to `deploy/knowii.net/legal/`, with no word change (decision 3). [Q] The sub-processors leave `config/legal.php` for `subprocessors.json` beside the texts, with the same words; data rule 9 changes.
3. Neutral templates in `resources/legal-templates/` (`draft: true`, `{{operator}}`, `{{contact}}`, `{{site}}`, `[TODO: ...]` marks). No code reads that folder.
4. A shared prop `legalPages` (the slugs that exist) hides each link: the footer, the newsletter text, the `Policy` line of `security.txt`, the privacy sentence of the invitation mail. [Q] Sign-up and the sign-in button name only the texts that exist; with none, the box keeps "I am 16 or older". [Q] Without `dpa.md`, a community is created with no agreement recorded. The sitemap is an empty static file: no change.
5. Tests: neutral fixtures in `tests/Fixtures/legal/` for the mechanics; one test file reads `deploy/knowii.net/legal/` and keeps every word check of today (the guard skips this one file).
6. Deploy: `storage/app` is a volume and the image holds no `deploy/` folder. The deploy copies the folder into `storage/app/legal/` BEFORE the new release starts (the release of today already reads that folder first). Host: one `cp` line in the deploy script. Docker: a workflow step that pipes a tar of the folder into `docker compose run --rm --no-deps -T --entrypoint sh app`. The Compose file and the image need no change.
7. The guard also reads `resources`; the exception of rule 24 goes. `SELF_HOSTING.md`, `rules.md` (data rules 9 and 10), `AGENTS.md`, `.github/CODEOWNERS` and the ADR get the new paths.
