# Design and brief: member tags (knowii-oss/knowii#1389)

## Decided by the owner on 2026-11-20

1. Every recommendation of "Questions for the owner" is the decision for the build: no screen shows a member their own `managers` tags; the owner and the admins define the tags, and the moderators with `manage_members` assign them; no display choice per tag (T2 has one switch); the palette of eight tokens, with pictures; the words "Members" and "Managers only"; the limits.
2. The four sentences of the privacy policy and the text of the form are built as proposed. The owner reads these exact words before the merge of T1 (they were not shown to him word for word when he said "go").
3. Build T1 after #1612, with the cut "the server first, then the screens" if it is too large for one commit.
4. 2026-11-22: the owner read and approved word for word the four sentences of the privacy policy (rule 27, points 1 to 4) and the text of the form (decision 35). The builder copies them exactly. No other approval is needed before the merge of T1.

State: DECIDED on 2026-11-20 (see "Decided by the owner"). Nothing is built. The owner answers the "Questions for the owner" before the build of part T1 starts. Until then, each recommendation and each [Q] rule is the proposal.

A member tag is a label that the managers of a community put on a member, for example "Founder" or "Onboarded". The platform has none today. This document holds the design (numbered decisions, each with its reason), the cut in four parts (T1 to T4), the full brief of part T1, and the scope lists of parts T2 to T4. Three other slices of the launch scope use tags: the member directory (#148), the bulk invitations from a CSV file (#1390) and the invite links (#1391). So the data model comes first, and section "The contract for #1390 and #1391" says what they get. A tag is personal data: data rules 8 to 10 apply. Paths are relative to `~/wks/knowii`.

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md` (Architecture 1 to 6, Data 1, 2, 6, 7, 8 and 10, Front-end 2 to 4, 11 and 12, the definition of done), `documentation/conventions/naming.md`.
2. The issues, with their comments: #1389, #148, #1390, #1391, #1241 (access groups, closed), #1466 (member privacy settings), #1401 (the member table, after the launch), #1402 (segments), #1403 (custom profile fields), #1602 (one `Badge` for every pill).
3. ADRs: 0004, 0006, 0011, 0014, 0017 (the four moderator rights), 0020, 0022, 0024, 0025 and 0041 (access groups: the model to compare with), 0035 (ownership transfer), 0043, 0044 (what a visitor never gets), 0049 (the toolkit).
4. `documentation/architecture/roles-and-permissions.md` (rows 52 to 54 and line 111), `documentation/architecture/backend.md` ("Access groups", line 414), `documentation/api/README.md` (the abilities table).
5. The briefs `brief-148-member-directory-slice-1.md` (facts 1 to 5 and 14 on the member list) and `brief-1688-profile-headline.md` (the member list gets a seventh field).

## What exists today (facts, with the evidence)

Line numbers are those of `main` on 2026-11-20 (commit `abaa1c9e`). Facts 1 to 12 are the access groups: what a tag can reuse, and what it must not copy. Facts 13 to 20 are the places where a tag will show.

1. The tables. `access_groups` has `community_id`, `name`, `description` and a unique `(community_id, name)` that reads the case: "Paid" and "paid" are two groups (`database/migrations/2026_11_09_239000_create_access_groups_tables.php:16-25`). `access_group_members` links a group to a user, not to the row of `community_members`, with `created_at` only (lines 27 to 35). Both foreign keys cascade.
2. Why the link is on the user. The owner of a community has no row in `community_members`: the member query adds the owner (`app/Support/CommunityMemberQuery.php:28-33`). A transfer of ownership moves rows of that table (ADR 0035).
3. The model. `AccessGroup` uses `GeneratesCuid`, `#[UseResource]`, hides `id` and `community_id`, and binds routes by `cuid` (`app/Models/AccessGroup.php:31-44,91-94`).
4. The actions. `CreateAccessGroup` checks the gate, trims the name, validates, holds the limit with a `BusinessException`, writes a log line with cuids only, and broadcasts (`app/Actions/AccessGroups/CreateAccessGroup.php:21-42`). The rules are in one class (`AccessGroupInputRules.php:14-25`). `AddAccessGroupMember` takes exactly one of `member`, `username` or `email`, changes nothing the second time, and answers "No member of the community matches" for a person outside the community (`AddAccessGroupMember.php:31-35,65-67`). `RemoveAccessGroupMember` answers 404 for a member who is not in the group (`RemoveAccessGroupMember.php:27-31`).
5. The policy. Three abilities on `CommunityPolicy`: `manageAccessGroups` (the owner and the admins), `manageAccessGroupMembers` (plus the moderators with `manage_members`), `viewAccessGroups` (`app/Policies/CommunityPolicy.php:145-166`). `CommunityRoles::allows` is the one place that applies ADR 0017 (`app/Support/CommunityRoles.php:74-78`).
6. The API. Seven routes for the groups and their members, inside `scopeBindings` (`routes/api.php:279-287`, `app/ApiRoutes.php:256-263`, `AccessGroupApiController.php:42-143`). The abilities `groups:read` and `groups:write` are not in the default set of a token (`app/Providers/JetstreamServiceProvider.php:165-167,173`, `libs/common/src/lib/types/jetstream/jetstream-inertia.intf.ts:207-208,236-237`). The members of a group are paged with `Pagination`, sorted by name, as `AuthorResource` plus the source (`AccessGroupApiController.php:99-114`).
7. The limits. `MIN_LENGTH_ACCESS_GROUP_NAME` 2, `MAX_LENGTH_ACCESS_GROUP_NAME` 64, a description of 500, 50 groups per community (`app/Constants.php:197-203`, `libs/common/src/lib/constants.ts:394-408`). The contracts are bound in `AppServiceProvider.php:292-296`.
8. The live signal. Each change sends `community.access_groups.changed` with the cuid of the community to every member on the community channel (`app/Events/AccessGroups/AccessGroupsChanged.php:36-42`). The settings page listens (`apps/knowii/src/Pages/Communities/Settings.tsx:73-81`).
9. The screens. `AccessGroupsManager` is one file of 748 lines with four dialogs (`apps/knowii/src/Components/Communities/AccessGroupsManager.tsx:112,196,398,502`). It sits on the settings page after `CommunityMembersManager` (`Settings.tsx:142-161`). The permission props come from `CommunityController.php:185-187` and `libs/common/src/lib/types/community.schema.ts:147-149`.
10. A leave and a removal. `Community::removeUser` deletes the group rows of the member in that community (`app/Models/Community.php:365-372`). A deleted account loses its rows by the cascade of the foreign key.
11. The exports. The member export lists the access groups of the member in `memberships.json` (`app/Actions/Users/ExportUser.php:102,267-281`, the readme line 906). The community export lists each group with the cuids of its members in `community.json` (`app/Actions/Communities/ExportCommunity.php:154,222-238`, the readme line 697).
12. The legal texts. The policy names "your access groups" (`deploy/knowii.net/legal/policy.md:23`, `resources/legal-templates/policy.md:22`), the data that other people give (line 27), and who sees the member list (line 44). The data processing agreement says that the community does not ask its members for special categories of data (`deploy/knowii.net/legal/dpa.md:26`). No text names a tag.
13. The member list of the API. `GET /api/v1/communities/{community}/members` answers to every member of the community, not only to the managers (`routes/api.php:269`, `MemberApiController.php:39-47`, `CommunityPolicy.php:28-31`). An entry holds six fields (`app/Http/Resources/MemberWithRoleResource.php:29-36`). The settings screen, the member pickers and the mention list read it (`CommunityMembersManager.tsx:87`). So a field `tags` on that entry reaches every member.
14. The member page. `/community/{communitySlug}/member/{username}` is behind the sign-in (`routes/web.php:70`). `MemberController` gives `MemberProfileResource`, which wraps a user and knows no community (`app/Http/Controllers/Inertia/MemberController.php:22-48`, `MemberProfileResource.php:37-48`). The name is the `h1` of `apps/knowii/src/Pages/Members/Show.tsx:75`.
15. The author of a post or a comment. `AuthorResource` holds four fields and knows no community (`app/Http/Resources/AuthorResource.php:23-29`). Posts, comments, chat messages, pages, events, notifications, conversations, reactions and the blocked list use it (`PostResource.php:34`, `CommentResource.php:28`, `ChatMessageResource.php:27`, `NotificationResource.php:45`, `ConversationResource.php:32`). A notification crosses communities, and a tag belongs to one community.
16. No shared author line exists in React. Three files write the name as plain text: `Components/Posts/PostCard.tsx:34`, `Pages/Posts/Show.tsx:211`, `Components/Comments/CommentSection.tsx:367`.
17. A visitor reads the posts of a public space with the name and the picture of the author (`policy.md:46`, ADR 0044). A visitor never gets a member list or a profile field.
18. The toolkit. `Badge` has five variants that carry a meaning, and its text is in the color of all text on every variant (`apps/knowii/src/Components/ui/Badge.tsx:8-24,50`). `Dot` has two tones. `Combobox`, `Dialog`, `Switch`, `SegmentedControl`, `Select`, `Checkbox`, `SaveStatus` and `LoadMoreButton` are in `Components/ui`. The color tokens hold no palette for labels (`apps/knowii/src/styles.css:38-65` and `99-120`).
19. An allow-list of emoji exists for the reactions (`App\Support\Emoji::isAllowed`, `app/Actions/Reactions/ToggleReaction.php:32-35`).
20. A unique index that ignores the case exists for the names of shared communities (`database/migrations/2026_11_10_170000_make_shared_community_names_unique.php:15`).
21. What does not exist: a table of tags (no migration names one), a member directory (#148 is open), a manager table with bulk actions (#1401 is after the launch), an invitation that carries a group or a tag (#1390, #1391).

## The design

Each decision has its reason. "Refused" names an option that this design does not take.

The data model:

1. Two tables: `member_tags` (the tags of a community) and `member_tag_members` (which member has which tag), with the model `MemberTag`. Reason: the same shape as `access_groups` and `access_group_members`, and `naming.md` asks for both names in a link table. Refused: one JSON column of names on the member row (no rename, no count, no filter by index).
2. The link is `(member_tag_id, user_id)`, and the community comes from the tag. Reason: facts 1 and 2. The owner can get a tag ("Founder"), and a transfer of ownership keeps the tags. Refused: a key on `community_members.id`.
3. `naming.md` gets the row "member tag" (do not use: label, badge, tag alone). The word "tag" alone stays free of the meaning "mention".
4. The name: 2 to 32 characters, trimmed, inner spaces reduced to one, no line break. It is unique per community without the case: a unique index on `(community_id, lower(name))`. The name is stored as typed. Reason: the CSV file of #1390 names tags by their name, and "Founder" and "founder" as two tags make wrong imports. Refused: the case-sensitive index of access groups (fact 1).
5. A tag has no description and no free text beside its name. A link row has no note. Reason: a managers tag is a note about a person. One short name limits what a manager can write about a member.
6. Limits: 100 tags per community, 20 tags per member in one community. Constants in `App\Constants`, mirrored in `constants.ts` (data rule 6).
7. The color is one key of a fixed palette of 8: `gray` (the default), `red`, `orange`, `yellow`, `green`, `teal`, `blue`, `purple`. The database stores the key with a check constraint. Refused: a free color (no contrast promise), the magenta of the brand (it marks actions, #1602), the five variants of `Badge` (they mean a state).
8. The palette is 8 tokens per theme, `--color-tag-<key>`, in `styles.css`. `Badge` gets an optional `color`: it colors the border and a small mark before the text. The text keeps the color of all text, so its contrast never depends on the tag. Each token has a contrast of 3:1 or more on `background`, `surface`, `surface-elevated` and `well` of its theme. The name is always shown: the color alone never carries a meaning.
9. No emoji and no icon in T1. T2 adds one optional emoji of the allow-list of the reactions (fact 19). Refused: a set of icons (it needs a picker and a second icon map, against #1602), and a pill with an emoji and no name (no name for a screen reader, no meaning for a new member).
10. A tag is a label and never an access rule. It opens no space and gives no right. Access groups do that (ADR 0025). Reason: so that a wrong tag never opens paid content, and a moderator can put one on any member, also on themself.

Visibility:

11. Two values: `members` (the members of the community see the tag) and `managers`. The default is `managers`. Reason: the default favors privacy. The issue says "public" for the first value. This design does not use that word: for a space, `public` means every person on the internet (ADR 0044).
12. "Managers" are the owner, the admins and the moderators with the right `manage_members`: the same people who see the member management today (fact 5). A moderator without the right reads tags as a member does. Refused: a fifth moderator right (ADR 0017 keeps four switches).
13. A `managers` tag does not exist for another reader: it is in no list, no count and no filter, and a route with its cuid answers 404 with the body of a tag that does not exist.
14. A member sees their own `members` tags, as the other members do. A member does not see their own `managers` tags on a screen, and reads them in the export of their data [Q, question 1]. The form of a tag tells the manager so.
15. A visitor and a person who is not a member get no tag, in no response, also next to an author of a public space.
16. A change from `managers` to `members` shows the tag on every member who has it. The dialog says the number of members and asks again. Only the owner and the admins can do it.

Who does what:

17. The owner and the admins create a tag, rename it, change its color and its visibility, and delete it (`CommunityPolicy::manageMemberTags`). Reason: each of these acts on every member who has the tag at once [Q, question 2].
18. The owner, the admins and the moderators with `manage_members` put a tag on a member and take it off (`CommunityPolicy::assignMemberTags`). The same people read every tag (`viewMemberTags`). The role hierarchy of #1242 does not apply: a tag gives no right (decision 10).
19. A delete removes the tag from every member, and from every invitation and invite link that carries it later (a cascade). The dialog says the number of members. Nothing can undo it.
20. Nothing tells a member that they got or lost a tag: no notification, no email. No broadcast is sent for a tag in T1. Refused: the signal of fact 8, which tells every member the moment of a change of a managers tag.
21. The platform does not store who put a tag. Reason: less data. The audit log (#934) is the place for that.

Where a tag shows:

22. One component, `MemberTagList`, renders the tags of one member as `Badge` items in a list with the name "Tags". No other pill is built (#1602). For a manager, a `managers` tag has a lock mark and the hidden words "managers only".
23. The member page shows every tag that the reader can see, under the name (T1).
24. The member list of the settings shows the tags on each row (T1). The directory shows at most three tags per entry, then "+N" (T4).
25. Next to the name of an author of a post and of a comment: at most two tags, and only the tags that a manager marked "Show next to the name in posts and comments" (T2, a boolean per tag, off by default) [Q, question 3]. Refused: the choice "label, icon, or both" of the issue. The label always shows. Not in chat messages, notifications or conversations.
26. `AuthorResource` does not change: it knows no community (fact 15). T2 gives `PostResource` and `CommentResource` a field `author_tags`, loaded with one query per page.

The filter:

27. `GET .../members` gets the query parameter `memberTag` (a cuid, one tag at first). A reader who cannot see that tag gets the validation error of a tag that does not exist. The manager list uses it in T3, the directory in T4 with `members` tags only.

The API:

28. Routes under `communities/{community}/member-tags`, like the access groups (fact 6): list, create, update, delete, the members of a tag (paged), add one member, remove one member. T3 adds one bulk command.
29. New abilities `member-tags:read` and `member-tags:write`, not in the default set of a token. Reason: ADR 0022 gives each area its pair, and a token that syncs tags must not manage paid groups. The field `tags` of a member entry follows the policy of the reader, with `communities:read`.
30. A member is named by `member` (the user cuid) or `username`. The routes of the tags take no email address: no sync by address is planned, and #1390 reads addresses in its own action.

Personal data:

31. The community owner decides which tags exist and who gets them. The operator stores them for the owner (policy, section 1). In plain words: the managers label the members to run their community, and the member did not write this data. So the policy names it under "data that we get from other people", and no new legal basis line is needed: "To run your account and the communities you join" covers it.
32. The member export lists every tag of the member, of both visibilities. The community export lists the tags with the cuids of their members.
33. A leave and a removal delete the tags of the member in that community. A deleted account loses all its tags. A member who comes back starts with none. No tag stays on a join block.
34. A tag has no time limit, so `config/retention.php` gets no entry and no prune step. The policy says when a tag ends (data rule 8 is for data with a limited life).
35. The form of a tag shows this text, and the terms or the content rules can repeat it [Q, question 5]: "A tag is personal data about each member who has it. Do not record health, religion, political opinions or other sensitive facts in a tag. Each member can read all their tags in the export of their data."
36. The log lines hold cuids and never the name of a tag.

## The contract for #1390 and #1391

T1 gives these slices five things. It adds no column to an invitation.

1. `App\Support\MemberTagAssignments::add(MemberTag $tag, User $member): bool` and `remove(...)`: the one writer of the link table. It checks that the member is in the community of the tag, holds the limit of 20, and changes nothing the second time. It checks no gate: the caller did.
2. `MemberTag::findByName(Community $community, string $name): ?MemberTag`: the lookup of the CSV file, without the case, after the same cleaning as the name rule.
3. `CommunityPolicy::assignMemberTags`: who can put a tag on an invitation, on a link or on a row of a file. An invitation holds at most 20 tags.
4. Their link tables (an invitation to a tag, an invite link to a tag) are theirs. Each has a foreign key to `member_tags` with `cascadeOnDelete`: a deleted tag leaves the invitation.
5. The tags apply when the person joins, through point 1, in the transaction of the join. A tag of any visibility can come this way. Whether a file can create a tag that does not exist is a decision of #1390. This design recommends no: an unknown name is a refused row in the preview.

## The cut

1. T1, "The tags, their API and the manager screens": the tables, the actions, the policy, the API, the field `tags` of a member entry, the section "Member tags" of the settings, the tags of one member from the member list, the tags on the member page, both exports, the policy text, the new ADR. It stores new personal data, so it carries the legal text (data rule 10).
2. T2, "Tags next to the author": the emoji, the order of the tags, the switch per tag, `author_tags` on posts and comments.
3. T3, "Bulk tagging and the filter of the manager list": the bulk command, the filter `memberTag`, the selection in the member list of the settings.
4. T4, "Tags in the directory": after slice 1 of #148. The tags in an entry and the filter by a `members` tag.
5. Each part is ONE commit on its own local branch, built on the part before it. Each part works alone: after T1 a manager labels members and a member sees the `members` tags on a member page.
6. The member page is in T1 and not in T2. Reason: without it, the choice "Members see it" of T1 shows nothing to anyone.
7. If the reviewer wants a smaller T1, the one clean cut is the server (rules 1 to 27, the exports, the policy) as a first commit and the screens (rules 28 to 34) as a second.

## Part T1: the tags, their API and the manager screens

You build the first part of #1389. ONE commit on a local branch: a reviewer reads your diff afterwards.

### Rules

[Q] marks a proposal that waits for an answer of the owner.

Access and privacy first:

1. Three new abilities on `CommunityPolicy`, each through `CommunityRoles`: `manageMemberTags` (`isOwnerOrAdmin`) [Q, question 2], `assignMemberTags` and `viewMemberTags` (`allows(..., ManageMembers)`).
2. One class decides which tags a reader gets: `App\Support\MemberTagsOfMembers::for(Community $community, iterable $users, ?User $reader)`. A reader who passes `viewMemberTags` gets every tag of each user in that community. Another member of the community gets the `members` tags. A person who is not a member, and no reader, get none. Every response of this part calls it. No resource reads the relation by itself.
3. It runs one query for all the users of a page. A test counts the queries of the member list for 3 and for 30 members: the same number.
4. `MemberWithRoleResource` gets the field `tags`: a list of `MemberTagResource`, sorted by name without the case, an empty list when there is none. `communityMemberSchema` mirrors it.
5. `MemberController::show` gives the prop `memberTags` with the same class. The member themself gets what another member gets (decision 14) [Q, question 1].
6. A route with the cuid of a `managers` tag answers 404 to a reader who does not pass `viewMemberTags`, with the same body as a cuid that does not exist. A tag of another community answers 404 (`scopeBindings`).
7. No response, no Inertia prop, no log line and no error text gives a `managers` tag, its name, its count or its existence to a reader who does not pass `viewMemberTags`. The one exception is the export of the member who has the tag. No event is broadcast.
8. The log lines of the actions hold the cuid of the community, of the tag and of the member, and never the name of a tag.

The tags:

9. Contracts in `App\Contracts\MemberTags` and actions in `App\Actions\MemberTags`: `CreatesMemberTags`/`CreateMemberTag`, `UpdatesMemberTags`/`UpdateMemberTag`, `DeletesMemberTags`/`DeleteMemberTag`, `AddsMemberTagMembers`/`AddMemberTagMember`, `RemovesMemberTagMembers`/`RemoveMemberTagMember`. Each one checks its gate first. The rules are in `MemberTagInputRules`, as fact 4.
10. `name`: required on a create, a string, cleaned (trim, inner spaces to one), 2 to 32 characters as `mb_strlen` counts, no control character. The unique rule compares `lower(name)` in the database for the community, and ignores the tag itself on an update. The error: "A tag with this name exists already."
11. `color`: optional, one of `KnowiiMemberTagColor`, `gray` when absent. `visibility`: optional, one of `KnowiiMemberTagVisibility` (`members`, `managers`), `managers` when absent. The resource returns both also after a create (data rule 7).
12. The 101st tag of a community is refused with a `BusinessException`: "A community can have at most 100 member tags".
13. `UpdateMemberTag` changes the fields that the request holds. A rename keeps the cuid and the members.
14. `DeleteMemberTag` deletes the tag, and the cascade deletes its links. It answers 204.
15. `AddMemberTagMember`: exactly one of `member` (user cuid) or `username`. A person who is not in the community (the owner counts as in it): a validation error on that field, "No member of the community matches". The same member twice changes nothing and answers 200. The 21st tag of a member is refused: "A member can have at most 20 tags".
16. `RemoveMemberTagMember`: 204. A member who does not have the tag: 404.
17. Both call `App\Support\MemberTagAssignments`, the one writer of the link table (the contract of #1390 and #1391). `MemberTag::findByName` exists, with a test.
18. `Community::removeUser` deletes the rows of the member in `member_tag_members` for the tags of that community. A test proves that the tags of the same person in another community stay.

The API:

19. Constants in `ApiRoutes.php`, mirrored in `constants.ts`: `PARAM_MEMBER_TAG` (`memberTag`), `COMMUNITY_MEMBER_TAGS`, `COMMUNITY_MEMBER_TAG`, `COMMUNITY_MEMBER_TAG_MEMBERS`, `COMMUNITY_MEMBER_TAG_MEMBER`. Seven routes in the signed-in group, inside `scopeBindings`:
    1. `GET .../member-tags`, `member-tags:read`: every member of the community. A manager gets every tag with `members_count`. Another member gets the `members` tags with `members_count` null. Sorted by name. No pagination: the limit is 100.
    2. `POST .../member-tags`, `member-tags:write`: 201 with the tag.
    3. `PATCH .../member-tags/{memberTag}` and `DELETE` (204), `member-tags:write`.
    4. `GET .../member-tags/{memberTag}/members`, `member-tags:read`, `viewMemberTags` only (also for a `members` tag, until T4): `limit` and `offset` (ADR 0011), `AuthorResource` entries, sorted by name then by id.
    5. `POST .../member-tags/{memberTag}/members`, `member-tags:write`: 200 with the member as `AuthorResource`.
    6. `DELETE .../member-tags/{memberTag}/members/{member}`, `member-tags:write`: 204.
20. `MemberTagResource` (declared with `#[UseResource]`): `cuid`, `name`, `color`, `visibility`, `members_count` (a number or null), `created_at`. Its Zod schema is `member-tag.schema.ts` in `libs/common`.
21. Order of the refusals on a write: 404 (rule 6), then 403, then 400 for a wrong field, then the limit.
22. The abilities `member-tags:read` and `member-tags:write` go in `JetstreamServiceProvider`, in `API_TOKEN_PERMISSIONS` with the labels "Read member tags and the members who have them" and "Manage member tags and put them on members", and in the API README. Neither is in the default set.
23. Each controller method has its docblock and attributes for the OpenAPI document. Bruno: "Member tags - List", "- Create", "- Update", "- Delete", "- List members", "- Add a member", "- Remove a member". "Members - List" says that an entry holds `tags`.

The data rules:

24. `ExportUser` adds `member_tags` to `memberships.json`, after `access_groups`: for each tag of the member, the cuid, the name, the visibility, the community as the other lists give it, and `tagged_at`. Both visibilities. No name of another person. The readme line of the file names the tags.
25. `ExportCommunity` adds `member_tags` to `community.json`, after `access_groups`: the cuid, the name, the color, the visibility, the cuids of the members, `created_at`. The readme line names them.
26. A deleted account loses its rows by the cascade. `DeleteAccountTest` proves it.
27. The policy, in both files (`deploy/knowii.net/legal/policy.md` and `resources/legal-templates/policy.md`). These are approved texts: the owner approves the words before the merge [Q, question 4]. Change no other word.
    1. Section 2, item 3, after "your access groups,": "the tags that the managers of a community put on you,".
    2. Section 2, item 7, at the end: "The managers of a community can put tags on you, for example "Founder". A tag is a short name and holds no other text."
    3. Section 4, after the sentence on the member list: "The members of a community see the tags that its managers chose to show, on your profile there. The owner, the administrators and the moderators who manage members see all your tags in that community. Your data export lists all of them."
    4. Section 5, a new item: "Tags: until a manager removes the tag, you leave the community, or you delete your account."

The screens:

28. `Badge` gets the optional prop `color` (decision 8), with a story per color in both themes and a spec that computes the contrast of each token from `styles.css` (3:1 or more on the four surfaces). The tokens `--color-tag-<key>` are new [Q, question 6].
29. `Components/Members/MemberTagList.tsx`: a `ul` with the name "Tags" and one `Badge` per tag. A `managers` tag gets a lock icon and the hidden text "managers only". No list is rendered for no tag.
30. The settings page gets the section "Member tags" (`Components/Communities/MemberTagsManager.tsx`) between the members and the access groups, for a reader with `canViewMemberTags`. It lists the tags with their color, their visibility in words and their member count. With `canManageMemberTags`: a form to create a tag, and "Edit" and "Delete" per tag, each in a `Dialog`.
31. The form: "Name", "Color" (a `Select` of the eight names, with a `Badge` that shows the result), "Who sees it" (a `SegmentedControl`: "Managers only", "Members"), and the text of decision 35 under it, tied to the form with `aria-describedby`.
32. A change to "Members" on a tag that members have asks: "N members have this tag. Every member of the community will see it on them." The delete asks: "Delete the tag "Founder"? N members lose it. This cannot be undone."
33. Each row of `CommunityMembersManager` shows `MemberTagList` and, with `canAssignMemberTags`, a button with the name "Tags of {name}". It opens a `Dialog` with one `Checkbox` per tag. Each change saves at once through the API client, `SaveStatus` says "Saved", and an error puts the old state back with `role="alert"`. With no tag in the community, the dialog says where to create one.
34. `Pages/Members/Show.tsx` shows `MemberTagList` under the name. The Inertia props `permissions.canManageMemberTags`, `canAssignMemberTags` and `canViewMemberTags` come from `CommunityController`, as fact 9.

### The data model and the migration

1. One new migration. It changes no existing row. `down()` drops the two tables.
2. `member_tags`: `id`, `cuid` (unique), `community_id` (constrained, `cascadeOnDelete`), `name` (string, 32), `color` (string, default `gray`, a check on the eight keys), `visibility` (string, default `managers`, a check on `members` and `managers`), timestamps. A unique index on `(community_id, lower(name))`, written with `DB::statement` as fact 20. The value lists are literals.
3. `member_tag_members`: `id`, `member_tag_id` and `user_id` (both constrained, `cascadeOnDelete`, with the reason in a comment: decision 2), `created_at`. Unique `(member_tag_id, user_id)`. Index on `user_id`. No `cuid` and no `updated_at`, as `access_group_members`: the row is never public. The new ADR says so, because data rule 1 names every table.
4. Model `App\Models\MemberTag` with its docblock, `GeneratesCuid`, the casts of the two enums, `community()`, `members()` (with `created_at` of the pivot), `getRouteKeyName()`, and a factory with the states `forMembers()` and `forManagers()`. `Community` gets `memberTags()`, `User` gets `memberTags()`.
5. Enums `App\Enums\KnowiiMemberTagVisibility` and `KnowiiMemberTagColor`, aligned with `MEMBER_TAG_VISIBILITIES` and `MEMBER_TAG_COLORS` in `libs/common`.

### Scope (in the commit)

1. Rules 1 to 34 and the migration.
2. New PHP files: the migration, `MemberTag`, the two enums, `MemberTagFactory`, the five contracts, the five actions, `MemberTagInputRules`, `MemberTagAssignments`, `MemberTagsOfMembers`, `MemberTagResource`, `MemberTagApiController`.
3. Changed PHP files: `Community`, `User`, `CommunityPolicy`, `MemberWithRoleResource`, `MemberApiController`, `MemberController`, `CommunityController`, `ExportUser`, `ExportCommunity`, `Constants`, `ApiRoutes`, `routes/api.php`, `AppServiceProvider` (the bindings), `JetstreamServiceProvider`.
4. `libs/common`: the constants, `member-tag.schema.ts`, `communityMemberSchema`, the three permissions, `knowiiApiClient.communities.memberTags` (`list`, `create`, `update`, `remove`, `members.list`, `members.add`, `members.remove`), `API_TOKEN_PERMISSIONS` and the labels.
5. `apps/knowii`: `Badge` and its stories, `styles.css`, `MemberTagList`, `MemberTagsManager`, the dialog of the tags of one member, `CommunityMembersManager`, `Settings.tsx`, `Members/Show.tsx`, the test utilities that build a member.
6. Pest, new `tests/Feature/MemberTagTest.php` (the actions) and `MemberTagApiTest.php` (the routes), and additions to the existing files:
   1. The matrix of the three abilities: the owner, an admin, a moderator with and without `manage_members`, a member, a person who is not a member.
   2. No leak, as scans of the whole JSON, written first: a member, a moderator without the right, the tagged member themself and a person who is not a member never get the name or the cuid of a `managers` tag from the member list, the tag list, the member page props or any tag route. A manager gets it.
   3. Rule 6: the same 404 body for a `managers` tag read by a member and for a cuid that does not exist, on each route with `{memberTag}`.
   4. The name: the limits, the cleaning, "Founder" then "founder" (refused), the same name in another community (accepted), a rename to its own name in another case (accepted).
   5. The limits of 100 and of 20. The defaults `gray` and `managers` in the response of a create. A wrong color and a wrong visibility (400).
   6. Add and remove: by cuid, by username, both fields (400), the owner as the member, a person outside the community, twice the same (200, one row), a remove of a member without the tag (404), a body with `email` (400).
   7. The members of a tag: the order, the paging, 403 for a member on a `members` tag.
   8. The query count of rule 3. The order of rule 21. A token without the ability is refused (`ApiTokenAbilitiesTest`).
   9. A delete of a tag removes its links. A leave and a removal delete the tags of that community only (`LeaveCommunityTest`). A deleted account (`DeleteAccountTest`). A transfer of ownership keeps the tags of both people (`TransferCommunityOwnershipTest`).
   10. `UserExportTest`: both visibilities, no other name. `CommunityExportTest`: the tags and the cuids. `HostedLegalTextsTest` and `LegalTemplatesTest` pass with the new sentences. `BrunoCollectionTest`, `OpenApiDocumentTest`, `ApiResourceArchitectureTest` and `InstanceNeutralNamesTest` pass. `down()` runs in a test database.
7. Vitest, with axe: `member-tag.schema.spec.ts`, `Badge` (the contrast of the eight tokens in both themes), `MemberTagList` (no list for no tag, the hidden words of a `managers` tag), `MemberTagsManager.spec.tsx` (the list, the form and its errors, the two questions, read only without `canManageMemberTags`), the dialog of one member (a change, an error that puts the state back, the focus that returns to the button), `CommunityMembersManager.spec.tsx`, the member page.
8. Documents:
   1. A new ADR with the next free number (0056 on 2026-11-20: make sure that it is free, the brief of #148 plans the same number): "Member tags: labels on members, for the members or for the managers only". It holds decisions 1 to 36, the refused options, and the contract for #1390 and #1391.
   2. `naming.md` (the row "member tag"), `roles-and-permissions.md` (three rows after line 54, and one line: a moderator with `manage_members` can put a tag on any member, also on themself), `backend.md` (a part "Member tags" after "Access groups"), `frontend.md` (the `color` of `Badge`), the API README (the two abilities and the seven routes).
9. The browser check (definition of done 1), in both themes, at 360 px and at 1920 px, with the keyboard:
   1. As the owner: create "Founder" (members, purple) and "Onboarded" (managers). Put both on a member from the member list. Rename one, change a color, delete one.
   2. As that member: open the own member page. "Founder" shows, "Onboarded" does not. Download the export: both are in `memberships.json`.
   3. As a moderator without `manage_members`: the settings show no tag section. As a moderator with it: the section is read only, and the dialog of one member works.
   4. Change "Onboarded" to "Members": the question shows the count. No CSP error in the console.
10. Pictures for the owner, in both themes, at 360 px and 1920 px: the eight colors of `Badge` on the four surfaces, the section "Member tags" (with tags, empty), the form with its text, the dialog of one member, a member page with tags as a member and as a manager.

### NOT in this commit

1. Parts T2 to T4 below: no emoji, no order, no tag next to an author, no bulk action, no filter, no directory.
2. An invitation or an invite link with tags (#1390, #1391), an import of the tags of another tool.
3. Segments (#1402), custom profile fields (#1403), the member table (#1401), an automation that puts a tag.
4. A notification, an email or a broadcast about a tag. A record of who put a tag.
5. A tag that a member puts on themself, and a setting of the member to hide a tag (#1466 decides what a member hides).

## Part T2: tags next to the author (scope list)

1. A migration: `emoji` (nullable, the allow-list of fact 19), `position` (data rule 3), `shown_with_author` (boolean, default false) on `member_tags`.
2. `ReorderMemberTags` and `POST .../member-tags/reorder`. Every list of tags sorts by `position`, then by name.
3. `PostResource` and `CommentResource` get `author_tags`: at most two `members` tags with `shown_with_author`, through `MemberTagsOfMembers`, one query per page. A deleted author and a visitor get an empty list.
4. One new component for the author line (name, tags), used by `PostCard`, `Posts/Show` and `CommentSection` in place of the three plain texts (fact 16).
5. The form of a tag gets the emoji field and the switch "Show next to the name in posts and comments". `Badge` shows the emoji as its decorative icon.
6. Pest: no `managers` tag in any `author_tags`, nothing for a visitor of a public space, the query count of a list of 20 posts and of 50 comments, the limit of two.
7. Vitest with axe, the stories, the pictures of a post list and a comment thread at 360 px, in both themes. A part "Tags next to the author" in the ADR.

## Part T3: bulk tagging and the filter of the manager list (scope list)

1. `POST .../member-tags/{memberTag}/members/bulk`, `member-tags:write`: `{ action: "add" | "remove", members: [user cuid] }`, at most 100 cuids, one transaction, the answer counts `changed` and `unchanged`. A cuid outside the community refuses the whole request and names its index.
2. A limiter `member-tags` per manager for the bulk command.
3. `GET .../members?memberTag=<cuid>` (decision 27), with its `QueryParameter`, Bruno request and tests of the cut by reader.
4. `CommunityMembersManager`: a filter "Tag" (`Select` or `Combobox`), a checkbox per row, "Select all on this page", and the actions "Add a tag" and "Remove a tag" for the selection, with the result in a status region.
5. "Members of {tag}" from the section "Member tags": it opens the member list with the filter set.
6. Pest for the bulk command (the limit of 20 per member inside a bulk, the limit of 100 cuids, the same request twice), Vitest with axe, pictures of the selection at 360 px.

## Part T4: tags in the directory (scope list, after slice 1 of #148)

1. An entry of the directory shows `MemberTagList` with at most three tags and "+N".
2. The filter by tag of the directory: `GET .../member-tags` gives a member the `members` tags (built in T1), and the list uses `memberTag`.
3. `GET .../member-tags/{memberTag}/members` opens to every member for a `members` tag, or stays for managers and the directory uses the list filter only: decide in the brief of T4.
4. A member whom #1466 hides from the directory is in no result of the filter and in no count.
5. Pest: a member never filters by a `managers` tag, the hidden member, the query count. Vitest with axe, pictures of the directory with tags at 360 px and 1920 px.

## How to work

1. `git fetch origin`, then branch `feat/1389-member-tags-t1` from `origin/main`. ONE commit, no push, no pull request. If the working tree holds changes of another person, stop and ask: no stash, no reset. Find the text when a line number moved.
2. Docker on this Linux machine, from `~/wks/knowii`: `docker compose exec -T -u sail laravel.test <command> </dev/null`. Do not start, stop or restart containers. No `migrate:fresh` and no seeder on the development database. Run the new migration with `php artisan migrate`, and prove `down()` in a test database only.
3. PHP commands in the container: `composer format`, `composer lint`, `composer security:php`, and the whole Pest suite ONE time with `php artisan test --parallel`. Front-end commands on the host: `npx nx format --all`, `npm run lint:common`, `npm run tsc`, `npm run tsc:spec`, `npm run test:all`. Never run the whole Pest suite and the whole Vitest suite at the same time.
4. A piped test command hides a failure: use `bash -c "set -o pipefail; ..."`. Without `--parallel`, `php artisan test` takes ONE path.
5. Write the leak tests of scope 6.2 and 6.3 before the code of rules 2 to 7, and see them fail.
6. `InstanceNeutralNamesTest` reads `tests/` too. Never name a third-party community product. Use `example.com` addresses and `example` hosts.
7. The commit: a conventional type, one of the allowed scopes (all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build), a header of at most 100 characters that does not start with a capital letter. Suggested: `feat(api): member tags, the model, the API and the manager screens (slice T1)`. Footer, after a blank line: `Refs #1389` (the issue closes with part T4). No trailer line that names a tool or a co-author.
8. If a rule cannot be built as written, skip that rule, build the rest, and say why in the report. Do not invent product rules.
9. If you find a weakness in the code of `main` while you work, do not write it in the commit, in a test name, in the ADR or in an issue. Handle it as `SECURITY.md` says, and tell the reviewer in private.

## Questions for the owner

1. A member and their own `managers` tags. The export shows them, because the member has a right to their data. Does a screen show them too? Recommendation: no screen. The member page shows the member what the other members see. The form tells each manager that the member can read the tag in the export, and the policy says that such tags exist. A screen makes "managers only" mean nothing for labels such as "Onboarded".
2. Who defines the tags. The owner and the admins only, or also the moderators with `manage_members`? Recommendation: the owner and the admins define (create, rename, color, visibility, delete), and the moderators with the right only put tags on members. A rename and a change of visibility act on every member who has the tag. It is the rule of access groups, so the settings page has one logic.
3. Tags next to the name in posts and comments. The issue asks for a choice per tag: label, icon, or both. Recommendation: a tag always shows its name, one switch per tag says if it shows next to an author (off by default), at most two tags show, and the whole point waits for T2. An icon without a name has no meaning for a new member and no name for a screen reader.
4. The four sentences of the privacy policy (rule 27) and the text of the form (decision 35). Approve the words, or change them. They are needed before the merge of T1.
5. Sensitive facts. The data processing agreement says that the community does not ask its members for special categories of data. A managers tag is a note that a manager writes, not an answer of the member. Does the agreement or the terms need one more sentence ("and does not record them in tags"), or is the text of the form enough? Recommendation: the text of the form now, and the sentence with the next change of the agreement.
6. The palette. Eight new color tokens per theme for labels (gray, red, orange, yellow, green, teal, blue, purple), without the magenta. It adds colors to the product. Recommendation: accept, with the pictures of T1 on the four surfaces of both themes. Eight is enough to tell the tags of one community apart.
7. The word. The issue says "public" for a tag that members see. For a space, "public" means every person on the internet. Recommendation: the screens say "Members" and "Managers only", and no tag is ever shown to a visitor.
8. The limits: a name of 2 to 32 characters, 100 tags per community, 20 tags per member, no description. Recommendation: accept. All four are constants and can grow later. A limit that goes down later is the hard direction.

## Your final report (max 400 words)

1. The commit hash and `git show --stat`.
2. The leak tests of scope 6.2 and 6.3: their failure before the code and their pass after it.
3. The query counts of rule 3, for 3 and for 30 members.
4. Each choice where the brief left room, and each [Q] rule that you built in another way.
5. The results of Pest, PHPStan, Pint, the security scan, the type checks, Vitest and the format check, with the exact text of anything that failed.
6. The four steps of the browser check, each with what you saw, and the contrast value of each of the sixteen tokens.
7. The text of the row for the register of processing. Proposal: "Member tags. Data: the name of a tag, the member who has it, the date. Purpose: the managers of a community label its members to run the community. Controller: the owner of the community. Processor: the operator. Who reads it: the members of the community for a tag that the managers show, else the owner, the admins and the moderators who manage members. Kept until a manager removes the tag, the member leaves, or the account is deleted. No special categories."
8. Anything of the scope not built, and why.
