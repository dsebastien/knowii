State: DECIDED on 2026-11-23 (see "Decided by the owner").

# Brief: the member setting "Show me in the member list" (knowii-oss/knowii#1466, slice 1: points 1 and 4 for the member list)

## Decided by the owner on 2026-11-23

1. Every recommendation of "Questions for the owner" is the decision (questions 1 to 12), with the server-side fix of "Send a message" from a member page.
2. The words of the privacy policy and of the screen proposed in this brief are approved word for word. Copy them exactly; change no other word of a legal text.
3. Build after events E3 (#1516) is merged.

Nothing is built. The owner answers the "Questions for the owner" at the end before the build starts. Until then, each recommendation is the proposal, and the builder builds what it says. [W] marks a word of a screen or of a legal text that the owner has not approved yet: each [W] word of a screen is one value in one words object, so a later change is one line.

Today every member of a community reads the whole member list of that community: on the page "Members" (#148, ADR 0056), in the member search, in the member fields and in the mention suggestions. Nobody can leave it. After this slice, each member has one switch in the profile: "Show me in the member list of my communities". When it is off, the other members do not find the person in a member list. The managers still see the person, with a marker. The team of a community (the owner, the admins, the moderators) is always listed. The member page, the posts and a typed mention do not change: hiding stops discovery, it is not a privacy wall.

This slice is a gate. In the live community of the owner, 117 of 610 people hid from the member list of the old tool. The import of #1396 must carry that choice, so this slice lands before the import, and before the launch.

You build ONE commit on a local branch, or the clean cut of section "The cut" into two commits. A reviewer reads your diff afterwards. Paths are relative to `~/wks/knowii`. Line numbers are those of `main` at `263eb757` (2026-11-23). Find the text when a line moved.

## Read first

1. The issue (`gh issue view 1466 --repo knowii-oss/knowii --comments`), the comment of 2026-11-20 on #148 (the later slices of the directory: point 1 is this slice), #1451 (the community defaults: points 2, 3 and 5), #1396 (the import), #1389 and ADR 0060 (member tags).
2. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md` (architecture rules 2, 3, 4, 5 and 6, data rules 6 and 10, front-end rules 2, 3, 10 and 12, the definition of done), `documentation/conventions/naming.md`.
3. ADRs: 0020 (with its update of 2026-11-20), 0022, 0027, 0028 (point 5), 0044, 0048, 0056, 0060.
4. `documentation/architecture/roles-and-permissions.md` (the paragraph of the member directory, line 118, and the paragraph "Visitors"), `documentation/architecture/backend.md` (`CommunityMemberQuery`), `documentation/api/README.md`.
5. Server code: `app/Support/CommunityMemberQuery.php`, `app/Http/Controllers/API/MemberApiController.php`, `app/Http/Controllers/Inertia/MemberController.php`, `app/Http/Controllers/Inertia/CommunityController.php` (`about`, `team`), `app/Http/Controllers/Inertia/ConversationController.php`, `app/Http/Resources/MemberWithRoleResource.php`, `app/Http/Resources/UserResource.php`, `app/Policies/CommunityPolicy.php`, `app/Support/CommunityRoles.php`, `app/Support/Markdown.php` (lines 103 to 126), `app/Actions/Users/UpdateOnlineStatus.php` and its contract, `app/Http/Controllers/API/UserApiController.php` (lines 85 to 97), `app/Actions/Users/ExportUser.php`, `routes/api.php`, `app/ApiRoutes.php`, `database/migrations/2026_11_10_130000_add_show_online_status_to_users.php`.
6. Front-end code: `apps/knowii/src/Pages/Profile/Show.tsx`, `Pages/Profile/Partials/OnlineStatusForm.tsx` and its spec, `Pages/Members/Index.tsx`, `Components/Communities/CommunityMembersManager.tsx`, `Pages/Messages/Index.tsx`, `Pages/Members/Show.tsx` (line 116), `Components/Messages/MemberPicker.tsx`, `Components/Members/use-mention-source.ts`, `libs/common/src/lib/types/member.schema.ts`, `libs/common/src/lib/types/user.schema.ts`, `libs/common/src/lib/api/knowii-api-client.ts` (lines 1373 to 1456), `libs/common/src/lib/constants.ts` (line 270).
7. Tests: `tests/Feature/MemberDirectoryTest.php` (the helpers `directoryQueriesOf` and `directoryMemberQueriesOf`, lines 120 to 140), `PresenceTest.php` (lines 100 to 112, the test of the online status route), `MentionTest.php`, `EmailAddressPrivacyTest.php`, `UserExportTest.php` (line 145), `ApiTokenAbilitiesTest.php`, `BrunoCollectionTest.php`, `OpenApiDocumentTest.php`.

## What exists today (facts, with the evidence)

1. One query for every member list. `CommunityMemberQuery::for(Community, string $search = '', KnowiiMemberSort $sort = Role)` gives the owner and every person with a membership row of the community, with `community_role` and `community_member_since` (`CommunityMemberQuery.php:34-67`). `team()` is `for()` with the owner, the admins and the moderators only (`:83-88`). The query has no reader: every caller gets every member.
2. The callers of `for()` on the server. The API list `GET /api/v1/communities/{community}/members` (`MemberApiController.php:44-58`, `routes/api.php:293`, ability `communities:read`, limiter `member-lists`). The answer of a role change (`MemberApiController.php:72`). The directory page (`MemberController.php:65`). The team of the page "About" through `team()` (`CommunityController.php:139`).
3. The callers of the API list on the client. The directory ("Load more", `Pages/Members/Index.tsx:268`). The member management of the settings (`CommunityMembersManager.tsx:127`). The transfer of the ownership, which picks the admins from the first page (`TransferOwnershipForm.tsx:46`). The member field of a new conversation and of the host of an event (`MemberPicker.tsx:66-69`, through `members.search`). The mention suggestions (`use-mention-source.ts:12`). "Send a message" on a member page: the link `?to=<username>` (`Pages/Members/Show.tsx:116`) makes the Messages page search the member list for that username (`Pages/Messages/Index.tsx:46-57`).
4. An entry of the list. `MemberWithRoleResource` gives eight fields: `cuid`, `name`, `username`, `avatar_url`, `headline`, `role`, `member_since`, `tags` (`MemberWithRoleResource.php:36-47`, ADR 0060 consequence 2). A manager gets what a member gets, except the tags of the managers.
5. The managers. ADR 0060 decision 12 names them: the owner, the admins, and the moderators with the right `manage_members`. `CommunityPolicy::addCommunityMember` (`:115-118`) and `viewMemberTags` (`:201-204`) both use `CommunityRoles::allows(..., ManageMembers)`. The directory gives such a person the prop `canManageMembers` (`MemberController.php:88`).
6. The member page. `/community/{communitySlug}/member/{username}` answers 200 for any person of the community and 404 for anyone else (`MemberController.php:97-125`, line 103). It never reads the member list.
7. A typed mention. `Markdown::mentionableMembers()` links `@username` for the owner and every person with a membership row, and the mentioned people get a notification (`Markdown.php:103-126`). It does not read `CommunityMemberQuery`.
8. The online list. "Online now" reads the presence channel `community.{cuid}.online`. A member who hides the online status (`users.show_online_status`) never joins it, and the API list of the online members drops that member (`CommunityOnlineChannel.php:22`, `OnlineMemberApiController.php:87-103`, ADR 0027). It does not read `CommunityMemberQuery`.
9. The member count. `CommunityResource::member_count` is the count of the membership rows plus the owner, only when the controller counts them (`CommunityResource.php:51-52`, `CommunityController.php:73,124`). A visitor never gets it (ADR 0044, `roles-and-permissions.md:120`). The directory shows the `totalCount` of its own query ("128 members").
10. The pattern of an account switch. The online status: a boolean column on `users` with the default `true` (migration `2026_11_10_130000`), the action `UpdateOnlineStatus` behind `UpdatesOnlineStatus` (`UpdateOnlineStatus.php:16-28`), the route `PATCH /api/v1/user/online-status` with `user:write` (`routes/api.php:101`, `ApiRoutes.php:95`, `UserApiController.php:85-97`), the field of `UserResource` for the person only (`UserResource.php:38`), the line of the export (`ExportUser.php:197`), and a section of the profile page with a `Switch` that saves at once (`OnlineStatusForm.tsx`, `Pages/Profile/Show.tsx:52-57`).
11. The profile fields (ADR 0020) live on `user_profiles.field_visibility`, with no choice per community (ADR 0020 point 3). The online status lives on `users`.
12. The account settings have no privacy page. The profile page holds the sections one under the other (`Pages/Profile/Show.tsx:38-87`).
13. The data export of an account. `account.json` holds the account switches (`ExportUser.php:187-206`), and the read-me line of `account.json` names them (`ExportUser.php:1024`). The community export lists the members without their profile fields (`ExportCommunity.php:173-195,822`).
14. The privacy policy. Section 2, item 1, names "your choice to show or hide your online status" (`deploy/knowii.net/legal/policy.md:21`, `resources/legal-templates/policy.md:20`). The approved sentence of ADR 0056: "The members of a community see its member list: your name, your username, your picture, your headline, your role and the date you joined." (`policy.md:45`, template `:44`). Both are approved texts.
15. The next free ADR number is 0063 on 2026-11-23 (`documentation/adr/README.md`). Another brief drafted on the same day may take it: check it before you write.

## Rules

These rules are the recommended design. They become "decided" when the owner answers the questions at the end.

### The setting

1. One switch per account: new column `users.show_in_member_list`, boolean, not null, default `true` (question 1). There is no choice per community, as ADR 0020 point 3 and the online status.
2. An existing account gets `true` from the default of the column: nothing changes for it until the person turns the switch off. A new account gets `true` too (question 9). The model reads it with `User::showsInMemberList()`, in the form of `showsOnlineStatus()` (`User.php:287-290`), with `true` when the attribute is not loaded.
3. New action `App\Actions\Users\UpdateMemberListVisibility` behind the contract `App\Contracts\Users\UpdatesMemberListVisibility`, `update(User $user, array $input): User`, a copy of the shape of `UpdateOnlineStatus`: the rule `show_in_member_list` `required`, `boolean`; `forceFill` and `save`; one log line `Member list visibility changed` with the cuid of the user and `shown`. Bind it in `AppServiceProvider` beside `UpdatesOnlineStatus`. It is the one writer of the column: the import of #1396 calls it too.
4. New API route `PATCH /api/v1/user/member-list` (`ApiRoutes::USER_MEMBER_LIST = 'user/member-list'`), in the group that starts at `routes/api.php:94` (an account with a verified address), with `ability:user:write`. New method `UserApiController::updateMemberListVisibility()`, with a `#[BodyParameter('show_in_member_list', ...)]`. It answers 200 with the current user (`UserResource`), as the route of the online status. 422 for a missing or a wrong value.
5. `UserResource` gets `show_in_member_list`, for the person only (`$this->when($isSelf, ...)`), beside `show_online_status`. No other resource reads the column directly: another reader learns the effect only through rule 14.

### Who is in the member list, list by list

6. A hidden member is a member whose `show_in_member_list` is `false`, in a community where the member is not the owner, an admin or a moderator.
7. The team is always listed: in a community where a person is the owner, an admin or a moderator (with or without a moderator right), that person is in the member list of that community whatever the switch says (question 2). In another community where the same person is a member, the switch applies.
8. `CommunityMemberQuery::for()` gets the reader as a required second parameter: `for(Community $community, User $reader, string $search = '', KnowiiMemberSort $sort = KnowiiMemberSort::Role)`. A required reader makes each new caller decide who reads the list. When the reader cannot see hidden members (rule 12), the query keeps only the people for whom `users.show_in_member_list` is true, or who are the owner, or whose membership row has the role `admin` or `moderator`, or who are the reader. Write it as one `where` group in SQL: no query per member.
9. `team()` keeps its people: move the common part of `for()` into a private builder, and build `for()` and `team()` on it. The team needs no reader (rule 7).
10. Each list, for a reader who is a plain member (or a moderator without `manage_members`):
    1. The directory page and its props: no hidden member. The status line counts the entries that the reader gets ("120 members"), since it reads the `totalCount` of the same query.
    2. The API list `GET .../members`, with and without `query`, with each `sort`, with each `offset`: no hidden member, and `totalCount` does not count them.
    3. The search of the directory, of the member fields and of the mention suggestions: no hidden member, also for the exact username (they all read the API list, fact 3).
    4. The member field of a new conversation and of the host of an event: no hidden member (the same list).
    5. The team of the page "About": unchanged (rule 7).
    6. The answer of a role change (`MemberApiController.php:72`): pass the caller as the reader. The caller is a manager, so the answer is unchanged.
11. What does not change, for every reader:
    1. The member page of a hidden member answers 200 for a member of the community, as today (fact 6). It stays reachable from a post, a comment, a mention and a conversation.
    2. A typed mention `@username` of a hidden member still makes a link and a notification (fact 7, question 4). Only the suggestions leave out the person.
    3. The online list follows its own switch of ADR 0027 (question 5). The screen of rule 21 says so.
    4. The community count `member_count` counts every member, hidden ones too (question 6). A number names nobody. #1451 point 5 is the later switch that hides the counts.
    5. The role badge of the directory: unchanged, and a listed admin or moderator keeps it.
    6. The access group lists, the member list of a tag (managers only), the event answers, the reactions, the authors of posts and comments, and the participants of a conversation: unchanged. They are not member lists.
    7. A block: unchanged (ADR 0028 point 5).

### Who still sees a hidden member

12. New ability `CommunityPolicy::viewHiddenMembers(User $user, Community $community)`: `CommunityRoles::allows($user, $community, KnowiiModeratorPermission::ManageMembers)`, so the owner, the admins and the moderators with `manage_members`. It is the same set of people as `addCommunityMember` and `viewMemberTags` (fact 5). `CommunityMemberQuery` calls `Gate::forUser($reader)->check('viewHiddenMembers', $community)` once per query: never once per member.
13. A manager gets every member in every list of rule 10: the directory, the API list, the search, the member fields and the mention suggestions, the member management of the settings and the transfer of the ownership (question 3). The person themself gets their own entry in each list and in the search (question 10).
14. The entry gets a ninth field, `in_member_list` (boolean): whether the other members of this community find the person in the member list (rules 6 and 7). It is `true` on every entry that a plain member gets, except the reader's own entry. It is `false` only on the entries of hidden people, and only a manager and the hidden person themself get such an entry. The query computes it in SQL as a selected value (`community_in_member_list`), so the resource reads no relation. Add it to `communityMemberSchema` with `.default(true)`, and to the WARNING comments on both sides.
15. The directory and the member management of the settings show a marker on an entry with `in_member_list` false: a `Badge variant="neutral"` with the icon `FaEyeSlash` (`aria-hidden`) and the words [W] "Hidden from the list". It sits after the role badge.
16. A hidden reader of the directory (not a manager, not on the team) gets one line above the list: [W] "You are hidden from this list. Only you and the managers see your entry." and the link [W] "Change it in your profile" to `USER_PROFILE_URL`, in the form of the line of `OnlineMembers.tsx:40-47`. New prop `hiddenFromList` (boolean) of `Members/Index`, computed on the server; WARNING comments on both sides.

### "Send a message" to a hidden member

17. The link `?to=<username>` of a member page must keep working for a hidden member: today the Messages page finds the person through the member list (fact 3), and a hidden person is not in it.
18. `ConversationController::render()` gets a prop `recipient`: when the address holds `to` as a text, the person of this community (the owner or a person with a membership row) whose username is equal to it without the case, as an `AuthorResource`, or `null`. It is `null` for the reader themself, for a person outside the community, for an unknown username and for a value that is not a text. It ignores `show_in_member_list`: the reader already holds the username, and the member page answers for it (fact 6). WARNING comments on both sides.
19. `Pages/Messages/Index.tsx` opens the dialog with `props.recipient` (when `canStart`) in place of the call to `members.search`. The rest of the flow does not change. `StartConversation` still checks the block and the setting of the community.

### The screen of the setting

20. New section `apps/knowii/src/Pages/Profile/Partials/MemberListForm.tsx`, a copy of the shape of `OnlineStatusForm`: an `ActionSection` with a `Switch` that saves at once through `useInstantSave`, with `SaveStatus`, then `router.reload({ only: ['auth'] })`. New API client method `knowiiApiClient.users.updateMemberListVisibility(show: boolean)`, new constant `USER_MEMBER_LIST_API_PATH`. `user.schema.ts` gets `show_in_member_list: z.boolean().optional()`.
21. The words, in one object `MEMBER_LIST_WORDS` [W]:
    1. Title: "Member list".
    2. Description: "Who finds you in the member list of your communities."
    3. Label of the switch: "Show me in the member list of my communities".
    4. Text under the switch: "When this is off, the other members do not find you in the member list, the member search or the mention suggestions. The owner, the admins and the moderators who manage members still see you, and you stay in the list of a community where you are the owner, an admin or a moderator. This is not a privacy wall: members still see your posts and your profile, and can still mention you or send you a message from your profile. Your online status has its own switch."
22. Place: on the profile page, right after the section "Online status" (`Pages/Profile/Show.tsx:52-57`), with the same `Divider`. No new page and no new route of the web (question 7).
23. Keyboard, screen reader, both themes, 360 px and 1920 px: as `OnlineStatusForm`. The text of rule 21.4 is the description of the switch (`aria-describedby` of `Switch`), so a screen reader hears the effect with the label.

### The query and its cost

24. No new query per request beyond the one check of rule 12, and no query per member. The page and the API list cost the same number of queries with 3 members and with 30, with hidden people among them, for a member and for a manager (extend the test of `MemberDirectoryTest`).
25. No index on `users.show_in_member_list`: the query starts from the people of one community (ADR 0056 consequence 4), and the filter runs on those rows. Say in the report if the query plan of the demo data changes shape.

### Personal data

26. The choice is personal data of the account (data rule 10). The export of the account gets `show_in_member_list` in `account` of `account.json`, beside `show_online_status`, and the read-me line of `account.json` (`ExportUser.php:1024`) names "your choice to be in the member lists". The column goes with the account on a deletion (ADR 0024): no extra step.
27. The community export does not get the choice (question 11).
28. No retention period: the choice lives as long as the account (data rule 8 does not apply).
29. The privacy policy, in `deploy/knowii.net/legal/policy.md` and in `resources/legal-templates/policy.md`, the same change in both, with the words of "Questions for the owner" question 8. Write them in this commit (data rule 10 asks for the same pull request). The owner approves them word for word before the merge. Change no other word of a legal text.

### What the import of #1396 needs from this slice

30. This slice gives #1396:
    1. The column and its one writer: the import calls `UpdatesMemberListVisibility::update($user, ['show_in_member_list' => false])`.
    2. The rule for the import, written in the ADR: a choice that an import carries only ever hides. It never turns the switch on, because the switch is per account and the person may have chosen in another community.
    3. Where the choice waits until the join: the import creates invitations, never accounts (#1396 scope 2). So #1396 adds the flag to the invitation (its own column, its own migration) and applies it in the transaction of the join, as ADR 0060 does for the tags. Not in this slice.
    4. The words that #1396 may show at the join of a hidden person: out of scope here. Name it in the ADR as a question for #1396.

## Scope (in the commit)

1. Rules 1 to 30.
2. Files:
   1. New: the migration `database/migrations/2026_11_23_<time>_add_show_in_member_list_to_users.php` (with `down()`), `app/Actions/Users/UpdateMemberListVisibility.php`, `app/Contracts/Users/UpdatesMemberListVisibility.php`, `apps/knowii/src/Pages/Profile/Partials/MemberListForm.tsx` and its spec, `tests/Feature/MemberListVisibilityTest.php`, `documentation/api/User - Member list.bru`, the ADR of point 7.1.
   2. Changed on the server: `app/Support/CommunityMemberQuery.php`, `app/Policies/CommunityPolicy.php`, `app/Http/Resources/MemberWithRoleResource.php`, `app/Http/Resources/UserResource.php`, `app/Models/User.php`, `app/Http/Controllers/API/MemberApiController.php`, `app/Http/Controllers/API/UserApiController.php`, `app/Http/Controllers/Inertia/MemberController.php`, `app/Http/Controllers/Inertia/ConversationController.php`, `app/Actions/Users/ExportUser.php`, `app/ApiRoutes.php`, `app/Providers/AppServiceProvider.php`, `routes/api.php`.
   3. Changed on the client: `Pages/Profile/Show.tsx`, `Pages/Members/Index.tsx` and its spec, `Components/Communities/CommunityMembersManager.tsx` and its spec, `Pages/Messages/Index.tsx` and its test (`Components/Messages/Messages.spec.tsx`, or a new `Pages/Messages/Index.spec.tsx`), `libs/common/src/lib/types/member.schema.ts`, `libs/common/src/lib/types/user.schema.ts`, `libs/common/src/lib/api/knowii-api-client.ts`, `libs/common/src/lib/constants.ts`.
   4. No new configuration value. No new dependency. No new block in `Components/ui`.
3. Pest, in the new `tests/Feature/MemberListVisibilityTest.php`. Set up one community with the owner O, an admin A, a moderator MM with `manage_members`, a moderator MO without it, a plain member M, and a hidden plain member H with a name and a username that appear nowhere else. One test for each line:
   1. The default: a new account through the action of the registration reads `true`. A row created without the column reads `true` (the default of the migration).
   2. The route: H turns it off (200, `data.show_in_member_list` false, the column is false), and on again. 422 without the field and with `"yes"`. 401 without an account. 403 for a token without `user:write`, 200 with it. The log line holds the cuid and not the name.
   3. `UserResource`: `show_in_member_list` is in the answer of `GET /api/v1/user` for the person, and in no answer about another person (the member page of H, the author of a post of H, the API list).
   4. The leak scan: for M and for MO, the whole JSON of the API list (no query, a search by the start of the name of H, the exact username of H, each `sort`, `offset` 0 and 20) and the props of the directory page do not hold the cuid, the name or the username of H, and `totalCount` does not count H.
   5. The managers: O, A and MM get H in the API list, in the search and in the directory, with `in_member_list` false. Every other entry has `in_member_list` true.
   6. The person themself: H gets their own entry with `in_member_list` false, finds themself in the search, and gets `hiddenFromList` true on the directory page. H does not get another hidden member.
   7. The team: H made an admin, then a moderator without a right: listed for M in the API list and in the team of "About", with `in_member_list` true. The owner with the switch off: listed. H is an admin in community C2 and a member in C1: hidden in C1 for M, listed in C2.
   8. What does not change: M opens the member page of H (200). A post of M with `@<username of H>` links H and H gets the notification. `member_count` of the community counts H. The online list follows only `show_online_status` (H with the online status shown is in `GET .../online-members` for M).
   9. The pagination: 25 listed members and 3 hidden ones give M 20 items with `nextOffset` 20, then the 5 others, with no hidden one and no double. A manager gets 28 in all.
   10. The cost: the same number of queries for the page and for the API list with 3 members and with 30, with hidden people among them, for M and for A.
   11. "Send a message": the Messages page with `?to=<username of H>` gives M the prop `recipient` with H. `null` for the username of M, of a person outside the community, an unknown username, and `?to[]=x`. The prop never holds an email address.
   12. The export: after the switch is off, `account.json` holds `show_in_member_list` false, and its read-me line names the choice.
   13. `MemberDirectoryTest`, `CommunityMemberManagementTest`, `MentionTest`, `EmailAddressPrivacyTest`, `PresenceTest`, `UserExportTest`, `ApiTokenAbilitiesTest` and `ApiResourceArchitectureTest` pass, with only the changes that the new parameter of `for()` and the ninth field need. Say each change of an existing test in the report.
4. Vitest:
   1. `MemberListForm.spec.tsx`: the label, the text, the state from the prop, the call of the client, the save status, an error that puts the switch back, one axe check.
   2. `Pages/Members/Index.spec.tsx`: the marker on an entry with `in_member_list` false and not on another; the line and the link of rule 16 only with `hiddenFromList`.
   3. `CommunityMembersManager.spec.tsx`: the marker.
   4. `Messages.spec.tsx`: with `recipient` the dialog opens with that member, and no call of `members.search`; without it nothing opens.
   5. The client spec: `updateMemberListVisibility` sends `PATCH` with the body.
   6. Stories: none. No block of the toolkit is new or changed (ADR 0049). Say so in the report.
5. Bruno: `documentation/api/User - Member list.bru` (in the form of `User - Online status.bru`), and the documentation of `Members - List.bru` names the field `in_member_list` and the people that the list leaves out. `BrunoCollectionTest` stays green.
6. OpenAPI: `OpenApiDocumentTest` stays green, and `/docs/api` shows the new route with its body.
7. Documents:
   1. New ADR, the next free number (fact 15): "A member chooses to be in the member list of their communities". Amends 0056 (decisions 7, 10, 13 and 23: an entry has nine fields, the list leaves out the hidden members for a member reader, a manager gets more entries than a member, the sentence of the policy). Context: facts 1 to 9 and the 117 people of the live community, without the name of the old tool. Decision: rules 1 to 19, 26 to 30. Consequences: a new member list reads `CommunityMemberQuery` with a reader, so it gets the rule; hiding stops discovery and is not a privacy wall (the member page answers for a known username, a typed mention works); the community count still counts everyone until #1451 point 5. Alternatives: a choice per community on `community_members` (refused: the owner has no membership row, a transfer moves the rows, and ADR 0020 point 3 chose no choice per community); the switch on `user_profiles` (refused: it is not a profile field, it changes no field of an entry); hidden members out of the team too (refused: the team is a public function of the community); a hidden member out of the online list too (refused: question 5); a 404 on the member page of a hidden member (refused: it breaks every link of their posts).
   2. `documentation/adr/README.md`: the row.
   3. `documentation/architecture/roles-and-permissions.md`: the paragraph of the directory (line 118) gets the hidden members, the team rule and who sees them; a row for `viewHiddenMembers` in the table.
   4. `documentation/architecture/backend.md`: `CommunityMemberQuery::for()` and its reader.
   5. `documentation/api/README.md`: the new route and the field `in_member_list`.
   6. `documentation/conventions/naming.md`: one row, "hidden member | unlisted member, private member | A member whom the other members do not find in the member list, by their own choice (`users.show_in_member_list`, the ADR of this slice)".
   7. The privacy policy and its template (rule 29).
8. The browser check, in the Docker development environment (definition of done 1), with the demo data:
   1. As a plain member, open the profile: the section "Member list" under "Online status". Turn it off: the status says it is saved. Reload: it stays off.
   2. As another plain member, open the directory of a shared community: the hidden person is not in it, and not in the search, also for the exact username. Type `@` and the start of the username in a post: no suggestion. Type the full username and send: the mention links and the hidden person gets the notification.
   3. Open a post of the hidden person, then the member page, then "Send a message": the dialog opens with that person.
   4. As the owner: the directory and the settings show the hidden person with "Hidden from the list".
   5. As the hidden person: the line of rule 16 above the list, and the own entry with the marker.
   6. Make the hidden person a moderator: a plain member sees the entry again, with the badge.
   7. Steps 1, 4 and 5 with the keyboard only, in both themes, at 360 px and at 1920 px, with no sideways scroll and no CSP error.
9. Pictures for the owner, in both themes, at 360 px and at 1920 px: the section of the profile, an entry with the marker in the directory and in the settings, the line of rule 16.

## The cut

One commit is the plan. If the reviewer wants a smaller diff, the one clean cut is:

1. Commit 1, `feat(api)`: rules 1 to 14, 17 to 19, 24 to 30, with the tests of scope 3, the Bruno and OpenAPI parts, the documents and the policy. It holds the fix of "Send a message" (rules 17 to 19), so it never breaks that link.
2. Commit 2, `feat(ui)`: rules 15, 16 and 20 to 23, with the Vitest specs of scope 4.1 to 4.3 and the pictures.

## NOT in this commit

1. The other settings of #1466, each a later slice:
   1. "Show my email on my profile" (default off). It changes ADR 0020 point 1 ("the email address is never shown"): it needs its own ADR, and the policy.
   2. "Accept direct messages from members" (default on), with the exception for the moderators and the owners and the note on the side of the sender (#1466 point 5).
   3. "Show last seen" (default on for members). ADR 0048 says that nobody else reads the date of the last visit: it needs its own decision first.
   4. The list of the blocked members with "Unblock" (#1466 point 2, with #552). The API exists (`GET /api/v1/user/blocks`).
   5. A page "Privacy" in the account settings that gathers the online status, the member list and the switches above (question 7).
2. The import of #1396: the flag on the invitation and its use at the join (rule 30).
3. The community setting "Who sees the directory: members, or managers only" (#1451 point 2) and the switch that hides the member counts (#1451 point 5).
4. Any change of the online list, the presence channels, the member page, the mention links, the team of "About", the access group lists or the member list of a tag.
5. A choice per community.
6. The choice in the community export.
7. A notification or an email about the switch.

## How to work

1. `git fetch origin`, then branch `feat/1466-show-me-in-the-member-list` from `origin/main`. ONE commit (or the cut), no push, no pull request.
2. Docker on this Linux machine, from `~/wks/knowii`: `docker compose exec -T -u sail laravel.test <command> </dev/null`. Do not start, stop or restart containers. No `migrate:fresh` and no seeder on the development database. `php artisan migrate` for the new migration is allowed.
3. PHP commands in the container: `composer format`, `composer lint`, `composer security:php`, and the whole Pest suite with `php artisan test --parallel`. Front-end commands on the host: `npx nx format --all`, `npm run tsc`, `npm run tsc:spec`, `npx nx run-many -t lint typecheck test`. Never run the whole Pest suite and the whole Vitest suite at the same time.
4. A piped test command hides a failure: use `bash -c "set -o pipefail; ..."`. Without `--parallel`, `php artisan test` takes ONE path.
5. Write the tests of scope 3.4, 3.5 and 3.11 before the code.
6. Use `example.com` addresses. Never name a third-party community product, in the code, in a test, in the ADR or in the commit.
7. The commit: a conventional type (`feat`), one of the allowed scopes of `commitlint.config.js`, a header of at most 100 characters that does not start with a capital letter. Suggested: `feat(all): a member can hide from the member list of their communities`. Footer, after a blank line: `Refs #1466`. Not `Closes`: the other settings stay open. No trailer line that names a tool or a co-author.
8. If a rule cannot be built as written, skip that rule, build the rest, and say why in the report. Do not invent product rules.
9. If you find a weakness in the code of `main` while you work, do not write it in the commit, in a test name, in the ADR or in an issue. Handle it as `SECURITY.md` says, and tell the reviewer in private.

## Questions for the owner

1. Where the choice lives. One switch per account (`users.show_in_member_list`), or one per community (on the membership row)? Recommendation: per account. ADR 0020 point 3 and the online status chose no choice per community, the owner has no membership row, and one switch is easy to understand. A choice per community can come later on top of it.
2. The team. Can the owner, an admin or a moderator hide from the member list of a community that they run? Recommendation: no. The team is a public function (the page "About" lists it), and members need to find who runs the place. The switch still applies in the communities where the person is a plain member.
3. The managers in the directory. Do the owner, the admins and the moderators with `manage_members` see the hidden members in the directory too, not only in the settings? Recommendation: yes, with the marker "Hidden from the list". It is one list for them, they see the person in the settings anyway, and the marker tells them that the other members do not.
4. A typed mention. Can a member still mention a hidden member by the full username? Recommendation: yes. The suggestions leave the person out, so nobody discovers them; a person who types the full username already knows them, and the member page answers for that username. A block or the direct message setting of #1466 is the tool to stop contact.
5. The online list. Does a hidden member also leave "Online now"? Recommendation: no, the two switches stay apart, and the text of the switch says "Your online status has its own switch." A member who hides only from the list can still show that they are online, and one switch never changes another in secret. Alternative: the hidden members also leave the online list of the community (not of a chat space); it needs a change of the channel authorization, the API of the online members and the client.
6. The member count. Does "128 members" on the community page count the hidden members? Recommendation: yes. A number names nobody, and #1451 point 5 is the switch that hides the counts. The directory counts what the reader gets, so its number can be lower.
7. The screen. A section on the profile page now, or a new page "Privacy" in the account settings? Recommendation: a section "Member list" under "Online status" now. The page "Privacy" comes with the second setting of #1466, and gathers the switches then.
8. The privacy policy (approved texts). Proposed words, word for word, in both files [W]:
   1. Section 2, item 1: after "and your choice to show or hide your online status," add "and your choice to show or hide yourself in the member lists of your communities,".
   2. After the sentence "The members of a community see its member list: your name, your username, your picture, your headline, your role and the date you joined." add: "You can hide yourself from the member lists in your profile. The owner, the administrators and the moderators who manage members still see you there, with your choice, and the owner, the administrators and the moderators of a community are always in its member list."
   Recommendation: approve both for this commit.
9. The default. Shown or hidden for a new account, and for the accounts of today? Recommendation: shown for both, as the issue says ("default: shown to members only": a visitor never gets the list, ADR 0044). A hidden default empties the directory, and the people who hid in the old tool are hidden by the import.
10. The person themself. Does a hidden member see their own entry, with the marker and the line "You are hidden from this list"? Recommendation: yes. It shows the effect of the switch where it acts, with a link to change it.
11. The community export. Does it hold the choice of each member? Recommendation: no, not in this slice. It is a choice of the account, like the online status, and the community export holds no profile field of the members today.
12. The words of the screen [W] (rule 21, rule 15 and rule 16). Recommendation: approve them as written.

## Your final report (max 400 words)

1. The commit hash (or the two hashes) and `git show --stat`.
2. The tests of scope 3.4, 3.5 and 3.11: their failure before the code and their pass after it.
3. Each change of an existing test, and why.
4. Each choice where the brief left room, each [W] word that you changed, and each rule that you did not build as written.
5. The number of queries of the page and of the API list for a member and for a manager, and the query plan of the directory on the demo data.
6. The results of Pest, PHPStan, Pint, the security scan, the type checks, the lint, Vitest and the format check, with the exact text of anything that failed.
7. The seven steps of the browser check, each with what you saw, and the pictures of scope 9.
8. The state of the policy words: written as proposed, waiting for the approval of the owner before the merge.
9. Anything of the scope not built, and why.
