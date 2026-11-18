# Brief: events, slice E1a: the space type, the event and its API (knowii-oss/knowii#1513)

You build the server part and the API of events: a host creates and publishes an event, members read it. One commit on a local branch. You do not push it and you do not open a pull request. This slice has no new screen. Paths are relative to `~/wks/knowii`.

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md`, `documentation/conventions/naming.md`.
2. The issue: `gh issue view 1513 -R knowii-oss/knowii --comments` (if it prints nothing, use `gh api repos/knowii-oss/knowii/issues/1513 --jq .body`). Issue 1514 is slice E1b, the screens: it is not your work.
3. The approved design: the two comments of 2026-11-16 11:51 on issue 1388 (`gh api repos/knowii-oss/knowii/issues/1388/comments --jq '.[].body'`). Read sections 1.2, 2, 3, 4, 5.A, 5.H, 5.I, 7.1 to 7.4, 8.1, 9, 13, 14 and 16. The 18 decisions of section 13 are approved as recommended. The rules below win over the design where they differ.
4. ADRs in `documentation/adr/`: 0006, 0011, 0012, 0013, 0014, 0015 (the model: a space type with its own table), 0022, 0042 (points 7, 9, 33 to 42), 0044 (points 4 to 12), 0048 (points 1 to 4), `README.md`, `template.md`.
5. `documentation/architecture/backend.md` (Database, Actions, Types, API, WebSockets and Events, Notifications), `roles-and-permissions.md`, `high-level-design.md`, `documentation/api/README.md`.
6. A space type end to end, the `resources` type: `database/migrations/2026_11_10_120000_add_link_spaces_and_skipped_captures.php` (how the check of `spaces.type` is replaced), `app/Models/CommunityResource.php`, `app/Policies/CommunityResourcePolicy.php`, `app/Policies/SpacePolicy.php`, `app/Policies/PostPolicy.php`, `app/Actions/Resources/`, `app/Contracts/Resources/`, `app/Http/Controllers/API/CommunityResourceApiController.php`, `app/Http/Resources/PageResource.php`, `app/ApiRoutes.php`, `routes/api.php`, `app/Providers/AppServiceProvider.php`.
7. Pages and posts: `database/migrations/2026_11_10_100000_create_pages_table.php` (table, upload usages, `down()`), `app/Models/Page.php` (slug per space), `app/Actions/Pages/CreatePage.php`, `app/Actions/Posts/CreatePost.php` (`notify_now`), `app/Traits/UsesUploads.php`, `app/Events/Spaces/SpaceContentChanged.php`.
8. Notifications, the kind `lesson_new` end to end: `app/Enums/KnowiiNotificationKind.php`, `app/Actions/Notifications/Notify.php`, `LevelTwoFanOut.php`, `LevelTwoWriter.php`, `PruneNotifications.php`, `ChooseSpaceLevels.php`, `app/Jobs/FanOutNotifications.php`, `app/Support/NotificationItem.php`, `NotificationBundles.php`, `SpaceNotificationLevels.php`, `app/Http/Resources/NotificationResource.php`, `app/Actions/Spaces/UpdateSpace.php`, `database/migrations/2026_11_16_100000_create_notification_follows_and_space_levels.php`.
9. Time zones: `app/Support/TimeZones.php`, `libs/common/src/lib/utils/time-zone.fn.ts`.
10. Guard tests: `tests/Feature/ApiTokenAbilitiesTest.php`, `BrunoCollectionTest.php`, `OpenApiDocumentTest.php`, `ApiResourceArchitectureTest.php`, `PolicyTest.php`, `PublicSpacesTest.php`, `tests/Unit/MixedTypeUsageTest.php`.
11. Front-end: `libs/common/src/lib/types/space.schema.ts`, `notification.schema.ts`, `notification-follow.schema.ts`, `jetstream/jetstream-inertia.intf.ts`, `libs/common/src/lib/constants.ts`, `libs/common/src/lib/hooks/use-socket.ts`, `apps/knowii/src/Pages/Spaces/Show.tsx`, `apps/knowii/src/Components/Spaces/SpaceDialog.tsx`, `apps/knowii/src/Components/Notifications/` (`NotificationLine.tsx`, `notification-link.ts`, `notification-thread.ts`).

## Decisions (hard rules)

### Names and patterns (where the design and the code differ, the code wins)

1. An event is a dated item of an events space. Names: model `App\Models\Event`, table `events`, `EventPolicy`, `EventResource`, `EventApiController`, `EventFactory`, route parameter `{event}` (`ApiRoutes::PARAM_EVENT = 'event'`), relation `Space::events()` (the scoped route bindings need it). The folder `app/Events/` holds broadcast events, not this model. In a file that also uses the Laravel facade `Event`, import the facade under an alias.
2. The design names the contracts `CreateEvent`, `UpdateEvent` and so on. The code names a contract with a third-person verb and a plural noun. Use these contracts in `app/Contracts/Events/`: `CreatesEvents`, `UpdatesEvents`, `SetsEventStates`, `DeletesEvents`, `DuplicatesEvents`. Use these actions in `app/Actions/Events/`: `CreateEvent`, `UpdateEvent`, `SetEventState`, `DeleteEvent`, `DuplicateEvent`. Bind them in `AppServiceProvider` like `CreatesPages`.
3. The repository has no form request classes. Each action checks the permission with `Gate::forUser($user)->authorize(...)` and validates with `Validator::make(...)`. The controller only calls the contract and builds the answer with the `ApiResponses` trait.
4. Status codes follow the code, not the design: a field error is a `ValidationException` (HTTP 400), a refused business rule is a `BusinessException` (HTTP 422). The design says 422 for the time errors: they are field errors, so 400.
5. Limits are constants in `app/Constants.php`, mirrored in `libs/common/src/lib/constants.ts`: `MIN_LENGTH_EVENT_TITLE` 3, `MAX_LENGTH_EVENT_TITLE` 200, `MAX_LENGTH_EVENT_BODY` 20000, `MAX_LENGTH_EVENT_MEETING_URL` 2048, `MAX_LENGTH_EVENT_PLACE` 255, `MAX_LENGTH_EVENT_COVER_ALT` 125, `MAX_EVENT_DURATION_HOURS` 24.
6. New enums in `app/Enums/`, string-backed: `KnowiiEventState` (`draft`, `published`, `cancelled`) and `KnowiiEventLocationVisibility` (`members`, `going`).

### The space type

7. Add `KnowiiSpaceType::Events = 'events'`. A migration replaces the check `spaces_type_check` with a literal list that holds `events`. Add `events` to `SPACE_TYPES` and `SPACE_TYPE_LABELS` in `space.schema.ts` (name "Events", a one-line description, a calendar emoji). The type of a space never changes.
8. Search every switch on the type (`KnowiiSpaceType::` in `app/`, and `'discussion'`, `'chat'`, `'resources'` in `apps/knowii/src` and `libs/common/src`). For each one, decide what an events space does and name it in the report. `SpacePolicy::contribute` needs no change: the posting policy of the space decides who creates an event.

### The tables

9. Table `events`, in this slice: `id`, `cuid` (unique), `community_id` and `space_id` (cascade), `host_id` (to `users`, nullable, `nullOnDelete`), `title`, `slug` (unique with `space_id`), `body` and `body_html` (text), `starts_at` and `ends_at` (`timestampTz`), `time_zone` (string), `meeting_url` (string 2048, nullable), `place` (string 255, nullable), `location_visibility` (default `members`), `cover_upload_id` (to `uploads`, nullable, `nullOnDelete`), `cover_alt` (string 125, nullable), `state` (default `draft`), `sequence` (integer, default 0), `published_notified_at` (nullable), timestamps. Add check constraints for `state` and `location_visibility` with literal lists.
10. Indexes: `(space_id, starts_at)`, and a partial index on `(starts_at)` where `state = 'published'`.
11. Do not add these columns. Later slices add them: `capacity` (E2), `replay_expected`, `replay_url`, `notes`, `notes_html` (E3), `replay_notified_at`, `reminded_24h_at`, `reminded_1h_at` (E4). Do not create `event_answers`.
12. `upload_usages` gets `event_id` (nullable, cascade, index, unique with `upload_id`). Its check `upload_usages_one_item` covers four columns today (`post_id`, `comment_id`, `page_id`, `chat_message_id`): rewrite it with five.
13. `notifications` gets `event_id` (nullable, cascade, index). Its check `notifications_one_item` covers four columns today (`post_id`, `comment_id`, `chat_message_id`, `page_id`): rewrite it with five. Add the partial unique index `notifications_event_unique` on `(user_id, kind, event_id)` where `event_id` is not null.
14. Every `down()` puts the old state back, like the pages migration does.
15. The model uses `GeneratesCuid`, `HasFactory`, `Sluggable`, `UsesUploads`, `#[UseResource(EventResource::class)]`, hides `id` and the foreign keys, and has the class docblock of `backend.md` ("Model docblocks"). The slug comes from the title, is unique in the space (`scopeWithUniqueSlugConstraints`, as in `Page.php`) and never changes on a rename.

### Who may do what (`EventPolicy`)

16. "Host" means: `host_id` is the user, the user is still a member of the community, and the user can view the space. A host who left keeps `host_id` and loses every host right.
17. `view(?User, Event)`: a draft is for its host, the owner and the admins. A published or cancelled event follows `SpacePolicy::view` of its space. `EventPolicy` never tests access groups itself.
18. `update`: the owner, an admin (`CommunityRoles::isOwnerOrAdmin`), or the host. It covers edit, publish, back to draft, restore and duplicate.
19. `cancel` and `delete`: `update`, or `SpacePolicy::moderate` (a moderator with `delete_content` who can read the space). Such a moderator cancels and deletes the events of others and does not edit them. Add no new moderator right.
20. `viewLocation(?User, Event)`: never a visitor, never a person who is not a member. With `members`: every member who can view the event. With `going`: only the host, the owner and the admins in this slice (slice E2 adds the members who said Yes).
21. Create is `SpacePolicy::contribute` on the space. The action refuses another space type with the `BusinessException` text `Events can only be created in events spaces`.
22. Add one row per ability to the datasets of `tests/Feature/PolicyTest.php` and to the table of `documentation/architecture/roles-and-permissions.md` (owner, admin, moderator, member, outsider, visitor).

### Times

23. `starts_at` and `ends_at` are UTC instants. They are the truth. `time_zone` is the IANA zone in which the host entered the time. It never changes the moment. The API returns both instants in UTC (ISO 8601) and `time_zone` as stored.
24. The API takes `starts_at` and `ends_at` as ISO 8601 with an explicit offset (`2026-11-18T17:00:00+01:00`) and `time_zone`. A value without an offset is a field error. `time_zone` must pass `TimeZones::isValid()`.
25. The server converts to UTC and checks that the offset of each value is the real offset of `time_zone` at that instant. If not, it is a field error on that field.
26. A local time that does not exist in the zone (28 March 2027, 02:30 in `Europe/Brussels`) gets this field error, with the two hours computed from the zone data: `This time does not exist in this zone. Choose 01:59 or earlier, or 03:00 or later.`
27. A local time that exists twice (31 October 2027, 02:30 in `Europe/Brussels`) is accepted with either real offset. The offset decides the instant.
28. `ends_at` is after `starts_at` and at most 24 hours later.
29. On create, and when an update changes `starts_at`: a start before now is a field error for everyone but the owner and the admins. They may enter past events. A past event never notifies anybody.
30. "Past" is never stored. The resource gives `is_past`: true when `ends_at` is before now. No job, no command.
31. Use PHP date classes and Carbon only. Add no dependency.
32. The design writes "Tuesday 18 November 2026". That date is a Wednesday. Never copy a weekday from the design into a test: let the code compute it.

### The actions

33. `CreateEvent` input: `title` (required), `body` (Markdown, optional, default empty), `starts_at`, `ends_at`, `time_zone` (all required), `meeting_url` (optional, `url:http,https`), `place` (optional), `location_visibility` (optional, default `members`), `cover_upload_cuid` (optional), `cover_alt` (optional, empty means a decorative image), `host_cuid` (optional, default the creator), `state` (optional, `draft` by default, or `published`), `notify_now` (optional boolean, rule 55).
34. `host_cuid` names a member of the community who can view the space. Anything else is a field error. A creator may name another member as host and then has no host right on the event.
35. `cover_upload_cuid` names an upload of the same community. `null` removes the cover. The file comes from the existing route `POST communities/{community}/uploads`. Do not change that route: a token client needs `posts:write` for the upload today, also for the images of pages, and a separate issue tracks that. The server never loads an image from another origin.
36. The address and the place may be empty, also for a published event. The server never fetches the address.
37. `UpdateEvent` (PATCH) accepts every field of rule 33 but `state` and `notify_now`, each optional. It validates the times with the stored values for the fields that are absent. `sequence` goes up by 1 when `starts_at`, `ends_at`, `meeting_url` or `place` changes. An update tells nobody in this slice (kind 26 is slice E4).
38. `SetEventState` allows: `draft` to `published`, `published` to `draft`, `published` to `cancelled`, `cancelled` to `published`. Every other change is a `BusinessException`. A published event that started cannot go back to draft: `An event that started cannot go back to draft. Cancel it instead.` The same state again changes nothing and answers 200. `cancelled` needs the ability `cancel`. The other targets need `update`. Each real change adds 1 to `sequence`.
39. `DeleteEvent` needs `delete`. In this slice every event can be deleted (the rule "cancel it first" needs answers: slice E3). The database deletes the usage rows and the notification rows by cascade.
40. `DuplicateEvent` needs `update` on the source. It creates a draft in the same space with the same title, body, zone, address, place, `location_visibility`, cover, cover text and host, `sequence` 0 and no `published_notified_at`. Start and end are 7 days later at the same wall-clock time in `events.time_zone`, not 168 hours later. Example for a test: 23 March 2027 17:00 in `Europe/Brussels` is 16:00 UTC, and its copy on 30 March 17:00 is 15:00 UTC.
41. The body is rendered with `Markdown::render()` (no mentions, like pages), with the rule `MaxMarkdownImages`. Add `events` to `app/Actions/Content/RenderContentAgain.php`.
42. After each write of the body or the cover, the event has one usage row per upload of the body and one for the cover. `syncUploadUsages()` deletes every row first, so add the cover row after it. A test proves that `uploads:prune` keeps the cover and the body images of an event and deletes them after the event is gone.
43. The first publication calls `TracksSpaceVisits::wroteIn($user, $space)`: the space has new content. A draft does not.
44. A member who leaves keeps `host_id`. A deleted account sets `host_id` to null ("Deleted member"). The account option that deletes the content of a member does not delete hosted events. Test the three cases.

### The resource and what each reader gets

45. `EventResource` fields: `cuid`, `title`, `slug`, `state`, `starts_at`, `ends_at`, `time_zone`, `is_past`, `location_visibility`, `can_view_location`, `meeting_url`, `place`, `cover` (the `UploadResource` of the cover, or null), `cover_alt`, `host` (`AuthorResource`, or null), `space` (`cuid`, `slug`, `name`, when loaded), `body` and `body_html` (only with `->withLargeFields()`, else null), `created_at`, `updated_at`. Mirror it in a new `libs/common/src/lib/types/event.schema.ts` (`eventSchema`, the two enums) with a spec.
46. The resource cuts on the server: when `viewLocation` is false for `$request->user()`, `meeting_url` and `place` are null and `can_view_location` is false. The client never hides them. A resource takes only the model in its constructor (`ApiResourceArchitectureTest`).
47. A visitor and an outsider get in a public events space: title, cover, body, times, zone, state and the host as name, username and avatar. They never get the address, the place or a draft.
48. Lists never hold a draft for a reader who may not view it. One event that the reader may not view gets the same answer as the other public reads give (see `PublicSpacesTest.php`).
49. A test scans the JSON of every event route for the address and the place as a visitor, as an outsider, and as a member of a `going` event. It finds neither.

### The API (parity is a project rule: route, ability, Bruno request, OpenAPI text and Pest tests for each)

50. Routes under `/api/v1`, constants in `app/ApiRoutes.php` and API paths in `constants.ts`:

| Constant                          | Method and path                                     | What                                                                         | Ability        | Visitor |
| --------------------------------- | --------------------------------------------------- | ---------------------------------------------------------------------------- | -------------- | ------- |
| `COMMUNITY_SPACE_EVENTS`          | `GET communities/{community}/spaces/{space}/events` | list, `scope` `upcoming` (default) or `past`, `limit`, `offset`              | `events:read`  | yes     |
| `COMMUNITY_SPACE_EVENTS`          | `POST` same                                         | create, 201                                                                  | `events:write` | no      |
| `COMMUNITY_SPACE_EVENT`           | `GET .../events/{event}`                            | one event with the large fields                                              | `events:read`  | yes     |
| `COMMUNITY_SPACE_EVENT`           | `PATCH .../events/{event}`                          | change                                                                       | `events:write` | no      |
| `COMMUNITY_SPACE_EVENT`           | `DELETE .../events/{event}`                         | delete, 204                                                                  | `events:write` | no      |
| `COMMUNITY_SPACE_EVENT_STATE`     | `PUT .../events/{event}/state`                      | body `state`, `notify_now`                                                   | `events:write` | no      |
| `COMMUNITY_SPACE_EVENT_DUPLICATE` | `POST .../events/{event}/duplicate`                 | a draft copy, 201                                                            | `events:write` | no      |
| `COMMUNITY_EVENTS`                | `GET communities/{community}/events`                | upcoming events of every events space the reader can view, `limit`, `offset` | `events:read`  | yes     |

51. `upcoming`: `ends_at` is now or later, ordered by `starts_at` then `id`. `past`: `ends_at` is before now, newest start first. Another `scope` is a field error. Use `Pagination::fromRequest()` and `self::success()` with the pagination metadata. An endpoint that answers with a plain list of arrays uses `self::successList()`.
52. `COMMUNITY_EVENTS` never holds a draft. For a member: the events spaces of `SpaceTree::for()`. For a visitor and an outsider: the public events spaces, with the guards of `PostApiController::newest()` (`PublicCommunity::isMember()`, `PublicCommunity::ensureExists()`).
53. The three `GET` routes go in the group `auth.optional` with `throttle:public-reads`. The writes go in the `auth:sanctum` group with `scopeBindings()`. Add the three paths to the exact list in `ApiTokenAbilitiesTest.php` and to the "Public spaces" table of `documentation/api/README.md`.
54. Abilities (ADR 0022): `events:read` is in the default set of a new token, `events:write` is not. Add both to `JetstreamServiceProvider`, to `API_TOKEN_PERMISSIONS` and its labels in `jetstream-inertia.intf.ts`, and to the table of `documentation/api/README.md`. Do not add `event-answers:read` (E2).
55. `notify_now` (on create with `state` `published`, and on the state route) skips the wait of rule 62. It needs `SpacePolicy::moderate`, as for a post. The design says "managers": follow the code.
56. Each controller method has `#[BodyParameter]` and `#[QueryParameter]` texts like the other controllers. The create text says what a public space shows to anyone on the internet. Each route has a Bruno request in `documentation/api/` named `Events - <What>.bru`, with the main error cases (wrong offset, a time that does not exist, a refused state change).
57. `app/Actions/Communities/ExportCommunity.php` gets `events.json` (the events with their Markdown, no HTML, no internal id), with its line in the README of the export and a test in `CommunityExportTest.php`.

### Live updates

58. The design names `community.event.created`, `.updated`, `.deleted`. The code sends identifiers on the space channel through `SpaceContentChanged`. Add `EVENT_CREATED = 'space.event.created'`, `EVENT_UPDATED = 'space.event.updated'`, `EVENT_DELETED = 'space.event.deleted'` and `forEvent()` with the payload `event_cuid` and `author_cuid`. A state change sends `space.event.updated`. Add the three names to `use-socket.ts`. The payload never holds a title, an address or a place. Do not add `community.event.answers.changed` (E2).

### The level of an events space and the line of kind 25

59. An events space may be at "Everything", and "Everything" is its built-in default. Change `SpaceNotificationLevels::builtInDefault()`, `ChooseSpaceLevels` (the member may choose `all`), `UpdateSpace` (a manager may set `all` or `mine` as default), their error texts, `spaceNotificationLevelsFor()` in `notification-follow.schema.ts`, and the level options and help text of `SpaceDialog.tsx`. In `SpaceDialog.tsx` a new events space starts with the posting policy `moderators`.
60. Add `KnowiiNotificationKind::EventNew = 'event_new'`: level 2, reason `KnowiiNotificationReason::SpaceEverything`. Add no other event kind. Extend what `lesson_new` touches: `Notifies`, `Notify`, `FanOutNotifications`, `LevelTwoFanOut`, `LevelTwoWriter`, `NotificationItem`, `NotificationBundles`, `PruneNotifications::forBlock()`, the `Notification` model, `NotificationResource`, the notification list of the member export, and `notification.schema.ts`.
61. The line is written at the first publication of an event whose start is in the future, never for a draft, never inside `Notify::silenced()`. `published_notified_at` makes it run once: the action sets it when it queues the fan-out. The age guard of `Notify::notify()` reads `created_at`: it must not apply to an event, because a draft can be old.
62. The fan-out waits 5 minutes (`Constants::NOTIFICATIONS_POST_FANOUT_DELAY_MINUTES`), then reads the event again. It tells nobody when the event is not `published` any more or has started.
63. Recipients: the members whose level for the space is "Everything", who can view the space, who did not mute it. Never the host. Never a member who blocked the host. The actor of the row is the host: an event without a host tells nobody.
64. One row per member and event: `dedupe_key` is `event_new:event:<id>`. The rows of an events space do not bundle (this differs from `lesson_new`, because the line always shows one time and one title).
65. When an event goes back to draft, its `event_new` rows are deleted (add a method to `PruneNotifications`). A second publication writes no new row.
66. `NotificationResource`: `title` is the title of the event, `excerpt` comes from its body, `target` gets `event_cuid` and `event_slug`, and a new field `event_starts_at` (null for the other kinds).
67. The client builds the sentence (ADR 0042, point 9), in the zone of the account, else of the browser: `Tue 18 Nov, 17:00 <zone in words>: <title>`. Add a small format function to `time-zone.fn.ts` with a spec, and a zone parameter to `notificationSentence()`. The zone words are what `Intl` gives with `timeZoneName: 'long'` (the design writes "Central European Time", `Intl` can give "Central European Standard Time"): do not shorten them by hand.
68. `notificationHref()` sends an `event_new` line to the page of the space. Slice E1b changes it to the page of the event. The line belongs to no thread (`notification-thread.ts`): only the space can be muted.

### Front-end in this slice

69. No new page and no new screen. Change only what the rules above name, plus `EMPTY_STATES.events` in `Pages/Spaces/Show.tsx` ("No events yet"). Do not add API client methods or a form schema: they come with the screens.
70. Use the components of `apps/knowii/src/Components/ui`. If a building block is missing, add it there with a spec. Never build one ad hoc in a page or a feature component.
71. The changed components keep their specs green, with an axe check where the spec has one. The reviewer checks the space dialog and one `event_new` line in the browser after you: you do not need a browser.

### Documents

72. A new ADR with the next free number (0049 on `main` at `382ded2d`, but the toolkit pull request of #657 takes 0049 first: use 0050, and check `origin/main` and the local branches again before the commit). Title: "Events: a space type with its own table". It amends ADR 0012 (a new type), ADR 0042 point 34 (events spaces may be at "Everything" and have it as default, the kind `event_new`, `notifications.event_id`) and ADR 0044 point 9 (three more public reads). It records: the model and why (design 3.1), UTC instants with the zone of the host, the derived "past", the states, `location_visibility` and the server cut, one host, the two abilities, the event names of rule 58, what waits for E1b to E5. Add it to the index of `documentation/adr/README.md`.
73. Update `backend.md` (events, the broadcast names, the kind), `high-level-design.md` (the domain model), `roles-and-permissions.md`, `naming.md` (the domain word "event"), `documentation/api/README.md`.
74. The privacy policy, approved by the owner on 2026-11-17, exactly these words and nothing more: in `resources/markdown/policy.md`, section 2, item 4 ("Your content"), the list becomes "...chat messages, direct messages, pages, lessons, events (their title, description, date, place and meeting address), and uploaded images and their alternative text." Set the `updated` date of the policy as the earlier policy changes did (see `git log -3 -- resources/markdown/policy.md`), and extend `LegalPagesTest`. Do not change any other legal text, `config/retention.php` or `config/legal.php`.
75. Never name a third-party community product in code, tests, documents, the commit or the pull request. Say "the old tool". Simple English in every text.

### Tests (Pest for the server, Vitest for `libs/common` and the changed components)

76. Policy matrix (rule 22). Each action: success and the main refusals. Times: wrong offset, missing offset, unknown zone, a time that does not exist, a time that exists twice, more than 24 hours, a past start for a member and for an admin. States: each allowed change, each refused change, `sequence`. Duplicate across the change of 28 March 2027.
77. API: each route with a session, with a read-only token (reads pass, writes get 403), as a visitor and as an outsider in a public and in a members space, drafts in lists, another space type, an event of another space (404), the scan of rule 49.
78. Notifications: one line per member at "Everything", the wait, `notify_now` and its permission, not the host, a blocked host, a muted space, a member at "Only what is for me", a past event, `silenced()`, a second publication, back to draft, a retry of the job.
79. Also: the broadcast names and payloads, the levels of an events space, the upload usages (rule 42), the export, the three cases of rule 44.

## Out of scope

1. E1b (#1514): the list of an events space, the event page, the host form, the web route `events.show`, Inertia props, "Next events" on the community home, what a visitor sees on screen, API client methods, the form schema, indexing of event pages.
2. E2 (#1515): `event_answers`, the answers Yes, Replay only and No, `capacity` and "places left", the list of answers, `event-answers:read`, the `.ics` file and its route, `GET user/events`, the Yes part of `going`, "back to draft only if nobody answered", retention, the member export of answers, the policy words.
3. E3 (#1516): comments on events, the replay link, the notes, `replay_expected`, "cancel it first" before a delete.
4. E4 and E5 (#1482): the kinds 26 to 29, `events:remind`, the reminder guards, emails.
5. Later: the summary block and the "Catch up" line (E6), the calendar feed (E7), series, a month grid, waitlist, paid events, several hosts, moving an event, pin, reactions on an event, an import of old events.

## How to work

1. Branch from `origin/main` at `382ded2d`: `feat/1513-events-e1a`.
2. Docker needs the group. From `~/wks/knowii`: `echo "docker compose exec -T -u sail laravel.test <command> </dev/null" | newgrp docker`. Do not start, stop or restart containers. The tests use their own database (`testing`): do not run `migrate:fresh` or a seeder on the development database.
3. Run `composer generate-phpdoc` after the migration, then fix the tags by hand (`backend.md`, "Model docblocks").
4. `MixedTypeUsageTest` counts the word `mixed` in each file of `app/`, docblocks included. A new file must have none outside the three general rules of that test. The slug scope of a model takes a `$config` array: give it a real array type, and remove the `array<mixed>` tags that ide-helper writes.
5. CodeQL refuses a hand-made regular expression escape in a test. Compare the exact text instead.
6. `php artisan test` takes one path only: run it with no path for the full suite, or once per path.
7. Before the commit, all of these pass in the container: `npm run tsc`, `npx nx run-many -t lint typecheck test`, `npx nx format:check`, `composer format`, `composer lint`, `php artisan test`.
8. One commit on the local branch. The commit hook allows only the scopes all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build. The subject does not start with a capital letter. A blank line follows the subject and comes before the footer. Suggested subject: `feat(all): events, the space type, the table and the API (slice E1a)`, footer `Closes #1513`. No trailer line that names a tool or a co-author (a rule of the owner).
9. Do not push. Do not open a pull request. A reviewer reads your diff first and publishes it. Leave the working tree clean and on your branch.
10. Rules that fail the build: no new use of `mixed` in `app/`, PHPStan at its highest level, Pint, `OpenApiDocumentTest`, `ApiTokenAbilitiesTest`, ESLint (no state setter in an effect), the TypeScript build.
11. If a rule cannot be built as written, stop that rule, build the others, and say so in the report. Do not invent a new rule for the product.

## Report format (your last message, nothing else)

1. The commit hash and the `git show --stat` summary.
2. What you built, one line per rule above: rule number, done or not, where.
3. The tests you added, with counts: Pest tests per file, Vitest tests per file, Bruno requests.
4. The switches on the space type that you found (rule 8) and what each does for an events space.
5. Every choice you made that the brief did not decide.
6. What you are not sure about, and what you did not do.
