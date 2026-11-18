# Brief: notifications, slice 4a: the email groundwork (knowii-oss/knowii#1477)

You build the ground that notification email will run on: the queue `mail` with a rate limit and its own worker, a monthly count of sent mail, and a budget check that alerts the operator at 80% and holds notification mail at 95%. No notification mail exists yet: slice 4c adds the first one. ONE commit on a local branch. You do not push, you do not open a pull request: a reviewer reads your diff afterwards. Paths are relative to `~/wks/knowii`.

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md`, `documentation/conventions/naming.md`.
2. The issue (`gh api repos/knowii-oss/knowii/issues/1477 --jq .body`) and its design: `documentation/design/notifications/email-groundwork.md`, sections 1, 2 and 8.
3. Slice 4b (#1478, NOT your work): sections 4, 5 and 7 of the same file.
4. The notification design: `documentation/design/notifications/design.md`, sections 6.5, 6.13, 6.14 (points 7, 8, 12), 8.5, 9.10, 9.11 and 12.
5. ADRs: 0042 (point 26, "Queues"), 0046 (points 6 and 11), 0048 (the format of a recent ADR), `documentation/adr/README.md`, `template.md`.
6. `documentation/architecture/backend.md` (lines 53 and 420 to 431), `documentation/architecture/overview.md` (lines 29 and 79), `SELF_HOSTING.md` (lines 65 to 104, 177 to 226, 323 to 336).
7. Code: `config/mail.php`, `config/queue.php`, `config/services.php` (`resend.key`), `docker/8.5/supervisord.conf`, `compose.production.yaml`, `docker/production/healthcheck-process.sh`, `.env.example` (lines 68 to 78), `.env.production.example` (lines 72 to 76), `app/Mail/*`, `app/Constants.php` (lines 241 to 245), `app/Providers/AppServiceProvider.php` (the `RateLimiter::for` block and the contract bindings), `app/Listeners/PruneNotificationsAfterAccessChange.php` (listeners are found by their `handle...` methods), `app/Console/Commands/HealthCheckCommand.php`, `routes/console.php`, `app/Actions/Auth/EntitlementsClient.php` (a client of a third party behind a contract).
8. Vendor code that the rules rely on: `Illuminate/Queue/Middleware/RateLimited.php`, `Illuminate/Queue/Worker.php` (`markJobAsFailedIfAlreadyExceedsMaxAttempts`, line 730), `Illuminate/Mail/SendQueuedMailable.php`, `Illuminate/Mail/Mailable.php` (`newQueuedJob`, line 289), `Illuminate/Mail/Transport/ResendTransport.php` (`doSend`), `vendor/resend/resend-php/src/Exceptions/ErrorException.php`, `src/Service/Usage.php`, `src/Usage.php`.
9. Tests: `tests/Feature/RetentionTest.php` (line 27, the schedule test), `HealthCheckCommandTest.php`, `MagicLinkTest.php`, `tests/Unit/MixedTypeUsageTest.php`, `phpunit.xml` (tests run with `QUEUE_CONNECTION=sync`, `CACHE_STORE=array`, `MAIL_MAILER=array`).

## Rules (decided, do not change them)

### Which mail goes where

1. Only notification mail goes on the queue `mail`. The three existing mailables (`MagicLink`, `CommunityInvitation`, `CommunityOwnershipTransferred`) and the address verification notification stay as they are, on `default`, with no limiter and no hold. They are level 0: never held (design 6.13 point 6). Do not touch the three actions that send them.
2. Add `Constants::QUEUE_MAIL = 'mail'` next to `QUEUE_BROADCASTS`, and the config key `mail.queue` (env `MAIL_QUEUE`, default the constant). Code reads `config('mail.queue')`, never the constant. `MAIL_QUEUE=default` is the documented downgrade for an instance with one worker: the limiter and the hold still apply.
3. New abstract `App\Mail\NotificationMail extends Mailable implements ShouldQueue` (`Queueable`, `SerializesModels`). Every notification mailable of slice 4c extends it. It fixes:
   1. the queue: `config('mail.queue')`, set so that every subclass gets it without code of its own;
   2. `middleware()`: `[new HoldNotificationMail(), new RateLimited('mail')]`, in this order;
   3. `retryUntil()`: now plus `Constants::MAIL_NOTIFICATION_RETRY_HOURS` (2). Laravel computes it when the job is queued and then ignores `--tries` (Worker line 734), so a release by the limiter never fails a job;
   4. `public $maxExceptions = 3` (only real exceptions count) and `backoff()`: `[60, 300, 1800]`.
4. No subclass ships in `app/` in this commit. Tests use a fixture class in `tests/Fixtures/Mail/` (namespace `Tests\Fixtures\Mail`, autoloaded by `autoload-dev`).

### The rate limit

5. Config key `mail.rate_limit_per_second` (env `MAIL_RATE_LIMIT_PER_SECOND`, default 5: half of the 10 requests a second of the provider, so the level 0 mails on `default` keep room). 0 means no limit. A value below 0 counts as 0.
6. Register `RateLimiter::for('mail', ...)` in `AppServiceProvider`, next to the other limiters. It returns `Limit::perSecond($n)->by('mail')`, or `Limit::none()` when `$n` is 0. One global key: two mail workers share the count. The closure gets the job, not a request: type it `object`, never `mixed`.
7. The vendored `RateLimited` middleware releases a limited job for `availableIn + 3` seconds. A release is not a failure.

### The hold and the quota refusal

8. New job middleware `App\Jobs\Middleware\HoldNotificationMail` (new folder). Signature `handle(object $job, Closure $next)`, no `mixed`.
   1. When notification mail is held (rule 18), it logs one info line and returns without calling `$next`: the job ends as done, nothing is sent, nothing goes to `failed_jobs`. The notification stays in the app (design 6.5 point 4).
   2. Else it calls `$next($job)` inside a try. The design says "detect the codes in the message, in `failed()`". The code does not allow that: `failed()` runs after the job failed, and the transport wraps the provider error. Follow the code: catch `Symfony\Component\Mailer\Exception\TransportExceptionInterface`, walk `getPrevious()`, and when a `Resend\Exceptions\ErrorException` has `getErrorType()` equal to `monthly_quota_exceeded` (hold until the first day of the next month, 00:00 UTC) or `daily_quota_exceeded` (hold until the next 00:00 UTC), set the hold, write `Log::warning`, and return. Any other exception is thrown again (normal backoff). Compare the exact type strings, no regular expression.

### The count of sent mail

9. Table `mail_usage_months` (migration `database/migrations/<date of the build>_100000_create_mail_usage_months_table.php`): `id`, `cuid` (unique, data rule 1, which the design did not list), `month` (date, the first day of a calendar month in UTC, unique), `sent_count` (unsigned big integer, default 0), `alert_80_sent_at`, `hold_notified_at`, `held_until` (all three `timestamp`, nullable; the code uses `timestamp`), timestamps. No personal data: no retention period, no policy line, no export line.
10. Contract `App\Contracts\Mail\RecordsSentEmails`, action `App\Actions\Mail\RecordSentEmails`, bound as a singleton. The design names it `RecordsMailSent`; `naming.md` wants a third-person verb and a plural noun. One SQL statement: `insert ... on conflict (month) do update set sent_count = mail_usage_months.sent_count + excluded.sent_count` (PostgreSQL only, rule 10). A fresh cuid goes in the insert values and is ignored on conflict.
11. Listener `App\Listeners\CountSentEmails` with `handleMessageSent(Illuminate\Mail\Events\MessageSent $event)`. It counts the envelope recipients (`$event->sent->getEnvelope()->getRecipients()`: to, cc and bcc), whatever the mailer, the queue or the kind. A refused send fires no event, so it counts nothing. The listener never throws: it catches, logs and returns, because an exception after a send would retry the job and send twice.

### The budget check

12. Config keys in `config/mail.php`, under `budget`: `monthly_quota` (env `MAIL_MONTHLY_QUOTA`, empty or 0 means none), `hold_percent` (env `MAIL_NOTIFICATION_HOLD_PERCENT`, default 95, kept between 1 and 100; 100 means no reserve), `alert_to` (env `MAIL_ALERT_TO`, addresses separated by commas, parsed like `$list` in `config/communities.php`). The alert threshold is `Constants::MAIL_BUDGET_ALERT_PERCENT = 80` (design 6.14 point 12). The alert address is configuration, never in the code (owner decision 2): anyone can host the platform.
13. Contract `App\Contracts\Mail\ReadsMailUsage`, class `App\Actions\Mail\ResendMailUsage` (like `EntitlementsClient`). It calls `Resend::client(config('services.resend.key'))->usage->get()` and returns a small typed value: monthly `used`, `limit` (nullable), `resets_at`, and the same for daily. It narrows the decoded arrays without `mixed`. It is called only when `config('mail.default') === 'resend'` and the key is not empty. Any failure returns null and logs one warning. Tests bind a fake: never call the network.
14. Contract `App\Contracts\Mail\ChecksMailBudgets`, action `App\Actions\Mail\CheckMailBudget`, with `check()`, `isHeld(): bool` and `holdUntil(CarbonImmutable $until, string $reason)`. All state lives on the row of the current UTC month.
15. Numbers, in this order. Quota: `MAIL_MONTHLY_QUOTA` when set; else the monthly `limit` of the provider; else none, and the check does nothing more (an instance with `log` or `smtp`). Used: the monthly `used` of the provider when the read worked (it sees the other senders of the same provider team); else `sent_count`. Owner decision 1: the quota is a configuration value.
16. At 80% or more, once a month (`alert_80_sent_at`): one `Log::warning` with the numbers, and the alert mail (rule 19) to each address of `alert_to`. With `alert_to` empty, the log line is the alert.
17. At the hold percent or more: `held_until` is the provider's monthly `resets_at`, else the first day of the next month 00:00 UTC. Once a month (`hold_notified_at`): a second warning and alert mail. When the provider gives a daily `limit` and the daily `used` reaches the hold percent of it: `held_until` is at least the daily `resets_at` (else the next 00:00 UTC), with a log line and no mail (a mail would use the daily quota). When both numbers are below the hold percent, the check clears `held_until` (the operator raised the quota). So a hold set by a refusal (rule 8.2) can end at the next check when the numbers disagree with the provider; the next refusal sets it again. That is accepted.
18. `isHeld()` is true while `held_until` of the current month row is in the future. A new month has a new row: the hold lifts by itself. The design keeps the hold in the cache; keep it on the row instead, so the check, the workers and the middleware read one place.
19. New mailable `App\Mail\MailBudgetAlert` (level 0, plain `Mailable`, on `default`, NOT a `NotificationMail`) with `resources/views/emails/mail-budget-alert.blade.php`. Content: the month, used, quota, percent, where the numbers come from (the provider or the count of this instance), and what happens next. Use the draft of the design, with real numbers: "This month the platform sent 2 400 of 3 000 emails (80%). Notification emails stop at 95%. Sign-in links and other account emails keep going." The hold variant says that notification emails are paused until <date> and that account emails keep going. Wrap every text in `__()`. The owner approved these words on 2026-11-17 (the 80% text as quoted, and a hold variant that says that notification emails are paused until the date and that account emails keep going). The mail goes only to the addresses of `alert_to`, never to a member.
20. Command `mail:check-budget` (`App\Console\Commands\CheckMailBudgetCommand`, attributes `#[Signature]` and `#[Description]` like `PruneExpiredDataCommand`). It calls the contract and prints one line: used, quota, percent, source, held or not. In `routes/console.php`: `Schedule::command('mail:check-budget')->hourly()->withoutOverlapping()->onOneServer();` with a comment line.

### The second worker

21. Each file that runs a worker gets a second one that reads only `mail`. It copies the flags of the first worker of the same file and changes only `--queue`. The first worker keeps `--queue=broadcasts,notifications,default` and never reads `mail`. Reason: `smtp` has `timeout => null`, and a slow send must never hold up the live updates.
1. `docker/8.5/supervisord.conf`: `[program:queue_worker_mail]`, `command=php /var/www/html/artisan queue:work --queue=mail`, the same lines as `queue_worker` (autostart, autorestart, `startretries=100`, user, logs), with a comment that names design 6.14 and the new ADR.
1. `compose.production.yaml`: service `queue-mail`, `<<: *knowii`, `command: ['php', 'artisan', 'queue:work', '--queue=mail', '--sleep=3', '--tries=3']`, the same `stop_grace_period`, `depends_on` and health check as `queue`. The design fears that `knowii-healthcheck-process queue:work` calls a dead mail worker healthy. The code shows it cannot: each service is its own container and the script reads the `/proc` of its own container. Keep the same check.
1. `SELF_HOSTING.md`: a Supervisor program `knowii-queue-mail` (`--queue=mail --sleep=3 --tries=3`, `stopwaitsecs=60`); `CONTRIBUTING.md` line 119: a second `queue:listen --queue=mail` command.
1. `app:health` gets a check `mail-queue` (add it to `CHECKS` and to the `--only` text). With the `database` queue driver it fails when a job of the queue `config('mail.queue')` has `reserved_at` null and `available_at` older than `Constants::MAIL_QUEUE_STALE_MINUTES` (10): "N job(s) of the queue mail wait for more than 10 minutes: is the mail worker running?". With another driver it passes.

### API parity

23. Slice 4a has nothing that a member can read or change. The operator reads the numbers with `mail:check-budget`, the log and the alert mail, and changes them only through configuration. So: no route, no ability, no Bruno request, no OpenAPI change. Say it in the ADR.

### The quota when a provider team is shared

24. The owner said on 2026-11-17: each person who hosts the platform chooses whether other products send with the same provider team. So nothing in the code assumes it. `SELF_HOSTING.md` and the comment of `MAIL_MONTHLY_QUOTA` in the two example files say: "If other products send with the same account of your mail provider, set this value lower than the limit of your plan." The provider read stays an option (rule 15): `MAIL_MONTHLY_QUOTA` is the main way and works with any provider.

## Scope (in the commit)

1. The migration of rule 9; config (`config/mail.php`: `queue`, `rate_limit_per_second`, `budget`); constants `QUEUE_MAIL`, `MAIL_NOTIFICATION_RETRY_HOURS`, `MAIL_BUDGET_ALERT_PERCENT`, `MAIL_QUEUE_STALE_MINUTES` (server only: not mirrored in `constants.ts`, like the other queue constants).
2. `NotificationMail`, `HoldNotificationMail`, the limiter, `CountSentEmails`, the three contracts with their actions and bindings, `MailBudgetAlert` and its view, `CheckMailBudgetCommand`, the schedule line, the `mail-queue` health check.
3. Workers and environment: `docker/8.5/supervisord.conf`, `compose.production.yaml` (its header comment says "five services": write the names, not a count), `.env.example` and `.env.production.example` (after `RESEND_KEY`, each with a comment: `MAIL_QUEUE=mail`, `MAIL_RATE_LIMIT_PER_SECOND=5`, `MAIL_MONTHLY_QUOTA=`, `MAIL_NOTIFICATION_HOLD_PERCENT=95`, `MAIL_ALERT_TO=`).
4. Tests (Pest, `tests/Feature/MailQueueTest.php` and `tests/Feature/MailBudgetTest.php`, plus the health test file). Tests that need a release or a real queue switch to the `database` connection and run `queue:work --once --queue=mail`, or call the middleware with a fake job. Use `Carbon::setTestNow()` and never `Mail::fake()` where the counter must run.
   1. Base class: queue `mail`, and `default` with `MAIL_QUEUE=default`; the two middlewares in order; `retryUntil` about 2 hours ahead; `maxExceptions` 3; `backoff` `[60, 300, 1800]`. Negative: the three existing mailables have no limiter, no hold, and stay on `default`.
   2. Limiter: at 2 a second, the third job of the same second is released (still in `jobs`, `available_at` later, not in `failed_jobs`) and is sent later; at 0 nothing is released; a job released five times under `--tries=3` is then sent once and is not failed.
   3. Hold: a held job sends nothing and is not failed; an unheld one sends; a level 0 mail sends while held. Refusal: `monthly_quota_exceeded` and `daily_quota_exceeded` set the right `held_until` and end the job; `rate_limit_exceeded` and a plain transport error are thrown again and set no hold.
   4. Counter: to plus cc plus bcc counts 3; two calls give the sum; a send that throws counts nothing; a new month gets a new row; a failing recorder never breaks the send.
   5. Check: no quota means no alert, no hold, no reader call; 79% nothing; 80% one warning and one mail per address on `default`, and a second run sends none; empty `alert_to` gives the log line and no mail; an invalid address is skipped; 95% sets the hold and one alert; `hold_percent` 100; the next month lifts the hold; the quota raised clears it; the provider `used` wins over `sent_count`; `MAIL_MONTHLY_QUOTA` wins over the provider limit; a failing reader falls back to `sent_count`; the daily hold; with `log` and `array` mailers the reader is never called.
   6. Schedule: `mail:check-budget` runs hourly (expression `0 * * * *`), the pattern of `RetentionTest.php` line 27.
   7. Guard: `docker/8.5/supervisord.conf`, `compose.production.yaml`, `SELF_HOSTING.md` and `CONTRIBUTING.md` each hold `--queue=broadcasts,notifications,default` and `--queue=mail`, and no line holds both `broadcasts` and `mail`.
   8. Health: `mail-queue` passes empty, fails with an old job, passes with a fresh job and with a released job (future `available_at`), passes on the `sync` driver. Set `queue.default` to `database` in these tests.
   9. The alert renders its numbers in HTML and in plain text (design 9.11 point 5; there is no screen, so nothing else of 9.11 applies).
5. Documents:
   1. A new ADR from `template.md`, Status Accepted, the date of the build, Issue #1477, "Pull request: not yet opened". Number: before the commit, list `documentation/adr` on `origin/main` and on every local branch (`git for-each-ref refs/heads`) and take the next free number. Title: "Email delivery: the mail queue, the budget, bounces and the one-click stop" (slice 4b adds its section later). It records rules 1 to 23 and the alternatives (all mail on `mail`; one worker for every queue; no hold). It extends ADR 0042 point 26 and changes ADR 0046 point 6: add "amended by [the new number](...)" to their status lines, like ADR 0012. Add it to the index of `documentation/adr/README.md`.
   2. `backend.md`: an "Email delivery" part and line 428; `overview.md` lines 29 and 79.
   3. `SELF_HOSTING.md`: the five values in the table of step 4; the worker paragraph (line 182) and "Knowii needs four processes" (line 179); the Supervisor example; the scheduled tasks list (item 5, `mail:check-budget`); the Docker section (lines 325 and 335: names, not counts); a paragraph "Mail volume": the provider limits (10 requests a second; the free plan allows 3 000 mails a month and 100 a day), the quota is per provider team, set `MAIL_MONTHLY_QUOTA` lower when another product sends with the same team, and the API key needs the right to read the usage, else the local count is used. Add an update note: from this release, run the second worker.

## NOT in this commit

1. Slice 4b (#1478): the webhook, `mail_deliveries` (the delivery log), the claim before a send (at most once), `X-Knowii-Delivery`, `email_suppressions`, the stop route, `List-Unsubscribe`, the route list test, the API of the suppression.
2. Slice 4c and later: any notification mail, the re-read of the state before a send, the ladder, the weekly budget of 7 (design 6.5), quiet hours, the settings.
3. Any policy words, `config/legal.php` (the Resend purpose changes in 4c), `config/retention.php`, the member export.
4. A queue of its own for the sign-in link (risk 1 of #1478); a change to the three existing mailables or to the mail layout.

## How to work

1. Branch `feat/1477-email-groundwork` from `origin/main`. The line numbers of this brief are those of 2026-11-17: find the text, do not trust the number.
2. Docker on this Linux machine, from `~/wks/knowii`: `echo "docker compose exec -T -u sail laravel.test <command> </dev/null" | newgrp docker`. Do not start, stop or restart containers. Do not run `migrate:fresh` or a seeder on the development database.
3. A piped test command hides a failure: use `bash -c "set -o pipefail; ..."`. `php artisan test` takes ONE path: no path for the whole suite, or one run per path. A `--filter` with `|` in parallel mode prints no summary.
4. An endpoint that answers with a plain list uses `self::successList()` (none in this slice). Validation errors are 400, business errors are 422.
5. Rules that fail the build: no new use of `mixed` in `app/` (docblocks included, `MixedTypeUsageTest`), PHPStan at its highest level, Pint, `OpenApiDocumentTest`, `ApiTokenAbilitiesTest`, ESLint, the TypeScript build.
6. CodeQL refuses a hand-made regular expression escape in a test: compare exact text. Helper function names in Pest files are global: use unique names.
7. Never name a third-party community product. Never write a real personal email address in code, tests or documents: use `example.com` addresses (`ops@example.com`).
8. Before the commit, after the last code change: `composer format` (Pint), `composer lint`, and the whole Pest suite (`php artisan test`).
9. The commit hook allows only the scopes all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build. The subject does not start with a capital letter. A blank line after the subject and before the footer. Suggested subject: `feat(all): the mail queue, its rate limit, a second worker and the budget alert`. Footer: `Closes #1477`. No trailer line that names a tool or a co-author.
10. If a rule cannot be built as written, skip that rule, build the rest, and say why in the report. Do not invent product rules.

## Your final report (max 400 words)

1. The commit hash and `git show --stat`.
2. Each choice where the brief left room (the ADR number you took included).
3. Results of Pest, PHPStan and Pint, with the exact failure text of anything that failed.
4. Anything of the scope not built, and why.
