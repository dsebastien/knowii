# Brief: events, slice E2: answers, places, the calendar file and "My events" (knowii-oss/knowii#1515)

State: DRAFT. The design is approved (#1388, 2026-11-16, with its 18 decisions). Part E2a is in build since 2026-11-20: until the owner answers the "Questions for the owner" at the end, the builder builds what each recommendation and each [Q] rule says.

Slice E2 lets a member answer an event, holds the limit of places, gives the meeting address to the members who answer Yes when the host asks for it, gives one calendar file per event, and adds the page "My events". It builds on E1a (#1513) and E1b (#1514), which are in `main` (ADR 0053). E2 is too large for one commit. This brief cuts it in three parts: E2a (the server), E2b (the screens of the answers), E2c (the calendar file and "My events"). Part E2a is written in full. Parts E2b and E2c are scope lists of ten lines each: each one gets its own full brief when the part before it is reviewed. Paths are relative to `~/wks/knowii`.

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md` (Architecture 2 to 6, Data 1, 2, 6, 7, 8 and 10, definition of done), `documentation/conventions/naming.md`.
2. The issue (`gh api repos/knowii-oss/knowii/issues/1515 --jq .body`), #1388 with its comments of 2026-11-16 and 2026-11-17, #1516 (slice E3), #1482 (slices E4 and E5), #1413 (waiting list: not now).
3. `documentation/design/events/design.md`: sections 1.2, 2.3, 3.3, 3.4, 3.6, 3.7, 5.B, 5.D, 5.F, 5.G, 5.H, 6.4, 6.5, 6.8, 7.1, 7.2, 7.3, 7.4, 7.6, 7.7, 7.8, 8.6, 9, 10, 11.4, 14. Then `release-test.md` of the same folder.
4. ADRs: 0053 (all of it), 0006, 0011, 0014, 0022, 0024, 0030, 0042 (points 9, 17, 21, 28), 0043, 0044 (points 4, 6, 8, 9, 13, 15), 0054 (points 1, 3, 18).
5. The code of E1a: `database/migrations/2026_11_18_100000_create_events_table.php`, `app/Models/Event.php`, `app/Policies/EventPolicy.php`, `app/Http/Resources/EventResource.php`, `app/Http/Controllers/API/EventApiController.php`, `app/Actions/Events/*`, `app/Contracts/Events/*`, `app/Support/UpcomingEvents.php`, `app/Events/Spaces/SpaceContentChanged.php`, `routes/api.php` (lines 79 to 82 and 204 to 209), `app/ApiRoutes.php` (lines 163 to 173).
6. The code that E2a touches for the data rules: `app/Models/Community.php` (`removeUser`, line 365), `app/Actions/Users/ExportUser.php` (lines 98 to 128 and 624), `config/retention.php`, `app/Support/Retention.php`, `app/Console/Commands/PruneExpiredDataCommand.php`, `app/Providers/JetstreamServiceProvider.php` (line 129), `app/Providers/AppServiceProvider.php` (line 398), `deploy/knowii.net/legal/policy.md`, `resources/legal-templates/policy.md`.
7. Tests: `tests/Feature/EventsTest.php`, `EventApiTest.php`, `EventPagesTest.php`, `EventNotificationsTest.php`, `PolicyTest.php` (line 115), `PublicSpacesTest.php`, `RetentionTest.php` (the design names it `RetentionScheduleTest`), `UserExportTest.php`, `ApiTokenAbilitiesTest.php`, `BrunoCollectionTest.php`, `OpenApiDocumentTest.php`.
8. `libs/common/src/lib/types/event.schema.ts` and its spec, `apps/knowii/src/test-utils/events.ts`, `apps/knowii/src/Components/Events/event-words.ts`.

## What the design decides for E2 (do not change it)

Each point names its place in `design.md`.

1. Three answers: `yes` ("Yes"), `replay` ("Replay only"), `no` ("No"). There is no "Maybe" (3.6 point 1, decision 2, section 15 "Rejected" point 3).
2. "Replay only" is offered only when the host set `replay_expected`. Else the member chooses between Yes and No (3.6 point 2).
3. One row per member and event in `event_answers`. No row means no answer. A "No" is a row (3.6 points 3 and 4).
4. Who answers: a member of the community who can see the event, when the event is published and not over. The owner, the admins and the moderators answer like members. A visitor and a person who is not a member never answer (3.6 point 6, 7.2 table, row "Answer").
5. A member changes or clears their answer until the event ends. Only the member writes their own answer: no manager adds a member or changes an answer (3.6 point 5, 7.6 point 5). Nothing answers Yes for a member (2.3 point 3).
6. A cancelled event and a past event take no new answer. The old answer stays, and its member still sees it. When the host publishes a cancelled event again, the answers are still there (3.6 point 5, 5.D points 2 and 3, 5.F point 1).
7. A draft has no answers. A published event goes back to draft only when nobody answered. Else the server says "Cancel it instead" (3.4 point 5).
8. Places: `capacity` is 1 to 5 000 or empty. It counts the Yes answers only (3.3, 7.6 point 1).
9. `AnswerEvent` locks the row of the event, counts the Yes answers, and refuses a new Yes at the limit with 422 "This event is full." (7.6 point 2).
10. A member who goes from Yes to another answer frees a place. Nobody is promoted. There is no waiting list (7.6 point 3, decision 7, #1413).
11. With a limit, a member reads "28 places left", and "Full" at the limit. No urgency words, no urgency colour. Without a limit, a member reads no count (7.6 point 4, 6.4 point 2).
12. A host can lower the limit under the number of Yes answers. Nobody is removed. The form says how many Yes answers exist (7.6 point 5).
13. A member sees their own answer, no other answer, and no count (principle 2 of 1.2, 6.4 point 2, decision 8).
14. The host, the owner and the admins read "Answers": the names of Yes, then of Replay only, in alphabetical order, each a link to the member page, and the number of No without names. Read only, no export (6.4 point 3, `EventPolicy::viewAnswers` in 7.2).
15. A moderator does not read the answers, also with the right `delete_content` (6.4 point 4, 7.2 point 2).
16. A visitor and a person who is not a member never get an answer, a count, a name or the answer buttons. A visitor reads "Sign in to answer" (7.4 point 2, 5.H point 1).
17. The meeting address and the place: with `members` (the default), every member who can see the event. With `going`, the members who answered Yes, the host, the owner and the admins. Never a visitor or a person who is not a member. The server cuts the fields. The client hides nothing (3.7, 7.3). The form of the host gets the field "who sees them" (6.3 point 2).
18. The calendar file: one event per file, RFC 5545, written by a small class `App\Support\IcsFile` with no new dependency. `GET .../events/{event}/calendar-file`, `text/calendar; charset=utf-8`, as an attachment, with the ability `events:read`. The access is checked first. A visitor gets no file (6.5 points 2, 3 and 5, 9.1).
19. The file holds one `VEVENT`: `UID` (the cuid of the event and the host name of the instance), `DTSTAMP`, `DTSTART` and `DTEND` in UTC with `Z` and no `VTIMEZONE`, `SUMMARY`, `DESCRIPTION` (300 characters of plain text and the address of the page), `URL`, `LOCATION`, `SEQUENCE` (`events.sequence`), `STATUS` (`CONFIRMED` or `CANCELLED`). The meeting address and the place are in it only when this reader can see them. No `VALARM`. No `METHOD`. The name of the file is the slug of the title and the date (6.5 points 3 to 5).
20. No calendar feed in this slice (6.6, decision 10). The platform stores nothing about who opens a calendar file (10.1 point 1).
21. "My events": the page `/events`, in the user menu. It lists the upcoming events with my answer Yes or Replay only in all my communities, and the events that I host. It shows the name of the community when I am in several. API: `GET user/events` with `answer` (`yes`, `replay`, `host`) and `scope`, ability `events:read` (6.8).
22. A card of the list of a space shows "N places left" when a limit exists, and "You said Yes" for a member who answered (6.1 point 4).
23. A member who leaves or is removed: `Community::removeUser` deletes their answers in that community. A member who comes back starts with none (7.7 point 1).
24. A deleted account: the database deletes its answers by cascade. The events that it hosted stay, without a host (7.7 point 2, 10.2 point 3).
25. Retention: an answer is deleted 90 days after the end of its event (`retention.event_answers_days`), with a step of `retention:prune` and its test (10.1, decision 14).
26. The member export lists the answers of the member: the cuid and the title of the event, the answer, the dates. No name of another person. The community export holds no answer (10.2 points 1 and 2).
27. The policy gets sentences 1 to 3 of 10.4. The owner approved them as proposed on 2026-11-17 (comment on #1388). Sentence 4 belongs to E3.
28. Abilities: a member reads their own answer with `events:read` and sets it with `events:write`. `event-answers:read` is new, is not in the default set of a token, and reads the list of the answers. The policy still limits it to the host, the owner and the admins (9.2 point 1).
29. A limiter `event-answers` per member (7.8). The answer group follows 11.4: a `radiogroup`, "Saved: Yes" in a status region, "Yes" on a full event is `aria-disabled` with the reason as text.

## What the design leaves open

Each gap has the answer that fits the design and the code. A gap that is a product decision is also in "Questions for the owner".

1. Section 14 and #1516 put `replay_expected` in slice E3, but the answer "Replay only" of E2 needs it. Proposal: E2 adds it (question 1).
2. A cancelled event "takes no new answer". The design does not say if a member can change or clear an old answer while the event is cancelled. Proposal: no change and no clear until the event is published again (question 2).
3. A member who loses the access to the space (an access group, a change of visibility) and stays in the community. Proposal: the answer stays and no read shows it (question 3).
4. The host removes `replay_expected` while "Replay only" answers exist. Proposal: the rows stay. Their members still read "Replay only" as their answer and can choose Yes or No. The list of the host keeps them.
5. What a member gets about places. Proposal: `places_left` for a member who can see the event, and `capacity` only for the readers of the answers. A member reads "28 places left" and never the number of Yes.
6. The form of `GET .../answers`. Proposal in rule 21 of part E2a.
7. The sentence before the answer (10.1 point 3) names only the host, but the owner and the admins also read the answers (question 4).
8. "Only when nobody answered" (3.4 point 5): this brief reads it as "no row": a "No" also blocks the return to draft.
9. A delete of a published future event with answers. The rule "cancel it first" is E3 (#1516). Until E3, a delete removes the answers by cascade. Accepted: nothing tells a member before slice E4.
10. The live signal `answers.changed` (9.4 point 2) goes to every reader of the space each time a member answers. Proposal: a narrower signal (question 5).
11. The design names the signal `community.event.answers.changed`. The code sends the content of a space as `space.event.*` (ADR 0053, point 9): the name is `space.event.answers.changed`.
12. The calendar file: the design names a button "in the card menu", and a card has no menu. It does not say what `LOCATION` holds, in which zone the date of the file name is, or what a draft gives (question 6, part E2c).
13. "My events": the design does not say if my drafts and my past events are in it (question 6, part E2c).
14. The register of processing is not in the repository. The report gives the text of its row, and the owner adds it.
15. Section 5.D gives the cancel question the words "This tells 14 members". Nothing tells a member in E2, so the question keeps the words of E1b until slice E4.
16. Section 4.3 point 5 says that the file waits for the zone of the account. The file holds UTC instants and no time in words, so it needs no zone.
17. The issue names a member who "is blocked". Section 7.7 point 3 is only about the line of a new event, which E1a built. A block changes nothing for an answer, so E2 builds nothing for it.

## Notifications and email

1. E2 sends no email and writes no notification row. Section 8.6 of the design gives each kind its slice: the line for a moved or cancelled event (kind 26), the two reminders (kinds 27 and 28) and the replay line (kind 29) are slice E4 (#1482, in the app) and slice E5 (#1482, by email).
2. Reminders before an event are not in E2. They need the command `events:remind`, the guards `reminded_24h_at` and `reminded_1h_at` and the kinds of E4 (8.3). E2 adds none of them.
3. On `main`, the only event kind is `event_new` (ADR 0053, point 10). The mail queue, the budget and the base class `App\Mail\NotificationMail` exist (ADR 0054), but no notification mail is sent yet: bounces, complaints and the one-click stop are slice 4b (#1478), and counsel checks the legal basis per kind of mail first (#1610). The event mails of slice E5 wait for both.
4. So an answer tells nobody: not the host, not the member. The page says "Saved: Yes" and nothing else. A change, a cancel and "Publish again" keep the answers and tell nobody until E4.
5. E2 gives the later slices their data: `event_answers` gives the recipients of kinds 26 to 29, and `App\Support\IcsFile` (part E2c) gives the attachment of the mails of E5.

## The cut

1. E2a, "The server of the answers and the places": the table, `AnswerEvent`, the policy, the limit with its lock, the Yes part of `going`, the API, the ability, the live signal, the leave, retention, export and policy rules. No screen changes. This is the part that stores new personal data, so it carries the legal text (rules.md, Data 10).
2. E2b, "The screens of the answers": the answer group, "places left", the list of the host, the two labels of a card, the three new fields of the form.
3. E2c, "The calendar file and My events": `IcsFile` with its route and button, `GET user/events`, the page `/events`.
4. Each part is ONE commit on its own local branch, built on the part before it. Each part works alone: after E2a an API client answers, after E2b a member answers on the page.
5. If the reviewer wants a smaller E2a, the one clean cut is the places (rules 10 to 13, 15 and their tests) as a second commit.

## Part E2a: the server of the answers and the places

You build the server part of #1515. A member answers an event through the API. No page changes. ONE commit on a local branch: a reviewer reads your diff afterwards.

### What exists today (facts, with the evidence)

Line numbers are those of `main` on 2026-11-20 (commit `a2482fa3`).

1. The table `events` has no `capacity`, no `replay_expected` and no table of answers beside it (`database/migrations/2026_11_18_100000_create_events_table.php:35-70`). `location_visibility` exists, with a check on `members` and `going` (lines 55 and 72). `sequence` exists (line 63).
2. `EventPolicy` has `view`, `update`, `cancel`, `delete`, `viewLocation` and the static `isHost` (`app/Policies/EventPolicy.php:27,39,49,58,67,81`). It has no `answer` and no `viewAnswers`. With `going`, `viewLocation` passes only for the host, the owner and the admins (lines 73 to 75).
3. `EventResource` asks `viewLocation` once per event and cuts `meeting_url` and `place` (`app/Http/Resources/EventResource.php:31,45-46`). It gives `location_visibility` to every reader. `eventSchema` mirrors it (`libs/common/src/lib/types/event.schema.ts`).
4. `CreateEvent::rules()` accepts `location_visibility` (`app/Actions/Events/CreateEvent.php:125`), and `UpdateEvent` stores it (`UpdateEvent.php:62-64`). The form never sends it (ADR 0053, "The screens", answer 4).
5. `SetEventState::assertAllowed` refuses the return to draft only for an event that started (`SetEventState.php:82-90`). `DeleteEvent` deletes every event that the person can delete (`DeleteEvent.php:19-32`).
6. `DuplicateEvent` copies a fixed list of fields (`DuplicateEvent.php:51-67`).
7. The actions that change an event call `EventPublication::assertVisible` first: a person who cannot see the event gets 404 (`EventPublication.php:59-64`).
8. The lists give each event its space and its community, so that the policy runs without a query per event (`EventApiController.php:213-219`, `UpcomingEvents.php:82-92`).
9. Live updates: `SpaceContentChanged::forEvent($name, $event, $authorCuid)` sends `event_cuid` and `author_cuid` on the space channel (`app/Events/Spaces/SpaceContentChanged.php:63-67,92`). A draft sends nothing (`EventPublication.php:46-51`).
10. Routes: three public reads with `auth.optional`, `public-reads` and `events:read` (`routes/api.php:80-82`), five writes with `events:write` (lines 205 to 209). The limiter `events` covers a create and a copy (`AppServiceProvider.php:398`, `Constants.php:230`).
11. Abilities: `events:read` is in the default set of a token, `events:write` is not (`JetstreamServiceProvider.php:163-164,173`).
12. `Community::removeUser` deletes the group rows, the course progress, the quiz results, the notifications and the visits of the member (`app/Models/Community.php:365-381`). It knows nothing of events.
13. The member export writes `events.json` with the events that the member hosts (`ExportUser.php:123,624-639`). `config/retention.php` has no entry for answers.
14. The policy names events in the content of a member (`deploy/knowii.net/legal/policy.md:24`, `resources/legal-templates/policy.md:23`). It does not name answers.
15. `PolicyTest.php:115-140` and `documentation/architecture/roles-and-permissions.md:76-82` hold one row per event ability.

### Rules

[Q] marks an answer of "What the design leaves open". It becomes "decided" when the owner answers the questions.

Access and privacy first:

1. New `EventPolicy::answer(User, Event)`: the user is a member of the community, `view` passes, the state is `published`, and the end is now or later. New `EventPolicy::viewAnswers(User, Event)`: the owner, an admin, or `isHost`, and the event is not a draft. Neither one tests an access group: `view` does (design 7.1 point 2).
2. `viewLocation` with `going` also passes for a member whose answer to this event is `yes`. `replay` and `no` do not pass. The answer counts also when the event is cancelled or over.
3. Every route of the answers calls `EventPublication::assertVisible` first. A draft that the person cannot see, and an event of a space that the person cannot read, answer 404, with the same body as an event that does not exist.
4. No response, no Inertia prop, no broadcast and no log line carries the answer of a member to another member. The three exceptions: the list of rule 21 for the host, the owner and the admins, the member's own answer, and the member export of that member.
5. `EventResource` gets five fields. `my_answer`: `yes`, `replay`, `no` or null, the answer of the reader. `can_answer`: the result of `answer`. `replay_expected`: a boolean, for every reader. `places_left`: a number from 0 when the event has a limit and the reader is a member who can see it, else null [Q]. `capacity`: the limit for a reader who passes `viewAnswers` or `update`, else null [Q]. For a visitor and a person who is not a member: null, false, the boolean, null, null.
6. A list never runs a query per event for these fields: one query loads the answers of the reader for the page, one loads the Yes counts. A test counts the queries of a list of 20 events.
7. The names of rule 21 hold only people who are members of the community and can read the space today. A member who lost the access to the space is in no name list and in no count of that list [Q, question 3]. Use the query that the fan-out of `event_new` uses for "the members who can view the space".
8. The log lines of the action hold the cuid of the event and never the answer, the name or the cuid of the member.

The answer:

9. New contract `App\Contracts\Events\AnswersEvents` and action `App\Actions\Events\AnswerEvent`, with `answer(User $user, Event $event, array $input): EventAnswer` and `clear(User $user, Event $event): void`. Both call `assertVisible`, then check the state, then `Gate::authorize('answer')`.
10. Order of the refusals: 404 (rule 3), then 403 for a person who is not a member, then 422 "This event is cancelled. It takes no answers." for a cancelled event and 422 "This event is over. It takes no answers." for an event that ended [Q: the words], then 400 for a wrong field. A change and a clear on a cancelled event are refused like a new answer [Q, question 2].
11. `answer`: `yes`, `no`, and `replay` only when `replay_expected` is true. Else a field error (400): "This event has no replay."
12. The same answer again changes nothing and answers 200. It is never refused for the limit.
13. The limit holds under two answers at the same time, with a lock and a constraint:
    1. `AnswerEvent` opens a transaction and reads the event again with `lockForUpdate()`. Every answer and every clear of one event waits for that row lock, so they run one after the other.
    2. Inside the lock it reads the state, the end and `capacity` from the fresh row, counts the rows with `yes`, and refuses a new Yes (no row, or a row that is not `yes`) when the count is at or over the limit: 422 "This event is full."
    3. The write is one `upsert` on `(event_id, user_id)`. The unique index stops a second row when the same member sends two answers at once.
    4. `UpdateEvent` and `SetEventState` take the same row lock when they change `capacity` or the state, so an answer never passes a cancel that runs at the same moment.
14. A clear deletes the row and answers 204, also when no row exists.
15. `capacity`: `sometimes`, nullable, an integer from `Constants::MIN_EVENT_CAPACITY` (1) to `MAX_EVENT_CAPACITY` (5 000), in `CreateEvent::rules()`, mirrored in `constants.ts`. A value under the number of Yes answers is accepted, and nobody is removed. `places_left` is never below 0.
16. `replay_expected`: `sometimes`, boolean, default false, in the same rules [Q, question 1]. A change to false keeps the `replay` rows.
17. `DuplicateEvent` copies `capacity` and `replay_expected`. The copy has no answers.
18. `SetEventState`: published to draft is refused with 422 "Members answered this event. Cancel it instead." when the event has a row in `event_answers`. Cancelled to published keeps the answers.
19. `Community::removeUser` deletes the rows of the member in `event_answers` for that community.

The API:

20. Constants in `ApiRoutes.php`, mirrored in `constants.ts`: `COMMUNITY_SPACE_EVENT_ANSWER` (`.../events/{event}/answer`) and `COMMUNITY_SPACE_EVENT_ANSWERS` (`.../events/{event}/answers`). All four routes are in the signed-in group with scoped bindings.
    1. `GET .../answer`, `events:read`: 200 with `data` `{ answer, updated_at }`, and `answer` null when the member did not answer. It needs `view` and a membership (403 for a person who is not a member).
    2. `PUT .../answer`, `events:write`, `throttle:event-answers`: body `{ answer }`. 200 with the same `data`. `PUT` because the member replaces their one answer (ADR 0011, point 3).
    3. `DELETE .../answer`, `events:write`, `throttle:event-answers`: 204.
    4. `GET .../answers`, `event-answers:read`: rule 21.
21. `GET .../answers` needs `viewAnswers` (403 for the others, 404 by rule 3). Query: `answer` (`yes`, the default, or `replay`), `limit` and `offset` (ADR 0011). `data`: the members with that answer as `AuthorResource`, sorted by name, then by username. `metadata`: the pagination and `counts` `{ yes, replay, no }` [Q]. No name for `no`, with any parameter. No time of an answer.
22. A new resource `EventAnswerResource` for the model `EventAnswer`, with `#[UseResource]`, and its Zod schema `event-answer.schema.ts` in `libs/common` (rules.md, Architecture 6).
23. The limiter `event-answers`: `Constants::EVENT_ANSWERS_PER_MINUTE` (30) per member [Q: the number].
24. The ability `event-answers:read` goes in `JetstreamServiceProvider`, in `API_TOKEN_PERMISSIONS` with its label in `API_TOKEN_PERMISSION_LABELS` (`libs/common/src/lib/types/jetstream/jetstream-inertia.intf.ts:186`) and in the API README. The label: "Read the answers to the events that you host or manage" [Q: the words]. It is not in the default set.
25. Each controller method has its docblock and attributes, so that the OpenAPI document describes the body, the answers and the errors. `capacity` and `replay_expected` get a `BodyParameter` on the create and the update. The text of `location_visibility` says that `going` includes the members who answer Yes.
26. Bruno: "Events - Answer - Get", "Events - Answer - Set", "Events - Answer - Set - Full event", "Events - Answer - Clear", "Events - Answers - List". Update "Events - Create" and "Events - Update".

The live update:

27. New constant `SpaceContentChanged::EVENT_ANSWERS_CHANGED` = `space.event.answers.changed`. Its payload holds `event_cuid` only. `author_cuid` is null: the cuid of the member who answered never goes on the channel.
28. It is sent after the commit, on the space channel, only when the number of Yes answers of an event with a limit changed [Q, question 5]. It is never sent for another change of an answer.
29. A change of `capacity`, of `replay_expected` or of `location_visibility` sends `space.event.updated`, as every change of a published event does today.

The data rules:

30. `config/retention.php`: `event_answers_days`, env `RETENTION_EVENT_ANSWERS_DAYS`, default 90, with its row in `.env.example` and `SELF_HOSTING.md`. `App\Support\Retention` gives the policy the placeholder `{{retention.event_answers}}`. `retention:prune` gets the step `event_answers`: it deletes the answers of the events whose end is older than the period, and `--dry-run` counts them.
31. `ExportUser` adds `event_answers` to `account.json`, next to `quiz_results`: for each answer, the cuid and the title of the event, its community and space as the other lists give them, `answer`, `created_at`, `updated_at`. No host, no other member.
32. The policy, in both files, with the approved words. Section 2, a new item: "Your answers to events (yes, replay only or no), until {{retention.event_answers}} after the event or until you leave the community." and "The host and the team of an event see who answered yes or replay only. They do not see when you open the meeting." and "We do not record who attends a meeting." The retention section gets its line with the same placeholder. Change no other word of the policy.

### The data model and the migration

1. One new migration. It changes no existing row in a way that cannot be undone.
2. `events` gets `capacity` (integer, nullable, with a check `capacity IS NULL OR capacity BETWEEN 1 AND 5000`) and `replay_expected` (boolean, default false). The value lists are literals, as in the migration of E1a.
3. `event_answers`: `id`, `cuid` (unique), `event_id`, `user_id`, `community_id` (all three constrained, `cascadeOnDelete`, with the reason in a comment), `answer` (string, with a check on `yes`, `replay`, `no`), timestamps. Unique `(event_id, user_id)`. Index `(user_id, answer)` for "My events" of part E2c. Index `(event_id, answer)` for the counts.
4. `down()` drops the table and the two columns.
5. Model `App\Models\EventAnswer` with its docblock, `GeneratesCuid`, the enum `App\Enums\KnowiiEventAnswer` (aligned with `EVENT_ANSWERS` in `libs/common`), a factory with the states `yes()`, `replay()`, `no()`. `Event` gets `answers()`, the casts of the two columns, and both columns in `$fillable`.

### Scope (in the commit)

1. Rules 1 to 32 and the migration.
2. New PHP files: the migration, `EventAnswer`, `KnowiiEventAnswer`, `EventAnswerFactory`, `AnswersEvents`, `AnswerEvent`, `EventAnswerResource`, `EventAnswerApiController` (the four routes).
3. Changed PHP files: `Event`, `EventPolicy`, `EventResource`, `CreateEvent`, `UpdateEvent`, `SetEventState`, `DuplicateEvent`, `EventApiController` (the attributes), `UpcomingEvents` and the list queries (rule 6), `SpaceContentChanged`, `Community`, `ExportUser`, `PruneExpiredDataCommand`, `Retention`, `config/retention.php`, `Constants`, `ApiRoutes`, `routes/api.php`, `AppServiceProvider` (the limiter and the binding of the contract), `JetstreamServiceProvider`.
4. `libs/common`: the constants, `eventSchema` with the five fields, `event-answer.schema.ts`, `API_TOKEN_PERMISSIONS` and its label. `apps/knowii/src/test-utils/events.ts` gets the five fields, so that the specs of E1b still pass. No component and no page changes.
5. Pest, new `tests/Feature/EventAnswersTest.php` (the action) and `EventAnswerApiTest.php` (the routes), and additions to the existing files:
   1. The matrix of `answer` and `viewAnswers` in `PolicyTest.php`: the owner, an admin, a moderator, the host, a member, a person who is not a member, in a public, a `members` and a `groups` space, with the member in and out of the group.
   2. Each refusal of rule 10, in its order, and that a draft and an event that does not exist give the same 404 body on the four routes.
   3. The answers: set, change, clear, the same answer again, `replay` with and without `replay_expected`, an event that started and did not end (accepted), an event that ended, a cancelled event, "Publish again" keeps the rows.
   4. The limit: the last place, a Yes at the limit (422), from `replay` to `yes` at the limit (422), a Yes that frees a place, a limit under the Yes count (nobody removed, `places_left` 0), the same Yes again at the limit (200).
   5. The lock: a test listens to the queries of one answer and finds `for update` on `events` inside a transaction. A second test inserts a second row for the same member and event and gets the unique violation.
   6. The address of a `going` event, on every read that carries an event (the list of the space, the list of the community, one event, the four Inertia screens of `EventPagesTest.php`): present for a Yes, for the host, the owner and an admin. Absent for `replay`, `no`, no answer, a moderator without an answer, a person who is not a member and a visitor. Absent again after the member changes Yes to No, and after the member leaves.
   7. No leak, as scans of the whole JSON: a member, a moderator, a person who is not a member and a visitor never get `capacity`, a name or a count from any route. `my_answer` is the reader's own. The payload of the broadcast has exactly `event_cuid`.
   8. `GET .../answers`: the order, the paging, `counts`, no name for `no`, 403 for a moderator and a member, 404 for a draft of another host, a member who left is gone, a member who lost the group is gone, a token without `event-answers:read` is refused.
   9. The query count of rule 6. The broadcast of rule 28: sent for a Yes on an event with a limit, not sent without a limit, not sent for a `no`.
   10. `removeUser` deletes the answers of that community only. A deleted account deletes its answers. The return to draft with an answer (422). `DuplicateEvent` copies the two fields and no answer.
   11. Retention: the step deletes an answer of an event that ended 91 days ago and keeps one of 89 days, `--dry-run` deletes nothing (`RetentionTest.php`). The export holds the answers of the member and no other name. `ApiTokenAbilitiesTest`, `BrunoCollectionTest` and `OpenApiDocumentTest` pass.
6. Vitest: `event.schema.spec.ts` (the five fields, null for a visitor), `event-answer.schema.spec.ts`, and the label of the new ability.
7. Documents:
   1. ADR 0053 gets a part "The answers and the places (#1515)", as it got "The screens (#1514)". No new ADR: E2 builds what ADR 0053 lists under "What waits for the later slices", inside the same model, and it breaks no rule of `rules.md`. The part holds rules 1 to 8, 13, 18, 21, 27 and 28, each [Q] answer, and the alternatives (a unique partial index in place of the lock, a count for every member).
   2. `documentation/architecture/roles-and-permissions.md`: the rows "Answer an event" and "Read the answers", and the changed row of the meeting address. `backend.md`: one paragraph on the lock.
   3. `documentation/design/events/design.md` and `documentation/design/README.md`: the status line says that the server part of E2 is built.
   4. The API README: the ability and the four routes.
8. The browser check (definition of done 1). No screen changes, so check that nothing broke and that the API works with a session:
   1. Open the list of an events space, an event page and the form of the host, in both themes, at 360 px and at 1920 px. They look as before, with no error in the console.
   2. With a token that has `events:write`, answer Yes to an event of the demo data with the Bruno request. Read the answer back. Set the limit to 1 with another account, answer Yes with a third account, and read "This event is full."
   3. Set an event to `going` with the Bruno request "Events - Update". Open its page as a member without an answer (no address), then as the member who answered Yes (the address shows).
9. Pictures for the owner: none. This part has no screen. Parts E2b and E2c have pictures.

### NOT in this commit

1. Parts E2b and E2c below. No word of `event-words.ts` changes here.
2. E3 (#1516): comments, the replay link, the notes, "cancel it first" before a delete, the sentence about recordings.
3. E4 and E5 (#1482): every notification row, every reminder, every email, `reminded_24h_at`, `reminded_1h_at`, `events:remind`, the words "This tells 14 members".
4. A waiting list, a promotion, paid events (#1413). A CSV of the answers. A calendar feed (E7). An import of old answers (decision 16).
5. A record of who joined a meeting or who opened a file, in any form.

## Part E2b: the screens of the answers (scope list)

1. The answer group on the event page, under the host: a `fieldset` "Your answer" with the toolkit `SegmentedControl` (a `radiogroup`), "Yes", "Replay only" (only with `replay_expected`), "No". It saves at once through the API client. `SaveStatus` or a status region says "Saved: Yes". An error is `role="alert"` and the old choice returns (design 6.4 point 1, 11.4).
2. A way to clear the answer [Q: a button "Clear my answer" under the group, since a radio cannot be unchecked].
3. The sentence of question 4 under the legend, before the first answer.
4. "N places left" and "Full" as text next to the group. "Yes" on a full event is `aria-disabled` with the reason as text. A past or cancelled event shows the old answer as text in place of the group (design 11.4 point 2).
5. A visitor reads "Sign in to answer" with `SignInPrompt`. A person who is not a member gets no group (design 5.H).
6. "Answers" for the host, the owner and the admins (`permissions.canViewAnswers`): the names of Yes, then of Replay only, each a link to the member page, the number of No, with `LoadMoreButton` and `CountBadge` of the toolkit when pull request #1687 is merged. It loads on `space.event.answers.changed` when the page is open.
7. The cards of the list: "You said Yes" (or the other answer) and "N places left" (design 6.1 point 4).
8. The form of the host: "Who sees the meeting address and the place" (two radios: "Every member" and "Members who answer Yes"), "Limit of places" with "N members answered Yes" under it, and "A replay will exist" (a `Switch` or a `Checkbox`). The two hints "Only members see it." follow the choice. `meetingForHostAndTeam` becomes "The meeting address is for the members who answer Yes." [Q: the words].
9. `knowiiApiClient.communities.events.answer` (`get`, `set`, `clear`) and `answers.list`, new words in `event-words.ts` in its style, the Inertia props `permissions.canViewAnswers`, Pest for the props per role, Vitest with axe for each rule, the stories of a changed toolkit block.
10. Documents and pictures: the keyboard script of `release-test.md` gets "answer, change, clear", the screen atlas gets the event page with an answer, a full event and the list of the host, with answers in `atlas-seed.php`. The owner approves pictures of the answer group (3 states), the list of the host and the form fields, at 360 px and 1920 px, in both themes.

## Part E2c: the calendar file and "My events" (scope list)

1. `App\Support\IcsFile`: design 6.5 point 3, with no new dependency. Lines end with CRLF, are folded at 75 bytes without a cut inside a character, and `\`, `,`, `;` and line breaks are escaped. A title cannot add a line or a property.
2. `GET communities/{community}/spaces/{space}/events/{event}/calendar-file`, signed in, `events:read`, like the download of a course material (ADR 0037). 404 by `assertVisible`, 403 for a person who is not a member. A draft answers 422 [Q].
3. The meeting address and the place are in `DESCRIPTION` and `LOCATION` only when `viewLocation` passes for this reader. `LOCATION` holds the place, else the meeting address [Q]. The response is never cached by a shared cache (`Cache-Control: private, no-store`).
4. `STATUS:CANCELLED` for a cancelled event, `SEQUENCE` from `events.sequence`, `UID` stable for the life of the event. The name of the file is the slug and the date of the start in the zone of the event [Q]. Nothing is stored and nothing names the member in a log.
5. "Add to calendar" on the event page for a member who can see the event, also when it is cancelled, and on each row of "My events". No card menu [Q, question 6].
6. `GET user/events`, `events:read`: `answer` (`yes`, `replay`, `host`, default all three), `scope` (`upcoming`, `past`), `limit`, `offset`. Each event carries its community and its space. It holds only events that the member can see today, in every community of the member, and never an event of a community that the member left.
7. The page `/events` ("My events") in the signed-in group, with its entry in the user menu: "Upcoming" and "Past" with `LinkTabs`, the name of the community when the member is in several, the labels "You said Yes", "You said Replay only", "You host", "Draft", "Cancelled", and an empty state.
8. Pest: the bytes of the file for the instant of design section 5 (16:00 UTC), a title with a comma, a line break and 300 characters, a cancelled event, a higher `SEQUENCE` after a move, the address present and absent per reader of a `going` event, a visitor (401), a draft. `GET user/events` per filter, across two communities, without a space that the member cannot read any more.
9. Vitest with axe for the page and the button, the API client, the constants. Bruno and OpenAPI for the two routes. The file route is the first one that answers `text/calendar`: say in the report how `OpenApiDocumentTest` describes it.
10. Documents and pictures: a part "The calendar file and My events (#1515)" in ADR 0053, the keyboard script ("add to calendar", "open My events"), the screen atlas entry of `/events`. The owner approves pictures of "My events" (with events, empty) and of the button. A person opens one file in two calendar applications before the merge and writes the result in `release-test.md`.

## How to work

1. `git fetch origin`, then branch `feat/1515-events-e2a` from `origin/main`. ONE commit, no push, no pull request. If the working tree holds changes of another person, stop and ask: no stash, no reset. Find the text when a line number moved.
2. Docker on this Linux machine, from `~/wks/knowii`: `docker compose exec -T -u sail laravel.test <command> </dev/null`. Do not start, stop or restart containers. No `migrate:fresh` and no seeder on the development database. Run the new migration with `php artisan migrate`, and prove `down()` in a test database only.
3. PHP commands in the container: `composer format`, `composer lint`, `composer security:php`, and the whole Pest suite ONE time with `php artisan test --parallel`. Front-end commands on the host: `npx nx format --all`, `npm run lint:common`, `npm run tsc`, `npm run tsc:spec`, `npm run test:all`. Never run the whole Pest suite and the whole Vitest suite at the same time.
4. A piped test command hides a failure: use `bash -c "set -o pipefail; ..."`. Without `--parallel`, `php artisan test` takes ONE path.
5. Write the leak tests of scope 5.6 and 5.7 before the code of rules 2 and 5, and see them fail.
6. `InstanceNeutralNamesTest` reads `tests/` too. Never name a third-party community or calendar product. Use `example.com` addresses and `example` hosts.
7. The commit: a conventional type, one of the allowed scopes (all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build), a header of at most 100 characters that does not start with a capital letter. Suggested: `feat(api): events, the answers of members and the limit of places (slice E2a)`. Footer, after a blank line: `Refs #1515` (the issue closes with part E2c). No trailer line that names a tool or a co-author.
8. If a rule cannot be built as written, skip that rule, build the rest, and say why in the report. Do not invent product rules.
9. If you find a weakness in the code of `main` while you work, do not write it in the commit, in a test name, in the ADR or in an issue. Handle it as `SECURITY.md` says, and tell the reviewer in private.

## Questions for the owner

1. `replay_expected`. The answer "Replay only" needs it, and the design builds it in E3. Add the column and the API field in E2a and the form field "A replay will exist" in E2b, or ship E2 with Yes and No only? Recommendation: add it in E2. It is one column and one field, and E3 keeps the replay link and the notes.
2. A cancelled event. Can a member change or clear an old answer while the event is cancelled? Recommendation: no. The answers are frozen until "Publish again", and the page shows the old answer as text.
3. A member who loses the access to the space and stays in the community. Keep the answer, or delete it? Recommendation: keep it. No read shows it (not the list of the host, not "My events", no file), it still holds its place, it comes back when the access comes back, and the retention step deletes it. A delete cannot be undone when a manager removes a group by mistake.
4. The sentence before the answer. The design says "The host of this event sees your answer (Yes or Replay only). Other members do not." The owner and the admins also see it. Recommendation: "The host and the team see your answer (Yes or Replay only). Other members do not.", in the words of the approved policy sentence.
5. The live signal. The design sends `answers.changed` to every reader of the space at each answer. A member who watches the connection learns the moment of each answer. Recommendation: send it only when the number of Yes answers of an event with a limit changes, with the cuid of the event alone. The list of the host loads again when the host opens it.
6. Part E2c. "Add to calendar" on the event page and on the rows of "My events", and not in a card menu (a card has no menu)? "My events" with "Upcoming" and "Past", and with my own drafts under "You host"? Recommendation: yes to both.

## Your final report (max 400 words)

1. The commit hash and `git show --stat`.
2. The leak tests of scope 5.6 and 5.7: their failure before the code and their pass after it.
3. How the lock of rule 13 is proved, and what you saw with two real requests at the same time, if you tried it.
4. Each choice where the brief left room, and each [Q] rule that you built in another way.
5. The results of Pest, PHPStan, Pint, the security scan, the type checks, Vitest and the format check, with the exact text of anything that failed.
6. The three steps of the browser check, each with what you saw.
7. The text of the row for the register of processing.
8. Anything of the scope not built, and why.
