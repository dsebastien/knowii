# Brief: events, slice E2b: the screens of the answers (knowii-oss/knowii#1515)

## Decided by the owner on 2026-11-20

1. The words, the pictures and every recommendation of "Questions for the owner" are approved.
2. One more behavior: on a full event, a member with a Yes who chooses another answer or clears the answer gets one confirmation question ("Give up your place?") before the save. It is added to the commit of this slice.

State: DECIDED on 2026-11-20 (see "Decided by the owner"). Part E2a (the server of the answers and of the limit of places) is built and reviewed. It is the ONE commit `c3ce5487` on the branch `feat/1515-events-e2a` (pull request #1694, not merged yet). This brief is the full text of part E2b. Part E2c (the calendar file and "My events") gets its own brief.

You build the screens of the answers. A member answers an event on its page. The member reads the places that are left. The host, the owner and the admins read who answered. The form of the host gets three fields. ONE commit on a local branch: a reviewer reads your diff afterwards. Paths are relative to `~/wks/knowii`.

Until the owner answers the "Questions for the owner" at the end, build what each recommendation and each [W] word says. [W] marks a word that the design does not give. Each [W] word is one value in `event-words.ts`: a later change is one line.

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md` (Front-end 2, 3, 4, 8, 9, 11 and 12, and the definition of done), `documentation/architecture/frontend.md` (the parts "Toolkit" and "Events").
2. `documentation/design/briefs/brief-1515-events-e2.md`: "What the design decides for E2", "What the design leaves open", the rules of part E2a, the scope list of part E2b and the questions for the owner.
3. `documentation/design/events/design.md`: sections 3.6, 3.7, 5.B, 5.D, 5.F, 5.H, 6.1, 6.2, 6.3, 6.4, 6.7, 7.2, 7.4, 7.6, 9.4, 10.1 point 3, 11.4, 11.5. Then `release-test.md` of the same folder.
4. ADR 0053: the parts "The screens (#1514)" and "The answers and the places (#1515)". ADR 0049 (the toolkit).
5. The commit of E2a: `git show c3ce5487 --stat`, then `app/Http/Resources/EventResource.php`, `app/Http/Controllers/API/EventAnswerApiController.php`, `app/Actions/Events/AnswerEvent.php`, `app/Policies/EventPolicy.php`, `libs/common/src/lib/types/event-answer.schema.ts`, `libs/common/src/lib/types/event.schema.ts`, the five request files "Events - Answer ..." and "Events - Answers - List" in `documentation/api`.
6. The screens of E1b: `apps/knowii/src/Pages/Events/Show.tsx` and `Form.tsx`, every file of `apps/knowii/src/Components/Events/`, with their specs.
7. The toolkit blocks that you use: `SegmentedControl`, `SaveStatus`, `use-instant-save.ts`, `InputError`, `InputLabel`, `Radio`, `Checkbox`, `Badge`, `Dialog`, `LoadMoreButton`, `EmptyState`, `Skeleton`, `Avatar`, each with its stories. `Components/Communities/JoinPolicySetting.tsx` shows a group of radios that saves at once.

## What the design and the E2 brief decide (do not change it)

1. Three answers: "Yes", "Replay only", "No". "Replay only" is offered only when the host set `replay_expected` (design 3.6).
2. The answer group is on the event page, after the host and before the meeting block (design 6.2 point 2). Its legend is "Your answer". It is a `radiogroup` built with `SegmentedControl`. It saves at once. A status says "Saved: Yes". An error is `role="alert"` and the old choice returns (design 6.4 point 1, 11.4 point 1).
3. "Yes" on a full event is `aria-disabled="true"` with the reason as text, not a tooltip. A past or cancelled event shows the text of the old answer in place of the group. No `disabled` and no `loading`: `aria-disabled` and `aria-busy` with a guard (design 11.4 point 2, rules.md Front-end 12).
4. With a limit, a member reads "28 places left", and "Full" at the limit. No urgency words and no urgency color. Without a limit, a member reads no count (design 7.6 point 4).
5. A member sees their own answer, no other answer and no count. The host, the owner and the admins open "Answers": the names of Yes, then of Replay only, in alphabetical order, each a link to the member page, and the number of No without names. Read only, no export. A moderator does not read it (design 6.4).
6. A visitor reads "Sign in to answer". A person who is not a member gets no answer group (design 5.H, 7.4).
7. A card of the list shows "N places left" when a limit exists, and "You said Yes" for a member who answered (design 6.1 point 4).
8. The form of the host gets "who sees them" (the meeting address and the place), the limit, and "A replay will exist" (design 6.3 point 2). A host can lower the limit under the number of Yes answers. Nobody is removed. The form says how many Yes answers exist (design 7.6 point 5).
9. The server cuts the fields. The client hides nothing (design 7.3).
10. An answer tells nobody: no email and no notification row (E2 brief, "Notifications and email").

## What exists today (facts, with the evidence)

Line numbers are those of the branch `feat/1515-events-e2a` at `c3ce5487`.

The API of E2a:

1. `EventResource` gives five fields (`app/Http/Resources/EventResource.php:70-77`). `my_answer`: `yes`, `replay`, `no` or null. `can_answer`: the result of `EventPolicy::answer`. `replay_expected`: a boolean, for every reader. `places_left`: a number from 0, or null. `capacity`: the limit, or null. `eventSchema` mirrors them (`libs/common/src/lib/types/event.schema.ts:44-53`).
2. What each reader gets, in the order `my_answer`, `can_answer`, `replay_expected`, `places_left`, `capacity`:
   1. A visitor and a signed-in person who is not a member: null, false, the boolean, null, null.
   2. A member who can see a published event that is not over: the own answer or null, true, the boolean, the places or null without a limit, null.
   3. A moderator who is not the host: the same as a member.
   4. The host, the owner and an admin, with a session: the own answer, true, the boolean, the places, the limit. They answer like members.
   5. Every member, for a cancelled event, an event that ended and a draft: `can_answer` is false. `my_answer` keeps the old answer. `places_left` is still a number when a limit exists (`app/Models/Event.php:270-273`).
3. `can_answer` is false for two reasons that the field does not tell apart: the reader is not a member, or the event takes no answers. The page knows which one from `community.is_member`, `event.state` and `event.is_past`.
4. The meeting address and the place. With `going`, `EventPolicy::viewLocation` passes for the host, the owner, the admins and a member whose answer is `yes` (`app/Policies/EventPolicy.php:70-80`). `replay` and `no` do not pass. The resource then gives `can_view_location` true with `meeting_url` and `place` (`EventResource.php:66-68`).
5. The own answer: `GET`, `PUT` and `DELETE .../events/{event}/answer` (`app/Http/Controllers/API/EventAnswerApiController.php:46,68,81`). `PUT` takes `{ answer }` and gives `{ answer, updated_at }`. `DELETE` gives 204, also without an answer. The answer of the `PUT` holds no place count and no meeting address: the page gets them from the event again.
6. The refusals of a write, in their order: 404 (the reader cannot see the event), 403 "Only the members of the community answer an event", 422 "This event is a draft. It takes no answers.", 422 "This event is cancelled. It takes no answers.", 422 "This event is over. It takes no answers.", 400 with the field `answer` "This event has no replay.", 422 "This event is full." (`app/Actions/Events/AnswerEvent.php:44-52,79-81,131-163`). The same answer again gives 200 and is never refused for the limit. The limiter `event-answers` allows 30 writes a minute per member (429).
7. The list: `GET .../events/{event}/answers` (`EventAnswerApiController.php:100-118`). Query: `answer` (`yes`, the default, or `replay`), `limit` (default 10, maximum 100) and `offset`. `data`: the members as `AuthorResource` (`cuid`, `name`, `username`, `avatar_url`), by name then by username. `metadata`: `pagination` and `counts` `{ yes, replay, no }`. It gives no name for `no` and no time of an answer. It needs `EventPolicy::viewAnswers` (`EventPolicy.php:101`): the owner, an admin or the host, and never for a draft (403).
8. A change of `capacity` under the number of Yes answers is accepted. The API gives no 422 for it (`app/Actions/Events/UpdateEvent.php:66-68`, `CreateEvent.php:132`). Nobody is removed, and `places_left` is 0. `capacity` is `nullable`, an integer from 1 to 5 000. `location_visibility` is `members` or `going` (`CreateEvent.php:127`). `replay_expected` is a boolean (`CreateEvent.php:134`).
9. No field of `EventResource` gives the number of Yes answers. With a limit and places left, it is `capacity` minus `places_left`. With 0 places left, or without a limit, only `metadata.counts.yes` of the list gives it.
10. The live signal `space.event.answers.changed` is in `SPACE_CONTENT_EVENTS` (`libs/common/src/lib/hooks/use-socket.ts:82`). Its payload holds `event_cuid` only, with no `author_cuid`. The server sends it only when the number of Yes answers of an event with a limit changed (`AnswerEvent.php:191-196`). A change of the limit, of `replay_expected` or of `location_visibility` sends `space.event.updated`.
11. The client has the constants `MIN_EVENT_CAPACITY`, `MAX_EVENT_CAPACITY` and the two paths (`libs/common/src/lib/constants.ts:173-174,312-313`), and the schemas `eventAnswerSchema`, `eventAnswerCountsSchema` and `eventAnswerMemberSchema`. `knowiiApiClient.communities.events` has no method for an answer (`libs/common/src/lib/api/knowii-api-client.ts:581-668`). `apiPageRequest` reads `metadata.pagination` only (`libs/common/src/lib/api/api-result.ts:108-127`).
12. The test fixture `eventFixture` already holds the five fields (`apps/knowii/src/test-utils/events.ts`).

The screens of E1b:

13. The event page (`apps/knowii/src/Pages/Events/Show.tsx`) shows the title, the state line, the actions, the cover, the time, `EventHost`, `EventMeeting` and the description (lines 83 to 118). Nothing is between the host and the meeting block (lines 104 and 106). Its props are `community`, `space`, `spaceGroups`, `event` and `permissions` (lines 19 to 27).
14. The page listens to `space.event.created`, `.updated` and `.deleted`, and then loads all its props again when the event is this one and the author is another person (`Show.tsx:46-59`). It does not listen to `space.event.answers.changed`.
15. `EventController::show` gives `permissions` with `canUpdate`, `canCancel`, `canDelete` and `canDuplicate` (`app/Http/Controllers/Inertia/EventController.php:49-55`). No prop says who reads the answers. `EventPermissions` mirrors it (`libs/common/src/lib/types/event.schema.ts:72-80`).
16. The meeting block. A reader without `can_view_location` reads one sentence: a visitor "The meeting address is for members." with "Sign in", a person who is not a member the same sentence alone, and a member "The meeting address is for the host and the team." (`apps/knowii/src/Components/Events/EventMeeting.tsx:56-63`, `event-words.ts:54`). The last one is true only while no member can answer. A spec holds it (`apps/knowii/src/Pages/Events/Show.spec.tsx:314-317`).
17. A card of the list has one row of labels with `EventStateBadges` ("Draft", "Cancelled", "Happening now"), built with `Badge` (`Components/Events/EventCard.tsx:30-32`, `EventStateBadges.tsx`). "Next events" shows "Cancelled" only (`Components/Events/NextEvents.tsx:48-52`), and it does not change live (line 25).
18. The list gets one event again and replaces its card on the three signals of fact 14 (`Components/Events/EventList.tsx:203-241`).
19. The form of the host (`Components/Events/EventForm.tsx`). It uses react-hook-form with `eventFormSchema` (`libs/common/src/lib/api/communities/event-request.schema.ts:64-75`). "Meeting address" and "Place" each have a hint that ends with "Only members see it." (`EventForm.tsx:826-858`, `event-words.ts`: `meetingUrlHint`, `placeHint`). "Host" follows (line 863). The form sends no `location_visibility`, no `capacity` and no `replay_expected`: `eventRequestSchema` does not hold them (`event-request.schema.ts:80-102`). A create sends every field (`EventForm.tsx:442-452`). An update sends only the fields that changed (lines 478 to 500). The errors of the server go next to their fields through a map of names (lines 375 to 385).
20. The form page gets `permissions` with `canNotifyNow` and `canManageAllEvents` (`EventController.php:99-104`). Every person who opens the form of a published or cancelled event passes `viewAnswers`: both rules name the owner, the admins and the host.

The toolkit:

21. `SegmentedControl` needs a value: `value: V` (`apps/knowii/src/Components/ui/SegmentedControl.tsx:13`). It cannot show "no choice". It can disable only the whole group, with the native `disabled` (lines 20 and 86). It has no `aria-busy`, and no option can be `aria-disabled` alone.
22. `SaveStatus` says "Saving" and "Saved" with fixed words, and shows an error in the same polite status region (`Components/ui/SaveStatus.tsx:24-25,42-52`). `useInstantSave` shows the new value at once, returns the old one on a failure, and ignores a change while a save runs (`use-instant-save.ts:44-47`).
23. `InputError` is a paragraph with `role="alert"`. `Badge` has the variants `neutral`, `info`, `success`, `warning` and `danger`. `LoadMoreButton`, `EmptyState`, `Skeleton`, `Dialog`, `Radio` and `Checkbox` exist with their stories.

## Rules

Each rule is one sentence that a spec can prove. The words are in the part "The words".

### The answer group on the event page

1. A new component `Components/Events/EventAnswer.tsx` renders between `EventHost` and `EventMeeting`.
2. It renders the group when the reader is a member and `event.can_answer` is true and the end of the event is after `now` (`useNow`).
3. The group is a `fieldset` with the visible legend "Your answer" and one `SegmentedControl` whose accessible name is that legend. A screen reader says the name one time when the focus enters the group: follow `JoinPolicySetting.tsx` and prove it in the browser check.
4. The options, in this order: "Yes", "Replay only", "No". "Replay only" is an option only when `event.replay_expected` is true or `event.my_answer` is `replay`.
5. No answer: no radio is checked, and the first radio is the one tab stop of the group.
6. The sentence "The host and the team see your answer (Yes or Replay only). Other members do not." is under the group at all times, and it is the description of the group (`aria-describedby`). It does not come and go with the answer.
7. A choice saves at once with `knowiiApiClient.communities.events.answer.set`. The radio of the new choice is checked at once.
8. While a save runs, the group has `aria-busy="true"`, no radio is `disabled`, and the focus stays where it is.
9. When the member changes the choice while a save runs, the group sends the last choice after the running save ends. No choice is lost and no choice is sent two times. This matters for the keyboard: an arrow key moves the choice and saves it, so a member who goes from "Yes" to "No" passes "Replay only".
10. After a save, a polite status region says "Saved: Yes", "Saved: Replay only" or "Saved: No". The region is in the page before the save, also when it is empty (`SaveStatus`, with "Saving" while the save runs).
11. After a save, the page loads the prop `event` again and no other prop (`router.reload({ only: ['event'] })`). The radio, the places and the meeting block then show what the server gave.
12. A refused save: the old choice is checked again, an `InputError` under the group (`role="alert"`) shows the message of the server, the status region is empty, the focus stays in the group, and the page loads the prop `event` again. A spec proves it for "This event is full.", "This event is cancelled. It takes no answers.", "This event has no replay." and a failed request.
13. A 404 or a 403 from a save loads the whole page again: the server decides what the reader then sees.
14. A button "Clear my answer" is under the group only when the member has an answer. It calls `answer.clear`. After the clear, no radio is checked, the status says "Answer cleared", the button is gone, and the focus is on the first radio of the group. A refused clear follows rule 12.
15. A full event (`places_left` is 0) for a member whose answer is not Yes: the radio "Yes" has `aria-disabled="true"` and `aria-describedby` with the id of the places line, which says "Full". A click, Space and an arrow key on it send no request and check nothing. "Replay only" and "No" work.
16. A full event for a member whose answer is Yes: "Yes" is checked and is not `aria-disabled`.
17. A member with an old answer on an event that takes no answers (`can_answer` false, or the end is passed): the text "Your answer: Yes" (or the other answer) under the heading "Your answer", with no radio, no button and no places line. This covers a cancelled event and an event that ended.
18. A member without an answer on such an event, and every reader of a draft: the component renders nothing.
19. A visitor on a published event that is not over: the heading "Your answer" and a `SignInPrompt` with "Sign in to answer". On a cancelled event or a past event: nothing.
20. A signed-in person who is not a member: nothing.
21. The component sends no request when it renders. It reads `event.my_answer` of the page.

### The places

22. The places line is one line of text under the group, only when the group shows and `event.places_left` is a number: "28 places left", "1 place left", or "Full" for 0.
23. The line has the same color as other secondary text for every number. It has no icon and no word of urgency.
24. Without a limit (`places_left` null), the page shows no line and no count.
25. No screen shows the limit to a member. The limit shows in two places only: the field of the form, and the line "12 of 40 places taken" of the dialog "Answers" (rule 36).

### The meeting block

26. `meetingForHostAndTeam` goes. A member who gets no meeting address reads "The meeting address is for the members who answer Yes." while `event.can_answer` is true, and "The meeting address is for the members who answered Yes." when it is false.
27. A member who answers Yes on a `going` event reads "Join the meeting" and the place after the reload of rule 11, with no reload by hand. A member who goes from Yes to another answer, or clears it, reads the sentence of rule 26 again. `EventMeeting` still shows only what it received.
28. A visitor and a person who is not a member read what they read today.

### The labels of a card and of "Next events"

29. A new component `Components/Events/EventAnswerBadges.tsx` renders the labels with `Badge`. `EventCard` renders it in its row of labels, after `EventStateBadges`. `NextEvents` renders it next to "Cancelled".
30. The label of the answer shows when `my_answer` is not null, on every event, also a cancelled or past one: "You said Yes" (`success`, with a check icon), "You said Replay only" (`info`), "You said No" (`neutral`).
31. The label of the places shows when `places_left` is a number and the event is published and not over: "28 places left", "1 place left" or "Full", always `neutral`.
32. A visitor and a person who is not a member get no label: the server gives them null for both fields.
33. No card and no line of "Next events" has an answer control. A member answers on the event page.

### "Answers", for the host, the owner and the admins

34. A button "Answers" shows in the header of the event page, next to "Edit", only when `permissions.canViewAnswers` is true.
35. The button opens a `Dialog` with the title "Answers" and the description "The host, the owner and the admins see this list. Other members do not." Escape closes it, and the focus returns to the button.
36. The dialog loads when it opens, and each time it opens: `answers.list` with `answer` `yes`, a limit of 20 and offset 0. While it loads, it shows `Skeleton` lines and `aria-busy`. It shows a summary line: "12 of 40 places taken" when `event.capacity` is a number.
37. A `SegmentedControl` with the accessible name "Answers to show" has the options "Yes (12)" and "Replay only (3)", with the numbers of `metadata.counts`. It shows only when `event.replay_expected` is true or `counts.replay` is over 0. A change of the choice loads the first page of that answer.
38. The names are a real list (`ul`). Each row has the `Avatar`, the name as a link to the member page (`MEMBER_URL`), and "@username" as `EventHost` writes it. Move the row of `EventHost` to one small component of `Components/Events` and use it in both places.
39. When `pagination.nextOffset` is not null, a `LoadMoreButton` "Load more" loads the next 20. After the load, the focus is on the name of the first new row, and a status says "20 more loaded" with the real number (`EVENT_WORDS.loaded`).
40. Under the list, one line: "4 members answered No." or "1 member answered No.". It shows also when the number is 0 ("No member answered No."). No name of a "No" is in the page.
41. An empty list shows an `EmptyState`: "Nobody answered Yes yet" or "Nobody answered Replay only yet".
42. A failed load shows the message of the server in an `InputError` in the dialog. A 403 or a 404 closes the dialog and loads the page again.
43. The dialog has no export, no copy button, no search and no time of an answer.
44. The dialog does not listen to the live signal. The signal is not sent for an event without a limit and not for "Replay only" or "No", so it cannot keep the list current. The list is current each time the dialog opens (ADR 0053, "The answers and the places", answer 7).

### The form of the host

45. Three fields come after "Place" and before "Host", in this order.
46. "Who sees the meeting address and the place": a `fieldset` with that legend and two `Radio` rows, "Every member" (`members`) and "Members who answer Yes" (`going`). A new event starts with "Every member". An edit starts with the stored value. Under the group, with `going`: "The host, the owner and the admins always see them."
47. The hints of "Meeting address" and "Place" follow the choice. With `members` they end with "Only members see it." as today. With `going` they end with "Only members who answer Yes see it."
48. "Limit of places": a number field with an `InputLabel`. Empty means no limit. The hint: "Leave empty for no limit. Only the Yes answers take a place." `eventFormSchema` accepts an empty text or a whole number from `MIN_EVENT_CAPACITY` to `MAX_EVENT_CAPACITY`. Another value shows "Use a whole number from 1 to 5000, or leave the field empty" in the `InputError` of the field, and nothing is sent.
49. On the form of a published or cancelled event, the form reads the number of Yes answers one time with `answers.list` (`answer` `yes`, limit 1) and shows "12 members answered Yes." or "1 member answered Yes." under the limit. With 0 it shows nothing. A new event and a draft send no such request. A failed request shows no line and no error.
50. When the typed limit is under that number, the line becomes "12 members answered Yes. Nobody is removed. No other member can answer Yes." in a polite status region. It is a hint, not an error: the save is not stopped, because the API accepts the value.
51. "A replay will exist": a `Checkbox` with its label, built like "Publish now" (`EventForm.tsx:891-904`). The hint: "Members can then answer Replay only." A new event starts without it.
52. `eventRequestSchema` gets `location_visibility`, `capacity` (a number or null) and `replay_expected`, all optional. A create sends the three. An update sends only the ones that changed. An empty limit is sent as null.
53. A field error of the server for `capacity`, `location_visibility` or `replay_expected` shows next to its field, through the map of fact 19.
54. The form of a copy shows the limit and the replay choice of the copied event: the server already copied them.

### The live update

55. The event page also listens to `space.event.answers.changed`. For this event, it loads the prop `event` again and no other prop. It does not close the dialog "Answers", it does not move the focus, and it does not empty the status region or the error of the answer group.
56. When the signal arrives while a save of the answer runs, the page does one reload after the save, not two.
57. The list of the space also gets the event again on this signal and replaces its card (`refreshEvent`), so "28 places left" and "Full" stay current. "Next events" still does not change live.
58. The form of the host listens to nothing, as today. No signal can change what the host typed.
59. A visitor still opens no connection.

### A member who lost the access, a visitor, a person who is not a member

60. The client adds no rule for these readers: it shows what the API gives. A member who lost the access to the space gets 404 from the page and from every route. A visitor follows rules 19 and 32. A person who is not a member follows rules 20 and 32, and reads the meeting sentence of today.
61. No request of this slice runs for a visitor. A spec of the event page and a spec of the list prove that no call of the answer client happens for a visitor.

### Keyboard, screen reader, themes and the phone width

62. Every new control works with the keyboard and shows its focus: Tab reaches the group one time, the arrow keys move the choice, Tab reaches "Clear my answer", "Answers" opens with Enter and with Space.
63. Each new screen passes `axe-core` in its spec, in each state of the rules above.
64. At 360 px the group wraps, nothing scrolls sideways, and each radio, "Clear my answer", "Answers" and each name of the dialog is at least 44 px high. Both themes use the brand tokens only.
65. "Full", "You said Yes" and every other label are text. No state is a color alone.

## The words

Every word goes in `event-words.ts`, in its style. D marks a word of the design. [W] marks a proposal for the owner.

1. D: "Your answer", "Yes", "Replay only", "No", "Saved: Yes" (`saved: (answer) => ...`), "28 places left", "Full", "Sign in to answer", "You said Yes", "Answers", "A replay will exist".
2. From the E2 brief, question 4 (it waits for the owner there): "The host and the team see your answer (Yes or Replay only). Other members do not."
3. [W] The answer group: "Clear my answer", "Answer cleared", "1 place left", "Your answer: Yes" (the text of an old answer, with each of the three answers).
4. [W] The labels: "You said Replay only", "You said No".
5. [W] The meeting block: "The meeting address is for the members who answer Yes.", "The meeting address is for the members who answered Yes."
6. [W] The dialog: "The host, the owner and the admins see this list. Other members do not.", "Answers to show", "Yes (12)" and "Replay only (3)", "12 of 40 places taken", "4 members answered No.", "1 member answered No.", "No member answered No.", "Nobody answered Yes yet", "Nobody answered Replay only yet".
7. [W] The form: "Who sees the meeting address and the place", "Every member", "Members who answer Yes", "The host, the owner and the admins always see them.", "An address that starts with https://. Only members who answer Yes see it.", "For an event in a room. Only members who answer Yes see it.", "Limit of places", "Leave empty for no limit. Only the Yes answers take a place.", "Use a whole number from 1 to 5000, or leave the field empty", "12 members answered Yes.", "1 member answered Yes.", "12 members answered Yes. Nobody is removed. No other member can answer Yes.", "Members can then answer Replay only."
8. The messages of a refusal are the ones of the server. The client adds only its two failure texts, in the style of the other calls: "Failed to save your answer." and "Failed to load the answers."

## Server changes

The server part is one prop. No API field, no route, no migration and no action changes.

1. `EventController::show` adds `permissions.canViewAnswers`: `Gate::check('viewAnswers', $event)`. It is false for a visitor, a person who is not a member, a member, a moderator who is not the host, and for every reader of a draft. `EventPermissions` gets the field.
2. Why a prop: the client cannot derive it. `capacity` is null for the host of an event without a limit, so no field of the event says who reads the answers.
3. Why no prop for the number of Yes answers of the form: the list route gives it in `metadata.counts`, and every person who opens the form of a published or cancelled event can read that route (fact 20).
4. Pest, in `tests/Feature/EventPagesTest.php`: the test of the permissions per role (line 300) gets `canViewAnswers` for the owner, an admin, a moderator, the host, a member, a person who is not a member and a visitor. A second test: the page of a draft gives `canViewAnswers` false to its host, the owner and an admin.
5. If a rule needs another server change, stop and say why in the report. Do not add an API field.

## Scope (in the commit)

1. Rules 1 to 65, the words, and the server change.
2. The toolkit. Two blocks change, no block is new:
   1. `SegmentedControl`: `value` accepts null (no radio checked). An option accepts `ariaDisabled` and `describedBy` (the radio gets `aria-disabled` and `aria-describedby`, and the block ignores a change to it). The group accepts `busy` (`aria-busy`). Stories with a `play` function: "No choice", "One option not available" (a click and an arrow key change nothing), "Busy". The comment of the meta gets the new contract.
   2. `SaveStatus`: an optional `savedText` in place of "Saved". One story, and its case in `SaveStatus.spec.tsx`.
   3. Check the two places that use `SegmentedControl` today (`CreateCommunityDialog` and `UpdateCommunityForm`) and `FormControls.spec.tsx`: they look and work as before.
3. New files: `Components/Events/EventAnswer.tsx`, `EventAnswerBadges.tsx`, `EventAnswersDialog.tsx`, the small component of rule 38, and one spec for each.
4. Changed client files: `Pages/Events/Show.tsx`, `Components/Events/EventMeeting.tsx`, `EventCard.tsx`, `NextEvents.tsx`, `EventList.tsx`, `EventHost.tsx`, `EventActions.tsx` (only when the button "Answers" lives there), `EventForm.tsx`, `event-words.ts`, and their specs.
5. `libs/common`:
   1. `knowiiApiClient.communities.events.answer` with `get`, `set` and `clear`, and `answers.list(communityCuid, spaceCuid, eventCuid, answer, limit, offset)`, which gives the items, the pagination and `counts`.
   2. `apiPageRequest` gets an optional schema for more keys of `metadata`, or a sibling function does it. The calls of today do not change.
   3. `EVENT_ANSWERS_PER_PAGE` (20) in `constants.ts`. `eventFormSchema` and `eventRequestSchema` with the three fields. `EventPermissions` with `canViewAnswers`. The comment "No `location_visibility`" of `event-request.schema.ts` goes.
6. Vitest, one case for each line, with axe in each spec of a screen:
   1. `EventAnswer.spec.tsx`: rules 2 to 21, one case per rule. Rule 9 with two changes during one slow save. Rule 12 with the four refusals. Rule 15 with a click, Space and an arrow key.
   2. `EventAnswersDialog.spec.tsx`: rules 35 to 44, with a list of 45 members over three pages, an empty list, a failed load, the filter absent without a replay, and no request while the dialog is closed.
   3. `Show.spec.tsx`: the order host, answer group, meeting block. The button "Answers" per `canViewAnswers`. Rules 26 to 28 (the case of line 314 changes). Rules 55 and 56 with a fake signal. No call of the answer client for a visitor.
   4. `EventList.spec.tsx` and `NextEvents.spec.tsx`: rules 29 to 33, and rule 57.
   5. `EventForm.spec.tsx`: rules 45 to 54. A create sends the three fields. An update sends only the changed ones. The limit "0", "5001", "1.5" and "abc" are refused. The line of the Yes answers for 0, 1 and 12, and the hint when the limit is under it.
   6. `libs/common`: `events-api.spec.ts` (the four calls, a 422 with its message, a 204), `event-request.schema.spec.ts` (the three fields), and the case of the pagination helper with `counts`.
   7. `stories.spec.tsx` runs the new stories. `stories-guard.spec.ts` and `boundary.spec.ts` pass.
7. Pest: the two tests of "Server changes", point 4.
8. The screen atlas (`tools/screen-atlas/`):
   1. `atlas-seed.php` (the events are at lines 237 to 251): one event with a limit of 40, `replay_expected` and `going`, with 12 Yes, 3 "Replay only" and 4 "No" answers, set through `AnswerEvent`. One event with a limit of 1 that is full. One cancelled event with a Yes of the member of the atlas. `atlas-unseed.php` removes them.
   2. `screens.json` (the area `events`, from line 1941): "Event page, a member who did not answer", "Event page, a member who answered Yes", "Event page, a full event", "Event page, a cancelled event with an old answer", "Event page, the answers for the host" and "Event form, an event with a limit".
9. Documents:
   1. ADR 0053, the part "The screens (#1514)": a new block "The screens of the answers (#1515)" with rules 2, 6, 9, 11, 15, 17, 25, 26, 30, 44, 49, 50 and 55, the prop `canViewAnswers`, and each [W] word as built. Answer 4 of that part ("The form has no field for `location_visibility`") and the sentence "for the host and the team" of answer 1 get the new state.
   2. `documentation/architecture/frontend.md`, the part "Events": points 2 to 5 get the labels, the answer group, the dialog and the three fields. The part "Toolkit" does not change: no block is new.
   3. `documentation/design/events/release-test.md`: the table of the specs gets the new specs and states. The keyboard script gets the steps "answer", "change the answer with the arrow keys", "clear the answer", "open Answers, load more, close". A new part "Slice E2b (#1515): what was run" says what you ran and what waits for a person.
   4. `documentation/design/events/design.md` and `documentation/design/README.md`: the status line says that the screens of the answers are built.
   5. `documentation/architecture/backend.md` or the page that lists the Inertia props of the event page, if one names `permissions`: add `canViewAnswers`.
10. The browser check (definition of done 1). Run `npm run build` on the host first: the application runs from built assets. Then, in both themes, at 360 px and at 1920 px, with no sideways scroll and no CSP error in the console:
    1. As a member, open an event with a limit and a replay. Answer Yes with the keyboard. Read "Saved: Yes" and one place less.
    2. Change to "No" with the arrow keys, through "Replay only". The page ends on "Saved: No", and the server holds "No".
    3. Press "Clear my answer". No radio is checked, and the focus is on the first radio.
    4. On a `going` event: read the sentence of rule 26, answer Yes, and read "Join the meeting" with no reload by hand. Answer "No": the sentence is back.
    5. With a second account, fill an event with a limit of 1. As the member, read "Full", and try "Yes" with a click and with the keyboard: nothing is sent. Open the page before the event is full, fill it with the second account, then press "Yes": read the alert "This event is full." and the old choice.
    6. With two browsers on the same event: an answer Yes in one changes "places left" in the other, on the page and on the card of the list.
    7. As the host: open "Answers", change to "Replay only", press "Load more" on a list of more than 20, close with Escape. The focus is on "Answers".
    8. As a moderator who is not the host, as a member, and as a visitor: no button "Answers". As a visitor: "Sign in to answer", and no request to the answer routes in the network panel.
    9. Cancel the event as the host. As the member: "Your answer: Yes" and no group. Publish it again: the group is back with "Yes" checked.
    10. The form: choose "Members who answer Yes" and read the two hints. Type a limit under the number of Yes answers, read the hint, and save. Tick "A replay will exist", save, and see "Replay only" on the event page.
    11. The screen reader pass of the answer group. Read the accessibility tree of the browser for the group: its role, its name (one time), its description, the state of each radio, `aria-disabled` on "Yes" of a full event, `aria-busy` during a save, and the text of the status region after a save and of the alert after a refusal. Say in the report that a pass with a real screen reader waits for a person, and add it to `release-test.md`.
11. Pictures for the owner. Make ONE HTML page, `~/Downloads/knowii-2026-11-20-events-e2b/index.html`, with the pictures side by side: one row per state, four pictures per row (dark 360, light 360, dark 1920, light 1920). A picture is named `<number>-<state>-<theme>-<width>.png`. The owner approves these looks before the merge:
    1. `answer-none`: the event page of a member who did not answer a `going` event with a replay and 28 places left. It shows the group, the sentence, the places line and the meeting sentence.
    2. `answer-yes`: the same page after "Yes": "Saved: Yes", "27 places left", "Clear my answer" and "Join the meeting".
    3. `answer-full`: a full event for a member without an answer: "Yes" not available, and "Full".
    4. `answer-refused`: the alert "This event is full." after a refused Yes.
    5. `answer-closed`: a cancelled event with "Your answer: Yes".
    6. `answer-visitor`: "Sign in to answer".
    7. `cards`: the list of the space with "You said Yes", "You said Replay only", "You said No", "28 places left", "Full" and "Cancelled" on its cards.
    8. `next-events`: "Next events" on the home with the labels.
    9. `answers-list`: the dialog "Answers" of the host with more than 20 Yes, the filter, "12 of 40 places taken", "Load more" and the line of the No answers.
    10. `answers-empty`: the dialog with "Nobody answered Yes yet".
    11. `form-fields`: the three fields with "Members who answer Yes" chosen, a limit under the number of Yes answers with its hint, and "A replay will exist" ticked.
        The page has 11 rows and 44 pictures. Do not put the pictures in the repository.

If the reviewer wants a smaller diff, the one clean cut is the three fields of the form (rules 45 to 54 with their specs) as a second commit. The rest does not split.

## NOT in this commit

1. Part E2c: "Add to calendar", the calendar file, `GET user/events`, the page "My events" and its entry in the user menu.
2. E3 (#1516): comments, the replay link, the notes, "cancel it first" before a delete, the sentence about recordings.
3. E4 and E5 (#1482): every notification row, every reminder, every email, and the words "This tells 14 members" of the cancel question. An answer tells nobody.
4. An export, a copy or a print of the answers. A search in the answers. The time of an answer.
5. A waiting list, a promotion, paid events (#1413). A manager who adds or changes the answer of a member.
6. An answer control on a card, on "Next events" or in a notification. The "Catch up" card.
7. A new API field, a new route, a change of `EventResource`, of a policy or of an action. A change of the live signal.
8. A new toolkit block. A change of a block other than the two of scope 2.
9. A record of who opened the dialog, the meeting or the page.

## How to work

1. Check first if pull request #1694 is merged. If it is not: branch `feat/1515-events-e2b` from the local branch `feat/1515-events-e2a` at `c3ce5487`. If it is: `git fetch origin`, then branch from `origin/main`, and make sure that the E2a commit is in it. ONE commit, no push, no pull request. If the working tree holds changes of another person, stop and ask: no stash, no reset. Find the text when a line number moved.
2. Docker on this Linux machine, from `~/wks/knowii`: `docker compose exec -T -u sail laravel.test <command> </dev/null`. Do not start, stop or restart containers. No `migrate:fresh` and no seeder on the development database. This slice has no migration.
3. PHP commands in the container: `composer format`, `composer lint`, `composer security:php`, and the whole Pest suite ONE time with `php artisan test --parallel`. Front-end commands on the host: `npx nx format --all`, `npm run lint:common`, `npm run tsc`, `npm run tsc:spec`, and the whole Vitest suite ONE time with `npm run test:all`. Never run the whole Pest suite and the whole Vitest suite at the same time.
4. A piped test command hides a failure: use `bash -c "set -o pipefail; ..."`. Without `--parallel`, `php artisan test` takes ONE path. While you build, run one spec file at a time.
5. Run `npm run build` on the host before each browser check: the application runs from built assets, and a page without a new build shows the old screens.
6. Write the specs of rules 9, 12, 15 and 61 before the code, and see them fail.
7. Use the toolkit blocks. Do not write a radio group, a status region, a dialog, a load-more button or a label by hand. A file of `Components/ui` imports nothing from the application (`boundary.spec.ts`).
8. `InstanceNeutralNamesTest` reads `tests/` too. Never name a third-party community, meeting or calendar product, in the code, in a test, in the atlas data, in a document or in the commit. Use `example.com` addresses.
9. The commit: a conventional type, one of the allowed scopes (all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build), a header of at most 100 characters that does not start with a capital letter. Suggested: `feat(ui): events, the answer of a member, the places and the list of the answers (slice E2b)`. Footer, after a blank line: `Refs #1515` (the issue closes with part E2c). No trailer line that names a tool or a co-author.
10. If a rule cannot be built as written, skip that rule, build the rest, and say why in the report. Do not invent product rules.
11. If you find a weakness in the code of `main` or of E2a while you work, do not write it in the commit, in a test name, in the ADR or in an issue. Handle it as `SECURITY.md` says, and tell the reviewer in private.

## Questions for the owner

1. The [W] words. The list is in "The words", points 3 to 7. Recommendation: approve them as proposed. Each one is one line of `event-words.ts`, and the pictures show them in place.
2. The clear of an answer. A radio cannot be unchecked, and the design says that a member clears their answer. A button "Clear my answer" under the group, or no clear on the screen (the member answers "No")? Recommendation: the button. "No" and "no answer" are two states for the later reminders and the summary.
3. The labels. The design names only "You said Yes" on a card. Show also "You said Replay only" and "You said No"? Show the labels on "Next events" too, with "28 places left" and "Full"? Recommendation: yes to both. A member then tells an answered event from an event that still waits for an answer, in the two places.
4. Where "Answers" opens. The design says that the host "opens" it. A dialog from a button on the event page, one answer at a time with a choice "Yes" or "Replay only", 20 names per load? Or a part of the page that is always open? Recommendation: the dialog. A list of up to 5 000 names does not push the description down, and the names of members are not on the screen until the host asks for them.
5. The limit for the host. A member reads "28 places left" and never the limit. The host, the owner and the admins read "12 of 40 places taken" in the dialog "Answers", and "12 members answered Yes." under the limit of the form. Recommendation: yes. They already read the names, and the form needs the number when the host lowers the limit.
6. The sentence about who sees the answer. The design shows it "before answering". Recommendation: keep it under the group at all times while the group shows. A member who changes an answer reads the same fact, and the page does not move after the first answer.

## Your final report (max 400 words)

1. The commit hash and `git show --stat`.
2. The specs of rules 9, 12, 15 and 61: their failure before the code and their pass after it.
3. Each choice where the brief left room, each [W] word that you built in another way, and each rule that you did not build as written.
4. The two changed toolkit blocks: the new properties, the stories, and the result for the two places that already use `SegmentedControl`.
5. The results of Pest, PHPStan, Pint, the security scan, the type checks, Vitest and the format check, with the exact text of anything that failed.
6. The eleven steps of the browser check, each with what you saw, and what waits for a person.
7. The path of the HTML page with the pictures, and the number of pictures.
8. Anything of the scope not built, and why.
