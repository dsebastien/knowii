# Brief: notifications slice 4b, bounces, complaints and the one-click stop (knowii-oss/knowii#1478)

State: DECIDED on 2026-11-23 (see "Decided by the owner"). Nothing is built.

## Decided by the owner on 2026-11-23

1. Every recommendation of "Questions for the owner" is the decision (questions 1 to 5).
2. The words: build with the 36 texts of "The words" as written. The owner approves them word for word with the pictures, before the merge. The policy lines and the sub-processor line merge only after that approval; a change of a word is one line.
3. There is no check by counsel before the first notification email, for this slice and for slice 4c (#1610 closed as "Won't do").

There is no check by counsel. The owner closed #1610 on 2026-11-23 with "Won't do". Do not wait for it, and do not write "counsel" in the code, the ADR or the documents. The legal words of this slice still need the approval of the owner, word for word, before the merge.

## The goal

The platform can send mail, but it does not know when a mail does not arrive, and a member cannot stop a mail in one click. Slice 4b adds four things, before the first notification email (slice 4c):

1. A public webhook route for the mail provider (Resend). It checks the signature, refuses a replay, and is rate limited. It reports bounces and complaints.
2. A suppression on the account after a hard bounce, a complaint, or 3 soft bounces in a row. A bounce of an invitation never suppresses an account.
3. A signed stop route without a sign-in, with a one-click POST (RFC 8058), and the headers `List-Unsubscribe` and `List-Unsubscribe-Post` on every notification email. Its page confirms in text and has an undo.
4. The delivery log, which links a report of the provider to the mail that it is about, and which makes a notification mail go out at most once (ADR 0054, consequence 4).

Nothing in this slice sends a notification email. The stop route and the headers are merged "dark": the first mail that carries them comes with slice 4c.

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md` (Architecture 1 to 6, Data 1, 2, 6, 7, 8, 9 and 10, the definition of done), `documentation/conventions/naming.md`.
2. The issues, with their comments: #1478 (the design ideas of 2026-11-16, the decisions and the addition "a suppressed address must never lock a member out", and the decisions of the owner of 2026-11-17), #1477, #1610 (closed), #1392 (the two comments of the final design, and the build plan after them), #841.
3. The design files in the repository: `documentation/design/notifications/email-groundwork.md` (sections 3, 4, 5, 6 and 7: the same text as the comments on #1478) and `documentation/design/notifications/design.md` (sections 5.6, 5.9 point 9, 6.2 point 5, 6.13, 6.14 points 8, 10 and 11, 8.3, 8.5, 9.10, 9.11 and 12).
4. ADRs: 0054 (this slice adds its section), 0064 (a signed route is a secret address), 0042 (points 24, 26 and 45), 0047 (the pattern of a public webhook), 0030 (retention), 0024 (account deletion), 0014 (one foreign key per kind), 0007 (CSP), 0023 (sign-in links), 0058 (a change of address), 0061 (invitations and their mail).
5. The provider: https://resend.com/docs/webhooks/verify-webhooks-requests, https://docs.svix.com/receiving/verifying-payloads/how-manual, https://github.com/standard-webhooks/standard-webhooks/blob/main/spec/standard-webhooks.md, https://resend.com/docs/webhooks/retries-and-replays, https://resend.com/docs/webhooks/emails/bounced, https://resend.com/docs/webhooks/emails/complained, https://resend.com/docs/webhooks/emails/suppressed, https://resend.com/docs/dashboard/emails/suppression-list, https://resend.com/docs/dashboard/emails/add-unsubscribe-to-transactional-emails, RFC 8058.

## What the design decides for 4b (do not change it)

1. The webhook is signed, checked and rate limited (design 6.13 point 1). It answers 404 when the instance has no webhook secret, 401 with an empty body for any invalid call, and 202 for a valid call, also for an event type that it ignores and for a person that it does not know (groundwork 3.2 points 2 and 5).
2. A hard bounce or a complaint stops all notification email of the account. Level 0 mail still goes (design 6.13 point 2). The suppression is a table `email_suppressions` tied to an address, not columns on `users` (owner decision 4). An address change lifts it (groundwork 3.3 point 3).
3. A soft bounce suppresses nothing. Three soft bounces in a row count as a hard bounce. "In a row" means no delivery in between: the webhook reads `email.delivered` to reset the count (design 6.13 point 3, owner decision 5).
4. A bounce of an invitation is stored on the invitation and never suppresses an account (design 6.13 point 4).
5. The stop link is a permanent signed URL, not a stored token (owner decision 7). A GET shows a page with one confirm button and changes nothing. A POST without a session stops (design 6.13 point 5).
6. "Stop emails like this" stops one group: replies and mentions, direct messages, important news, the weekly summary, or event emails (design 5.6 point 8). The header stops the same group as the footer link. Account mail has no such header (design 5.6 point 9). The page says what stopped and what still arrives (design 8.3 point 3), and has "Undo" (design 5.6 point 8, 9.10 point 7).
7. The page after the stop has "Pause all email for 2 weeks", "Pause all email for 1 month" and "Stop all email from <community>" only from slice 4c, with the preferences (groundwork 3.4 point 10). "Weekly summary only" is not promised in 4b (groundwork, risk 12).
8. A mail that a person asks for is always tried: the sign-in link, the password reset, the address check, and the confirmation of a new address. Before such a mail, the platform asks the provider to take the address off its list, at most 1 time an hour and 3 times in 24 hours per address. A `delivered` event then lifts a `hard_bounce`, `soft_bounces` or `provider_list` row. A `complaint` row is lifted only by the member (groundwork 7.2 and 7.3, owner decision 12). The sign-in page shows the same text in every case.
9. The member lifts a wrong suppression with "Try this address again", once an hour, with a best-effort removal at the provider (owner decision 9). No `mail:suppress` command in 4b (owner decision 10).
10. The webhook route and the stop routes have no token ability, and the ability test lists them by name (design 6.14 point 10).
11. Retention (design 8.5, owner decision 9 of the design): the delivery log 30 days, the suppression "while the address stays", no text of a mail is stored. Account deletion removes the delivery log of the member (design 6.14 point 11).
12. The words of the page, the banner, the footer and the policy lines go to the owner first (owner decision 11, design decision 9).

## What exists today (facts, with the evidence)

Line numbers are those of `origin/main` on 2026-11-23 (commit `c7d2a706`). Vendor code is read at the versions of `composer.lock`: `laravel/framework` v13.40.0 and `resend/resend-php` v1.18.0.

The mail layer of slice 4a (#1677, ADR 0054):

1. `App\Mail\NotificationMail` (`app/Mail/NotificationMail.php:34-107`) is the base of every notification mail. It sets the queue from `MailSettings::queue()` in `queue()` and `later()` (lines 53-73), the middleware `[new HoldNotificationMail, new RateLimited('mail')]` (lines 80-83), `retryUntil()` (88-94), `$maxExceptions = 3` and `backoff()` `[60, 300, 1800]`. No class of `app/` extends it. The one subclass is the fixture `tests/Fixtures/Mail/TestNotificationMail.php`.
2. `App\Jobs\Middleware\HoldNotificationMail` (`app/Jobs/Middleware/HoldNotificationMail.php:32-52`) ends a job as done while the budget holds mail, and reads a quota refusal of the provider.
3. `App\Listeners\CountSentEmails` (`app/Listeners/CountSentEmails.php:21-29`) counts the recipients of each `MessageSent`, and never throws. Listeners are found by their `handle<Event>` method (no `Event::listen` in the providers).
4. There is no delivery log, no suppression, no webhook of the provider and no stop route. A search for `mail_deliver`, `suppress`, `bounce`, `List-Unsubscribe`, `svix` and `webhook_secret` in `app`, `config`, `database`, `routes` and `tests` finds only the EmailOctopus code. ADR 0054, consequence 4, says that the delivery log of 4b makes a notification mail go out at most once.
5. The configuration. `config/services.php:75-77` has `resend.key` only. `config/mail.php:121-160` holds the values of 4a and the caps of the invitation mails. `.env.example:79-96` and `.env.production.example:108-121` list them. `SELF_HOSTING.md` has the values at lines 76-83, "Mail volume" at 115-122, the retention table at 147-164, the processes at 310-316 and the scheduled tasks at 361-364.

The mail provider and its transport:

6. The Laravel transport (`Illuminate/Mail/Transport/ResendTransport.php`, `doSend()`) sends every header except `from`, `to`, `cc`, `bcc`, `reply-to`, `sender`, `subject` and `content-type`. So `List-Unsubscribe`, `List-Unsubscribe-Post` and an own header pass. It sends no Resend tags. After the send it sets the message id to the Resend email id and adds the header `X-Resend-Email-ID` to the original message. The other transports (`smtp`, `log`, `array`) add no such header.
7. The SDK has `Resend\WebhookSignature::verify(string $payload, array $headers, string $secret, ?int $tolerance = 300)` (`src/WebhookSignature.php:9-50`). It reads the keys `svix-id`, `svix-timestamp` and `svix-signature` of the array, removes the prefix `whsec_` from the secret and decodes the rest with `base64_decode()` (lines 64-72), signs `"{id}.{timestamp}.{payload}"` with HMAC-SHA256 and encodes it in base64 (lines 52-59), accepts any item `v1,<signature>` of the header list separated by spaces with `hash_equals()` (lines 24-43), and refuses a timestamp more than the tolerance in the past or in the future (lines 77-95). It throws `WebhookSignatureVerificationException` on a failure.
8. The provider side (sources above). Resend signs its webhooks with Svix. The headers are `svix-id`, `svix-timestamp` (Unix seconds) and `svix-signature`. The raw body must be used: a body that is parsed and encoded again fails. The Standard Webhooks specification, which Svix follows, says that the id stays the same on every retry of a message, that the timestamp is the time of the attempt and is renewed on each retry, and that the id is the key for idempotency. A delivery is a success on a 2xx answer within 30 seconds (Svix). Resend retries at once, then after 5 s, 5 min, 30 min, 2 h, 5 h, 10 h and 10 h, then disables the endpoint and mails the team. The documentation lists no IP addresses of the sender. The documentation states no tolerance; the SDK default is 300 seconds.
9. The payload of `email.bounced`: `type`, `created_at`, and `data` with `email_id`, `to` (a list), `bounce.type` (`Permanent` or `Temporary` in the documentation), `bounce.subType`, `bounce.message`, `tags`. The payload holds no header of the mail. `email.complained` has the same `data` without `bounce`. `email.suppressed` has `data.suppressed` with `type` and `message`.
10. The SDK has `suppressions->remove(string $idOrEmail)` (`src/Service/Suppression.php:74`): one address per call.
11. Signed URLs use `app.key` and `app.previous_keys` (`config/app.php:129`, `APP_PREVIOUS_KEYS`; `Illuminate/Routing/RoutingServiceProvider.php:81`). So an old stop link stays valid after a change of `APP_KEY` when the old key stays in `APP_PREVIOUS_KEYS`.

The existing mails:

12. Mailables in `app/Mail`: `MagicLink` (holds the token, `ShouldBeEncrypted`), `CommunityInvitation`, `CommunityOwnershipTransferred`, `ConfirmNewEmailAddress` (holds a signed link, `ShouldBeEncrypted`), `EmailAddressChanged`, `MailBudgetAlert`, `NotificationMail`. Notifications: `App\Notifications\VerifyEmailAddress` (the address check, sent inside the request) and the password reset of the framework (`Illuminate\Auth\Notifications\ResetPassword`, not overridden, sent inside the request).
13. The send sites: `MagicLinks.php:52` (`Mail::to($user->email)->queue(...)`, the user is known), `TransferCommunityOwnership.php:63`, `ChangeEmailAddress.php:122` (to the previous address) and `:153` (to the new address), `CheckMailBudget.php:201` (to `MAIL_ALERT_TO`), `User.php:426-428` (the address check).
14. The invitation mail has two send paths: a single invitation, `InviteCommunityMember.php:108` (queued on `default`), and a row of an import, `SendInvitationImportMails.php:74` (`send()` inside the job of the row, on the mail queue with `HoldInvitationImportMail`). An import row sets `created_at` of the same invitation again before each send (lines 60-66), so "send again" reuses the invitation. A new invitation to the same address replaces the old one (`InviteCommunityMember.php:83,95,101`).
15. `community_invitations` has `id`, `cuid`, `community_id`, `email`, `role` and timestamps, unique `(community_id, email)` (`database/migrations/2024_08_29_081030_create_community_invitations_table.php`). `CommunityInvitationResource::toArray()` (`app/Http/Resources/CommunityInvitationResource.php:23-37`) holds no bounce state. The words of the invitation screens are in `apps/knowii/src/Components/Communities/invitation-words.ts`.
16. The sign-in link form gives the same answer for every address: `MagicLinks.php:36-41` and the status text "If an account has this address, we sent it a sign-in link. The link works once, for %d minutes." (`MagicLinkController.php:32`).

Public routes, secret addresses and abilities:

17. The pattern of a public webhook: `routes/web.php:186-199`, `POST` `OidcEntitlements::WEBHOOK_PATH`, `withoutMiddleware([EncryptCookies, AddQueuedCookiesToResponse, StartSession, ShareErrorsFromSession, PreventRequestForgery, HandleInertiaRequests])`, `throttle:oidc-entitlements`, name `oidc.entitlements.webhook`. The controller (`app/Http/Controllers/Webhooks/OidcEntitlementsWebhookController.php`) answers 404, 401 with an empty body, or 202. The limiter is in `AppServiceProvider.php:519-521` with `Constants::OIDC_ENTITLEMENT_EVENTS_PER_MINUTE = 120` (`app/Constants.php:315`). Its test is `tests/Feature/EntitlementsProviderTest.php:561-585`.
18. A signed web route: `community-invitations.accept` with `->middleware('signed')` (`routes/web.php:79`).
19. `App\Support\SecretAddresses` (ADR 0064, `app/Support/SecretAddresses.php`): a route with the `signed` middleware holds a secret (lines 79-102). The session keeps no such address, the statistics never see it, and `HandleInertiaRequests` stores no previous address for it. `PATHS` (lines 51-60) must hold a pattern of the path of each such route.
20. `tests/Feature/SecretAddressesTest.php`: `SA_ROUTES` (lines 55-64) lists every GET route that holds a secret with its states; the guard test (lines 586-619) fails for a new signed GET route that is not in `SA_ROUTES` and not covered by `PATHS`; a parameter name of a route must be in `SECRET_PARAMETERS` or `SA_PUBLIC_PARAMETERS` (lines 97-102, 572-584); no code checks a signature by hand (lines 621-635). ADR 0064 point 11: a queued mail that holds a link is encrypted in the queue, or builds the link when it is sent.
21. `tests/Feature/ApiTokenAbilitiesTest.php` has a test that every `api/v1/` route behind `auth:sanctum` or `auth.optional` names an ability (lines 26-42), and a test that lists the visitor routes of the API by name (lines 85-120). No test lists the public web routes.
22. The abilities `notifications:read` and `notifications:write` exist, not in the default set (`JetstreamServiceProvider.php:166-167`). `PUT user/notification-settings` uses `notifications:write` (`routes/api.php:109`).

Screens, data and documents:

23. The notification settings page is `/user/notifications`, `NotificationSettingsController::show()`, which renders `Profile/Notifications` with the communities of the member (`apps/knowii/src/Pages/Profile/Notifications.tsx`).
24. A guest page in the app layout exists: `Pages/InviteLinks/Show.tsx` uses `AppLayout` with `contentWidth="form"`, and its words are in `Components/InviteLinks/invite-link-words.ts`. In production, a 403 renders `Pages/Error` (`bootstrap/app.php:380-388`).
25. Retention. `config/retention.php` holds the periods. `App\Support\Retention::periods()` (`app/Support/Retention.php:155`) gives the words of the placeholders `{{retention.<key>}}` of the legal pages. `PruneExpiredData::prune()` (`app/Actions/Retention/PruneExpiredData.php:47-113`) has one step per kind. The test file is `tests/Feature/RetentionTest.php`; the design names `RetentionScheduleTest`, which does not exist.
26. The member export writes `account.json`, `memberships.json` and `activity.json` (`app/Actions/Users/ExportUser.php:103-128`), with a readme line per file (lines 1028-1039). `activity.json` already lists the invitation mails of the account (line 128).
27. The privacy policy: the template `resources/legal-templates/policy.md` and the text of the hosted instance `deploy/knowii.net/legal/policy.md` (one line lower: its header has an `updated` line). Section 2 "The data we collect" (template lines 18-30), section 3 "Why we use it" (34-40, the emails at line 39), section 4 "Who receives the data" (42-56), section 5 "How long we keep the data" (58-75, items 1 to 16). The policy never writes a period: it uses `{{retention.*}}`.
28. The sub-processor line of the provider: `deploy/knowii.net/legal/subprocessors.json:17-21`, purpose "Emails of the service: sign-in links, address verification, invitations".
29. The instance has no postal address value. `config/legal.php` holds `operator`, `contact_email` and `path` only.
30. The screen atlas: `tools/screen-atlas/` (`atlas.mjs`, `screens.json`, `atlas-seed.php`, `atlas-unseed.php`). A screen is a row of `screens.json` with a static `path`.

## Rules

Each rule is a test, in Pest unless it says Vitest. Numbers marked [first value] are values to test with the real instance; they are constants or configuration.

The webhook:

1. The route is `POST /webhooks/resend`, named `mail.webhook`, in `routes/web.php` right under the OIDC webhook, with the same `withoutMiddleware` list (fact 17) and `->middleware('throttle:mail-webhook')`. Controller `App\Http\Controllers\Webhooks\MailWebhookController`, action behind a contract (`ReceivesMailWebhooks`, `ReceiveMailWebhook`). Test as `EntitlementsProviderTest.php:561`: only POST, no `StartSession`, no `PreventRequestForgery`, no `auth*`, no `ability*`, no `verified`; a signed-in session does not change the answer.
2. The secret is `services.resend.webhook_secret` from `RESEND_WEBHOOK_SECRET`. The route answers 404 to every call, and never checks a signature, when the secret is empty, does not start with `whsec_`, or its rest is not strict base64 or decodes to an empty value. Such a secret writes one warning in the log, at most once a day. Test each case: 404, no row written.
3. `App\Support\MailWebhookSignature` is the only caller of `Resend\WebhookSignature::verify()`. It passes the raw body (`$request->getContent()`), the three headers as plain strings with the lower-case names `svix-id`, `svix-timestamp`, `svix-signature`, the secret, and the tolerance `Constants::MAIL_WEBHOOK_TOLERANCE_SECONDS = 300`. It catches every `Throwable` of the check and answers "not valid". Tests, each with an empty body in the answer: a valid call gives 202; a wrong secret, one changed byte of the body, the same JSON with other white space, each header missing in turn, a signature of another id, a header with only `v2,` items, a header item without a comma, an empty header, a timestamp that is not a number: each gives 401, never 5xx. A header with two items, one valid, gives 202. The test signs with a known `whsec_` secret, built in the test (never a real secret).
4. The replay window: a timestamp 301 seconds in the past or in the future gives 401; 299 seconds gives 202. `SELF_HOSTING.md` says that the clock of the server must be right (NTP).
5. Idempotency: after a valid check, and only then, the route does `insertOrIgnore` of the `svix-id` into `mail_webhook_events` (`id`, `cuid`, `provider_event_id` unique, `type`, timestamps). When no row was added, the event was handled: 202 and nothing else. Test: the same id twice, each time with a fresh valid timestamp, adds 1 soft bounce, not 2. A refused call writes no row, so it can never use up the id of a real event.
6. The work runs in a queued job on `default`, after the insert. The answer never waits for it. The log holds the event type, the cuid of the delivery row and the cuid of the account or the invitation, never an address, a header, the secret or the body.
7. The limiter `mail-webhook`: per IP address, `Limit::perMinute(Constants::MAIL_WEBHOOK_EVENTS_PER_MINUTE)`, 600 [first value]. Test: the 601st call in one minute gives 429.
8. The events. Test each one:
   1. `email.bounced` with `bounce.type` `Permanent`: a hard bounce.
   2. `email.bounced` with `Temporary`, or with any other or missing value: a soft bounce, and a log line for a value that is not `Temporary`.
   3. `email.complained`: a complaint.
   4. `email.suppressed`: reason `provider_list`, handled as a hard bounce.
   5. `email.delivered`: rule 16.
   6. Any other type, or a payload without `data.email_id` or without exactly one address in `data.to`: 202 and nothing else.

Linking a report to a mail:

9. Table `mail_deliveries`: `id`, `cuid`, `kind` (string, enum `KnowiiMailKind`: the five groups of rule 26, `invitation`, `account`, `asked_for`, `operator`), `user_id` (nullable, `cascadeOnDelete`), `community_invitation_id` (nullable, `nullOnDelete`, so a late report on a deleted invitation still reads `kind = invitation`), a check that at most one of the two is set, `notification_id` (nullable, `cascadeOnDelete`), `channel` (`email`), `status` (`queued`, `sending`, `sent`, `failed`, `held`, `skipped`), `provider_email_id` (nullable, unique), `sent_at`, timestamps. A unique index on `(notification_id, channel)` where `notification_id` is not null. No address, no subject, no text.
10. These mails get a row, with the header `X-Knowii-Delivery: <cuid of the row>`: every `NotificationMail` (its account and its group), `CommunityInvitation` on both paths (its invitation), `MagicLink`, `ConfirmNewEmailAddress` (its account, kind `asked_for`), `CommunityOwnershipTransferred`, `EmailAddressChanged` (its account, kind `account`), `MailBudgetAlert` (no account, kind `operator`). The address check and the password reset of the framework get no row. One map from the class of a mail to its kind, `App\Support\MailKinds`. A guard test lists every class of `app/Mail` and `app/Notifications` plus `ResetPassword`, and fails for a class that the map does not name.
11. The listener on `MessageSent` writes `provider_email_id` from the header `X-Resend-Email-ID` and `sent_at`, for the row named by `X-Knowii-Delivery`. It never reads `getMessageId()`, because the other transports give their own id. It never throws. Test: a send through the Resend transport with a fake client fills the column; a send with `array` leaves it null and fails nothing.
12. A notification mail goes out at most once. The row is written as `queued` before the mail goes to the queue. The job claims it with one conditional update (`queued` or `failed` to `sending`) before the send; when no row changes, the job ends and sends nothing. The listener sets `sent`. A transport exception sets `failed`, so the next try can claim it. A held mail sets `held`. Test with `TestNotificationMail`: the second run of the same job sends nothing; a run after `failed` sends once.
13. The webhook finds the row by `data.email_id`:
    1. A row of kind `invitation`: rule 19. Never an account, also when the invitation is gone.
    2. A row of kind `operator`: nothing.
    3. A row with an account: the account.
    4. No row: the address of `data.to`, compared without the case with `users.email`, only when no row of `community_invitations` holds the same address. When an invitation holds it too, nothing changes and a log line says so.
       Test each case, and an account and an open invitation with the same address: a report on the invitation mail changes the invitation and not the account.

The suppression:

14. Table `email_suppressions`: `id`, `cuid`, `user_id` (unique, `cascadeOnDelete`), `email` (lower case: the address of the report), `reason` (nullable, enum `KnowiiEmailSuppressionReason`: `hard_bounce`, `complaint`, `soft_bounces`, `provider_list`), `suppressed_at` (nullable), `soft_bounce_count` (small integer), `last_event_at`, timestamps.
15. `App\Support\EmailSuppression::appliesTo(User $user): bool` is the only reader. It is true when a row of the account has `suppressed_at` set and its `email` equals the address of the account without the case. A hard bounce, a complaint and `email.suppressed` set `suppressed_at`, `reason`, `email` and `last_event_at`, and replace a row of soft bounces. A second hard bounce changes nothing.
16. Soft bounces: each soft bounce adds 1 for the same address (a row with `suppressed_at` null). At 3 the row is suppressed with `soft_bounces`. `email.delivered` for the address: the count goes to 0, and a suppressed row with `hard_bounce`, `soft_bounces` or `provider_list` is deleted. A `complaint` row stays. A `delivered` event whose `created_at` is older than `last_event_at` of the row changes nothing. Test: 2 soft, 1 delivered, 2 soft: not suppressed; 3 soft: suppressed; a late `delivered` from before the bounce: still suppressed.
17. An address change lifts the suppression at once (rule 15 compares the address). A model event of `User` deletes the rows of the old address when `email` changes, so one place covers every path that saves the account. Test: change the address, the account is not suppressed; a bounce on the new address makes a new row; change it back: not suppressed.
18. What a suppressed address means for each kind of mail. Test each row:
    1. A notification mail (`NotificationMail`, from slice 4c): not sent. A middleware after `HoldNotificationMail` and before the rate limiter ends the job as done, with the row `skipped`, each time the job runs. A group that the member stopped (rule 26) gives the same answer. Test with `TestNotificationMail`.
    2. A mail that a person asks for (kind `asked_for`: the sign-in link, the address check, the password reset, the confirmation of a new address): always handed to the mailer. Before the send, a listener on `MessageSending` asks the provider to remove the address (contract `RemovesProviderSuppressions`, the SDK `suppressions->remove()`), when the mailer is `resend` and a row of that address has `hard_bounce`, `complaint` or `provider_list`. The limiter `mail-provider-removals` allows 1 removal an hour and 3 in 24 hours per address (`MAIL_PROVIDER_REMOVALS_PER_HOUR=1`, `MAIL_PROVIDER_REMOVALS_PER_DAY=3`). Above the cap, or when the removal fails, the mail still goes and a log line says why. The row stays. The class map of rule 10 decides the kind, also for the two notifications of the framework.
    3. The other account mail (ownership transfer, "your address changed", the budget alert): handed to the mailer as today, with no removal. The provider can skip it.
    4. An invitation, single or from a file: handed to the mailer as today, with no removal. The suppression of an account at the same address does not stop it.
    5. The answer of the sign-in link form is the same, byte for byte, for an unknown address, a healthy address and a suppressed address.
    6. With the mailer `smtp` or `log`: no call to the provider.
19. A bounce of an invitation never touches `email_suppressions`. It sets two new columns of `community_invitations`: `bounced_at` and `bounce_type` (`hard` for a hard bounce, a complaint and `email.suppressed`; `soft` for a soft bounce). A new send of the same invitation (a row of an import sent again) clears both, and a replace makes a new invitation without them. `CommunityInvitationResource` gets `bounced_at` and `bounce_type`, under the abilities of the invitation routes. The row of pending invitations shows the words of "The words" point 29 or 30.
20. The member reads and lifts the state through the API (rule 4 of the architecture), and the settings page uses the same routes:
    1. `GET /api/v1/notifications/email-status`, ability `notifications:read`: `suppressed`, `reason`, `since`, `address_hint` (the address of the account) and `email_choices` (the five groups with their state). A count of 1 or 2 soft bounces shows as not suppressed.
    2. `DELETE /api/v1/notifications/email-suppression`, ability `notifications:write`: deletes the row of the account, then the best-effort removal at the provider through the contract of rule 18.2. The limiter `mail-suppression` allows one call an hour per account; the second gives 429.
    3. Tests: a token without the ability gets 403; a member never reads another account; the removal is faked and a failure only logs. A Bruno request for each route, the README table of `documentation/api/README.md`, the OpenAPI document, resource classes per rule 6.
21. A suppressed member sees a box on the notification settings page (`Profile/Notifications`) with the words of "The words" points 22 to 28, and the two buttons. "Try this address again" calls the route of rule 20.2. "Change my address" opens the profile page. Vitest with axe: the box is a region with a heading, the result goes through a status region, an error is `role="alert"` and keeps the focus on the button.

The stop route:

22. Three routes in `routes/web.php`, all with `throttle:mail-stop` first and then `signed`, all without `auth`:
    1. `GET /mail/stop`, `mail.stop.show`: the `web` group, so the page has the shared props and the signed-in header. It changes nothing.
    2. `POST /mail/stop`, `mail.stop.store`: the `withoutMiddleware` list of fact 17. The signature is the only proof.
    3. `POST /mail/stop/undo`, `mail.stop.undo`: the same list.
       The query holds `user` (the cuid of the account) and `group`, and the undo also `restore`. No path parameter. One signed URL (`URL::signedRoute('mail.stop.show', ...)`, no expiry) serves the GET and the one-click POST, because the signature covers the path and the query, not the method.
23. GET: no write of any kind (assert no row and no change). It shows the confirm state, or the done state when the group is already off.
24. The one-click POST (RFC 8058, no `source=page`): stops the group, answers 200 with an empty body, sets no cookie and writes no session. It needs no CSRF token. It accepts the body `List-Unsubscribe=One-Click` as `application/x-www-form-urlencoded` and as `multipart/form-data`. A second POST changes nothing.
25. The POST of the page (`source=page`, from the button): the same action, then a 303 to the signed show URL, which shows the done state with the undo form. The undo POST restores the state of the signed `restore` (in 4b, `default`: the row is deleted) and redirects to the show URL, which shows the undone state. A changed `user`, `group` or `restore` gives 403.
26. The groups: enum `KnowiiNotificationEmailGroup`: `replies_and_mentions`, `direct_messages`, `important_news`, `weekly_summary`, `events`. Add the row "notification email group" to `naming.md` (do not use: list, topic, subscription). Table `notification_email_choices`: `id`, `cuid`, `user_id` (`cascadeOnDelete`), `group`, `state` (a string; 4b writes only `off`), timestamps, unique `(user_id, group)`. No row means the default of the group. The values mirror in `libs/common`.
27. A bad signature: the GET answers 403 with the stop page in its "not valid" state (not the general error page); a POST answers 403 with an empty body. A valid signature for an account that does not exist gives the same answers as for a live account, and writes nothing.
28. The limiter `mail-stop`: per IP address, `Constants::MAIL_STOP_PER_ADDRESS_PER_MINUTE = 60` [first value], for the three routes together. It runs before the signature check, so a flood of bad links is limited too. It has no key per account, because a key per account would let a stranger use up the limit of another person with bad links. Test: the 61st call of one address in a minute gives 429, also with bad signatures.
29. A secret address (ADR 0064): add `mail/stop` to `SecretAddresses::PATHS` and the row `'mail/stop' => ['guest', 'account', 'another']` to `SA_ROUTES`. The session keeps no previous address of it, and the page sends no page view.
30. The page names no person and shows no address and no community: the link can be forwarded. It has `lang`, one `h1`, a status that is text (never only a color or an icon), the button with a visible focus, and works with the keyboard only. Vitest with axe for each state.

The headers and the footer:

31. `NotificationMail` knows its account and its group (abstract methods). Its `headers()` adds `List-Unsubscribe: <URL>` and `List-Unsubscribe-Post: List-Unsubscribe=One-Click`. One builder, `App\Support\MailStopLink::for(User $user, KnowiiNotificationEmailGroup $group)`, gives the URL of the header and of the footer. The link is built when the mail is sent, never stored in the queued job (ADR 0064 point 11). Tests: the header of `TestNotificationMail` holds the signed URL of its group, starts with `APP_URL`, and equals the footer link; the row of `jobs` holds neither `mail/stop` nor `signature=`; a loop over every class of `app/Mail` that does not extend `NotificationMail` (and the two notifications of the framework) finds neither header.
32. The footer of a notification mail is one partial, `resources/views/emails/partials/notification-footer.blade.php`, with the words of "The words" points 17 to 20. The links are a list, before the postal address. The plain-text part has the same order, each address on its own line after its label. The reason line ("You got this because ...") is slice 4c.

No token ability:

33. A new test in `ApiTokenAbilitiesTest.php`: "the web routes that need no token ability are these". It walks the router and keeps each route outside `api/` that has no `auth`, `auth:sanctum`, `auth.optional`, `guest` or `verified` middleware, is not the fallback route, and either has the `signed` middleware or is not a GET and has no `PreventRequestForgery`. The list of names is exactly `oidc.entitlements.webhook`, `mail.webhook`, `mail.stop.show`, `mail.stop.store`, `mail.stop.undo`. Per route it checks the method, a `throttle:` middleware, no `ability:`, no `PreventRequestForgery` for the three POST routes, and `signed` for the three stop routes. If another route of today matches the filter, stop and say which one in the report: do not add it to the list.

Retention, export, deletion:

34. `config/retention.php`: `mail_deliveries_days` 30 (`RETENTION_MAIL_DELIVERIES_DAYS`), `mail_webhook_events_days` 30 (`RETENTION_MAIL_WEBHOOK_EVENTS_DAYS`), `email_soft_bounces_days` 90 (`RETENTION_EMAIL_SOFT_BOUNCES_DAYS`, question 2). `Retention::periods()` gets `mail_deliveries`, `mail_webhook_events` and `email_soft_bounces`. `PruneExpiredData` gets the steps `mail_deliveries` (rows older than the period, by `created_at`), `mail_webhook_events` (the same), and `email_suppressions` (rows with `suppressed_at` null and `last_event_at` older than the period; rows whose `email` is not the address of the account). `notification_email_choices` and a suppressed row have no period. Tests in `RetentionTest.php`: the dry run counts, a recent row stays, a suppressed row of the current address stays.
35. The member export gets the suppression state (reason, date, address) and the email choices, in `account.json`, with the readme line. Account deletion removes the delivery rows, the suppression, the choices (the cascades). Test.
36. An instance without the provider: with `RESEND_WEBHOOK_SECRET` empty the webhook is 404; the stop routes, the headers and the delivery log work with `log` and `smtp`; the checks run on an empty table; no call to the provider.

## The words

Mark: [D] the approved design gives the text; [W] the design does not give it, or gives only its content. Every [W] text is a proposal. Each screen keeps its words in one file: `apps/knowii/src/Components/Mail/mail-stop-words.ts` for the stop page, `apps/knowii/src/Components/Notifications/email-status-words.ts` for the box, `invitation-words.ts` for the invitation row. The owner approves every text below word for word.

The groups, as they show in a sentence and on a button:

1. `replies_and_mentions`: "replies and mentions" [D]
2. `direct_messages`: "direct messages" [D]
3. `important_news`: "important news" [D]
4. `weekly_summary`: "the weekly summary" [D]
5. `events`: "events" [W] (the design says "event emails"; in "Stop emails about <group>" the shorter word reads better)

The stop page, confirm state:

6. Browser title and `h1`: "Stop emails about <group>?" (for the summary: "Stop the weekly summary?") [W]
7. Text: "Press the button to stop these emails. Nothing changes before you press it." [W]
8. Button: "Stop emails about <group>" (for the summary: "Stop the weekly summary") [W]

The stop page, done state (also a GET when the group is already off):

9. `h1`: "You will not get emails about <group> any more." (for the summary: "You will not get the weekly summary any more.") [W, content from groundwork 3.4 point 10]
10. Text: "This counts for every community that you are in." [W, see question 4]
11. Text: "You still get sign-in links and the emails about your account. You still get the other emails that you chose." [W, content from groundwork 3.4 point 10]
12. Button: "Undo" [D]
13. Link: "Email settings" (it opens `/user/notifications`, after a sign-in) [W]

The stop page, undone state, not valid state, failure:

14. Undone: `h1` "Emails about <group> are on again." and the text "You get them as before." [W]
15. Not valid: `h1` "This link is not valid." and the text "Sign in and open your email settings." [W, content from groundwork 3.4 point 12]
16. A failed POST of the page: "We could not save this. Try again." (`role="alert"`) [W]

The footer of a notification mail (built in 4b, first sent in 4c):

17. Link: "Stop emails like this" [D]
18. Link: "Settings" [D]
19. Line: "<operator>, <postal address>" [D, the source of the address is question 1]
20. Plain text: "Stop emails like this: <URL>", "Settings: <URL>", each on its own line [W]

The sign-in link form:

21. One more line after the sent text, the same for every address: "No email after a few minutes? Look in your spam folder, then ask again. An address that refused our mail before can need a second try." [W, content from groundwork 7.2 point 6]

The box on the notification settings page:

22. Heading: "We cannot reach <address>." [D] (the design adds "Fix your address."; the button of point 26 takes its place) [W]
23. Text after a bounce (`hard_bounce`, `soft_bounces`, `provider_list`): "Emails to this address came back. Notification emails are paused. Emails about your account still try to reach you." [W]
24. Text after a complaint: "An email from us was marked as spam. Notification emails are paused until you turn them on again. Emails about your account still try to reach you." [W]
25. Button: "Try this address again" [W, from groundwork 3.3 point 7]
26. Button: "Change my address" [W, from groundwork 3.3 point 7]
27. Status after the first button: "We will try this address again with the next email." [W]
28. The second try within the hour (the 429): "You tried this address less than an hour ago. Try again later." [W]

The row of a pending invitation, for the managers:

29. `bounce_type = hard`: "This address could not be reached. Check it, then invite again." [W, the first sentence from groundwork 3.3 point 6]
30. `bounce_type = soft`: "The email to this address did not arrive yet. Invite again later." [W]

The sub-processor line (`deploy/knowii.net/legal/subprocessors.json`, purpose of Resend, Inc.):

31. "Emails of the service: sign-in links, address verification, invitations, and the reports of emails that could not be delivered or that were marked as spam" [W]

The privacy policy, the same text in the template and in the text of the hosted instance. Exact proposed sentences [W]:

32. Section 2, a new item 10: "10. Email delivery: for each email that we send you, the kind of email, its state (sent, held, skipped or failed), its date and the number that our mail provider gives it, without its subject or its text. When an email to your address cannot be delivered or is marked as spam, our mail provider sends us a report: we keep the report (its number, its kind and its date), and we stop the notification emails to that address, with the reason and the date. A temporary failure is counted, and 3 in a row stop the notification emails too. We also keep the groups of notification emails that you stopped with the link in an email."
33. Section 2, item 7, a sentence at the end: "If an invitation email to your address cannot be delivered, we note it on the invitation, and the managers of the community see that the address could not be reached."
34. Section 3, a new item after item 4: "To stop the emails to an address that does not accept them or that marked them as spam, to keep the groups of emails that you stopped, and to trace a report of our mail provider: our legitimate interest in sending email only where it arrives and where it is wanted (art. 6(1)(f))."
35. Section 4, a paragraph after the list of sub-processors: "Our mail provider keeps its own list of addresses that did not accept an email or that marked one as spam, and sends nothing to them. When you ask for a sign-in link, a password reset or the check of an address, or when you choose to try your address again, we ask our mail provider to take your address off that list, a few times a day at most."
36. Section 5, a new item 17: "17. Email delivery: the record of each email, {{retention.mail_deliveries}}. The reports of our mail provider, {{retention.mail_webhook_events}}. The stop of the notification emails after a report: until an email reaches the address again (not after a spam report), until you try the address again, until you change your address, or until you delete your account. A count of temporary failures that stopped nothing: {{retention.email_soft_bounces}} after the last failure. The groups of emails that you stopped: while your account exists."

Not in this slice, for 4c: the sentence of design 8.5 point 2 (a sent email stays at the mail provider and in the mailbox; the platform cannot call it back), the legal basis of each group of notification email (design decision 1), and the reason line of each mail.

## Scope

Cut the work into two commits, in this order. If the first commit alone is small enough to review, one commit is fine too.

Commit 1, the server: the delivery log, the webhook and the suppression (rules 1 to 20, 33 with the two webhook names, 34 to 36).

1. Migrations: `create_mail_deliveries_table`, `create_mail_webhook_events_table`, `create_email_suppressions_table`, `add_bounce_columns_to_community_invitations_table`. Each foreign key choice is written in the migration.
2. `app/Enums/KnowiiMailKind.php`, `app/Enums/KnowiiEmailSuppressionReason.php`; the models `MailDelivery`, `MailWebhookEvent`, `EmailSuppression` (with `GeneratesCuid`, hidden ids).
3. `app/Http/Controllers/Webhooks/MailWebhookController.php`, `app/Contracts/Mail/ReceivesMailWebhooks.php`, `app/Actions/Mail/ReceiveMailWebhook.php`, the queued job of the event, `app/Support/MailWebhookSignature.php`, `app/Support/EmailSuppression.php`, `app/Support/MailKinds.php`, `app/Contracts/Mail/RemovesProviderSuppressions.php` and its Resend action (with a timeout of 10 seconds, like `ResendMailUsage`).
4. The listeners: `MessageSent` (the provider id), `MessageSending` (rule 18.2). The `User` model event of rule 17.
5. `NotificationMail`: the delivery row, the claim, the skip middleware (rules 12 and 18.1). The delivery row and the header for the mails of rule 10 (`MagicLinks.php`, `InviteCommunityMember.php`, `SendInvitationImportMails.php`, `TransferCommunityOwnership.php`, `ChangeEmailAddress.php`, `CheckMailBudget.php`, or a small trait in the mailables).
6. `app/Constants.php`: `MAIL_WEBHOOK_EVENTS_PER_MINUTE`, `MAIL_WEBHOOK_TOLERANCE_SECONDS`, `MAIL_STOP_PER_ADDRESS_PER_MINUTE`. The limiters `mail-webhook`, `mail-provider-removals`, `mail-suppression` in `AppServiceProvider` (and `mail-stop` in commit 2).
7. `config/services.php` (`resend.webhook_secret`), `config/mail.php` (the two removal caps), `config/retention.php`, `app/Support/Retention.php`, `PruneExpiredData.php`, `ExportUser.php`.
8. The API: `routes/api.php`, `app/ApiRoutes.php`, a controller, the resources, `libs/common` schemas, two Bruno requests, `documentation/api/README.md`. `CommunityInvitationResource` and its schema.
9. `.env.example` and `.env.production.example`: `RESEND_WEBHOOK_SECRET=` with the comment "The signing secret of the webhook of Resend (whsec_...). Empty: no webhook route", `MAIL_PROVIDER_REMOVALS_PER_HOUR=1`, `MAIL_PROVIDER_REMOVALS_PER_DAY=3`, and the three `RETENTION_*` values.
10. `SELF_HOSTING.md`: the rows of the new values (after line 83 and in the retention table), and a part "Bounces and complaints" in "Mail volume": the endpoint to set at Resend (`https://<host>/webhooks/resend`), the four event types to subscribe to (`email.bounced`, `email.complained`, `email.suppressed`, `email.delivered`), where the secret comes from, NTP, that Resend disables an endpoint that keeps failing (a wrong secret gives 401 to every call), that the removal from the list of Resend needs an API key with full access, how an operator reads the state of an address (`email_suppressions` and the list of Resend), and that `APP_PREVIOUS_KEYS` keeps the stop links valid after a change of `APP_KEY`.
11. The policy lines 32 to 36 and the sub-processor line 31 in both files: only with the words that the owner approved. Until then, the commit holds the proposed words, and the report says so.
12. The tests of the rules, in new files (`tests/Feature/MailWebhookTest.php`, `tests/Feature/EmailSuppressionTest.php`, `tests/Feature/MailDeliveryTest.php`) and in `ApiTokenAbilitiesTest.php`, `RetentionTest.php`.
13. Header: `feat(api): the delivery log, the bounce webhook of the mail provider and the suppression`. Footer: `Refs #1478`.

Commit 2, the stop and the screens (rules 21 to 33).

14. Migration `create_notification_email_choices_table`, enum `KnowiiNotificationEmailGroup`, model, action behind a contract (`StopsEmailGroups`, `StopEmailGroup`), `app/Http/Controllers/Mail/MailStopController.php`, `App\Support\MailStopLink`, the render of a bad signature on the stop routes (rule 27).
15. `NotificationMail::headers()`, the footer partial, `TestNotificationMail` with an account and a group.
16. `SecretAddresses::PATHS`, `SecretAddressesTest.php` (`SA_ROUTES`), the full list of rule 33.
17. The front end: `Pages/Mail/Stop.tsx` (the four states, in `AppLayout` with `contentWidth="form"`) with its spec, the words file, the box on `Profile/Notifications.tsx` with its spec, the bounce words on the row of a pending invitation (`CommunityMembersManager.tsx`), the help line of the sign-in form.
18. `naming.md` (the row of rule 26).
19. ADR 0054: a section "Slice 4b" with the decisions of this brief, and the change of point 3 (a third middleware). The status line names #1478. Update the design file `email-groundwork.md`: #1610 is closed (no check by counsel; the owner approves the words), and a short section 9 "What the build changed", where the build differs from section 3 (the GET of the stop page keeps the `web` group, the delivery row of an invitation uses `nullOnDelete`, the model event of rule 17, the prune of rows of another address). `documentation/architecture/backend.md`, section "Email delivery" (line 547).
20. The screen atlas: rows in `tools/screen-atlas/screens.json` for the stop page (confirm, done, undone, not valid) as `visitor` and as `member`, the box (bounce, complaint, after "Try again", the second try) as `member`, and a pending invitation with each bounce type as `owner`. The seed writes the signed URLs of the member and gives the member a suppression row; the unseed removes them. Find in `atlas.mjs` how a row reads a path that the seed writes; if it cannot, add the smallest change.
21. Pictures for the owner in `~/Downloads/knowii-<date>-notifications-4b/` (the date of the build, `YYYY-MM-DD`), with an `index.html` that shows each picture with its name: each screen of point 20 at 360 px and 1920 px in both themes; the footer of `TestNotificationMail` rendered as HTML (both themes) and as plain text; and a table of every text of "The words" with the screen where it shows.
22. Header: `feat(all): the one-click stop of a notification email, its page and its headers`. Footer: `Closes #1478`.

## NOT in this commit

1. Any notification mail (slice 4c): the ladder, the rhythm, quiet hours, the weekly budget, the pause, the reason line, "Weekly summary only", "Pause all email for 2 weeks", "Pause all email for 1 month", "Stop all email from <community>".
2. The settings of the email choices (#841): 4b writes only `off` through the stop route, and shows the state in the API read.
3. A stop link or a one-click header on an invitation, a sign-in link or any other account mail.
4. The `mail:suppress` and `mail:unsuppress` commands (owner decision 10), the alert mail for an asked-for mail above the cap (groundwork 7.4 point 3), a check of the time of the last webhook event.
5. A bounce state on the rows of an import of invitations. The row of the pending invitation shows it.
6. The policy sentence of design 8.5 point 2 and the legal basis per group of notification email (slice 4c).
7. A new dependency (the SDK is installed), a queue of its own for level 0 mail.

## How to work

1. `git fetch origin`, then branch `feat/1478-notifications-4b` from `origin/main`. One commit, or the two commits of "Scope". No push, no pull request. If the working tree holds changes of another person, stop and ask: no stash, no reset, no checkout over them. Find the text when a line number moved.
2. Docker, from `~/wks/knowii`: `docker compose exec -T -u sail laravel.test <command> </dev/null`. Do not start, stop or restart a container. No `migrate:fresh` and no seeder on the development database. Run the new migrations with `php artisan migrate`.
3. Run `npm run build` on the host before the first Pest run.
4. Tests first. Write the tests of each group of rules, run their file (`php artisan test tests/Feature/MailWebhookTest.php`), and see them fail. Then build.
5. In the container: `composer format`, `composer lint`, `composer security:php`. Then the whole Pest suite ONE time, after the last change: `bash -c "set -o pipefail; php artisan test --parallel 2>&1 | tail -n 40"`, with its summary written to a file outside the repository (for example `/tmp/knowii-1478-pest.txt`).
6. The front end: `npx nx format --all`, `npm run lint:common`, `npm run tsc`, `npm run tsc:spec`, and the full run `npx nx test common --skip-nx-cache && npx nx test knowii --skip-nx-cache`. Never at the same time as the Pest suite.
7. Never call the network in a test. Fake the Resend client, the removal and the usage read through their contracts. Sign the webhook in the test with a secret that the test builds.
8. Never name a third-party community product, in code, tests, documents or pictures. Use `example.com` addresses.
9. The commits: a conventional header of at most 100 characters, with a scope of `commitlint.config.js` (`all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build`), not starting with a capital letter. The footers of "Scope". No trailer that names a tool or a co-author.
10. If a rule cannot be built as written, skip it, build the rest, and say why in the report. Do not invent product rules or words.
11. If you find a weakness in the code while you work, do not write it in a commit, a test name, the ADR or an issue. Tell the reviewer in private.

## Questions for the owner

1. The postal address of the footer (design 5.6 point 7). The instance has no such value (fact 29). Recommendation: a new optional instance value `LEGAL_POSTAL_ADDRESS`, listed in `SELF_HOSTING.md`; the footer shows "<operator>, <postal address>", and only "<operator>" when it is empty. The hosted instance sets the company address that its privacy policy already shows.
2. The rows of soft bounces that stopped nothing (groundwork, risk 13). They are personal data with no use after a time. Recommendation: delete them 90 days after the last failure (`RETENTION_EMAIL_SOFT_BOUNCES_DAYS=90`), and say it in the policy (line 36).
3. The sub-processor line (point 31). The provider and the kind of data (the address) stay the same; only the purpose grows. Recommendation: change the line in this slice, with no notice of 14 days under the data processing agreement (it is not a new or a replaced sub-processor), and name the change in the release notes.
4. Does "Stop emails like this" stop the group in every community, or only in the community of the mail? Design 2.6 puts "Important news" per community, and design 5.6 point 8 names the groups without a community. Recommendation: in 4b the stop is for the account, in every community (one click, one meaning), and the page says so (line 10). Slice 4c can add a choice per community for "important news" in the settings, with no change of the link.
5. Where a member sees that the address is suppressed. Design 5.9 point 9 says the settings page; groundwork 7.3 point 4 says "after the sign-in". Recommendation: the notification settings page only in 4b, because no notification mail goes out before 4c. Slice 4c adds one line in the "Needs you" list after a sign-in, with its own words.
6. The words. Recommendation: approve the 36 texts of "The words" as they are, or change them in the pictures. The policy lines 32 to 36 and the sub-processor line 31 go into the files only after your approval, word for word.

## Your final report (max 400 words)

1. The commit hashes and `git show --stat` of each.
2. For each group of rules (the webhook, the linking, the suppression, the stop route, the headers, the ability list, retention): the failure of its tests before the code and the pass after it.
3. The summary of the full Pest run (the file of step 5), and the results of `composer format`, `composer lint`, `composer security:php` and the front-end checks.
4. The routes that the test of rule 33 found, and any route that matched the filter and was not in the list.
5. The path of `index.html` of the pictures, and the list of texts of "The words" that differ from this brief, with the reason.
6. Each choice where the brief left room (the place of the delivery row of each mail, how the atlas reads a signed path), and anything of the scope not built, with the reason.
7. Anything that you checked against the real provider and could not check (the DKIM coverage of the two headers, the acceptance of a `https`-only `List-Unsubscribe` by Gmail and Apple Mail, the permission of the API key for the removal): say "not checked" for each.
