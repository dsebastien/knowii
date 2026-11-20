# Brief: the values of one instance leave the code, part B: the legal texts (knowii-oss/knowii#1653)

You build the second and last part of #1653. The legal texts are read only from `storage/app/legal/`. The six texts of the hosted instance move to `deploy/knowii.net/legal/` without one changed word, the sub-processors leave the configuration for a JSON file beside them, and the repository gets neutral templates that are never served. A missing text answers 404 and hides its links. Part A (pull request #1683, ADR 0055) is in `main` before you start. ONE commit on a local branch: a reviewer reads your diff afterwards. Paths are relative to `~/wks/knowii`.

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md` (data rules 8 to 10, definition of done 9), `documentation/conventions/naming.md`.
2. The issue and its comment (`gh api repos/knowii-oss/knowii/issues/1653 --jq .body`, `.../1653/comments --jq '.[].body'`): "issue decision N". The owner approved part B on 2026-11-19: "owner decision N" is in the rules below. `documentation/design/instance-values/comparison.md`, recommendations 2 and 3.
3. Part A: ADR 0055 (all of it), `app/Support/InstanceValues.php`, `config/legal.php`, `tests/Unit/InstanceNeutralNamesTest.php`, `tests/Pest.php` (`configuredInstance()`), `SELF_HOSTING.md` ("Legal pages" with the anchor `legal-pages`, "Instance values", step 8, "Docker").
4. Code: `app/Support/LegalPages.php`, `app/Http/Controllers/LegalPageController.php`, `routes/web.php` (the legal routes), `app/Support/SecurityTxt.php`, `app/Mail/CommunityInvitation.php` and its view, `app/Actions/Communities/CreateCommunity.php` (the DPA lines), `app/Http/Middleware/HandleInertiaRequests.php`.
5. Front end: `libs/common/src/lib/footer-links.ts`, `constants.ts` (the `*_URL` route names), `types/jetstream/jetstream-inertia.intf.ts`; `apps/knowii/src/Components/Footer.tsx`, `Pages/Auth/Register.tsx`, `Components/Auth/OpenIdConnectButton.tsx`, `Components/NewsletterForm.tsx`, `Components/Communities/CreateCommunityDialog.tsx`, and their specs.
6. Tests: `tests/TestCase.php`, `tests/Feature/LegalPagesTest.php`, `CommunityMemberManagementTest.php` (the two tests of #1345), `CreateCommunityTest.php`, `SecurityTxtTest.php`, `tests/Unit/MixedTypeUsageTest.php` (the line of `LegalPages.php`).
7. Deploy: `Dockerfile` (the folders it copies, `VOLUME`), `.dockerignore`, `compose.production.yaml` (the `storage` volume), `.github/workflows/deploy-acceptance.yml`, `storage/app/.gitignore`, `.prettierignore`, `.github/CODEOWNERS`.
8. The six files of `resources/markdown/`, with their placeholders.

## Rules (decided, do not change them)

### The source of the texts

1. Slugs and files do not change: `privacy-policy` is `policy.md`, `terms-of-service` is `terms.md`, `dpa` is `dpa.md`, `imprint` is `imprint.md`, `content-policy` is `content-policy.md`, `security` is `security.md`. A seventh file, `subprocessors.json`, holds the sub-processors.
2. `config/legal.php` gets `path`, with the value `storage_path('app/legal')` and no environment name. `LegalPages` reads only `config('legal.path')`: the fallback to `resource_path('markdown')` goes (issue decision 2).
3. `LegalPages` gets `exists(string $page): bool` and `available(): list<string>` (the slugs with a file, in the order of `PAGES`). `updated()` returns null when the file is missing or when `updated` is not `YYYY-MM-DD`.
4. The six routes stay registered: the route cache and the route names of the front end need them. `LegalPageController` answers 404 when the file is missing. It checks at each request, so a file that is copied later is served without a restart.
5. `LegalPages::subprocessors()` reads `subprocessors.json` of the same folder: a JSON list of objects with the keys of today (`name`, `country`, `purpose`, `safeguard`, `scope`, and `feature` when an optional feature uses it). A missing file gives an empty list. A file that is not a JSON list gives an empty list and one `Log::warning`. An entry that is not an object is left out. `config/legal.php` loses `subprocessors` (owner decision 2). The table header, the sentence of an optional feature and the scope filter keep their code. No new `mixed`.
6. Three new placeholders: `{{operator}}` (`InstanceValues::operator()`), `{{contact}}` (`InstanceValues::contactEmail()`) and `{{site}}` (the host of `config('app.url')`). The others stay. An unknown placeholder stays visible.

### The move (owner decisions 1, 2, 4 and 6)

7. `git mv` the six files from `resources/markdown/` to `deploy/knowii.net/legal/`, with the same names. Not one byte of them changes, the places that say `knowii.net` included. `resources/markdown/` no longer exists.
8. New `deploy/knowii.net/legal/subprocessors.json`: the five entries of `config/legal.php`, in their order, each text copied character for character (real characters, no `\u` escape). New `deploy/knowii.net/README.md`: what the folder is, the commands of rule 21, and the note about the transfer safeguard that the PHP comment held (#1347).
9. The proof. Before your first change, with an empty `storage/app/legal/`, run in the container and keep the output: `php artisan tinker --execute='foreach (array_keys(App\Support\LegalPages::PAGES) as $p) { echo $p, " ", hash("sha256", App\Support\LegalPages::render($p)["html"]), PHP_EOL; }'`. Run it again after the last step of "How to work": the six hashes must be the same. `git diff -M100% --stat origin/main` must show six renames with no changed line, also after the formatters. If a formatter changes a moved file, undo that change and report it.
10. `SECURITY.md`, line 3: only the path becomes `deploy/knowii.net/legal/security.md`. No other word.

### The templates (owner decision 3)

11. Seven new files in `resources/legal-templates/`: the six names of rule 1 and `subprocessors.example.json` (two entries with invented names and `example.com`-style data). Make each text from the hosted text, and keep every sentence that these rules do not name:
    1. Front matter: `title`, `draft: true`, no `updated` line.
    2. The name of the operator becomes `{{operator}}`, its contact address `{{contact}}` (also in a `mailto:` link), the site `{{site}}`.
    3. What only the operator knows becomes a mark `[TODO: what to write]`: the street address, the company and tax numbers, the contact person, the country, the law and the courts, the supervisory authority, the languages of a report, the price.
    4. `policy.md`: the sentences about the statistics and the bot check get the mark "keep only if you use it". `imprint.md`: only the headings and "Source code" stay; each other body is a mark. `security.md`: what the code does stays; what one server set-up does (country, host, network, backups) becomes marks.
    5. The platform placeholders (`{{retention.*}}`, `{{subprocessors}}`, `{{cookies.session}}`, `{{sign_in_provider}}`) stay.
12. Never served: no file of `app/`, `config/` or `routes/` names `legal-templates`.

### A missing text (owner decision 5)

13. `HandleInertiaRequests` shares `legalPages` lazily: `LegalPages::available()`. `InertiaSharedProps` gets `legalPages?: LegalPageSlug[]`, and `constants.ts` gets `LEGAL_PAGE_SLUGS`, aligned with `LegalPages::PAGES` (a WARNING comment on both sides). Without the prop, no text exists.
14. `buildFooterLinkGroups(instance, legalPages)`: the "Legal" column holds only the pages that exist, and is left out when none exists. The compact footer follows.
15. `Register.tsx`, the box of the terms. Both texts: the sentence of today. One text: "I am 16 or older and I agree to the" and that one link. None: "I am 16 or older". The box and the server validation of `terms` do not change.
16. `OpenIdConnectButton.tsx`. Both: the sentence of today. One: "... you are 16 or older and you agree to the" and that one link, then a full stop. None: "If you have no account yet, this creates one: you are 16 or older."
17. `CreateCommunityDialog.tsx` hides its two lines about the agreement without `dpa`. `CreateCommunity` sets `dpa_accepted_at` and `dpa_version` only when the community is shared and `LegalPages::exists('dpa')`. Else both are null, and the community is created.
18. `NewsletterForm.tsx`: "See the privacy policy." shows only with `privacy-policy`.
19. The invitation mail: the last sentence, "Your rights are in the privacy policy: :link.", becomes its own `__()` text in the same paragraph, shown only with `privacy-policy`. With the text, the mail reads as today.
20. `SecurityTxt`: the `Policy:` line is `SECURITY_TXT_POLICY` when set, else the security page when `security` exists, else no line.

### The deploy (issue decision 3)

21. A fact: `main` already reads `storage/app/legal/<file>.md` before its own file (`LegalPages::path()`, proved by the test "a self-hosted instance replaces a page with its own file" of `tests/Feature/LegalPagesTest.php`). So an operator copies the texts BEFORE the update, and no page answers 404 for one second. The release of today ignores `subprocessors.json`. `SELF_HOSTING.md` and the README of rule 8 say, in this order:
    1. Host-based, the hosted instance, once, before the update: `git fetch`, then `mkdir -p storage/app/legal && git archive <new ref> deploy/knowii.net/legal | tar -x --strip-components=3 -C storage/app/legal`. Then, for ever, the first line of its deploy script after `cd`: `mkdir -p storage/app/legal && cp deploy/knowii.net/legal/*.md deploy/knowii.net/legal/subprocessors.json storage/app/legal/`.
    2. Host-based, another operator, before the update: each legal page that it wants is a file in `storage/app/legal/`, with its own `subprocessors.json`. To start from the templates: `git archive <new ref> resources/legal-templates | tar -x --strip-components=2 -C storage/app/legal`, then rename the example file, replace every mark, add `updated`, and set `draft: false`. Then the commands of step 8.
    3. Docker: `storage/app` is the volume `storage`, and the image holds no `deploy/` folder. Before `pull`, from the folder of the Compose file: `tar -c -C <folder of your texts> . | docker compose -f compose.production.yaml run --rm --no-deps -T --entrypoint sh app -c 'mkdir -p /app/storage/app/legal && tar -x -C /app/storage/app/legal'`. Then "Update". The Compose file, the image and the entrypoint need no change.
22. `deploy-acceptance.yml` (a skeleton: its connection step still fails on purpose): a checkout step (the pinned action of `release.yml`), and the command of rule 21.3 between `pull` and `up`, through `remote.sh`, which must pass its standard input (say it in the TODO). The folder comes from a new environment variable `LEGAL_TEXTS_DIR` (for example `deploy/knowii.net/legal`; empty: no copy). Refuse a value with a character outside `A-Za-z0-9._/-` or with `..`, like the tag check.

### The guard

23. `instanceNeutralFolders()` reads `resources` in place of `resources/views`. The exception list is empty. `instanceValueSkippedFiles()` holds exactly `tests/Feature/HostedLegalTextsTest.php`. The guard still never reads `deploy/`. Change its own tests and its header comment to say so.

## Scope (in the commit)

1. Rules 1 to 23. The docblock of `LegalPages` and the header of `config/legal.php` say where the texts and the sub-processors live.
2. Test data: neutral texts in `tests/Fixtures/legal/` (the six files and `subprocessors.json`), which use every placeholder. `Tests\TestCase::setUp()` sets `legal.path` to that folder: no test reads or writes `storage/app/legal/`. A test that needs a missing file uses its own temporary folder and deletes it.
3. Pest:
   1. `tests/Feature/LegalPagesTest.php`, on the fixtures: every page is public and rendered; 404 for each missing file, on an empty folder too; a file that appears is served at the next request; `available()` and the `legalPages` prop; front matter and the draft notice; each placeholder of rule 6 and the old ones; an unknown placeholder stays; rule 5 (the list, the scopes, `feature`, a missing file, a wrong file with its warning); `updated()` (rule 3); the "Remember me" test stays.
   2. New `tests/Feature/HostedLegalTextsTest.php`: sets `legal.path` to `base_path('deploy/knowii.net/legal')`, and keeps EVERY check of the hosted words of today, the sub-processor names and the privacy policy test of #1345 in `CommunityMemberManagementTest` included. It is skipped when that folder is missing (a fork can delete it).
   3. New `tests/Feature/LegalTemplatesTest.php`: seven files; `draft: true`; no `{{` left after `fillPlaceholders`; a `[TODO:` mark in each text; the example JSON is a valid list; rule 12; with an empty folder no page is served, although the templates exist.
   4. Rules 17, 19 and 20, with and without the file. `resources/markdown` does not exist.
4. Vitest: `footer-links.spec.ts`, `Footer.spec.tsx`, `Register.spec.tsx`, `OpenIdConnectButton.spec.tsx`, `NewsletterForm.spec.tsx`, `CreateCommunityDialog.spec.tsx`: each variant of rules 14 to 18, and nothing without the prop.
5. Documents:
   1. `SELF_HOSTING.md`: "Legal pages" written again (the folder, the seven files, the templates, the front matter, every placeholder, 404 and what hides); the row of the legal texts in "Instance values" (not required; when empty: rules 13 to 20 in one sentence); a note in step 8 and in "Docker, Update" with rule 21.
   2. `rules.md`: data rule 9 says that a new service goes, in the same pull request, into `deploy/knowii.net/legal/subprocessors.json` when the hosted instance uses it (the 14 days of notice: tell the owner before the merge), and that its row in `SELF_HOSTING.md` tells an operator to list it in its own file. Keep the other sentences. Data rule 10 and definition of done 9 get the new paths. `CONTRIBUTING.md` (security list, point 3) and `AGENTS.md` ("Approved texts") too. `CONTRIBUTING.md`, environment setup: a local instance has no legal page until the developer runs `mkdir -p storage/app/legal && cp resources/legal-templates/*.md storage/app/legal/ && cp resources/legal-templates/subprocessors.example.json storage/app/legal/subprocessors.json`.
   3. `.github/CODEOWNERS`: `/deploy/` and `/resources/legal-templates/` replace `/resources/markdown/`.
   4. ADR: add a part "The legal texts" to ADR 0055 (recommended: its text says that part B adds its details there; no new number). It records rules 1 to 23 and one more consequence: the update that needs the copy.
   5. `documentation/architecture/backend.md` ("Instance values") and `frontend.md` ("Global data").

## NOT in this commit

1. One word of a hosted text or of `SECURITY.md` beyond rule 10.
2. Texts in the database, an administration screen, a setup wizard, a command that installs the templates.
3. A check that refuses a text with a `[TODO:` mark: the draft notice shows.
4. Old ADRs and old design files that name `resources/markdown/`.
5. `compose.production.yaml`, the `Dockerfile`, the entrypoint, the screen atlas.

## How to work

1. After #1683 is merged: `git fetch origin`, then branch `feat/1653-legal-texts` from `origin/main`. ONE commit, no push, no pull request. Line numbers are those of 2026-11-20: find the text.
2. Docker on this Linux machine, from `~/wks/knowii`: `docker compose exec -T -u sail laravel.test <command> </dev/null`. Do not start, stop or restart containers. No `migrate:fresh` and no seeder on the development database.
3. Front-end commands on the host: `npx nx format --all`, `npm run lint:knowii`, `npm run lint:common`, `npm run tsc`, `npm run tsc:spec`, `npm run test:all`. PHP commands in the container: `composer format`, `composer lint`, `composer security:php`, and the whole Pest suite with `php artisan test --parallel`. Never run the whole Pest suite and the whole Vitest suite at the same time.
4. A piped test command hides a failure: use `bash -c "set -o pipefail; ..."`. Without `--parallel`, `php artisan test` takes ONE path.
5. A wrong method on a web route answers 404, not 405.
6. The guard reads `tests/` too: a test that checks that a name is absent builds that name from two parts. Only `HostedLegalTextsTest.php` may name the hosted operator. The code scanner refuses a hand-made regular expression escape: compare exact text.
7. Never name a third-party community product. Use `example.com` addresses.
8. Your last step: the development instance keeps its legal pages. Copy the six files and the JSON file to `storage/app/legal/` of this clone (`mkdir -p storage/app/legal && cp deploy/knowii.net/legal/*.md deploy/knowii.net/legal/subprocessors.json storage/app/legal/`). `storage/app/.gitignore` ignores that folder: check that `git status` shows nothing of it. Then run the hashes of rule 9, and say both in the report.
9. The commit: a conventional type (`feat`), one of the scopes all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build, a header of at most 100 characters that does not start with a capital letter. Suggested: `feat(all): legal texts come only from storage/app/legal, and the hosted texts move to deploy`. Footers, after a blank line: `Closes #1653` and `BREAKING CHANGE: the legal texts are read only from storage/app/legal. Copy your texts there before the update, else the legal pages answer 404 (SELF_HOSTING.md, "Legal pages").` No trailer line that names a tool or a co-author.
10. If a rule cannot be built as written, skip that rule, build the rest, and say why in the report. Do not invent product rules.

## Your final report (max 400 words)

1. The commit hash and `git show --stat`.
2. The proof of rule 9: the six hashes before and after, and the rename lines of `git diff -M100% --stat`.
3. The seven template paths, for the owner to read before the merge, with each `[TODO:` mark that you wrote.
4. Each choice where the brief left room.
5. Results of Pest, Vitest, PHPStan, ESLint, the type check, Pint and the security scan, with the exact failure text of anything that failed.
6. That the development clone has its seven files in `storage/app/legal/`, and anything of the scope not built, and why.
