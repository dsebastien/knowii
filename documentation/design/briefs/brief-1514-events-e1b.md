# Brief: events, slice E1b: the screens (knowii-oss/knowii#1514)

You build the screens of events on the server part of slice E1a (#1513, ADR 0053, in `main`): the list of an events space with "Upcoming" and "Past", the event page, the host form, "Next events" on the home of a community, and what a visitor and an outsider see. No answers, no calendar file, no comments, no replay: slices E2 and E3 add them. ONE commit on a local branch. You do not push, you do not open a pull request: a reviewer reads your diff afterwards. Paths are relative to `~/wks/knowii`.

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md` (Front-end rules, definition of done), `naming.md`.
2. The issue (`gh api repos/knowii-oss/knowii/issues/1514 --jq .body`) and the design `documentation/design/events/design.md`: sections 3.4, 3.7, 4, 5.A, 5.H, 5.I, 6.1 to 6.3, 6.7, 7.1 to 7.4, 11, 14.
3. ADRs 0053, 0044 (points 4, 6, 8, 13, 15, "Public community page"), 0048 (points 1 to 4), 0049.
4. E1a: `app/Http/Controllers/API/EventApiController.php`, `app/Http/Resources/EventResource.php`, `app/Policies/EventPolicy.php`, `app/Models/Event.php`, `app/Actions/Events/*`, `app/Support/EventTimes.php`, `TimeZones.php`, `libs/common/src/lib/types/event.schema.ts`, `utils/time-zone.fn.ts`, `tests/Feature/EventApiTest.php`.
5. The pattern to copy: `routes/web.php` (line 76), `app/Http/Controllers/Inertia/SpaceController.php`, `CommunityResourceController.php`, `CommunityController.php` (line 50, `showPublic`), `app/Support/PublicCommunity.php`, `NoAccess.php`; in `apps/knowii/src`: `Pages/Spaces/Show.tsx`, `Pages/Resources/Show.tsx`, `Pages/Communities/Show.tsx`, `Public.tsx`, `Components/Posts/PostList.tsx`, `Components/Reader/Reader.tsx`.
6. The toolkit: `documentation/architecture/frontend.md` ("Toolkit", "Tests"), `Components/ui/index.ts`, `ToolkitContext.tsx`, `boundary.spec.ts`, `stories.spec.tsx`, `stories-guard.spec.ts`, `Select.tsx`, and the four blocks of slice 4a with their stories: `Combobox.tsx`, `Badge.tsx`, `LinkTabs.tsx`, `CoverImage.tsx`.
7. `Pages/Profile/Partials/TimeZoneForm.tsx`, `UpdateAvatarForm.tsx` (a file field), `Components/Messages/MemberPicker.tsx`, `Components/Notifications/NotificationsPanel.tsx` (line 597), `notification-link.ts` (line 48), `Components/TimeZone/TimeZoneAsk.spec.tsx` (line 200, axe).
8. `tests/Feature/PublicSpacesTest.php`, `tools/README.md`, `tools/screen-atlas/screens.json`.

## Rules (decided, do not change them)

[Q] marks a recommended answer where the design is silent: the owner confirms it, and its words are proposals.

### The server: reads only

1. E1a gives the API and `eventSchema`, nothing else for a screen: you add the web routes, the Inertia props, the API client functions and the request schema. Every change goes through the API (rules.md, Architecture 4); the new controllers only read.
2. Routes. `events.show`: `GET /community/{communitySlug}/space/{spaceSlug}/event/{eventSlug}`, in the `auth.optional` group (design 6.2 point 1). In the signed-in group: `events.create`: `GET .../space/{spaceSlug}/events/new` (plural: an event with the slug `new` never collides) and `events.edit`: `GET .../event/{eventSlug}/edit`. A new `Inertia\EventController` finds the space by slug and type, then calls `NoAccess::authorizeView` and `TracksSpaceVisits`, like `CommunityResourceController`.
3. `events.show`: a reader who fails `EventPolicy::view` (a draft) gets 404 (ADR 0053 point 9). Props: `community`, `space`, `spaceGroups`, `event` (with the large fields), `permissions`: `canUpdate`, `canCancel`, `canDelete` (`EventPolicy`), `canDuplicate` (`update` and `SpacePolicy::contribute`). All false for a visitor (ADR 0044 point 8).
4. `events.create`: `SpacePolicy::contribute`, else 403. `events.edit`: 404 without `view`, 403 without `update`. Props: `community`, `space`, `spaceGroups`, `event` (null for a create), `timeZones` (`TimeZones::identifiers()`), `permissions`: `canNotifyNow` (`SpacePolicy::moderate`), `canManageAllEvents` (`CommunityRoles::isOwnerOrAdmin`).
5. `spaces.show` of an events space gets `events` (`items`, `pagination`: the first 20 of `$space->events()->visibleTo(...)`, with `host` and `cover`) and `eventsScope` (`upcoming` or `past`, from `?scope=`; another value means `upcoming`). `SpaceController::EVENTS_PER_PAGE = 20`, mirrored in `constants.ts` (design 6.1 point 3).
6. Sort (design 6.1 points 3 and 5). "Upcoming": drafts first, then the next start first. `Event::scopeUpcoming` (line 236) sorts by start only: add the draft order there, so that the API and the page agree; update the docblock of `EventApiController::index`, `Events - List.bru` and `EventApiTest.php` (line 160). "Past" stays: newest start first.
7. Move the query of `EventApiController::community` (line 82) to `App\Support\UpcomingEvents`, with the same behaviour. The home and the public page get the prop `nextEvents`: its first 3 (`Constants::NEXT_EVENTS`).

### Times (design 4.3, 11.2)

8. The reader's zone is `useAccountTimeZone()`, else the browser zone. Server rendering is off (`config/inertia.php`). Inertia dates are strings: accept both (rules.md, Front-end 6).
9. New functions in `time-zone.fn.ts`, `Intl` only, locale `en-GB`, 24 hours, like `formatEventStart` (line 77): the long range; a local date and time in a zone to ISO 8601 with its offset (with the cases of rule 26); the reverse, for an edit; "UTC+1".
10. Long form: "Tuesday 18 November, 11:00 to 12:00 Eastern Standard Time". The zone words are those of `Intl` with `timeZoneName: 'long'` (ADR 0053 point 10): "Central European Standard Time". An end on another day: "Tuesday 18 November, 23:00 to Wednesday 19 November, 01:00 ..." [Q]. The year follows the month when it is not the current year [Q]. Short form, for lists: `formatEventStart`; the "EST" of the design cannot be built (`Intl` gives "GMT-5").
11. Every event time is a `time` element with the UTC start in `datetime`. A list shows the short form, with the long form as visually hidden text. No `title`, no `RelativeTime`, no "Starts in 3 hours".
12. When the start reads differently in `event.time_zone` and in the reader's zone, the event page adds "The host's time: Tuesday 18 November, 17:00 Central European Standard Time".

### The list of an events space (design 6.1)

13. A branch for the type `events` in `Spaces/Show.tsx` renders `Components/Events/EventList`. "New event", a link to `events.create`, shows with `permissions.canContribute`.
14. Two links "Upcoming" and "Past" (`?scope=past`, `preserveState`) with `aria-current="page"` from `eventsScope` (`isCurrentUrl` ignores the query) and a marker that is not only a colour. Not an ARIA tab widget. The list state resets when the scope changes.
15. A card is an `li`: the cover (16:9 box, `object-cover`, `alt` from `cover_alt`), the title as a heading that links to `events.show`, the time (rule 11), "Hosted by <name>" or "Hosted by Deleted member". Text labels: "Draft", "Cancelled", "Happening now" (published, start reached, end not reached).
16. Paging: 20. "Past": the button "Load older"; "Upcoming": "Load more" [Q]. After a load, the focus goes to the link of the first new card and a polite status says "20 more loaded" (the real count). A failed load shows the message of the server in a toast.
17. Empty [Q]: "No upcoming events" / "Events that are planned will show here."; "No past events" / "Events that ended will show here." (`EmptyState`, with "New event" for those who may create).
18. Live: `space.event.created`, `.updated`, `.deleted` on the space channel, never for a visitor (ADR 0044 point 13). Get the one event again and insert, replace or remove its card, like `PostList.tsx`; a 403 or 404 removes it. A card with the focus gives it to the next card link before it goes.

### The event page (design 6.2)

19. `Pages/Events/Show.tsx`, in `AppLayout` and `CommunityShell contentWidth="readable"`. Order: the title (the only `h1`); the state line; the cover; the date block as text (rules 10, 12); the host with avatar, name and username (no link to the member for a visitor, ADR 0044 point 6); the meeting block; the description (`MarkdownContent`, in an `ErrorBoundary variant="block"`).
20. State line, as text: a draft: "Draft. Only the host, the owner and the admins see it." [Q]; cancelled: a banner "Cancelled"; in progress: "Happening now"; `is_past`: "This event is over".
21. Meeting block. With `can_view_location`: the link "Join the meeting" (`safeHttpUrl`, new tab, `rel="noopener noreferrer"`, "Opens in a new tab" in its name) when an address exists, also when cancelled or past (design 6.2 point 4); the place as text; "Meeting address to come" when both are empty. Without it: a visitor reads "The meeting address is for members." and a `SignInPrompt` "Sign in"; an outsider reads the sentence alone; a member reads "The meeting address is for the host and the team." [Q].
22. Actions [Q: the menu of the design is in the form, which a moderator cannot open]: "Edit" (`canUpdate`); "Publish" (a draft, `canUpdate`); a `DropdownMenu` "Event actions": "Cancel event" (published, `canCancel`), "Publish again" (cancelled, `canUpdate`), "Back to draft" (published, start in the future, `canUpdate`), "Duplicate" (`canDuplicate`; it opens the edit form of the copy), "Delete" (`canDelete`). Two ask first, in a `Dialog`: "Cancel this event? It stays visible with the label Cancelled." and "Delete this event? This cannot be undone." [Q]. A refusal (422) shows the message of the server with `role="alert"`.
23. Live: `space.event.updated` or `.deleted` for this event reloads the page: the server decides what the reader then sees.

### The host form (design 4.2, 6.3)

24. `Pages/Events/Form.tsx`: react-hook-form and a Zod schema `event-request.schema.ts` in `libs/common`, with the limits of `constants.ts` (line 149). Fields, in order: "Title"; "Description" (`MarkdownEditor`); "Cover" (a file field, a preview, "Remove the cover") and "Cover description" (hint: "Leave empty when the image is decoration") [Q]; the `fieldset` "When"; "Meeting address"; "Place"; "Host" (`MemberPicker`, `max` 1, default you). No field for `location_visibility` [Q: until answers exist, `going` hides the address from every member; an edit never sends the field], the limit or the replay.
25. "When" (design 11.1): "Start date" and "Start time" (native `date` and `time` inputs), "End time", "Time zone". "End date" shows only when the end time is not after the start time, set to the next day [Q]. "Time zone": the toolkit `Combobox` with the entries of `timeZoneOptions()` (`time-zone.fn.ts`, #1666: "Brussels, Europe (UTC+1)"), like `TimeZoneForm.tsx`; `pinned`: the zone of the account and the zone of the browser. Default: the account zone, else `browserTimeZone()`, else UTC. An edit starts from `event.time_zone`. The zone names need no mapping: `browserTimeZone()` and the entries give the current name of a zone, and the server accepts an older name and stores the current one (#1678).
26. A status region, updated on blur and never on each key: "Tuesday 18 November 2026, 17:00 to 18:00, Central European Standard Time (UTC+1). In your zone: <long form>." or "... In your zone: the same." [Q: the design also writes "For you:"]. A time that the zone skips: "This time does not exist in this zone. Choose 01:59 or earlier, or 03:00 or later." (`role="alert"`, nothing is sent). A time that exists twice: a radio group "02:30 in summer time (UTC+2) or in winter time (UTC+1)?" (the larger offset is summer time). The form sends `starts_at` and `ends_at` with the offset, and `time_zone`.
27. Buttons. A new event or a draft: "Save as draft", "Publish". A published or cancelled event: "Save changes". With `canNotifyNow`, next to "Publish": a `Checkbox` "Publish now", hint "Without it, members are told after 5 minutes." [Q]; it sends `notify_now`.
28. The cover is uploaded with `knowiiApiClient.communities.uploads.image` when the file is chosen. That route needs a description (`UploadImage.php`, line 32): send the cover description, else "Cover of an event" [Q]. `cover_alt` holds only what the host typed.
29. After a save: visit `events.show`, with a toast ("Draft saved", "Event published", "Changes saved") [Q]. A draft with another host, saved without `canManageAllEvents`, is no longer visible to its creator: say so under "Host" before the save, and visit the space afterwards.
30. Errors: a field error of the server (400) shows next to its field with `role="alert"` (`starts_at` under "Start time", `ends_at` under "End time"); the focus stays. A 422 shows above the buttons. A busy button keeps the focus (`Button loading`). No label depends on a placeholder.

### "Next events" (design 6.7 points 1 and 3)

31. Home (`Communities/Show.tsx`): a `Card` "Next events" after `CatchUpCard` and before "Recent Resources" [Q]: up to 3 lines, each with the title as a link to `events.show`, the time (rule 11), the space name, and "Cancelled" when it is [Q: the list of the API route holds cancelled events]. No event, no block. No live update.
32. Public page (`Communities/Public.tsx`): the same card, title and time only. `notificationHref` (line 48) opens `events.show` with `target.event_slug`.

### Visitor and outsider (design 5.H, 7.4)

33. In a public events space they read the list and the event page: title, cover, description, time, host. Never the address or the place: the server cuts them, the client hides nothing. A draft or a space that is not public gives a visitor the sign-in page and an outsider the page without access. "Sign in to answer" is slice E2.

### Accessibility (design 11; 11.4 is slice E2)

34. 11.1: rules 25, 26, 30. 11.2: rules 11 and 20; no state by colour alone. 11.3: a real `ul`, rules 14 and 16.
35. 11.5: at 360 px one column, the cover above the title of a card, targets of 44 px (`touch-target`), no sideways scroll, usable at 400% zoom; both themes; forced colours; brand tokens only.
36. 11.6, the release test: axe in the Vitest specs of the list, the page and the form (no violation; only `color-contrast` and `region` may be off), and a keyboard script (create, save as draft, publish, edit, cancel, publish again, delete, load older) in `documentation/design/events/release-test.md`, which also lists what a person must still do: NVDA, VoiceOver on a phone, forced colours.

### Toolkit (ADR 0049)

37. The four blocks that these screens need exist in `Components/ui` since slice 4a of the toolkit (#1682): `Badge` (the text labels of rules 15 and 20), `LinkTabs` ("Upcoming" and "Past", rule 14), `CoverImage` (the 16:9 box of rules 15 and 19) and `Combobox` ("Time zone", rule 25). Use them as they are: read their docblocks and stories first. If one of them lacks something that a rule needs, change it in the toolkit with its story, under the toolkit rules (no import of the application, `boundary.spec.ts`), and say so in the report. Another block that is missing is built in the toolkit under the same rules. Feature parts go in `Components/Events`.

## Scope (in the commit)

1. PHP: the three routes, `EventController`, the props of rules 5 and 7, `UpcomingEvents`, the sort of rule 6, the constants.
2. `libs/common`: the constants, `knowiiApiClient.communities.events` (`list`, `listUpcoming`, `get`, `create`, `update`, `setState`, `duplicate`, `delete`, through `apiRequest()`), the request schema, the time functions.
3. Front end: the two pages, `Components/Events/*`, the four toolkit blocks, the changes of `Spaces/Show.tsx`, `Communities/Show.tsx`, `Public.tsx`, `notification-link.ts`.
4. Pest, `tests/Feature/EventPagesTest.php`: the props of the space page (scope, 20, drafts first for the host, the owner and an admin, none for the others); each page for the owner, an admin, a moderator, the host, a member, an outsider and a visitor, in a public, a `members` and a `groups` space; a draft answers 404; the `permissions` of each role; the form pages (200, 403, 404); a wrong method; no address and no place anywhere in the props of the four screens for a visitor, an outsider and a member of a `going` event (design 7.3 point 2); `nextEvents` (3 at most, the order, no draft, no space that the reader cannot read). Add the events space and the event to the datasets of `PublicSpacesTest.php`.
5. Vitest: the time functions (the instant of design 5 in Brussels, New York and Singapore; 28 March 2027 02:30 and 31 October 2027 02:30 in Brussels; journey 5.I; an end on the next day); the schema; the API client; a case for each of the rules 13 to 32, per kind of reader; `notification-link.spec.ts`; the stories.
6. Documents: a section "The screens (#1514)" in ADR 0053 (the routes, rules 6 and 25, the [Q] points); `frontend.md`; the status of the design in `documentation/design/README.md` and `design.md`; `release-test.md`; an area "events" in `screens.json`, with its data in `atlas-seed.php`.

## NOT in this commit

1. E2 (#1515): answers, "You said Yes", "N places left", "Full", "Add to calendar", "My events", the Yes part of `going`, policy words.
2. E3 (#1516): comments, the replay link, the notes, "A replay will exist", "cancel it first" before a delete.
3. #1482 and later: kinds 26 to 29, reminders, emails, the "Catch up" line, the calendar feed, series, a month grid.
4. A new API route; a change of `EventResource`, of the policies or of the actions.

## How to work

1. Branch `feat/1514-events-e1b` from `origin/main`. If the working tree holds changes of another person, stop and ask: no stash, no reset. The line numbers of this brief are those of 2026-11-19: find the text, do not trust the number.
2. Docker on this Linux machine, from `~/wks/knowii`: `docker compose exec -T -u sail laravel.test <command> </dev/null`. Do not start, stop or restart containers. No `migrate:fresh` and no seeder on the development database: change `atlas-seed.php`, do not run it.
3. A piped test command hides a failure: use `bash -c "set -o pipefail; ..."`. `php artisan test` takes ONE path: no path for the whole suite, or one run per path.
4. A wrong method on a web route answers 404, not 405. `InstanceNeutralNamesTest` reads `tests/` too: a test that checks that a product name is absent builds that name from two parts. Never name a third-party community product.
5. Every screen works at 360 px and at 1920 px, in the dark and in the light theme, with the keyboard alone. Check it in the running application, with events that you create through the screens.
6. Before the commit, after the last change: `npx nx format --all`, `composer format` (Pint), `composer lint`, `composer security:php`, `npm run lint:knowii`, `npm run lint:common`, `npm run tsc`, `npm run tsc:spec`, `npm run test:all` (Vitest: it runs the stories, their guard and the boundary), `npm run storybook:build`, and the whole Pest suite.
7. The commit hook allows only the scopes all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build. The subject does not start with a capital letter. Suggested: `feat(ui): events, the list, the event page, the host form and the next events (slice E1b)`. Footer: `Closes #1514`. No trailer line that names a tool or a co-author.
8. If a rule cannot be built as written, skip it, build the rest, and say why in the report. Do not invent product rules.

## Your final report (max 400 words)

1. The commit hash and `git show --stat`.
2. Each choice where the brief left room, and each [Q] rule that you built in another way.
3. The result of each command of "How to work" point 6, with the exact text of anything that failed.
4. What you checked in the browser (widths, themes, keyboard), and what waits for a person.
5. Anything of the scope not built, and why.
