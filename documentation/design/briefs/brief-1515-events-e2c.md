# Brief: events, slice E2c: the calendar file and "My events" (knowii-oss/knowii#1515)

State: DRAFT. Parts E2a (the server of the answers and of the limit of places, #1694) and E2b (the screens of the answers, #1697) are merged in `main` (commit `83cb6037`). This brief is the full text of part E2c. It is the last part of slice E2: its commit closes #1515.

You build two things. A member downloads one calendar file per event ("Add to calendar"). A member opens the page "My events", which lists the events that the member answered Yes or Replay only and the events that the member hosts, in all the communities of the member. ONE commit on a local branch: a reviewer reads your diff afterwards. Paths are relative to `~/wks/knowii`.

Until the owner answers the "Questions for the owner" at the end, build what each recommendation and each [W] word says. [W] marks a word that the design does not give. [Q] marks a rule that a question for the owner can change. Each [W] word of a screen is one value in `event-words.ts`: a later change is one line.

## Decided by the owner on 2026-11-20 (question 6 of the E2 brief)

1. "Add to calendar" is on the event page and on each row of "My events". It is not in a card menu: a card has no menu.
2. "My events" has "Upcoming" and "Past". It holds the own drafts of the member, under the label "You host".

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md` (Architecture 2 to 6 and 11, Data 1, 2, 6, 7 and 8, Front-end 2, 3, 4, 8, 9, 10 and 12, and the definition of done), `documentation/conventions/naming.md`.
2. `documentation/design/briefs/brief-1515-events-e2.md`: "What the design decides for E2" (points 17 to 21), "What the design leaves open" (points 12, 13 and 16), the scope list of part E2c, and "Decided by the owner".
3. `documentation/design/events/design.md`: sections 1.2, 4.1, 4.3, 5.B, 5.C, 5.D, 5.H, 6.2, 6.5, 6.6, 6.8, 7.1, 7.3, 7.4, 7.7, 9.1, 9.2, 9.3, 10.1, 11.2, 11.3 and 11.6. Then `release-test.md` of the same folder.
4. ADRs: 0053 (all of it, with the parts "The screens" and "The answers and the places"), 0011, 0022, 0037, 0044 (points 4, 8, 9 and 13), 0048, 0049.
5. The standard: RFC 5545 (https://www.rfc-editor.org/rfc/rfc5545), sections 3.1, 3.1.4, 3.3.5, 3.3.11, 3.4, 3.6.1, 3.7.2, 3.7.3, 3.7.4, 3.8.1.5, 3.8.1.7, 3.8.1.11, 3.8.1.12, 3.8.2.2, 3.8.2.4, 3.8.4.3, 3.8.4.6, 3.8.4.7, 3.8.7.2, 3.8.7.3, 3.8.7.4 and 8.1. RFC 7986, sections 5.3 and 8. RFC 6266, sections 4.2 and 4.3. Read the text of each section before you write its rule. Do not write a rule of the standard from memory.
6. The code of the events on `main`: `app/Models/Event.php`, `app/Models/EventAnswer.php`, `app/Policies/EventPolicy.php`, `app/Http/Resources/EventResource.php`, `app/Support/EventAnswers.php`, `app/Support/UpcomingEvents.php`, `app/Actions/Events/EventPublication.php`, `UpdateEvent.php` and `SetEventState.php`, `app/Http/Controllers/API/EventApiController.php` and `EventAnswerApiController.php`, `app/Http/Controllers/Inertia/EventController.php` and `SpaceController.php` (lines 78 to 98).
7. The download of a course material: `app/Http/Controllers/API/CourseMaterialApiController.php` (lines 114 to 147), `routes/api.php` (line 197), `tests/Feature/CourseMaterialsTest.php` (lines 239 to 272), `apps/knowii/src/Components/SpacePages/CourseMaterials.tsx` (lines 225 to 238).
8. The pages of a signed-in account across communities: `routes/web.php` (lines 51 to 62), `DashboardController.php`, `NotificationsPageController.php`, `apps/knowii/src/Pages/Notifications/Index.tsx`, `apps/knowii/src/Layouts/AppLayout.tsx` (lines 137 to 224), `apps/knowii/src/Components/MainMenu.tsx`.
9. The screens of the events: `apps/knowii/src/Pages/Events/Show.tsx`, every file of `apps/knowii/src/Components/Events/`, `libs/common/src/lib/types/event.schema.ts`, `libs/common/src/lib/utils/time-zone.fn.ts`, the toolkit blocks `LinkTabs`, `LoadMoreButton`, `EmptyState`, `Badge`, `Card`, `Button` with their stories.
10. Tests: `EventApiTest.php`, `EventAnswerApiTest.php`, `EventAnswersTest.php` (lines 387 to 417), `EventPagesTest.php`, `ApiTokenAbilitiesTest.php`, `BrunoCollectionTest.php`, `OpenApiDocumentTest.php`, `tests/Unit/InstanceNeutralNamesTest.php`.

## What the design and the E2 brief decide (do not change it)

1. One calendar file per event, in the iCalendar format (RFC 5545), written by a small class `App\Support\IcsFile` with no new dependency (design 6.5 point 3, decision 10).
2. The route is `GET communities/{community}/spaces/{space}/events/{event}/calendar-file`, with the ability `events:read`. The access is checked first. A visitor gets no file (design 6.5 points 2 and 5, 9.1).
3. The file holds one `VEVENT`: `UID`, `DTSTAMP`, `DTSTART` and `DTEND` in UTC with `Z` and no `VTIMEZONE`, `SUMMARY`, `DESCRIPTION` (300 characters of plain text and the address of the page), `URL`, `LOCATION`, `SEQUENCE` (`events.sequence`), `STATUS` (`CONFIRMED` or `CANCELLED`). No `VALARM`. No `METHOD` (design 6.5 points 3 to 5).
4. The meeting address and the place are in the file only when this reader can see them. The server cuts them. The client hides nothing (design 3.7, 7.3).
5. The name of the file is the slug of the title and the date: `weekly-review-office-hour-2026-11-18.ics` (design 5.B point 3, 6.5 point 4).
6. A cancelled event gives a file with `STATUS:CANCELLED` (design 5.D point 2). A moved event gives a file with the same `UID` and a higher `SEQUENCE` (design 5.C point 3).
7. The platform stores nothing about who opens a calendar file (design 10.1 point 1).
8. No calendar feed in this slice (design 6.6, decision 10).
9. "My events" is the page `/events`, with its entry in the user menu. It lists the events with my answer Yes or Replay only in all my communities, and the events that I host. It shows the name of the community when I am in several. The API is `GET user/events` with `answer` (`yes`, `replay`, `host`) and `scope`, with the ability `events:read` (design 6.8).
10. Every time on a screen is in the zone of the reader, with the zone in words, in a `time` element (design 4.3, 11.2, ADR 0048).
11. A member who leaves a community loses the answers of that community. A host who left keeps `host_id` and loses every right of a host (design 7.7, ADR 0053 point 5).

## What exists today (facts, with the evidence)

Line numbers are those of `main` at `83cb6037`.

The event and its rules:

1. `events.sequence` exists (`database/migrations/2026_11_18_100000_create_events_table.php:63`, an integer, default 0). `UpdateEvent` adds 1 when `starts_at`, `ends_at`, `meeting_url` or `place` changes (`app/Actions/Events/UpdateEvent.php:24,88-90`). `SetEventState` adds 1 at each real change of state, also at the first publication (`SetEventState.php:78-79`). A new event and a copy start at 0 (`CreateEvent.php:93`, `DuplicateEvent.php:70`). A change of the title, of the description, of `location_visibility` or of the host adds nothing.
2. `events.updated_at` moves at every save of the event. An answer of a member does not touch the row of the event.
3. `EventPolicy::view` (`app/Policies/EventPolicy.php:29-36`): a draft is for its host, the owner and the admins. A published or cancelled event follows `SpacePolicy::view`. A signed-in person who is not a member passes `view` in a public space.
4. `EventPolicy::viewLocation` (lines 71 to 81): never a visitor, never a person who is not a member. With `members`, every member who can see the event. With `going`, the host, the owner, the admins and a member whose answer is `yes`. The answer counts also when the event is cancelled or over.
5. `EventPublication::assertVisible` gives 404 with the body of an event that does not exist to a signed-in reader who cannot see the event (`app/Actions/Events/EventPublication.php:59-64`). Every route of the answers calls it first, then answers 403 "Only the members of the community answer an event" to a person who is not a member (`app/Http/Controllers/API/EventAnswerApiController.php:46-52`).
6. `EventResource` asks `viewLocation` once and cuts `meeting_url` and `place` (`app/Http/Resources/EventResource.php:51,66-68`). It gives `space` (`cuid`, `slug`, `name`) when the relation is loaded (line 83). It gives no field for the community. `eventSchema` mirrors it (`libs/common/src/lib/types/event.schema.ts:59`).
7. `EventAnswer::scopeOfReaders` writes the reading rule of a space for the database: a member of the community who can read the space today (`app/Models/EventAnswer.php:127-161`). It is tied to the column `event_answers.user_id`. A test holds it together with the policy (`tests/Feature/EventAnswersTest.php:387-417`). No scope of `Event` gives the same rule for one user.
8. `EventAnswers::preload` loads the answers of the reader and the Yes counts of a list with two queries (`app/Support/EventAnswers.php:25-52`). `EventAnswers::forList` takes ONE community (lines 65 to 73).
9. `Event::scopeUpcoming` (the end is now or later, the drafts first, then the next start first) and `Event::scopePast` (the end is before now, the newest start first) hold the order of every list (`app/Models/Event.php:331-349`). `Event::scopeVisibleTo` takes one community (lines 309 to 321).
10. `Community::removeUser` deletes the answers of the member in that community (`app/Models/Community.php:365,378`).
11. `App\Support\Excerpt::of($text, $length)` gives one line of plain text from Markdown or HTML, cut at a character with "…" (`app/Support/Excerpt.php:10-19`).

The API and the download of a file:

12. Routes of the events: three public reads (`routes/api.php:81-83`), the writes and the answers in the signed-in group with scoped bindings (lines 205 to 216). The constants are in `app/ApiRoutes.php:163-178`. No route of an event answers a file. No route `user/events` exists (the `user/...` routes are at `routes/api.php:90-110`, their constants at `ApiRoutes.php:77-104`).
13. The download of a course material is the model (`CourseMaterialApiController.php:118-147`): a route of the signed-in group with an ability (`routes/api.php:197`), the access check first, then the headers `Content-Type`, `X-Content-Type-Options: nosniff` and `Cache-Control: private, no-store`, and the file as an attachment. Its tests read the headers and refuse a visitor (401), a person who is not a member (403) and a token without the ability (`CourseMaterialsTest.php:239-272`).
14. The page gives the member a plain link: an `a` element with `href` (the API address) and `download` (`CourseMaterials.tsx:231-238`). The address comes from `knowiiApiClient.communities.materials.downloadUrl` (`libs/common/src/lib/api/knowii-api-client.ts:1158-1159`). The session of the browser signs the request. No JavaScript reads the file.
15. `events:read` is in the default set of a token (`app/Providers/JetstreamServiceProvider.php:163,177`). The limiters `events` and `event-answers` count per member (`app/Providers/AppServiceProvider.php:406-413,511-516`, `app/Constants.php:235-238`).
16. `composer.json` and `composer.lock` hold no package that writes iCalendar. No file of `app/` writes `text/calendar`.

The screens:

17. The signed-in group of `routes/web.php` holds `/dashboard` (line 56) and `/notifications` (line 59). Both controllers give the page the communities of the account and nothing else (`DashboardController.php:19-33`, `NotificationsPageController.php:18-26`). No route `/events` exists. The routes of an event are `events.show`, `events.create` and `events.edit` (lines 77, 78 and 99).
18. The main menu has one entry, "Dashboard" (`apps/knowii/src/Layouts/AppLayout.tsx:137-141`). The user menu has "Catch up" (only with `catchUpSince`), "Edit profile", "Notification settings", "API tokens" and "Log out" (lines 145 to 224). Each entry is a `DropdownMenuItem` with an Inertia `Link`, an icon and `aria-current="page"` on the open page.
19. The event page shows, in this order: the title, the state line, the actions, the cover, `EventWhen`, `EventHost`, `EventAnswer`, `EventMeeting`, the description (`apps/knowii/src/Pages/Events/Show.tsx:163-207`). It knows a member from `community.is_member` (line 56).
20. `EventCard` shows the cover, the labels (`EventStateBadges`, `EventAnswerBadges`), the title as the link, the time (`EventTime`, short form) and the host (`apps/knowii/src/Components/Events/EventCard.tsx:24-52`). It has no line for the community or the space and no place for an action.
21. `EventList` is the list of ONE space: `LinkTabs` for "Upcoming" and "Past" (`EventList.tsx:92-98`), `LoadMoreButton` with 20 events per load, the focus on the first new card and the status "20 more loaded" (lines 245 to 268), and the live updates of the space channel (lines 229 to 243).
22. `EventTime` writes a time in the zone of the account, else of the browser (`Components/Events/EventTime.tsx`, `formatEventStart` and `formatEventRange` in `libs/common/src/lib/utils/time-zone.fn.ts:219,530`).
23. `event-words.ts` holds "Upcoming", "Past", "Load more", "Load older", "Draft", "Cancelled", "You said Yes" and the texts of the empty lists. It holds no "Add to calendar", no "My events" and no "You host".

## What the standard says (each rule with its section)

RFC 5545, unless another document is named.

1. Section 3.1: a content line ends with CRLF. The standard asks for lines of at most 75 octets without the line break. A longer line is folded: CRLF, then ONE space, then the rest. The reader removes each CRLF that a space or a tab follows. The note of the section warns against a cut inside a UTF-8 sequence of several octets.
2. Section 3.1.4 and section 8.1: the character set of the file is UTF-8, and the `charset` parameter of the content type is given on a MIME transport. Section 8.1 registers `text/calendar` and the file extension `ics`.
3. Section 3.3.11 (the type TEXT): a backslash is written `\\`, a semicolon `\;`, a comma `\,`, a line break `\n`. A colon is NOT escaped. Control characters are not part of a text value (a space and a tab are).
4. Section 3.3.5 (the type DATE-TIME): a UTC time is `19980119T070000Z`. A time with a UTC offset is not valid. The `TZID` parameter is never on a UTC time. So a file with UTC times needs no `VTIMEZONE`.
5. Section 3.4: the file starts with `BEGIN:VCALENDAR` and ends with `END:VCALENDAR`. Sections 3.7.3 and 3.7.4: `PRODID` and `VERSION` are each given one time. `VERSION` is `2.0`.
6. Section 3.7.2: `METHOD` and the `method` parameter of the content type go together. Without `METHOD`, the content type has no `method`.
7. Section 3.6.1: in a `VEVENT`, `DTSTAMP` and `UID` are required. `DTSTART` is required when the file has no `METHOD`. `DESCRIPTION`, `LOCATION`, `SEQUENCE`, `STATUS`, `SUMMARY`, `URL` and `LAST-MODIFIED` are optional, each at most one time. `DTEND` and `DURATION` never go together.
8. Section 3.8.2.2: `DTEND` has the type of `DTSTART` and is later than `DTSTART`.
9. Section 3.8.7.2: `DTSTAMP` is in UTC. In a file without `METHOD` it is the time of the last revision of the data in the store, and it equals `LAST-MODIFIED` (section 3.8.7.3). So this file gives `DTSTAMP` and no `LAST-MODIFIED`.
10. Section 3.8.7.4: `SEQUENCE` starts at 0 and goes up at each significant revision. Section 3.8.1.11: the `STATUS` of a `VEVENT` is `TENTATIVE`, `CONFIRMED` or `CANCELLED`.
11. Section 3.8.4.7: `UID` is persistent and unique in the world. RFC 7986, section 5.3, changes its advice: a `UID` holds no data that identifies a user, a host or a domain. An opaque value is under 255 octets and holds only letters, digits and "-" (the `iana-token` of section 3.1).
12. Section 3.8.4.6: `URL` has the type URI, one time in a `VEVENT`. The escapes of TEXT are not for a URI.
13. Section 3.8.4.3: `ORGANIZER` is for a group-scheduled event. It is not in a component that is on the calendar of one user only. It also needs a calendar address (an email address). So this file has no `ORGANIZER` and no `ATTENDEE`: the answer stays in Knowii, and the file never names a member.
14. RFC 6266, sections 4.2 and 4.3: `Content-Disposition: attachment` with `filename`. A name in ASCII needs no `filename*`.

## Rules

Each rule is one sentence that a test can prove.

### Who gets the file

1. New constant `ApiRoutes::COMMUNITY_SPACE_EVENT_CALENDAR_FILE` (`.../events/{event}/calendar-file`), mirrored in `constants.ts`. The route is a `GET` in the signed-in group with scoped bindings, next to the routes of the answers, with `ability:events:read` and `throttle:calendar-files`.
2. The order of the refusals: 401 for a visitor, also in a public space. Then 404 by `EventPublication::assertVisible`, with the body of an event that does not exist. Then 403 for a signed-in person who is not a member of the community: "Only the members of the community get the calendar file of an event" [W]. Then 422 for a draft: "This event is a draft. It has no calendar file." [W].
3. A draft gives no file to anybody, also to its host, the owner and the admins. Why: the design gives the file two states only (`CONFIRMED` and `CANCELLED`), a draft is not told to members and its time is not final, and the routes of the answers refuse a draft in the same way (422, "This event is a draft. It takes no answers.").
4. A published event and a cancelled event give a file. An event that ended gives a file too.
5. A token without `events:read` gets 403. A token of the default set gets the file.
6. The limiter `calendar-files`: `Constants::CALENDAR_FILES_PER_MINUTE` (30) per member, built like `event-answers`. The 31st request of a minute gets 429.

### What the file holds

7. `App\Support\IcsFile` is a final class with no state and no query. It gets the values, and it returns the text of the file. It knows nothing of a request, a user or a policy. The controller decides what each reader gets, then calls it. Slice E5 uses the same class for the attachment of its mails.
8. The content lines, in this order, each one time:
   1. `BEGIN:VCALENDAR`
   2. `VERSION:2.0`
   3. `PRODID:-//Knowii//Events//EN`
   4. `BEGIN:VEVENT`
   5. `UID:` the cuid of the event, and nothing else [Q, question 1].
   6. `DTSTAMP:` rule 10.
   7. `DTSTART:` the start, and `DTEND:` the end, as `Ymd\THis\Z` in UTC: `20261118T160000Z`.
   8. `SUMMARY:` the title. For a cancelled event: "Cancelled: " and the title [Q, question 2].
   9. `DESCRIPTION:` rule 11.
   10. `LOCATION:` rule 12. The line is absent when it has no value.
   11. `URL:` the absolute address of the event page, from `route('events.show', ...)`.
   12. `SEQUENCE:` `events.sequence`.
   13. `STATUS:CONFIRMED` for a published event, `STATUS:CANCELLED` for a cancelled event.
   14. `END:VEVENT`
   15. `END:VCALENDAR`
9. The file has no other line: no `METHOD`, no `VTIMEZONE`, no `TZID`, no `VALARM`, no `ORGANIZER`, no `ATTENDEE`, no `LAST-MODIFIED`, no `CREATED`, no `CATEGORIES`, no `X-` property.
10. `DTSTAMP` is the later of two instants, in UTC: `events.updated_at`, and `updated_at` of the answer of this reader to this event when a row exists. Why the answer: on a `going` event, a Yes of the reader changes what the file holds (the address), so it is a revision of the data of this file. It is never the time of the request: two downloads of the same data give the same bytes.
11. `DESCRIPTION` is built from these parts, joined with line breaks:
    1. `Excerpt::of($event->body, 300)`. With an empty description, this part and the empty line after it are absent.
    2. An empty line.
    3. "Event page: " and the address of rule 8.11 [W].
    4. "Meeting: " and the meeting address [W], only when `viewLocation` passes for this reader and the event has a meeting address.
12. `LOCATION`, only when `viewLocation` passes for this reader: the place when the event has one, else the meeting address, else no line [Q, question 3]. When the event has both, the place is in `LOCATION` and the meeting address is in `DESCRIPTION`.
13. The controller asks `Gate::forUser($user)->check('viewLocation', $event)` ONE time and passes the meeting address and the place to `IcsFile` only when it passes. It never builds its own rule. A reader who does not pass gets a file in which the meeting address and the place appear nowhere.
14. The file never holds: the name, the username, the email address or the cuid of a member, of the host or of the reader. It never holds the answer of the reader, the places that are left, the limit, the name of the community or of the space as a line, or the full description.
15. Two readers with the same rights get the same bytes for the same event. A reader who passes `viewLocation` and a reader who does not get two files that differ only in `DESCRIPTION`, `LOCATION` and, by rule 10, `DTSTAMP`.

### Text, lines and bytes

16. `SUMMARY`, `DESCRIPTION` and `LOCATION` are TEXT values. `IcsFile` escapes them in this order: `\` becomes `\\`, then `;` becomes `\;`, `,` becomes `\,`, and each line break (CRLF, a lone CR, a lone LF) becomes the two characters `\n`. A colon stays. A tab becomes one space. Every other control character (below U+0020, and U+007F) is removed.
17. `URL` is a URI value: it gets no escape of rule 16. The application builds it from its own route. The meeting address is typed by a host, so it goes only in TEXT values (rules 11 and 12), never in `URL`.
18. A title, a place or a meeting address can never add a line or a property. A test gives the title `Review, part; two\r\nATTENDEE:mailto:someone@example.com` and reads back, after unfolding, the same 15 content lines of rule 8 and the summary `Review\, part\; two\nATTENDEE:mailto:someone@example.com`.
19. Each line of the file is at most 75 octets without its CRLF. A longer content line is folded: the next line starts with ONE space, and that space counts in its 75 octets. `IcsFile` never cuts inside a UTF-8 character: each line of the file is valid UTF-8 alone.
20. Every line ends with CRLF, also the last one. The file holds no lone LF and no byte order mark.
21. Unfolding the file (remove each CRLF that a space follows) gives the content lines of rule 8 exactly.

### The response

22. Status 200 with the body of rule 8 and these headers: `Content-Type: text/calendar; charset=utf-8` (no `method` parameter), `Content-Disposition: attachment; filename="<name>"`, `X-Content-Type-Options: nosniff`, `Cache-Control` with `private` and `no-store`. No shared cache keeps the file: it differs per reader.
23. The name of the file is `<slug>-<date>.ics`: `events.slug`, then the date of the start as `Y-m-d` in the zone of the event (`events.time_zone`) [Q, question 3]. Every reader gets the same name. When the slug is empty, the name starts with `event`. The name holds only what the slug holds (lower-case letters, digits and "-"), so it needs no `filename*`.
24. The route stores nothing: no file on a disk, no cache entry, no row, no job. It writes no log line. Nothing records who asked for a file.
25. The file is built at each request. It is never signed, never given a public address, and never reachable without a session or a token of the member.
26. A refusal (401, 403, 404, 422, 429) is the JSON error of the API, never a file.

### "Add to calendar"

27. A new component `Components/Events/AddToCalendar.tsx` renders ONE plain link: an `a` element with `href` (the address of rule 1, from `knowiiApiClient.communities.events.calendarFileUrl`) and the attribute `download`. It is not an Inertia `Link` and it runs no request in JavaScript. It looks like a secondary button of the toolkit, with a calendar icon that is `aria-hidden`.
28. It is a link and not a `button`: it loads a file from an address, as the link of a course material does.
29. On the event page it shows directly after `EventWhen` and before `EventHost`, when `community.is_member` is true and the state of the event is not `draft`. It shows also on a cancelled event and on an event that ended.
30. Its visible text is "Add to calendar". On the event page that text is its accessible name: the title of the event is the `h1` of the page.
31. On a row of "My events" the visible text is the same, and the accessible name is "Add to calendar: Weekly review office hour" [W]: the title follows in a visually hidden `span`, so that the visible text starts the name.
32. A visitor, a signed-in person who is not a member, and every reader of a draft get no link and no text in its place. The answer group already tells a visitor to sign in.
33. The link is at least 44 px high, shows its focus, and works with Enter.
34. A refusal of the server after the page loaded (the member lost the access, the event went back to draft, the limiter) shows what the browser shows for a download that failed. This part adds no error screen for it. Say in the report what you saw.

### The API of "My events"

35. New constant `ApiRoutes::USER_EVENTS` (`user/events`), mirrored in `constants.ts`. The route is a `GET` in the signed-in group, next to `user/entitlements`, with `ability:events:read`. A new controller `UserEventApiController::index` serves it.
36. Query parameters: `scope` (`upcoming`, the default, or `past`. Another value: a field error, "Use upcoming or past", as `EventApiController` does), `answer` (absent, or `yes`, `replay` or `host`. Another value: a field error, "Use yes, replay or host" [W]), `limit` (default 10, maximum 100) and `offset` (ADR 0011).
37. Without `answer`, the list holds every event of rules 39 and 40. With `yes` or `replay`, only the events with that answer of the reader. With `host`, only the events that the reader hosts.
38. `data` is a list of `EventResource` without the description. `metadata.pagination` is the pagination of ADR 0011. Each event carries `space` (`cuid`, `slug`, `name`) and the new field `community` (`cuid`, `slug`, `name`).
39. Through an answer: the events to which the reader answered `yes` or `replay`, in the state `published` or `cancelled`. Never a draft. Never an event with the answer `no` or with no answer.
40. As the host: the events with `host_id` of the reader, in every state. The own drafts are in the list. A draft of another host is never in the list, also for the owner and the admins.
41. For both ways, the reader is a member of the community of the event AND can read its space today. This is the reading rule of `EventAnswer::scopeOfReaders`.
42. So the list never holds: an event of a community that the reader left (as a member or as the host), an event of a `groups` space that the reader cannot read any more, an event of a public space of a community of which the reader is not a member. When the access comes back, the event is in the list again.
43. The reading rule for the database exists ONE time. Move the rule of `scopeOfReaders` to one place that both `scopeOfReaders` and a new scope of `Event` for one user use. The test of `EventAnswersTest.php:387-417` gets a twin for the new scope: the database and `SpacePolicy::view` agree for the owner, an admin, a moderator, a member, a person who is not a member, in a public, a `members` and a `groups` space.
44. `upcoming` is `Event::scopeUpcoming` and `past` is `Event::scopePast`: the same limit between the two and the same order as the list of a space [Q, question 4]. An event is in exactly one of the two lists.
45. An event that the reader hosts and answered is ONE item.
46. The database filters, sorts and cuts the page. The number of queries of a page does not grow with the number of its events: the events of the page come from one query, with one more for the total, and their hosts, covers, spaces and communities load for the whole page. The answers of the reader and the Yes counts load with `EventAnswers::preload` (two queries). A test reads a page of 5 events and a page of 20 events of the same spaces and counts the same number of queries.
47. Each event passes through `EventResource`, which cuts the meeting address and the place per event and gives `my_answer`, `can_answer`, `places_left` and `capacity` as it does on every other route. The list gives nothing that `GET .../events/{event}` does not give to the same reader for the same event.
48. Put the query in one class, `App\Support\MyEvents`, as `UpcomingEvents` does. The API controller and the page controller both use it.
49. `EventResource` gets the field `community` when the relation is loaded, and `eventSchema` gets it as an optional object. The lists that exist set that relation for the policy, so they carry the field too. It holds only what a reader of the event already knows.

### The page "My events"

50. A new web route `/events`, named `events.index`, in the signed-in group of `routes/web.php` next to `/notifications`. A new controller `Inertia\MyEventsController::index` serves the page `Pages/Events/Index.tsx`. A visitor goes to the sign-in page.
51. Props: `events` (the first 20 events of the open scope, with their pagination, built as `SpaceController` builds the events of a space), `scope` (`upcoming` or `past`. Any value of `?scope` other than `past` means `upcoming`), and `showCommunity` (true when the account is in more than one community).
52. The page uses `AppLayout` like the page "Notifications". Its only `h1` is "My events". Under it, one sentence: "The events that you answered Yes or Replay only, and the events that you host." [W].
53. `LinkTabs` with the name "My events" and two links: "Upcoming" (`/events`) and "Past" (`/events?scope=past`). The open one has `aria-current="page"`. A press keeps the focus on the pressed link, and a polite status says which list opened and how many events it holds, as `EventList` does.
54. The list is a real list (`ul`) with the name "Upcoming events" or "Past events". Each item is one `EventCard`.
55. `EventCard` gets three optional props, and the list of a space does not change: `context` (one line of text above the title), `actions` (a place under the host line) and `cover` (false: the card shows no cover). A row of "My events" has no cover.
56. The context line is the name of the space, with the name of the community before it when `showCommunity` is true: "Example community, Office hours" [W: the form]. With one community: "Office hours". It is text, not a link.
57. The title is the link to the event page (`EVENT_URL` with the slugs of `community` and `space` of the event).
58. The time is `EventTime` in the short form: the zone of the account, else of the browser, with the zone in words and the long form for a screen reader. No row shows a second time for the zone of the host.
59. The labels, as text with `Badge`, in this order: "Draft", "Cancelled" and "Happening now" (`EventStateBadges`), then "You host" [W] when `event.host` is the reader, then "You said Yes" or "You said Replay only" and the places (`EventAnswerBadges`). An event that the reader hosts and answered shows both.
60. `actions` holds "Add to calendar" (rule 31) on every row that is not a draft.
61. `LoadMoreButton` shows while the list has more: "Load more" for "Upcoming", "Load older" for "Past". It loads the next 20 with `knowiiApiClient.user.events.list`. After the load, the focus is on the title of the first new row, and the status says "20 more loaded" with the real number. An event that is already shown is not added a second time.
62. The empty lists use `EmptyState`: "No upcoming events" with "The events that you answer Yes or Replay only, and the events that you host, show here." [W], and "No past events" with "Your events that ended show here." [W]. They have no action.
63. The page opens no live connection. It shows what the server gave when it loaded. Say so in ADR 0053.
64. The page sends no request when it renders. It sends one request per press on the load button.
65. Every word of the page is in `event-words.ts`. Each state is text, never a color alone. The page passes `axe-core` in its spec in each state. At 360 px nothing scrolls sideways.

### The entry in the navigation

66. The user menu gets the entry "My events", with a calendar icon, as the first entry after "Catch up" (when "Catch up" shows) and before "Edit profile". It is built like the other entries (`AppLayout.tsx:172-182`), with `aria-current="page"` on `/events`, also with `?scope=past`.
67. Every signed-in account gets the entry, also an account in no community. A visitor has no user menu, so a visitor has no entry.
68. The main menu does not change.
69. `MY_EVENTS_URL` (`events.index`) joins the route names of `constants.ts`.

### Privacy and access (each one is a test)

70. The file of a `going` event holds the meeting address and the place for the host, the owner, an admin and a member whose answer is Yes. It holds neither for a member with `replay`, with `no` or with no answer, and for a moderator without a Yes. After the member changes Yes to No, the next file holds neither.
71. For each reader and each event, the meeting address is in the file if and only if `meeting_url` is not null in `GET .../events/{event}` for the same reader. The same holds for the place, and for each item of `GET user/events`.
72. A scan of the whole file for each reader finds no name, no username, no email address and no cuid of any member.
73. A member who left the community: `GET user/events` holds no event of that community, as an answer and as a host. The calendar file of an event of that community answers 403 in a public space and 404 in another space.
74. A member who lost the access group of a `groups` space: no event of that space in `GET user/events`, and 404 for its calendar file, with the body of an event that does not exist. The answer row stays. After the access is back, the event and the file are back.
75. A deleted account: its answers and its tokens go with it. An event that it hosted stays without a host: it is in "My events" of nobody as a hosted event, its row for a member who answered Yes says "Hosted by Deleted member", and its file is the same file (the file never named the host).
76. The owner and an admin never get the draft of another host in `GET user/events`. A member gets 404 for the file of a draft that is not theirs. The host of a draft gets 422.
77. A member of two communities gets the events of both in one list, each with its own `community`. A member never gets an event of a community of another member.
78. `GET user/events` and the page `/events` give 401 and the sign-in page to a visitor. A token without `events:read` gets 403 from both API routes. A token with `events:read` and without `event-answers:read` gets no `capacity`, as on the other routes.

## The words

D marks a word of the design. [W] marks a proposal for the owner.

1. D: "Add to calendar", "My events", "Upcoming", "Past", "You said Yes".
2. From E2b, already approved: "You said Replay only", "Draft", "Cancelled", "Happening now", "Load more", "Load older", "No upcoming events", "No past events".
3. [W] The label of a hosted event: "You host" (the design writes "I host", the E2 brief writes "You host").
4. [W] The accessible name of the link on a row: "Add to calendar: {title}".
5. [W] The sentence under the title of the page: "The events that you answered Yes or Replay only, and the events that you host."
6. [W] The context line of a row: "{community}, {space}", and "{space}" alone for an account in one community.
7. [W] The two descriptions of the empty lists: "The events that you answer Yes or Replay only, and the events that you host, show here." and "Your events that ended show here."
8. [W] In the file: "Event page: {address}", "Meeting: {address}", and "Cancelled: {title}" as the summary of a cancelled event. These three are constants of `IcsFile`, not of `event-words.ts`.
9. [W] The messages of the server: "Only the members of the community get the calendar file of an event", "This event is a draft. It has no calendar file.", "Use yes, replay or host".

## Scope (in the commit)

1. Rules 1 to 78 and the words. No migration: `events.sequence`, `events.slug` and the index `(user_id, answer)` of `event_answers` exist.
2. New PHP files: `app/Support/IcsFile.php`, `app/Support/MyEvents.php`, `app/Http/Controllers/API/UserEventApiController.php`, `app/Http/Controllers/Inertia/MyEventsController.php`. The calendar file is a method of `EventApiController` or a small controller of its own: choose, and say why in the report.
3. Changed PHP files: `Event` (the scope of rule 43), `EventAnswer` (its scope uses the shared rule), `EventResource` (`community`), `ApiRoutes`, `routes/api.php`, `routes/web.php`, `Constants` (`CALENDAR_FILES_PER_MINUTE`), `AppServiceProvider` (the limiter).
4. `libs/common`: the two API paths and `MY_EVENTS_URL` in `constants.ts`, `eventSchema` with `community`, `knowiiApiClient.communities.events.calendarFileUrl(communityCuid, spaceCuid, eventCuid)` and `knowiiApiClient.user.events.list(scope, limit, offset)`. `apps/knowii/src/test-utils/events.ts` gets `community`.
5. New client files: `Pages/Events/Index.tsx`, `Components/Events/MyEventList.tsx` (it follows `EventList`, without the space and without the live updates), `Components/Events/AddToCalendar.tsx`, and one spec for each.
6. Changed client files: `Pages/Events/Show.tsx`, `Components/Events/EventCard.tsx`, `EventStateBadges.tsx` or a small sibling for "You host", `event-words.ts`, `Layouts/AppLayout.tsx`, and their specs. No toolkit block changes. If a block must change, it gets its story with a `play` function, and you say why in the report.
7. Pest, a unit test `tests/Unit/IcsFileTest.php` for the class alone:
   1. The bytes of the whole file for a fixed event, as one literal text in the test: the instant of design section 5 (start `2026-11-18T16:00:00Z`, end `17:00:00Z`), the title "Weekly review office hour", a description, a place, a meeting address, `sequence` 3, a fixed `DTSTAMP`. The test compares byte for byte.
   2. The same event without the place and the meeting address, with an empty description, and cancelled: three more literal files.
   3. Rule 16: a backslash, a comma, a semicolon, a colon, CRLF, a lone LF, a tab and a control character in each TEXT value.
   4. Rule 18: the title that tries to add a line.
   5. Rules 19 to 21: a title of 200 characters with letters of 2, 3 and 4 octets. Every line is at most 75 octets and valid UTF-8, every line ends with CRLF, and the unfolded text equals the content lines.
   6. A description of 2 000 characters gives 300 characters in `DESCRIPTION`, and the page address is still whole.
8. Pest, a feature test `tests/Feature/EventCalendarFileTest.php` for the route:
   1. The headers of rule 22 and the name of rule 23, with an event whose start is on another date in UTC than in its zone (23:30 UTC, zone `Asia/Singapore`).
   2. Rule 2 in its order: a visitor (401, also in a public space), a member who cannot read the space (404, the same body as a cuid that does not exist), a person who is not a member in a public space (403), a draft for its host, for the owner and for an admin (422), a draft for a member (404).
   3. Each reader of rule 70 on a `going` event, and every member on a `members` event. Rule 71 as one data set over the readers: the owner, an admin, a moderator, the host, a member with each answer and with none.
   4. A cancelled event (`STATUS:CANCELLED`, the summary of rule 8.8, 200). An event that ended (200).
   5. `SEQUENCE` after a move through `UpdateEvent` is higher, `UID` is the same, and `DTSTAMP` is later.
   6. Rule 10: two requests give the same bytes. After the reader answers Yes on a `going` event, `DTSTAMP` is the time of the answer and the address is in the file.
   7. Rule 72, the scan. Rule 24: the request writes no row (count the write queries), no file (a fake disk stays empty) and no log line (a spy on the log).
   8. The limiter (429 at the 31st request). The token cases of rule 5.
   9. Rules 73 to 76 for the file.
9. Pest, `tests/Feature/UserEventsApiTest.php` and `MyEventsPageTest.php`:
   1. Each value of `answer` and of `scope`, the two field errors, the pagination.
   2. Rules 39 to 45 with a member of two communities: a Yes, a Replay only, a No, no answer, a hosted event, an own draft, the draft of another host, a cancelled event with a Yes, an event that ended.
   3. Rules 73 to 78 for the list. Rule 47: each item equals the event of `GET .../events/{event}` for the same reader, field by field, without the description.
   4. The twin test of rule 43. The query count of rule 46.
   5. The page: its props for a member of one community (`showCommunity` false) and of two, `?scope=past`, the first 20 events with the pagination, and the redirect of a visitor.
   6. `ApiTokenAbilitiesTest`, `BrunoCollectionTest` and `OpenApiDocumentTest` pass, with a new case in `OpenApiDocumentTest` for the two routes.
10. Vitest, with axe in each spec of a screen:
    1. `AddToCalendar.spec.tsx`: rules 27 to 33. The `href`, the attribute `download`, the name with and without the title, no element for a visitor, a person who is not a member and a draft.
    2. `Show.spec.tsx`: the order time, "Add to calendar", host. The link per reader and per state.
    3. `MyEventList.spec.tsx` and `Index.spec.tsx`: rules 52 to 65. The two links and `aria-current`, a row of each kind with its labels, the context line with and without the community, the load of 45 events over three pages with the focus and the status, a failed load, the two empty states, no request at render.
    4. `EventList.spec.tsx`: a card of the list of a space looks as before (a cover, no context line, no action).
    5. `AppLayout` or its spec of the user menu: the entry, its place, `aria-current` on `/events` and on `/events?scope=past`.
    6. `libs/common`: `event.schema.spec.ts` (`community` present and absent), the spec of the API client (the address of the file, the list with its parameters), the constants.
11. Bruno, in `documentation/api`: "Events - Calendar file", "Events - Calendar file - Draft", "Events - My events" and "Events - My events - Past".
12. OpenAPI. The calendar file is the first route that answers `text/calendar`. Read what the generator writes for it. The document must say: 200 with the media type `text/calendar`, and the answers 401, 403, 404, 422 and 429. `GET user/events` gets its three `QueryParameter` attributes. Say in the report how you described the file answer.
13. The API README: the two routes, and one sentence that a script with `events:read` reads a file and that a calendar application cannot subscribe to it.
14. The screen atlas (`tools/screen-atlas/`): `atlas-seed.php` gives the member of the atlas a Yes, a Replay only, a hosted event, an own draft and a cancelled event with a Yes, and `atlas-unseed.php` removes them. `screens.json`, area `events`: "My events, upcoming", "My events, past", "My events, empty" and "Event page, a member with Add to calendar".
15. Documents:
    1. ADR 0053 gets a part "The calendar file and My events (#1515)", as it got "The answers and the places". It holds rules 2, 3, 8, 10, 12, 13, 14, 23, 24, 39 to 43, 49 and 63, each [Q] answer, and these alternatives: a library for the file (a new dependency for 15 lines of output), `DTSTAMP` as the time of the request (the bytes then change at each download), a `UID` with the host name (RFC 7986, section 5.3), a `VTIMEZONE` with a local time (more lines, and no gain for one instant), the `CONFERENCE` property of RFC 7986 for the meeting address (few applications read it).
    2. The same part says why this slice has no feed: see "NOT in this commit", point 2.
    3. `documentation/architecture/backend.md`: one paragraph on `IcsFile` and on the shared reading rule. `frontend.md`, the part "Events": the page, the link and the three props of `EventCard`.
    4. `documentation/architecture/roles-and-permissions.md`: the row "Get the calendar file".
    5. `documentation/design/events/release-test.md`: the specs in the table, the keyboard steps "add to calendar from the event page", "open My events from the user menu", "change to Past", "load more", "add to calendar from a row", a new part "Slice E2c (#1515): what was run", and under "What a person still does" the point "open one file in two calendar applications and write the result here".
    6. `documentation/design/events/design.md` and `documentation/design/README.md`: the status line says that slice E2 is built.
16. The browser check (definition of done 1). Run `npm run build` on the host first. Then, in both themes, at 360 px and at 1920 px, with no sideways scroll and no CSP error in the console:
    1. As a member, open an event, reach "Add to calendar" with Tab, press Enter. The browser saves `<slug>-<date>.ics`.
    2. Read the saved file as bytes on this machine (`xxd` or `od -c`): CRLF at each line end, no line over 75 octets, the lines of rule 8.
    3. Parse the saved file on this machine with an iCalendar parser that you install in a temporary folder outside the repository. It is never added to `package.json` or `composer.json`. The parser gives the same start, end, summary, sequence and status as the event page. Never paste a file in a validator on the web, and never send it to another service.
    4. If this machine has a desktop calendar application, import the file in a local calendar that syncs with no account. Use an event of the demo data only. Check the hour in the zone of the machine, then download the file of the same event after a move and import it again: the entry moves, and no second entry appears. If no such application exists, say so: this step then waits for a person.
    5. As a member of a `going` event without an answer: the file has no address. Answer Yes, download again: the address is in `DESCRIPTION` and `LOCATION`.
    6. Cancel the event as the host. As the member: the link is still there, and the file says `STATUS:CANCELLED`.
    7. As a visitor and as a person who is not a member: no link. Ask the address of the file by hand: 401 and 403.
    8. Open the user menu, choose "My events". Check the rows of an account in two communities, "Past", "Load more" with the keyboard, the two empty states with a new account, and "Add to calendar" on a row.
    9. Leave one of the two communities with the member, then open "My events": no row of that community, and no community name on the rows.
    10. Read the accessibility tree of the browser for one row: the heading link, the labels as text, the time in its long form, and the name "Add to calendar: <title>".
17. Pictures for the owner. Make ONE HTML page, `~/Downloads/knowii-2026-11-20-events-e2c/index.html`, with one row per state and four pictures per row (dark 360, light 360, dark 1920, light 1920). A picture is named `<number>-<state>-<theme>-<width>.png`. Do not put the pictures in the repository. The rows:
    1. `my-events-upcoming`: "Upcoming" for a member of two communities, with a Yes, a Replay only, a hosted event, an own draft, a cancelled event with a Yes and an event that happens now.
    2. `my-events-one-community`: the same list for a member of one community (no community name).
    3. `my-events-past`: "Past" with more than 20 events and "Load older".
    4. `my-events-empty`: the empty state of "Upcoming".
    5. `user-menu`: the open user menu with the entry "My events" marked as the open page.
    6. `event-page-link`: the event page of a member, with "Add to calendar" under the time.
    7. `event-page-cancelled`: a cancelled event with the link.
    8. `file-text`: not a picture. The text of one downloaded file of the demo data, in a `pre` block, as a member who sees the address and as a member who does not.
       The page has 7 rows of pictures (28 pictures) and one row of text.

If the reviewer wants a smaller diff, the one clean cut is two commits. First the file: rules 1 to 34 and 70 to 72, with `IcsFile`, its route, the link on the event page, their tests and documents, footer `Refs #1515`. Then "My events": rules 35 to 69 and 73 to 78, with the link on the rows, footer `Closes #1515`. Rules 73 to 76 for the file go with the first commit.

## NOT in this commit

1. E3 (#1516): comments, the replay link, the notes, "cancel it first" before a delete. E4 and E5 (#1482): every notification row, every reminder, every email, and the calendar file as an attachment of a mail.
2. A calendar feed that an application subscribes to (design 6.6, slice E7). It is a different privacy question. The file of this part is asked by the member, with a session or a token, one event at a time, and each request passes the access check of that moment. A feed is one secret address that works without a login. It is a new credential outside the tokens of ADR 0022. It gives the list of the events of a member, with the meeting addresses, to every program and person that holds the address, at each fetch, also after the member left a community unless each fetch checks the access again. It needs a reset, limits, a line in the policy and its own ADR.
3. A signed public link to a file. A file for a visitor or for a person who is not a member.
4. "Add to calendar" in a card of the list of a space, in "Next events", in a notification or in a card menu.
5. A direct link that adds the event to the calendar of a named product. One file in the standard format serves every application.
6. `VALARM`, `METHOD`, `ORGANIZER`, `ATTENDEE`, `VTIMEZONE`, recurrence, one file with several events.
7. A change of when `events.sequence` goes up. A migration.
8. "My events" with the events that the member answered No or did not answer, with a search, a filter on the screen, a month grid, or live updates.
9. A record of who downloaded a file or who opened "My events", in any form.
10. A new toolkit block. An entry in the main menu.

## How to work

1. `git fetch origin`, then branch `feat/1515-events-e2c` from `origin/main`, and make sure that the commits of E2a and E2b are in it (`83cb6037` or later). ONE commit, no push, no pull request. If the working tree holds changes of another person, stop and ask: no stash, no reset. Find the text when a line number moved.
2. Docker on this Linux machine, from `~/wks/knowii`: `docker compose exec -T -u sail laravel.test <command> </dev/null`. Do not start, stop or restart containers. No `migrate:fresh` and no seeder on the development database. This slice has no migration.
3. PHP commands in the container: `composer format`, `composer lint`, `composer security:php`, and the whole Pest suite ONE time with `php artisan test --parallel`. Front-end commands on the host: `npx nx format --all`, `npm run lint:common`, `npm run tsc`, `npm run tsc:spec`, and the whole Vitest suite ONE time with `npm run test:all`. Write the summary of each full run to a file and read the file. Never run the whole Pest suite and the whole Vitest suite at the same time.
4. A piped test command hides a failure: use `bash -c "set -o pipefail; ..."`. Without `--parallel`, `php artisan test` takes ONE path. While you build, run one test file or one spec file at a time.
5. Run `npm run build` on the host before the first Pest run on the branch and before each browser check: the application runs from built assets, and a page without a new build shows the old screens.
6. Write the tests of rules 13, 18, 70, 71 and 73 to 76 before the code, and see them fail.
7. Use the toolkit blocks. Do not write a row of links, a load button, a label or an empty state by hand. A file of `Components/ui` imports nothing from the application (`boundary.spec.ts`).
8. `InstanceNeutralNamesTest` reads `tests/` too. Never name a third-party community, meeting or calendar product, in the code, in a test, in the atlas data, in a document or in the commit. Use `example.com` addresses and `example` names.
9. The commit: a conventional type, one of the allowed scopes (all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build), a header of at most 100 characters that does not start with a capital letter. Suggested: `feat(ui): events, the calendar file of an event and the page "My events" (slice E2c)`. Footer, after a blank line: `Closes #1515`. No trailer line that names a tool or a co-author.
10. If a rule cannot be built as written, skip that rule, build the rest, and say why in the report. Do not invent product rules.
11. If you find a weakness in the code of `main` while you work, do not write it in the commit, in a test name, in the ADR or in an issue. Handle it as `SECURITY.md` says, and tell the reviewer in private.

## Questions for the owner

Until the owner answers, the builder builds what each recommendation says. The owner approves the words and the pictures before the merge.

1. The `UID` of the file. The design says "the cuid of the event and the host name of the instance". RFC 7986 (section 5.3) says that a `UID` holds no host and no domain name. Recommendation: the cuid alone. It is unique, it is stable for the life of the event, and the address of the instance is in `URL` anyway.
2. A cancelled event. The file says `STATUS:CANCELLED`, as the design asks. Several calendar applications show nothing for that line when a person imports a file by hand. Add "Cancelled: " before the title in the file? Recommendation: yes. The words are the ones of the later notification line.
3. Two rules of the file that the E2 brief left open. `LOCATION` holds the place, else the meeting address, and the meeting address is always in the description for a reader who can see it. The date in the name of the file is the date of the start in the zone of the event, so every member gets the same name. Recommendation: yes to both.
4. The order of "Upcoming" in "My events". The own drafts first, then the next start first, as in the list of a space? Or by start only, with the drafts between the other events? Recommendation: as in the list of a space. A member reads one order everywhere, and a draft is not lost under the published events.
5. The [W] words and the look of a row (no cover, the community and the space as one line of text, the labels, "Add to calendar" also on a cancelled event and on a past event). The list is in "The words", points 3 to 9. Recommendation: approve them as proposed, with the pictures.

## Your final report (max 400 words)

1. The commit hash and `git show --stat`.
2. The tests of rules 13, 18, 70, 71 and 73 to 76: their failure before the code and their pass after it.
3. The literal file of scope 7.1, as text, and how you checked it against each rule of "What the standard says".
4. Each choice where the brief left room, each [W] word and each [Q] rule that you built in another way, and each rule that you did not build as written.
5. How the reading rule exists one time (rule 43), and the query count of rule 46.
6. How the OpenAPI document describes the file answer.
7. The results of Pest, PHPStan, Pint, the security scan, the type checks, Vitest and the format check, with the exact text of anything that failed.
8. The ten steps of the browser check, each with what you saw, and what waits for a person.
9. The path of the HTML page with the pictures, and the number of pictures.
10. Anything of the scope not built, and why.
