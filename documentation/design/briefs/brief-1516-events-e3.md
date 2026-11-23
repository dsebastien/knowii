State: DECIDED on 2026-11-23 (see "Decided by the owner").

# Brief: events, slice E3: comments, the replay link and the notes after the event (knowii-oss/knowii#1516)

## Decided by the owner on 2026-11-23

1. Every recommendation of "Questions for the owner" is the decision (questions 1 to 9), with the two commits of the cut.
2. The recording sentence approved on 2026-11-17 stays the only consent text of the form, word for word: "Before you add a recording, make sure the people in it agreed."
3. The privacy policy, both texts, word for word: section 2 item 4 becomes "events (their title, description, date, place, meeting address, replay link and notes)"; the video sentence becomes exactly: "Videos in posts, comments, course lessons and events, and in the replays of events, come from YouTube or Vimeo. Your browser contacts YouTube or Vimeo only when you press Play: from then on, their own privacy policy applies." (the names of the two video hosts are already in the policy). Change no other word of a legal text.
4. In the first commit, also: editing or deleting a comment needs the read access to its space (`SpacePolicy::view`), on every route of the comments (posts, resources, events), with a test for each route. Public words: "editing or deleting a comment needs the access to its space".

Slice E2 (#1515) is merged in `main`: the answers, the places, the calendar file and "My events". This brief is the full text of slice E3. It closes #1516.

You build three things. Members comment an event, with the comments of posts and resources (replies, mentions, reactions on comments, moderation). After the event, the host adds a replay link and notes, and the event page shows them. A published event that members still expect (a Yes or a Replay only answer, the end not passed) cannot be deleted: the host cancels it first. Paths are relative to `~/wks/knowii`.

Build it as TWO commits on one local branch (the cut is at the end of "Scope"). A reviewer reads your diff afterwards. Until the owner answers the "Questions for the owner" at the end, build what each recommendation and each [W] word says. [W] marks a word that the design does not give. [Q] marks a rule that a question can change. Each [W] word of a screen is one value in `event-words.ts` (or in the notification words of `libs/common`): a later change is one line.

## Decided by the owner (do not change it)

1. 2026-11-16 (comment 6915748686 on #1388): the design is approved with its 18 decisions as recommended. For E3: decision 11 (reuse the comments of posts through `comments.event_id`, no reactions on the event, no follows), decision 13 (the words), decision 17 (a published future event with answers must be cancelled first, so that members are told).
2. 2026-11-17 (comment 6949756864 on #1388): the four policy sentences of design 10.4 and the sentence at the replay field are approved as proposed. The sentence at the replay field is: "Before you add a recording, make sure the people in it agreed." Use it word for word (question 6).

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md` (Architecture 2 to 9, Data 2, 6, 7, 8 and 10, Front-end 2, 3, 5, 9, 11 and 12, and the definition of done), `documentation/conventions/naming.md`.
2. `documentation/design/events/design.md`: sections 1.2, 2.4, 3.3, 3.4, 5.D, 5.F, 5.G, 5.H, 6.2, 6.3, 7.1, 7.2 (the table and point 3), 7.4, 7.5, 8.5, 8.6 (the last row), 9.1, 10.1 to 10.4, 11.5, 11.6, 13 (decisions 11, 13, 17) and 14 (slice E3). The issue says "7.6" for the comments: the comments are section 7.5 (7.6 is the limit of places). Then `release-test.md` of the same folder.
3. The notification design, `documentation/design/notifications/design.md`: the table of kinds (row 1 "Comment on my post", row 2, row 3) and sections 6.6, 6.8 and 6.9.
4. ADRs: 0053 (all of it), 0014, 0029, 0031, 0042 (points 7, 9, 30, 34 and 39), 0043, 0044 (points 4, 6, 8, 9 and 13), 0050.
5. The comments of `main`: `app/Models/Comment.php`, `app/Actions/Comments/*`, `app/Contracts/Comments/*`, `app/Policies/CommentPolicy.php`, `app/Support/CommentThread.php`, `app/Http/Controllers/API/CommunityResourceCommentApiController.php`, `ReactionApiController.php` (lines 43 to 73), `app/Http/Controllers/Inertia/CommunityResourceController.php` (line 53), `apps/knowii/src/Components/Comments/CommentSection.tsx`, `apps/knowii/src/Pages/Resources/Show.tsx` (lines 151 to 157).
6. The notifications of a comment: `app/Actions/Notifications/Notify.php`, `app/Support/NotificationBundles.php`, `app/Support/NotificationItem.php`, `app/Http/Resources/NotificationResource.php`, `app/Enums/KnowiiNotificationKind.php` and `KnowiiNotificationReason.php`, `libs/common/src/lib/types/notification.schema.ts`, `apps/knowii/src/Components/Notifications/notification-link.ts`.
7. The events of `main`: `app/Models/Event.php`, `app/Policies/EventPolicy.php`, `app/Http/Resources/EventResource.php`, every file of `app/Actions/Events/`, `EventApiController.php`, `app/Http/Controllers/Inertia/EventController.php`, `apps/knowii/src/Pages/Events/Show.tsx`, every file of `apps/knowii/src/Components/Events/`, `libs/common/src/lib/types/event.schema.ts`, `libs/common/src/lib/api/communities/event-request.schema.ts`.
8. The video of a lesson: `app/Support/VideoEmbed.php`, `app/Models/Page.php` (lines 101 to 108), `apps/knowii/src/Components/Video/VideoPlayer.tsx`, `config/secure-headers.php` (the `frame-src` and `autoplay` parts), `tests/Feature/CourseSpacesTest.php` (line 140), `tests/Feature/ContentSecurityPolicyTest.php`.
9. The exports and the policy: `app/Actions/Communities/ExportCommunity.php`, `app/Actions/Users/ExportUser.php`, `app/Actions/Content/RenderContentAgain.php`, `deploy/knowii.net/legal/policy.md` and `resources/legal-templates/policy.md` (section 2 and the sentence about videos).
10. Tests: `CommentTest.php`, `ResourceCommentTest.php`, `ReactionTest.php`, `NotificationTest.php`, `EventApiTest.php`, `EventsTest.php`, `EventAnswersTest.php` (lines 640 to 680), `EventPagesTest.php`, `PolicyTest.php`, `PublicSpacesTest.php`, `CommunityExportTest.php`, `UserExportTest.php`, `ApiTokenAbilitiesTest.php`, `BrunoCollectionTest.php`, `OpenApiDocumentTest.php`, `tests/Unit/InstanceNeutralNamesTest.php`.

## What the design decides for E3 (do not change it)

1. Comments reuse the comments of posts and resources: a new column `comments.event_id` (cascade), and the check of `comments` covers three columns (design 7.5 point 1, ADR 0014).
2. That gives replies (one level), mentions, reactions on comments, moderation and the markers of ADR 0043 and ADR 0050, with no second system (7.5 point 2).
3. A reply to my comment and a mention work as for the comments of a resource. A first-level comment on an event tells the host, at level 1. No follows for events (7.5 point 3, ADR 0042 point 30).
4. No reactions on the event itself: a like is a count of people (7.5 point 4, principle 2, section 15 "Rejected" point 9).
5. Who comments: `EventPolicy::comment` (view): the owner, the admins, the moderators and the members, never a person who is not a member, never a visitor (7.2, table). A visitor reads the comments of an event of a public space, as for posts (7.4 point 1, 7.4 point 3).
6. The routes of the comments are those of the comments of a resource, with `events:read` and `events:write`, the limiter `comments`, and the list is a public read (9.1, 9.2, 7.4 point 3).
7. The platform never receives a recording. After the event the host pastes a link in "Replay link" (2.4 point 1).
8. A link of one of the two video hosts that the design names (2.4 point 2, `VideoEmbed::PLAYER_HOSTS`) shows the click-to-load player of lessons. Any other http or https address is a plain link (2.4 point 2). Nothing loads from the video host before the click (ADR 0029, 10.3).
9. The replay link is never for a visitor or a person who is not a member (2.4 point 3, 3.7 point 2, 7.4 point 2). The notes are for every reader of the event, a visitor of a public space too (7.4 point 1).
10. "Edit ... replay" is `EventPolicy::update`: the owner, the admins and the host. A moderator does not set it (7.2, table).
11. The page order: title, state line, cover, date block, host, answer group, meeting block, description, notes and replay (after the event), comments (6.2 point 2).
12. A published future event that has a Yes or a Replay only answer cannot be deleted: "Cancel it first, so members are told." Drafts, past and cancelled events can be deleted, with their answers, comments and rows by cascade (7.2 point 3, decision 17).
13. Events, with the replay link and the notes, are community content: kept with the community, in the community export, deleted with the space or the community (10.1, 10.2 point 2). No new third party (10.3).
14. Kind 29 ("The replay of ... is ready") and its guard `replay_notified_at` are slice E4 (#1482), not E3 (8.5, 8.6, section 14).

## What exists today (facts, with the evidence)

Line numbers are those of `main` at `263eb757`. Find the text when a line moved.

Comments:

1. `comments` has `post_id` and `community_resource_id`, both nullable, and the check `comments_one_item_check`: `num_nonnulls(post_id, community_resource_id) = 1` (`database/migrations/2026_11_09_220000_add_resource_comments.php:18-24`). `deleted_at` marks a marker (`2026_11_15_130000_add_deleted_at_to_posts_and_comments.php`).
2. `Comment::subject()` returns `Post|CommunityResource` (`app/Models/Comment.php:156-160`). `$fillable` and `$hidden` list the two columns (lines 84 to 108).
3. `CreateComment::create(User, Post|CommunityResource, array)` authorizes `comment` on the subject, sets `post_id` or `community_resource_id`, sends `space.comment.created`, records the visit, follows the thread of a post only, and calls `Notifies::notify` (`app/Actions/Comments/CreateComment.php:28-77`).
4. `CommentPolicy::view`, `react`, `delete` and `deleteWithReplies` all read `$comment->subject()` (`app/Policies/CommentPolicy.php:18-54`). `CommunityResourcePolicy::comment` is a member who can read the space (`CommunityResourcePolicy.php:25-28`). `EventPolicy` has no `comment` ability (`app/Policies/EventPolicy.php`).
5. `CommentThread::for(Post|CommunityResource)`: the top-level comments with authors, reactions and replies, at most 500 (`app/Support/CommentThread.php:24-31`).
6. `SpaceContentChanged::subjectPayload` gives `post_cuid` or `resource_cuid` (`app/Events/Spaces/SpaceContentChanged.php:158-161`). `SpaceContentPayload` already has `event_cuid` (`libs/common/src/lib/hooks/use-socket.ts:101-112`).
7. The resource comment routes: the list is a public read (`routes/api.php:85`), the writes and the reactions are in the signed-in group with scoped bindings (lines 234 to 239). Their constants are `ApiRoutes.php:173-179`.
8. `CommentSubject` and `ReactionTarget` know a post and a resource (`libs/common/src/lib/api/knowii-api-client.ts:223-235`). `commentsPath` builds the two addresses (lines 335 to 347). `CommentSection` picks the cuid of the subject (`CommentSection.tsx:164`) and filters the live updates on `post_cuid` or `resource_cuid` (line 241).
9. The exports: the community export writes `post` and `resource` for a comment (`ExportCommunity.php:400-405`). The member export writes `post` and `resource` with their titles (`ExportUser.php:700-707`). `LeaveMarkers` and `DeleteMemberContent` work on comment ids, whatever the subject.

Notifications of a comment:

10. `Notify::reasons` gives `post_comment` to the author of the post for a first-level comment, `comment_reply` to the author of the parent, and `mention` (`app/Actions/Notifications/Notify.php:196-217`). A comment without a post never goes to the fan-out of level 2 (line 121).
11. `Notify::dedupeKey` writes `post_comment:post:{post_id}` (line 276): with `post_id` null every comment of every event would share one key.
12. `NotificationBundles::bundles` reads `post_comment` rows through `comment.post_id` (`app/Support/NotificationBundles.php:93-94`) and loads `comment.post` and `comment.communityResource` (line 46).
13. `NotificationItem::spaceOf` reads the space of a comment from its post or its resource (`app/Support/NotificationItem.php:31`). `threadOf` gives null for a comment without a post (line 48).
14. `NotificationResource::titleOf` and `targetOf` know the post, the resource and the event of an `event_new` row, not the event of a comment (`app/Http/Resources/NotificationResource.php:69-120`). `notificationHref` opens the event page without the anchor of a comment (`notification-link.ts:48-50`).
15. The sentences: `post_comment` is "{who} replied to your post", and the reason `wrote_post` is "You wrote this post, and someone replied to it." (`libs/common/src/lib/types/notification.schema.ts:163-164, 222-223`). A kind is one level and one reason, and "a later kind adds a case here, never a rule of its own" (`app/Enums/KnowiiNotificationKind.php:5-8`). `notifications.kind` has no check in the database (`2026_11_15_120000_create_notifications_table.php:29`).
16. `NotificationAccess::canView` checks the membership and `SpacePolicy::view` of the space of the row, never the event (`app/Support/NotificationAccess.php:31-45`).

Events:

17. `events` has no `replay_url`, `notes` or `notes_html` (`2026_11_18_100000_create_events_table.php:35-70`). `replay_expected` and the answer "Replay only" exist since E2 (`2026_11_20_100000_create_event_answers_table.php:25`, the form field `EventForm.tsx:1012-1029`).
18. `EventPolicy::view` (lines 29 to 36), `update` (41 to 44), `cancel` and `delete` (51 to 63), `viewLocation` (71 to 81, `going` passes only a Yes, the host, the owner and the admins).
19. `EventPublication::assertVisible` gives 404 with the body of an event that does not exist to a signed-in reader who cannot see the event (`app/Actions/Events/EventPublication.php:59-64`). `EventApiController::show` calls it first, then `Gate::authorize('view')` for a visitor (`EventApiController.php:106-116`): a visitor gets 401 in a `members` space and on a draft (`tests/Feature/EventApiTest.php:239-244`).
20. `DeleteEvent` checks `delete` and deletes, with no rule on the answers (`app/Actions/Events/DeleteEvent.php:19-32`). ADR 0053 ("The answers and the places", point 10) says that "cancel it first" is E3.
21. `SetEventState` refuses published to draft when a member who can read the space answered, inside the lock of the row: "Members answered this event. Cancel it instead." (`SetEventState.php:30, 58-83`). Back to draft deletes the `event_new` rows only (`PruneNotifications::forDraftEvent`, `PruneNotifications.php:104-107`).
22. `UpdateEvent` takes the rules of a create (`CreateEvent::rules`, `CreateEvent.php:116-137`) and `host_cuid`, bumps `sequence` on `SEQUENCE_FIELDS` (`UpdateEvent.php:24, 88-90`) and syncs the uploads when `body` or the cover changes (line 91). `Event::syncUploads` reads `body` only (`Event.php:397`). `DuplicateEvent` lists each copied field (`DuplicateEvent.php:50-71`). `RenderContentAgain` renders `body` of events (`RenderContentAgain.php:59-64`).
23. `EventResource` cuts `meeting_url` and `place` with `viewLocation` and gives `body` and `body_html` only with the large fields (`EventResource.php:52, 68-69, 87-88`).
24. `EventController::show` gives `event` and `permissions` (`canUpdate`, `canCancel`, `canDelete`, `canDuplicate`, `canViewAnswers`) and no comments (`app/Http/Controllers/Inertia/EventController.php:42-59`).
25. `Show.tsx` renders the title, the state line, the actions, the cover, `EventWhen`, `AddToCalendar`, `EventHost`, `EventAnswer`, `EventMeeting` and the description (`apps/knowii/src/Pages/Events/Show.tsx:164-211`). It listens to `space.event.*` only (lines 116 to 139).
26. `EventActions` shows "Delete" for `canDelete` and asks "Delete this event?" with "This cannot be undone." (`EventActions.tsx:205-208, 256-272`, `event-words.ts:126-128`). A refusal of the server shows its words in the alert of the actions (lines 91 to 95).

The video and the policy:

27. `VideoEmbed::parse` turns an address of the two video hosts into `{provider, id, url, embed_url}` and refuses every other address (`app/Support/VideoEmbed.php:13-62`). `Page::video()` uses it for a lesson (`Page.php:105-108`). `VideoPlayer` loads nothing until "Play the video", then shows the frame of the player, and links to the original address in a new tab (`VideoPlayer.tsx:14-69`).
28. `frame-src` already allows the two player hosts, and `autoplay` allows `VideoEmbed::PLAYER_HOSTS` (`config/secure-headers.php:261-267, 585-594`). So the replay player needs no change of the Content Security Policy.
29. The policy lists "events (their title, description, date, place and meeting address)" (`deploy/knowii.net/legal/policy.md:24`, `resources/legal-templates/policy.md:23`). It says that videos in course lessons come from the two video hosts and load only on Play (`policy.md:55`, template line 54). Comments are in the same item 4, and content is kept "until its author, a moderator or the owner deletes it, or the community is deleted" (`policy.md:62`).
30. The next free ADR number is 0063 (`documentation/adr/README.md`). This slice needs no new ADR: it adds a part to ADR 0053.

## Rules

Each rule is one sentence that a test can prove.

### The data

1. A migration adds `comments.event_id`: `foreignId` nullable, constrained, `cascadeOnDelete` (a deleted event deletes its comments, with their reactions, mentions, usages and notification rows by the cascades that exist), and an index `(event_id, created_at)`.
2. The same migration replaces `comments_one_item_check` with `num_nonnulls(post_id, community_resource_id, event_id) = 1`. Its `down` deletes the comments of events first, then puts the check of two columns back. The column list is a literal, as in the other migrations.
3. `Comment` gets `event_id` in `$fillable` and `$hidden`, the relation `event()`, the docblock, and `subject()` returns `Post|CommunityResource|Event`. `Event` gets `comments()` (`hasMany`, oldest first, as `CommunityResource::comments()`).
4. A second migration adds to `events`: `replay_url` (string 2048, nullable), `notes` (text, not null, default `''`) and `notes_html` (text, not null, default `''`). An empty text means no notes, as for `body`.
5. New constants: `MAX_LENGTH_EVENT_REPLAY_URL` (2048) and `MAX_LENGTH_EVENT_NOTES` (20 000), in `App\Constants` and `constants.ts`.
6. Every create of an event (`CreateEvent`, `DuplicateEvent`) sets `replay_url` null and `notes` and `notes_html` to `''` (Data rule 7). A copy never takes the replay, the notes or the comments.

### Who reads and writes the comments

7. `EventPolicy::comment(User, Event)`: a member of the community for whom `view` passes, and the event is not a draft. The owner, the admins and the moderators comment like members. The state `published` or `cancelled` and a past event do not change it.
8. A draft takes no comment, also from its host, the owner and the admins. Why: `NotificationAccess` checks the space, not the event (fact 16), so a mention or a reply in a draft would tell a member who cannot see the draft.
9. Back to draft is refused when the event has a comment (a marker too): "Members commented on this event. Cancel it instead." [W] [Q, question 4]. `SetEventState` checks it inside the lock, next to the check of the answers. So a draft never holds a comment.
10. Every route of the comments of an event calls, in this order: 401 for a visitor on a write; `EventPublication::assertVisible` for a signed-in reader (404 with the body of an event that does not exist); on a write, 403 for a person who is not a member; on a create, 422 for a draft: "This event is a draft. It takes no comments." [W]; then the action of the comment. The list for a visitor answers as `EventApiController::show` does (200 in a public space, 401 elsewhere and for a draft).
11. A member who lost the access to the space gets 404 on every route of the comments of the event, a write of their own comment too. Their comments stay, under their name, and stay in their member export.
12. Edit, delete, a marker, `delete_replies` and the reactions on a comment of an event follow `CommentPolicy` and `DeleteComment` as they are: the author edits, the author and the people who moderate the space delete. Nothing in `CommentPolicy` tests the type of the subject.
13. The comment of another event (or of a post, or of a resource) through the address of this event is 404 (scoped bindings).
14. No route, ability or column gives a reaction to the event itself. `reactions` gets no `event_id`. `POST .../events/{event}/reactions` does not exist.
15. `CreateComment` sets `event_id` for an event, records a visit of the space (`wroteIn(..., false)`, as for a resource), follows no thread, and calls `notify`. `CommentThread::for` and `SpaceContentChanged::forComment` and `forReactions` take an event: the payload has `event_cuid` and `comment_cuid`, never a text.

### The notifications of a comment on an event

16. A first-level comment on an event tells its host, at level 1, with a new kind `event_comment` and a new reason `hosts_event` [Q, question 2]. Never the host for their own comment, never a host who left the community or cannot read the space, never a host who blocked the author, never in a muted space (the rules of `Notify::deliver` as they are).
17. `event_comment` rows bundle as `post_comment` rows do: `dedupe_key` `event_comment:event:{event_id}`, and `NotificationBundles` counts the live first-level comments of the event since the first one that the host did not read.
18. A reply tells the author of the parent (`comment_reply`) and a mention tells the mentioned member (`mention`), as for a resource. The order of the reasons stays: mention, then reply, then comment.
19. A comment on an event writes no row of level 2 and no follow (fact 10 stays true).
20. `NotificationItem::spaceOf` reads the space of the event of a comment. `NotificationResource`: the title of a row of a comment on an event is the title of the event, and `target` carries `event_cuid`, `event_slug` and `comment_cuid`. `notificationHref` opens the event page with `#comment-{cuid}` when the target has a comment.
21. The sentences: `event_comment` is "{who} commented on your event" [W], and the reason `hosts_event` is "You host this event, and someone commented on it." [W]. The member export, `ListNotifications` and the "Catch up" lines need nothing more than rule 20.

### After the event: the replay link and the notes

22. `PATCH .../events/{event}` takes `replay_url` (`sometimes`, nullable, `url:http,https`, at most 2048) and `notes` (`sometimes`, nullable, Markdown, at most 20 000, `MaxMarkdownImages`). They are not in `CreateEvent::rules`: a create, a duplicate and `POST` ignore them.
23. A value that is not empty is accepted only when the event, after the change, is published and its end is before now. Else a field error (400): "Add the replay once the event is published and over." [W] for `replay_url`, and "Add the notes once the event is published and over." [W] for `notes` [Q, question 3].
24. An empty value (null or `''`) is always accepted from the people who change the event: it clears the field, also on a cancelled event or a draft.
25. The server never opens the replay link. A change of `replay_url` or `notes` does not bump `sequence` and does not change the calendar file except its `DTSTAMP` (through `updated_at`). It sends `space.event.updated`, as every change does.
26. `notes_html` is `Markdown::render` of `notes` (raw HTML escaped, ADR 0013), the same as the description: a mention in the notes is a link and tells nobody. `RenderContentAgain` renders `notes` too. `Event::syncUploads` reads `body` and `notes`, and `UpdateEvent` syncs the uploads when `notes` changes.
27. A new ability `EventPolicy::viewReplay(?User, Event)`: a member of the community for whom `view` passes. It does not follow `location_visibility`: a member with Replay only, with No or with no answer reads the replay of a `going` event [Q, question 1].
28. `EventResource` gets `can_view_replay`, `replay_url` (null when `viewReplay` fails), `replay` (`VideoEmbed::parse` of the link, the shape of `Page::video()`, null when `viewReplay` fails or the link is not of the two video hosts), and `notes` and `notes_html` with the large fields only. The cut is on the server, on every route that carries an event (the list of a space, the list of a community, one event, `GET user/events`, the event page and the form). The client hides nothing.
29. A visitor and a person who is not a member get `can_view_replay` false, `replay_url` null and `replay` null, and nothing that says whether a replay exists. They get the notes.
30. The calendar file does not change: no replay link and no notes in it.

### The page of the event

31. `EventController::show` gives `comments` (`CommentThread::for($event)`, for every reader who passes `view`) and three new permissions: `canComment` (`EventPolicy::comment`), `canModerate` (`SpacePolicy::moderate` of the space), and `mustCancelBeforeDelete` (rule 40). `EventPermissions` mirrors them.
32. After the description, a section "After the event" [W] (an `h2`) shows when the event is published and past and at least one of these is true: a replay that the reader may see, notes, `replay_expected`, or `canUpdate`. It never shows before the end, and never on a draft or a cancelled event.
33. Inside it, in this order: the replay, the notes, the link of the host.
34. The replay: with `replay`, the toolkit `VideoPlayer` (the same component as a lesson, with the title of the event). With `replay_url` and no `replay`, a plain link "Watch the replay" [W] through `safeHttpUrl()`, in a new tab with `rel="noopener noreferrer"` and "(opens in a new tab)" in visually hidden text. A link that `safeHttpUrl()` refuses shows nothing.
35. Without a replay that the reader may see, a member reads "The host plans a replay. It shows here when the host adds it." [W] when `replay_expected` is true, and nothing when it is false. A visitor and a person who is not a member read nothing about the replay.
36. The notes: a heading "Notes" [W] (an `h3`) and `MarkdownContent` of `notes_html`, in an `ErrorBoundary`, as the description.
37. For `canUpdate`: a link to the form, "Add the replay and notes" [W] when both are empty, else "Edit the replay and notes" [W].
38. The comments come last, after the article, with `CommentSection` and the subject `{ communityCuid, spaceCuid, eventCuid }`. `CommentSubject`, `ReactionTarget`, `commentsPath` and the filter of the live updates (`event_cuid`) know an event. The comments of a post and of a resource work as before.
39. A visitor reads the comments of a public space with the sign-in prompt of `CommentSection`, and the page opens no live connection for a visitor (ADR 0044 point 13).

### Delete and cancel

40. `DeleteEvent` refuses the delete of an event that is published, whose end is now or later, and that has a `yes` or `replay` answer of a member who can read the space today (`EventAnswer::scopeOfReaders`): `BusinessException` (422), "Cancel it first, so members are told." It reads the row with `lockForUpdate()` inside a transaction and decides from the locked row, as `SetEventState` does [Q, question 5].
41. An event that happens now counts as "future" in rule 40: its end is not passed. A `no` answer alone does not block a delete. The answer of a member who lost the access does not block it.
42. A draft, a cancelled event, an event whose end passed, and a published event with no Yes and no Replay only can be deleted, with their answers and comments.
43. The person who deletes needs `delete` as today. Rule 40 is a business rule of the action, not of the policy.
44. `mustCancelBeforeDelete` is true when `canDelete` is true and rule 40 would refuse the delete. Then "Delete" opens a dialog "Cancel this event first?" [W] with "Members answered Yes or Replay only. Cancel it first, so members are told." [W] and the buttons "Keep" and "Cancel event" (the words of today). "Cancel event" runs the cancel, as the menu does. It names no member and gives no count.
45. Else the delete dialog says "Its comments and the answers of members are deleted too. This cannot be undone." [W]. A refusal of the server after the page loaded (a Yes that came in between) shows its words in the alert of the actions, as today.

### The form of the host

46. The form of an event that is published and past gets a `fieldset` "After the event" [W] after "A replay will exist" and before "Host": "Replay link" (D, an `input type="url"`) and "Notes" (D, the `MarkdownEditor` of the description). The form of a new event, of a draft, of a cancelled event and of an event that is not over has no such fieldset.
47. Under "Replay link", as its description (`aria-describedby`): "Before you add a recording, make sure the people in it agreed." (approved, question 6), then "Members who can see the event see it. Visitors do not." [W].
48. Under "Notes": "Everyone who can read the event reads the notes." [W].
49. The two fields send only what changed, as the other fields of the form do. A field error of the server shows under its field (`role="alert"`), and the focus stays.

### Privacy and access (each one is a test)

50. For each reader (visitor, outsider in a public space, member with each answer and with none, moderator, host, admin, owner) and each route of rule 28: `replay_url` is not null if and only if `viewReplay` passes. A scan of each JSON answer and of the props for the replay address finds it only then.
51. The notes, the description and the comments of an event in a `members` or `groups` space reach no visitor and no outsider. In a public space they reach both.
52. A member who leaves the community, or who loses the access group of a `groups` space, gets 404 on the event, its comments and its reactions, and no new notification row of the event. The old rows follow the pruning that exists.
53. The member export lists the comments of the member on events with `event` (`cuid` and `title`) and `null` for `post` and `resource`. The community export lists `event` (the cuid) for each comment, and `replay_url` and `notes` (Markdown) for each event. Each `README.txt` says so.
54. A deleted account: its comments stay under "Deleted member", or as markers, as for posts (ADR 0043). The option `delete_content` deletes its comments on events too.
55. The policy, both files, section 2 item 4: "events (their title, description, date, place, meeting address, replay link and notes)" [Q, question 7]. The sentence about videos: "Videos in course lessons and in the replays of events come from ..." with the two names that the sentence holds today, the rest unchanged [Q, question 7]. No new retention entry: the replay link, the notes and the comments are content, kept as content (fact 29, design 10.1). No new third party (fact 28).

## The words

D marks a word of the design, A a word that the owner approved, [W] a proposal.

1. D: "Replay link", "notes", "Cancel it first, so members are told."
2. A: "Before you add a recording, make sure the people in it agreed." (2026-11-17).
3. [W] The page: "After the event", "Watch the replay", "The host plans a replay. It shows here when the host adds it.", "Notes", "Add the replay and notes", "Edit the replay and notes".
4. [W] The form: "After the event" (the legend), "Members who can see the event see it. Visitors do not.", "Everyone who can read the event reads the notes.".
5. [W] The delete: "Cancel this event first?", "Members answered Yes or Replay only. Cancel it first, so members are told.", "Its comments and the answers of members are deleted too. This cannot be undone.".
6. [W] The server: "Members commented on this event. Cancel it instead.", "This event is a draft. It takes no comments.", "Add the replay once the event is published and over.", "Add the notes once the event is published and over.".
7. [W] The notification: "{who} commented on your event", "You host this event, and someone commented on it.".
8. [W] The policy: rule 55.

## Scope

Commit 1, the comments (rules 1 to 3, 7 to 21, 31 except `mustCancelBeforeDelete`, 38, 39, 50 to 54 for the comments). Footer `Refs #1516`.

1. The migration of rule 1. `Comment`, `Event::comments()`, `EventPolicy::comment`, `CreateComment` and its contract, `CommentThread`, `SpaceContentChanged`, `SetEventState` (rule 9).
2. `ApiRoutes`: `COMMUNITY_SPACE_EVENT_COMMENTS` (`.../events/{event}/comments`), `COMMUNITY_SPACE_EVENT_COMMENT` (`.../comments/{comment}`), `COMMUNITY_SPACE_EVENT_COMMENT_REACTIONS`, mirrored in `constants.ts`. Routes: the list in the public group with `ability:events:read`; `POST` with `ability:events:write` and `throttle:comments`; `PATCH` and `DELETE` with `ability:events:write`; the reactions `GET` with `ability:events:read` and `POST` with `ability:events:write` and `throttle:reactions`, as the routes of a resource. A new controller `EventCommentApiController` (index, store, update, destroy) and two methods of `ReactionApiController` (`toggleEventComment`, `listEventComment`), each with its docblock and `BodyParameter` attributes. The text of `body` says that in a public space anyone on the internet reads the comment with the name and the picture of its author.
3. Notifications: `KnowiiNotificationKind::EventComment` (level 1), `KnowiiNotificationReason::HostsEvent`, `Notify::reasons` and `dedupeKey`, `NotificationBundles`, `NotificationItem::spaceOf`, `NotificationResource`, the kinds and the reasons in `notification.schema.ts` with their sentences, `notification-link.ts`.
4. Exports: `ExportCommunity` and `ExportUser` (rule 53) and their README texts.
5. The page: `EventController::show` (`comments`, `canComment`, `canModerate`), `Show.tsx` (the comments), `CommentSection` and the API client (rule 38), `EventPermissions`.
6. Tests of commit 1:
   1. Pest `tests/Feature/EventCommentsTest.php`: each route with each reader of rule 50 (a data set), the order of rule 10, a draft for its host, the owner and an admin (422), a cancelled event and a past event (201), a member who lost the access (404 on each route, also on their own comment), rule 13 with a comment of another event, of a post and of a resource, the markers and `delete_replies` on an event, the reactions on a comment, rule 14 (the route does not exist, `reactions` has no `event_id`), the check of rule 2 (an insert with `event_id` and `post_id` fails, with none fails), the cascade of a deleted event, a deleted space and a deleted community.
   2. In the same file: rule 9 (back to draft refused with a comment, also a marker; allowed without; the check runs inside the lock), the payloads of the three live updates (`event_cuid`, `comment_cuid`, no text).
   3. `NotificationTest.php` or a new `EventCommentNotificationsTest.php`: rules 16 to 21. The host gets one row for two comments of two members (the bundle, "2" in `items_count`), no row for their own comment, none when they blocked the author, none in a muted space, none when they left; a reply and a mention on an event; the title, the target and the excerpt of each row; two events never share a row (fact 11).
   4. `PolicyTest.php`: the rows of `comment`. `CommunityExportTest` and `UserExportTest`: rule 53. `PublicSpacesTest`: the comments of an event for a visitor in a public space and not in a `members` space.
   5. `ApiTokenAbilitiesTest`, `BrunoCollectionTest` and `OpenApiDocumentTest` pass, with a case in `OpenApiDocumentTest` for the new routes.
   6. Vitest: `CommentSection.spec.tsx` with an event subject (create, reply, the live update of this event and not of another one), `notification-link.spec.ts` (the anchor on the event page), the spec of the sentences (the new kind and reason), the spec of the API client (the six addresses), `Show.spec.tsx` (the comments after the article, for a member, a visitor and a draft), with axe.
7. Bruno: "Event Comments - List", "- Create", "- Reply", "- Update", "- Delete", "- Toggle a reaction", "- Who reacted", as the requests of "Resource Comments".

Commit 2, after the event and "cancel it first" (rules 4 to 6, 22 to 37, 40 to 49, 50 and 51 for the replay, 53 for the events, 55). Footer `Closes #1516`.

8. The migration of rule 4, the constants of rule 5, `Event` (`$fillable`, casts, docblock, `syncUploads`), `UpdateEvent`, `CreateEvent` and `DuplicateEvent` (rule 6), `RenderContentAgain`, `EventPolicy::viewReplay`, `EventResource`, `eventSchema` (the five fields, `replay` with the schema of the video of a page), `eventUpdateRequestSchema` and `eventFormSchema` (the two fields, with the limits).
9. `DeleteEvent` (rules 40 to 43), `EventController::show` (`mustCancelBeforeDelete`), `EventActions` (rules 44 and 45).
10. The page (rules 32 to 37) in a new component `Components/Events/EventAfter.tsx`, and the form (rules 46 to 49) in `EventForm.tsx`. Every word in `event-words.ts`.
11. The policy in both files (rule 55). The export of rule 53 for the events.
12. Tests of commit 2:
    1. Pest `tests/Feature/EventAfterTest.php`: rules 22 to 26 (each field on a published past event, a published future event, a cancelled event and a draft; a clear on each; an owner, an admin, the host, a moderator with `delete_content` (403), a member (403); `sequence` does not move; the upload usage of an image of the notes; the HTML escaped), rule 6 (the copy), `RenderContentAgain` for `notes`.
    2. Rules 27 to 30 and 50 as one data set over the readers and the routes, on a `going` event and a `members` event, with an address of each of the two video hosts (as `CourseSpacesTest` writes them) and with an `example.com` address. The calendar file holds no replay and no notes.
    3. `EventsTest.php` or `EventApiTest.php`: rules 40 to 42 (a Yes, a Replay only, a No alone, a Yes of a member who lost the access, an event that happens now, an event that ended, a cancelled event, a draft) through the action and through `DELETE`, with the status and the words. `EventPagesTest.php`: the props of rule 31 and `mustCancelBeforeDelete` for the host, a moderator and an admin.
    4. `ContentSecurityPolicyTest.php`: the event page sends the same `frame-src` as a lesson, with the two player hosts and no other new origin.
    5. `PolicyTest.php`: the rows of `viewReplay`. `CommunityExportTest`: `replay_url` and `notes`.
    6. Vitest: `EventAfter.spec.tsx` (each state of rules 32 to 37: before the end, the player that loads nothing before Play, the plain link, a link that `safeHttpUrl()` refuses, `replay_expected` without a link, a visitor with notes, the host links), `Show.spec.tsx` (the order of design 6.2), `EventForm.spec.tsx` (the fieldset only on a published past event, the descriptions of rules 47 and 48, a field error), `EventActions` in its spec (the dialog of rule 44 and its "Cancel event", the text of rule 45), `event.schema.spec.ts`, with axe in each spec of a screen.
13. Bruno: "Events - Update - Replay and notes", "Events - Update - Replay before the end", "Events - Delete - Cancel it first".

For both commits:

14. Documents. ADR 0053 gets a part "Comments and after the event (#1516)": rules 1, 2, 7 to 10, 14, 16, 17, 22, 23, 27 to 29, 40 to 44, each [Q] answer, the amendment of ADR 0042 (the new kind `event_comment` and the reason `hosts_event`) and of ADR 0044 point 9 (the list of the comments of an event is a public read), and these alternatives: reuse `post_comment` for an event (wrong words, a shared key), comments on a draft (the notification rule reads the space), the replay with the readers of the meeting address (a member with Replay only would not get the replay), the replay or the notes at any time (a replay line before the event in E4), a delete with a confirmation that tells nobody (decision 17). `documentation/architecture/backend.md` (comments of three items, the delete rule), `frontend.md` part "Events" (the section after the event, the comments), `roles-and-permissions.md` (the rows "Comment an event" and "Read the replay link of an event", and "Delete" with rule 40), the API README (the routes), `documentation/design/events/design.md` and `documentation/design/README.md` (the status line: slice E3 is built), `release-test.md` (the specs in the table, the keyboard steps of the "browser check" points 1 to 5, a part "Slice E3 (#1516): what was run").
15. The screen atlas (`tools/screen-atlas/`): `atlas-seed.php` gives the atlas a past event with a player, a past event with a plain link and notes, a past event with `replay_expected` and no link, an event with comments and replies, and a future event with a Yes; `atlas-unseed.php` removes them. `screens.json`, area `events`: "Event page, after the event with a replay", "Event page, after the event, plain link and notes", "Event page, comments", "Event form, after the event", "Delete, cancel it first".
16. The browser check (definition of done 1). Run `npm run build` on the host first. Then, in both themes, at 360 px and at 1920 px, with no sideways scroll and no CSP error in the console:
    1. As a member, comment an event, reply, edit, delete with the keyboard. As the host in another browser: the line "<name> commented on your event" opens the event page at the comment.
    2. As the host of a past event: open the form, reach "Replay link" with Tab, hear its description, paste an address of one of the two video hosts and notes, save. On the event page, read the network panel: no request to the video host before Play; after Play the frame loads with no CSP error.
    3. The same with an `example.com` address: a plain link that opens in a new tab.
    4. As a visitor on a public space: the notes and the comments, no replay, no word about it. As a member with Replay only of a `going` event: the replay.
    5. As the host of a future event with a Yes: "Delete" opens "Cancel this event first?", "Cancel event" cancels; then "Delete" deletes. As a moderator with `delete_content`: the same dialog.
17. Pictures for the owner. ONE HTML page, `~/Downloads/knowii-2026-11-23-events-e3/index.html`, one row per state, four pictures per row (dark 360, light 360, dark 1920, light 1920), named `<number>-<state>-<theme>-<width>.png`, not in the repository: `after-player` (before Play), `after-link-notes`, `after-replay-expected`, `after-visitor`, `comments`, `form-after`, `delete-cancel-first`, `notification-line`. 8 rows, 32 pictures.

If the reviewer wants one commit, squash the two: the cut exists so that the comments, which touch the notifications, are read apart from the rest.

## NOT in this commit

1. Kind 29 ("The replay of ... is ready"), `replay_notified_at`, kind 26 for a cancel, every other notification row of an event and every email (E4 and E5, #1482). Until E4, a cancel tells members through the page, "My events" and the calendar file only.
2. Marking the rows of an event as seen when its page opens (E4, with the reminder rule "saw the event").
3. A follow of an event, a level 2 row for a comment, a reaction on the event, a count of comments on a card or in "Next events".
4. An upload of a recording, a player for any other host, a new origin in the Content Security Policy, a fetch of the replay link by the server, a check that the link still works.
5. The replay or the notes in the calendar file, in "My events", or on a card.
6. A change of `CommentPolicy` for posts and resources, of the comment form, or of the toolkit. If a toolkit block must change, it gets its story with a `play` function, and you say why in the report.
7. The delete rule for a space or a community that holds events (their cascade stays).
8. The import of past events with replay links (decision 15: a script of the owner, later).

## How to work

1. `git fetch origin`, then branch `feat/1516-events-e3` from `origin/main`. Two commits, no push, no pull request. If the working tree holds changes of another person, stop and ask: no stash, no reset.
2. Docker on this Linux machine, from `~/wks/knowii`: `docker compose exec -T -u sail laravel.test <command> </dev/null`. Do not start, stop or restart containers. No `migrate:fresh` and no seeder on the development database. Run `php artisan migrate` for the two new migrations, and check that each `down` works on the test database (a test, or `migrate:rollback --step=1` then `migrate` on the test database only).
3. PHP commands in the container: `composer format`, `composer lint`, `composer security:php`, and the whole Pest suite ONE time per commit with `php artisan test --parallel`. Front-end commands on the host: `npx nx format --all`, `npm run lint:common`, `npm run tsc`, `npm run tsc:spec`, and the whole Vitest suite ONE time per commit with `npm run test:all`. Write the summary of each full run to a file and read the file. Never run the whole Pest suite and the whole Vitest suite at the same time.
4. A piped test command hides a failure: use `bash -c "set -o pipefail; ..."`. While you build, run one test file or one spec file at a time.
5. Run `npm run build` on the host before the first Pest run on the branch and before each browser check.
6. Write the tests of rules 8, 10, 11, 13, 16, 27 to 29, 40 and 50 before the code, and see them fail.
7. Use the toolkit blocks (`VideoPlayer`, `MarkdownContent`, `MarkdownEditor`, `Dialog`, `Button`, `ErrorBoundary`). A file of `Components/ui` imports nothing from the application (`boundary.spec.ts`).
8. `InstanceNeutralNamesTest` reads `tests/` too. Never name a third-party community, meeting or calendar product, in the code, a test, the atlas data, a document or a commit. The two video hosts appear only where the code of lessons already names them (`VideoEmbed`, the configuration, the tests of videos, the policy sentence). Use `example.com` addresses and `example` names everywhere else.
9. The commits: a conventional type, an allowed scope (all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build), a header of at most 100 characters that does not start with a capital letter. Suggested: `feat(api): events, comments on an event with their notifications (slice E3)` and `feat(ui): events, the replay link and the notes after the event, and cancel before a delete (slice E3)`. No trailer line that names a tool or a co-author.
10. If a rule cannot be built as written, skip it, build the rest, and say why in the report. Do not invent product rules.
11. If you find a weakness in the code of `main` while you work, do not write it in a commit, a test name, the ADR or an issue. Handle it as `SECURITY.md` says, and tell the reviewer in private.

## Questions for the owner

Until the owner answers, the builder builds what each recommendation says. The owner approves the words and the pictures before the merge.

1. Who reads the replay link. Design 2.4 point 3 says "the readers of the meeting address: members who can see the event". With `going`, the meeting address is for Yes only, so a member with Replay only would not get the replay that the answer asks for. Recommendation: every member of the community who can see the event, whatever `location_visibility` says (rule 27). Never a visitor or a person who is not a member.
2. The notification of a comment on an event. The design says "kind 1" (the kind of a comment on my post). Its words ("replied to your post", "You wrote this post") are wrong for an event, and its key is the post. Recommendation: a new kind `event_comment`, level 1, reason `hosts_event`, with "{who} commented on your event" and "You host this event, and someone commented on it." (rules 16 to 21). Alternative: `post_comment` with other words when the subject is an event.
3. When the replay and the notes can be set. Recommendation: only on a published event whose end passed; a clear at any time (rules 23 and 24). The design puts them "after the event", and kind 29 of E4 would say "the replay is ready" before an event that did not happen. Alternative: from the start of the event.
4. Back to draft with comments. Recommendation: refused, "Members commented on this event. Cancel it instead." (rule 9), as with an answer today. A draft then never holds comments, and no notification can name a draft. Alternative: allow it, hide the comments and delete their rows.
5. The delete rule. Recommendation: an event counts as "future" until its end (an event that happens now too); only the Yes and Replay only answers of members who can read the space today block a delete; the page offers "Cancel event" in place of the delete (rules 40 to 44). The words "so members are told" stay: until E4 the cancel shows in the page, "My events" and the calendar file, and E4 adds the line.
6. The sentence about the agreement of the people in a recording. You approved it on 2026-11-17 as proposed: "Before you add a recording, make sure the people in it agreed." The form shows it word for word as the description of "Replay link", with no other sentence about consent (rule 47). Recommendation: confirm it word for word with the picture `form-after` before the merge.
7. The policy (Data rule 10), both files, section 2 item 4: "events (their title, description, date, place, meeting address, replay link and notes)". The sentence about videos: "Videos in course lessons and in the replays of events come from" the two names of today, the rest unchanged. Recommendation: approve both as written. No new retention line: the link, the notes and the comments are content.
8. The [W] words of "The words", points 3 to 7, and the look of the section "After the event". Recommendation: approve them as proposed, with the pictures.
9. The cut. Recommendation: two commits on one branch, comments first (`Refs #1516`), then the rest (`Closes #1516`). The first one touches the notifications and the shared comments; the second one touches the event, the form and the policy.

## Your final report (max 400 words)

1. The two commit hashes and `git show --stat` of each.
2. The tests of rules 8, 10, 11, 13, 16, 27 to 29, 40 and 50: their failure before the code and their pass after it.
3. Each choice where the brief left room, each [W] word and each [Q] rule that you built in another way, and each rule that you did not build as written.
4. How `Comment::subject()` and every place of facts 3 to 16 now know an event, as a list of files.
5. The results of Pest, PHPStan, Pint, the security scan, the type checks, Vitest and the format check, for each commit, with the exact text of anything that failed.
6. The five steps of the browser check, each with what you saw, and the requests that the network panel showed before and after Play.
7. The path of the HTML page with the pictures, and the number of pictures.
8. Anything of the scope not built, and why.
