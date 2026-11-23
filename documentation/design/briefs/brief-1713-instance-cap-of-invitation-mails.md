# Brief: a daily cap of invitation mails for the whole instance (knowii-oss/knowii#1713)

State: DECIDED on 2026-11-23. Decided by the owner on 2026-11-22: add the cap before the first deployment, "with the limits per community of #1454". The question below (the default) is decided by the main session as recommended: 1000 (the owner can overrule it; a later change is one value). Nothing is built.

## The goal

An account and a community each have a daily cap of invitation mails, but the instance has none, so many communities together can send without a limit. This slice adds a third cap: the invitation mails of the whole instance in the last 24 hours. It reads the same table and the same kind of setting as the two caps of today, and it refuses with the time from when it works again.

## What "with the limits per community of #1454" means in the code

1. #1454 is open and not built: no limit per community is stored in the database, and no screen shows one. The limit of a community for invitation mails is today a value of the configuration (`MAIL_INVITATIONS_PER_COMMUNITY_PER_DAY`, fact 2).
2. So the instance cap is one more value of the same group `mail.invitations`, next to the caps of a community and of an account: the same reader (`MailSettings`), the same rules for an empty or a wrong value, the same count (`invitation_mails`), the same refusal (`BusinessException`, 422) and the same documents.
3. #1454 later shows these values on its usage page and lets the platform admin change them. That is not in this commit.

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md`.
2. The issues: #1713, #1390 (with its comment of 2026-11-15, point 5), #1454.
3. ADR 0061, decisions 13 and 18, consequence 4 and alternative 7. ADR 0054, point 1.
4. `documentation/architecture/backend.md`, the section on the invitations from a file, point 5.

## What exists today (facts, with the evidence)

Line numbers are those of `origin/main` on 2026-11-23 (commit `21542639`).

1. The two caps live in one class, `App\Support\InvitationMailCaps` (`app/Support/InvitationMailCaps.php:22-144`). `assertRoom()` reads both caps from one list (lines 58-61), counts the rows of `invitation_mails` of the last 24 hours for each (line 65), and keeps the words of the cap that frees last, with the fewest places that can go now (lines 62-77). A single invitation gets "Invite this person from <time>." (line 84); a set gets "<n> more can go now." and "These <n> invitations can go from <time>." (lines 86-87). The time is UTC, rounded up to the minute (lines 139-143). `record()` writes one row per mail (lines 96-105).
2. The settings. `MailSettings::invitationsPerCommunityPerDay()` and `invitationsPerAccountPerDay()` read `mail.invitations.per_community_per_day` and `per_account_per_day`: a whole number of at least 1, else 1000, also for an empty or a wrong value (`app/Support/MailSettings.php:17,57-77`). The keys are in `config/mail.php:152-160`, `.env.example:94-96` and `SELF_HOSTING.md:82-83`, and the "Mail volume" list says it again (`SELF_HOSTING.md:121`).
3. The table. `invitation_mails` holds the community, the account and the time, both keys `nullOnDelete`, with an index on `created_at` alone (`database/migrations/2026_11_22_220000_create_invitation_mails_table.php:22-31`). So a count of every row of the last 24 hours needs no new index. A row whose community and account are both deleted keeps both keys null, and still is a mail that went out.
4. The locks. `lock()` takes two advisory locks of the transaction, the community then the account, each with its own namespace, `LOCK_COMMUNITY = 1390_01` and `LOCK_ACCOUNT = 1390_02` (`InvitationMailCaps.php:29-43`). `assertRoom()` calls `lock()` first (line 55).
5. Every path that sends an invitation mail goes through the caps:
   1. A single invitation (API `POST .../invitations`, `routes/api.php:347`): `InviteCommunityMember::invite()` takes the locks first (`app/Actions/CommunityMembers/InviteCommunityMember.php:82`), refuses a hold of the budget (lines 90-93), then calls `assertRoom(..., 1, single: true)` (line 94) and `record(..., 1)` (line 103). A replace of an invitation is a new mail and counts.
   2. The confirm of an import: `ConfirmInvitationImport` (`app/Actions/InvitationImports/ConfirmInvitationImport.php:75,94,120`), through `InvitationImportMailChecks::assertCanSend()`.
   3. The send again, both `not_sent` and `not_joined`: `ResendInvitationImport` (`app/Actions/InvitationImports/ResendInvitationImport.php:72,100,114`), through the same check.
   4. `InvitationImportMailChecks::assertCanSend()` checks the hold, then the month, then the caps (`app/Support/InvitationImports/InvitationImportMailChecks.php:33-46`).
   5. No other path: the mail class `App\Mail\CommunityInvitation` is sent only by `InviteCommunityMember.php:108` and by the job of an import row (`app/Actions/InvitationImports/SendInvitationImportMails.php:74`), whose row was counted at its confirm or its send again. A retry of that job is the same mail and does not count again. An invite link sends no mail. The import of another tool (#1396) goes through `CreateInvitationImport` and the same confirm (ADR 0061, consequence 4).
6. The rule for numbers of the instance: the checks of an import "never show a number of the instance" (`InvitationImportMailChecks.php:17-18`, ADR 0061 decision 13). The month check says "This instance cannot send so many this month. Send fewer rows." with no number (`InvitationImportMailChecks.php:42`).
7. The prune step deletes the rows older than 24 hours (`app/Actions/Retention/PruneExpiredData.php:65-66`). The account export lists the rows of the account (`app/Actions/Users/ExportUser.php:663`).
8. The tests of the caps are in `tests/Feature/InvitationImportTest.php`: every path counts (lines 505-546), `not_joined` (548-563), the account cap (565-586), the words when both caps refuse (604-627), the lock order (648-670), the reading of the settings (688-699). The helper `importMailsSent()` writes rows at a time, with or without a community and an account (lines 151-154).
9. The API README says the caps for a single invitation (`documentation/api/README.md:184`) and for the confirm and the send again (lines 198-199). `backend.md:462` describes the caps and the locks.

## Rules

Each rule is a test. Put the tests in `tests/Feature/InvitationImportTest.php`: its helpers are plain functions of that file.

1. `MailSettings::invitationsPerInstancePerDay()` reads `mail.invitations.per_instance_per_day` (`MAIL_INVITATIONS_PER_INSTANCE_PER_DAY`): a whole number of at least 1, else 1000, also for an empty value, 0 and a word. Extend the data set of the test at line 688.
2. The instance count is every row of `invitation_mails` of the last 24 hours, whatever its community and its account, also a row with both keys null. Test: with the instance cap at 3, three rows of `importMailsSent(null, null, 3, ...)` refuse a single invitation.
3. Every path counts in the instance cap and is refused past it: a single invitation, a replace of an invitation, a confirm, a send again `not_sent`, a send again `not_joined`. Test: the instance cap at 4, the community and account caps at 100, mails from two communities and two accounts; the fifth mail of each kind is refused.
4. A refusal changes nothing: no invitation, no row of `invitation_mails`, no mail queued, no change of an import or of its rows (as the test at lines 533-537).
5. The words of the instance cap (the time as in fact 1). They hold no number of the instance: not the cap, and not the places left.
   1. A single invitation: "This instance sends no more invitation emails now. Invite this person from 2026-11-19 10:00 UTC."
   2. A set: "This instance cannot send so many invitation emails now. These 3 invitations can go from 2026-11-19 10:00 UTC." For a set of one: "This invitation can go from ...", as today.
   3. A set larger than the instance cap: "This instance cannot send so many invitation emails in 24 hours. Send fewer rows."
6. When the instance cap refuses, its words win, with the latest time of all the caps that refuse. When one of the refusing caps can never take the set in one go, the words are those of rule 5.3. Test: the community refuses until 10:00 and the instance until 11:00, then the other way round; both times the instance words, with 11:00.
7. When the instance cap does not refuse, the words are those of today. The tests at lines 505-627 pass with no change. (Its places left are then more than the set, so the number "can go now" never comes from the instance.)
8. `lock()` takes a third advisory lock, the instance, after the community and the account: a new namespace `LOCK_INSTANCE = 1713_01`, with the key 0. Extend the test at line 648 to three locks in this order. Reason: two requests of two communities must never both pass on the same instance count.
9. The order of the refusals does not change: for a single invitation the hold first, then the caps; for an import the hold, the month, then the caps (fact 5).

## Scope (in the commit)

1. `app/Support/MailSettings.php`: `invitationsPerInstancePerDay()`.
2. `app/Support/InvitationMailCaps.php`: the third cap in `assertRoom()`, its words, `LOCK_INSTANCE` in `lock()`, the class comment.
3. `config/mail.php`: `per_instance_per_day` in the group `invitations`, with the comment.
4. `.env.example`: `MAIL_INVITATIONS_PER_INSTANCE_PER_DAY=1000`, and the comment line above the caps says "an account, a community and the whole instance".
5. `SELF_HOSTING.md`: a row of the table after line 83, and point 6 of "Mail volume" (line 121). Add one sentence: on a plan of the mail provider with a daily quota, set the instance cap well below it, so that the sign-in links keep room.
6. ADR 0061: decision 18 (the third cap, its count, its lock, its words without a number of the instance) and consequence 4 (the instance cap is in, #1713; the limits that the operator sets per community stay #1454). Edit in place, with "(#1713)".
7. `documentation/api/README.md:184,198-199`: "an account, a community and the instance".
8. `documentation/architecture/backend.md:462`: the third cap and the third lock.
9. The comments that list the caps: `InviteCommunityMember.php:40-48`, `ConfirmInvitationImport.php:40,51`, `ResendInvitationImport.php:40-43`, `InvitationImportMailChecks.php:26`.
10. The tests of the rules, in `tests/Feature/InvitationImportTest.php`.

## NOT in this commit

1. Limits per community that the operator sets in the database, the usage page, the warning at 80 percent and the admin panel (#1454).
2. A migration, a new table or a counter: the count reads `invitation_mails` (ADR 0061, alternative 7).
3. A change to the privacy policy, the exports or the retention: the cap stores nothing new.
4. A change of the front end. The pages show the message of the server as it comes.
5. A change of the caps of a community and of an account, of their words or of their defaults.

## How to work

1. `git fetch origin`, then branch `feat/1713-instance-cap-of-invitation-mails` from `origin/main`. ONE commit, no push, no pull request. If the working tree holds changes of another person, stop and ask: no stash, no reset. Find the text when a line number moved.
2. Docker, from `~/wks/knowii`: `docker compose exec -T -u sail laravel.test <command> </dev/null`. Do not start, stop or restart a container. No `migrate:fresh` and no seeder on the development database.
3. Run `npm run build` on the host before the first Pest run.
4. Write the tests of rules 1 to 8 first, run them (`php artisan test tests/Feature/InvitationImportTest.php`), and see them fail. Then build.
5. In the container: `composer format`, `composer lint`. Then the whole Pest suite ONE time, after the last change: `bash -c "set -o pipefail; php artisan test --parallel 2>&1 | tail -n 40"`, with its summary written to a file outside the repository (for example `/tmp/knowii-1713-pest.txt`).
6. No front-end file changes. If one does: `npx nx format --all`, `npm run lint:common`, `npm run tsc`, `npm run tsc:spec`, and the full front-end run `npx nx test common --skip-nx-cache && npx nx test knowii --skip-nx-cache`. Never at the same time as the Pest suite.
7. Never name a third-party community product, in code, tests or documents. Use `example.com` addresses.
8. The commit: a conventional header of at most 100 characters, with a scope of `commitlint.config.js` (`all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build`), not starting with a capital letter. Suggested: `feat(api): a daily cap of invitation mails for the whole instance`. Footer, after a blank line: `Closes #1713`. No trailer that names a tool or a co-author.
9. If a rule cannot be built as written, skip it, build the rest, and say why in the report. Do not invent product rules.
10. If you find a weakness in the code while you work, do not write it in the commit, a test name, the ADR or an issue. Tell the reviewer in private.

## Questions for the owner

1. The default of the instance cap. Recommendation: 1000, the same as the two caps of today, and never "no limit" (a mistake must not remove a cap, fact 2). An instance with one community sees no change. An operator with many communities raises it; an operator on a plan with a daily quota lowers it (scope 5).

## Your final report (max 250 words)

1. The commit hash and `git show --stat`.
2. The tests of rules 1 to 8: their failure before the code and their pass after it.
3. The summary of the full Pest run (the file of step 5), and the results of `composer format` and `composer lint`.
4. The exact words of each refusal of the instance cap.
5. Each choice where the brief left room, and anything of the scope not built, with the reason.
