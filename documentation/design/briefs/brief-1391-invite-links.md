State: DECIDED on 2026-11-22 (see "Decided by the owner").

# Brief: invite links (knowii-oss/knowii#1391)

## Decided by the owner on 2026-11-22

1. Every recommendation of "Questions for the owner" is the decision.
2. The three sentences of the privacy policy are approved word for word. Copy them exactly; change no other word of a legal text.
3. Build after #1390 is merged, as two commits (the server, then the screens).

Nothing is built. The owner answers the "Questions for the owner" at the end before the build starts. Until then, each recommendation and each [Q] rule is the proposal, and the builder builds what it says. [W] marks a word of a screen that the owner has not approved: each [W] word is one value in `invite-link-words.ts`, so a later change is one line.

An invite link is an address that a manager shares (on a sales page, in a newsletter, in a course). Any person who opens it can join the community as a member, and gets the access groups and the member tags of the link. Today a person joins only through an invitation to their own email address (ADR 0019) or by themselves in an open community (ADR 0044, "Who can join"). This brief holds the rules, the facts with their evidence, the scope, the tests, the cut and the questions. It extends ADR 0019, as the issue asks. Paths are relative to `~/wks/knowii`. Line numbers are those of `main` at `9317a167` (2026-11-22). Find the text when a line moved.

You build ONE commit on a local branch, or the clean cut of section "The cut" into two commits. A reviewer reads your diff afterwards.

## This slice comes after #1390

#1390 (bulk invitations from a CSV file) is built and merged FIRST. It builds the parts that an invitation and an invite link both need. This slice uses them and does not build them again. The names below are the ones that the brief of #1390 is expected to use. When #1390 named a part in another way, use its name and say so in the report.

1. The class that gives the access groups and the tags to a person who becomes a member, inside the database transaction of the join. Expected: `App\Support\JoinGrants::apply(Community $community, User $member, Collection $accessGroups, Collection $memberTags)`. It calls `AccessGroupMemberships::addManual` (flag `manual`) and `MemberTagAssignments::add`, the two only writers of their tables, and it sends the events of the groups (`AccessGroupsChanged`, `SpaceTreeChanged`) after the commit.
2. The input rules of the two lists: `access_groups` and `member_tags`, each a list of cuids of the same community, without duplicates, at most `MAX_ACCESS_GROUPS_PER_COMMUNITY` groups and 20 tags (the contract of #1389, point 3), with the gates `manageAccessGroupMembers` and `assignMemberTags`.
3. The fields of the grants in a resource (`access_groups` as `{cuid, name}`, `member_tags` as `MemberTagResource`) and their Zod schemas.
4. The React picker of the groups and the tags of the invitation form.
5. The ADR of #1390 and its sentences in the privacy policy. This slice adds to them and changes none of their words.

If #1390 is not in `origin/main`, stop and report. If one of points 1 to 4 does not exist after #1390, build it in this slice, in that shape and in its own file, and say so in the report. Do not change the behavior of the invitations of #1390.

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `SECURITY.md`, `documentation/conventions/rules.md` (Architecture 2 to 6, Data 1, 2, 6, 7, 8 and 10, Front-end 2 to 5, 9, 11 and 12, the definition of done), `documentation/conventions/naming.md`, `documentation/adr/README.md`.
2. The issues with their comments: #1391, #1390, #1242, #1486, #1612, #1386, #934 (the audit log, not built), #1454 (limits per community, not built).
3. ADRs: 0019 (invitations), 0044 (the part "Who can join"), 0057 (all of it: points 9, 16, 17 and consequence 5 matter most), 0058 and 0059 (the proof of an address), 0025, 0041 and 0047 (access groups and the rules on entitlements), 0060 (member tags, with its contract for #1390 and #1391), 0022 (token abilities), 0011 (the REST guide), 0030 (retention), and the ADR of #1390.
4. The code of the join and of the invitations: `app/Actions/CommunityMembers/JoinCommunity.php`, `AcceptCommunityInvitation.php`, `InviteCommunityMember.php`, `app/Http/Controllers/Inertia/CommunityInvitationController.php`, `app/Http/Controllers/API/MemberApiController.php` (`join`), `app/Models/Community.php` (`isOpen`, `joinBlocks`, `removeUser`), `app/Policies/CommunityPolicy.php`, `app/Support/CommunityRoles.php`.
5. The way in: `app/Http/Middleware/AuthenticateIfPresent.php`, `app/Support/Landing.php`, `app/Http/Responses/VerifyEmailResponse.php`, `routes/web.php` (lines 53 to 109), `tests/Feature/LandingTest.php` (lines 194 to 216).
6. Groups and tags: `app/Support/AccessGroupMemberships.php`, `app/Support/MemberTagAssignments.php`, `app/Models/AccessGroup.php` (`rules()`), `app/Support/EntitlementReads.php`.
7. Screens: `apps/knowii/src/Pages/Communities/Settings.tsx`, `Components/Communities/CommunityMembersManager.tsx`, `MemberTagsManager.tsx`, `JoinCommunity.tsx`, `Pages/Communities/Public.tsx`, `Components/Comments/CommentSection.tsx` (lines 225 to 232, the copy of a link), `Components/Events/EventForm.tsx` (the date field).
8. Data: `app/Actions/Users/ExportUser.php`, `app/Actions/Communities/ExportCommunity.php`, `config/retention.php`, `app/Support/Retention.php`, `app/Actions/Retention/PruneExpiredData.php`, `deploy/knowii.net/legal/policy.md`, `resources/legal-templates/policy.md`, `config/robots.php`, `resources/views/app.blade.php`, `app/Support/Analytics.php`.

## What the issue and the owner decide (do not change it)

1. A manager creates an invite link with a name, a state (on or off), an optional end date and an optional maximum number of uses (issue, scope 1).
2. A link sets the access groups and the tags of the people who join through it (scope 2).
3. The list of the links shows how many members joined through each one (scope 3).
4. A person who opens the link signs up or signs in, then joins (scope 4). Nothing joins by itself after a sign-up: the person presses "Join" (ADR 0044, "Who can join", point 4).
5. The rate limits and the bot check of the sign-up apply (scope 5).
6. The owner, 2026-11-15: a link to a paid group is a free pass for anyone who has it, as long as it is on. A link to a group that a rule on an entitlement feeds (#1386) must not be possible, or must need an end date and a maximum number of uses. The list shows clearly which links are on and what they open. Each join through a link is recorded (the audit log #934 does not exist yet).
7. Nothing in the platform is specific to one community (ADR 0044, "Who can join", point 1). A personal community never takes a member.

## What exists today (facts, with the evidence)

The join and the invitations:

1. `JoinCommunity::join` (`app/Actions/CommunityMembers/JoinCommunity.php:28-62`) refuses an address that is not verified (line 31), locks the community row (line 36), refuses a community that is not open (line 39), a member (line 43, "You are already a member of this community") and a person with a join block (line 47, "You cannot join this community by yourself. Ask its managers for an invitation."). It attaches the role member, then logs with cuids, sends `CommunityMembersChanged` and calls `EntitlementReads::afterJoin` after the transaction (lines 56 to 59). `Community::isOpen` is false for a personal community (`app/Models/Community.php:279-282`).
2. `AcceptCommunityInvitation` (`AcceptCommunityInvitation.php:24-51`) compares the address of the account with the invited one, deletes the join block of the person in the same transaction (line 41), and deletes the invitation. The controller does it on the GET of a signed link (`CommunityInvitationController.php:22-32`, `routes/web.php:73`), then redirects to the community home with the banner "Welcome to {name}".
3. The open join on the API: `POST communities/{community}/membership`, `communities:write`, `throttle:joins` (`routes/api.php:295`). It answers 201 with the community as a member sees it (`MemberApiController.php:98-103`). The limiter `joins` is 10 a minute per account, per IP address without an account (`app/Providers/AppServiceProvider.php:494-496,564-569`, `app/Constants.php:233`).
4. The policy. `manageJoinPolicy` is the owner and the admins (`app/Policies/CommunityPolicy.php:79-82`). `addCommunityMember` and `manageAccessGroupMembers` are the owner, the admins and the moderators with `manage_members` (lines 105 to 108 and 154 to 157). `manageAccessGroups` is the owner and the admins (lines 145 to 148). `assignMemberTags` is the owner, the admins and the moderators with `manage_members`, and its docblock names the invite links (lines 182 to 185).
5. Groups. `AccessGroupMemberships` is the only writer of `access_group_members`. `addManual` sets the flag `manual` and changes nothing the second time (`app/Support/AccessGroupMemberships.php:27-34`). A group has its rules on entitlements in `AccessGroup::rules()` (`app/Models/AccessGroup.php:67`). A rule works only in a community that the operator lists (ADR 0047, point 3).
6. Tags. `MemberTagAssignments::add` is the only writer of `member_tag_members`. It locks the membership row, needs a member of the community and holds the limit of 20 tags (`app/Support/MemberTagAssignments.php:31-56`).
7. A removal and a leave. `Community::removeUser` deletes the groups, the tags, the progress, the answers, the notifications and the visits of the member in that community (`app/Models/Community.php:375-398`). A removal by a manager also writes a join block (ADR 0044, "Who can join", point 5).

The way in:

8. A visitor who opens a page of the group `auth.optional` gets that page as the intended address of the session (`app/Http/Middleware/AuthenticateIfPresent.php:55-58`). After the sign-up, Fortify answers with that address (`redirect()->intended()` of its `RegisterResponse`). A signed-in account without a verified address is sent to the address check (line 45), and the framework keeps the page as the intended address again (`Redirect::guest` of `EnsureEmailIsVerified`). The answer of the address check goes to the intended address (`app/Http/Responses/VerifyEmailResponse.php:41-46`). The mail of the address check is sent at the sign-up, when the session still holds the page, and its landing is null for a page that is not in `Landing::ROUTES` (ADR 0057, point 11).
9. The group `auth.optional` of `routes/web.php` (lines 93 to 109) must hold exactly the routes of `Landing::ROUTES` (the warning at lines 91 and 92). `tests/Feature/LandingTest.php:194-216` takes every `GET` route of the groups `web` and `auth.optional`, compares the names with `Landing::ROUTES`, and wants each parameter to be a slug whose name ends with `Slug`.
10. `Landing::intend` keeps the intended address of the session when it is a page of a community or the link of an invitation (`app/Support/Landing.php:206-216`, the constant `INVITATION_ROUTE` at line 45). Else a link of the address check with a landing replaces it (ADR 0057, points 9 and 16.1).
11. The link of the address check carries a landing, which is a path of one of six pages, never a token (ADR 0057, points 6 and 10). The person who signs up is not always the person who owns the address (ADR 0057, consequence 5).
12. The sign-up page has the bot check (`app/Providers/FortifyServiceProvider.php:61`, `App\Support\Turnstile`). The sign-in has its own limits (`FortifyServiceProvider.php:87-93`).

Tokens, statistics and search engines:

13. The sign-in link makes its token with `Str::random(64)` and keeps only `hash('sha256', $token)` as the key (`app/Actions/Auth/MagicLinks.php:44,111`). The API token page shows a new token once (`apps/knowii/src/Pages/API/Index.tsx:138-143`).
14. When the instance sets the statistics values, every page loads the statistics script, which sends the address of the page to the statistics service (`resources/views/app.blade.php:39-48`, `app/Support/Analytics.php:16-30`).
15. `robots.txt` blocks `/community-invitations/` (`config/robots.php:24`). `Robots::directive` answers `noindex, nofollow` for every page that is not one of the public pages that it names (ADR 0044, "Indexing", point 3).

The screens and the data:

16. The settings page shows the members (`CommunityMembersManager`), then the member tags, then the access groups (`apps/knowii/src/Pages/Communities/Settings.tsx:149-185`). The members card holds the invitation form, the card "Pending invitations" with the words "Invitation links work for 7 days, only for an account with the invited email address." (`CommunityMembersManager.tsx:388-423`) and the card "Removed members" (lines 426 to 450).
17. `JoinCommunity.tsx` is the way in of an open community: "Sign in" and "Sign up to join" for a visitor, a "Join" button with `aria-busy` and a guard for an account (`apps/knowii/src/Components/Communities/JoinCommunity.tsx:19-72`).
18. The public shape of a community for a person who is not a member holds the cuid, the name, the slug, the description, the visibility, `join_policy` and `is_member: false` (`app/Http/Resources/CommunityResource.php:28-38`).
19. The member export lists the access groups, the tags and the join blocks in `memberships.json` (`app/Actions/Users/ExportUser.php:103-106`). The community export lists the join blocks, the groups and the tags in `community.json` (`app/Actions/Communities/ExportCommunity.php:154-156`).
20. The privacy policy names the invitations and the join block in section 2, item 3 (`deploy/knowii.net/legal/policy.md:23`, `resources/legal-templates/policy.md:22`), the readers of the tags in section 4 (line 45), and 15 items in section 5 (lines 61 to 75). The template has the same text one line higher.
21. `config/retention.php` holds the periods. `App\Support\Retention` gives the placeholders of the policy (`app/Support/Retention.php:129-136`). `PruneExpiredData::prune` has one step per kind of data (`app/Actions/Retention/PruneExpiredData.php:42-85`).
22. The token abilities: `member-tags:read` and `member-tags:write` are the last pair, and the default set holds reads only (`app/Providers/JetstreamServiceProvider.php:188-196`).
23. What does not exist: an invite link, a table of joins, a member limit per community (#1454 is open), an audit log (#934 is open).

## Rules

Each rule is one sentence that a test can prove.

### The link

1. Model `App\Models\InviteLink` (table `invite_links`), declared with `#[UseResource(InviteLinkResource::class)]`, with `GeneratesCuid`, hiding `id`, `community_id`, `token_hash` and `token`, and bound by `cuid`.
2. The token is `Str::random(40)` (letters and digits, about 238 bits). The table keeps `token_hash` (`hash('sha256', $token)`, unique) to find the link, and the token itself with the `encrypted` cast, so that the managers can copy the link again [Q, question 1]. No query reads a link by its token: every lookup hashes the token first.
3. The address of a link is `/invite/{inviteLinkToken}` (route `invite-links.show`, the parameter limited to `[A-Za-z0-9]{40}`). It holds no slug, so a rename of the community keeps every link working.
4. A link always gives the role `member`. No field of the API sets a role. Reason: a link works for anyone who holds it, and ADR 0019 keeps the other roles for an invitation to one address.
5. `name`: required, a string, cleaned as a tag name (trim, inner spaces to one, no control character), 2 to 64 characters as `mb_strlen` counts. Not unique. Only the managers see it.
6. `access_groups` (optional): groups of the same community, through the rules of #1390. A group with at least one rule (`AccessGroup::rules()`) is refused, on the field `access_groups`: "A link cannot give a group that a rule on an entitlement feeds" [Q, question 3]. The check reads the stored rules, whether the feature of ADR 0047 is on or not.
7. `member_tags` (optional): tags of the same community, at most 20, through the rules of #1390. A tag of any visibility can be on a link (contract of ADR 0060, point 5).
8. `expires_at` (optional, an ISO 8601 time, null clears it): later than now when a request sets it, else "The end must be in the future".
9. `max_uses` (optional, null clears it): an integer from 1 to `Constants::MAX_INVITE_LINK_USES` (10000). An update can set it at or below `uses_count`: the link is then full.
10. `active` (optional, a strict boolean, true by default): off, the link works for nobody. On again, it works again.
11. `uses_count` starts at 0 and goes up by 1 at each join through the link, in the transaction of the join. It never goes down: a member who leaves stays counted. Only the join writes it.
12. `InviteLink::status()` is the one place of the state, in this order: `off` when `active` is false, else `ended` when `expires_at` is now or before, else `full` when `max_uses` is set and `uses_count` is at least `max_uses`, else `active`. Only `active` lets a person join.
13. A community has at most `Constants::MAX_INVITE_LINKS_PER_COMMUNITY` (50) links, whatever their state. The 51st is refused with a `BusinessException`: "A community can have at most 50 invite links".
14. A personal community has no link: a create answers a validation error on `community`, as `PATCH .../join-policy` does ("A personal community has no invite link").
15. A change of the groups or the tags of a link changes nothing for the people who joined before. A delete of a link changes nothing for its members: they keep their groups and tags. A delete removes the link and its rows of rule 36 (a cascade).
16. A link has no column for its creator. Reason: less data. The audit log (#934) is the place for that.
17. A deleted group or tag leaves every link (a cascade on the link tables, ADR 0060 contract point 4).

### Who manages the links

18. New ability `CommunityPolicy::manageInviteLinks`: `CommunityRoles::isOwnerOrAdmin` [Q, question 2]. It covers the list, a read, a create, an update (also on and off), a delete, and the list of who joined. Reason: a link opens the community to anyone who holds it, like the setting "Who can join" (owner and admins, fact 4), and it can give paid groups.
19. A create and an update also check `manageAccessGroupMembers` when the request names groups, and `assignMemberTags` when it names tags. The owner and the admins pass both today. The checks stay, so that a later change of rule 18 keeps them.
20. A moderator, with or without `manage_members`, gets 403 on each route of the managers, and the settings page shows no section of links.

### The join through a link

21. New action `App\Actions\InviteLinks\JoinCommunityThroughInviteLink` behind `App\Contracts\InviteLinks\JoinsCommunitiesThroughInviteLinks`: `join(User $user, string $token): Community`. It does not call `JoinCommunity`: that action refuses a community that is not open.
22. The checks, in this order:
    1. The address of the account is verified. Else the `BusinessException` of `JoinCommunity` line 31 (the middleware refuses before, the action checks again).
    2. The hash of the token finds a link. Else rule 33 (the neutral 404).
    3. In one transaction: lock the community row, then the link row (`lockForUpdate`), always in this order.
    4. The link is `active` (rule 12) and its community is not personal. Else rule 33.
    5. The person is not a member (the owner counts). Else 422 "You are already a member of this community". Nothing changes: no group, no tag, no count, no row [Q, question 4].
    6. The person has no join block in that community. Else 422 with the text of `JoinCommunity` line 47. The link does not lift the block, and nothing is counted [Q, question 5].
    7. Attach the person with the role `member`, add 1 to `uses_count`, write the row of rule 36, and give the groups and the tags of the link through `JoinGrants` (point 1 of "This slice comes after #1390"). A group that has a rule at that moment is not given (rule 6 holds at the save; a rule can come later).
23. After the commit: the log line "Member joined the community through an invite link" with the cuids of the community, the link and the member, `CommunityMembersChanged`, `EntitlementReads::afterJoin`, and the events that `JoinGrants` sends.
24. The join works the same in a community "by invitation" and in an open community.
25. Two clicks of one person give one membership and one count. Two people at the last use: one joins, the other gets rule 33.
26. Nobody joins without a `POST` of that person. Opening the page writes nothing: no count, no row, no membership. The only write is the intended address of the session of a visitor (fact 8).

### The way in for a person without an account

27. The page `invite-links.show` is a `GET` route with `auth.optional` and the session middleware of the group of fact 9, in its own group in `routes/web.php` with a comment. A visitor reads it. A signed-in account whose address is not verified goes to the address check, which keeps the page as the intended address.
28. A visitor signs up (with the bot check) or signs in, then confirms the address in the same browser: the answer of the address check is the intended address (`VerifyEmailResponse`), so the person is back on the page and presses "Join". A sign-in with a password, the second factor, a provider or a sign-in link in the same browser comes back to the page in the same way.
29. `Landing` gets `INVITE_LINK_ROUTE = 'invite-links.show'` and `KEPT_ROUTES = [INVITATION_ROUTE, INVITE_LINK_ROUTE]`. `intend()` keeps an intended address of a route of `KEPT_ROUTES` (it replaces the test of `Landing.php:213`). Reason: else a link of the address check with the landing of another page of a community replaces the page of the link.
30. The page of a link is not a landing. It is not in `Landing::ROUTES`, and its token never goes in the link of the address check, in a sign-in link or in any mail [Q, question 7]. Reason: the person who signs up is not always the owner of the address (fact 11), so a token in that mail gives the link to another person. A person who confirms the address in another browser lands as before, and opens the link again: the page tells a visitor so.
31. `LandingTest` (lines 194 to 216) leaves out `Landing::INVITE_LINK_ROUTE` by name, with a comment that gives the reason of rule 30. A new test proves that the group `auth.optional` holds exactly `Landing::ROUTES` and `INVITE_LINK_ROUTE`.

### Abuse

32. The page and the API read of a link use the new limiter `invite-links`: `Constants::INVITE_LINK_READS_PER_MINUTE` (20) per account, per IP address for a visitor (the key of fact 3). The join uses `throttle:joins` (fact 3).
33. An unknown token, a link that is off, ended or full, a deleted link and a link of a personal community give the same answer: on the page, status 404 with the state `unusable`, and on the API, 404 with one message, "This invite link does not work. Ask the person who gave it to you for a new one." [W]. A person learns nothing about a link that does not work.
34. The page of a link loads no statistics script and sends no request to another host. Reason: the script sends the address of the page, with the token, to the statistics service (fact 14). `App\Support\Analytics::plausible()` returns null for the route `invite-links.show` (one place), and a test reads the HTML of the page.
35. `config/robots.php` blocks `/invite/`. The page answers `noindex, nofollow` (fact 15). No log line, error text, broadcast, export or Inertia prop of another page holds a token or a `token_hash`. The resource of rule 45 gives `url` to the managers only.

### The record of the joins

36. Table `invite_link_uses`: `id`, `invite_link_id` and `user_id` (both constrained, `cascadeOnDelete`), `created_at`, unique `(invite_link_id, user_id)`, an index on `user_id`. No `cuid` and no `updated_at`, as `access_group_members`: the row is never public. The ADR says so (data rule 1) [Q, question 6].
37. `Community::removeUser` deletes the rows of the member for the links of that community. A test proves that the rows of the same person in another community stay.
38. The rows have a retention period: `invite_link_uses_days` in `config/retention.php` (`RETENTION_INVITE_LINK_USES_DAYS`, 365), a step `invite_link_uses` in `PruneExpiredData` that deletes the rows older than the period, a placeholder `invite_link_uses` in `App\Support\Retention`, and the value in `SELF_HOSTING.md` and `.env.example` (data rule 8).
39. The link itself holds no personal data of a member and has no retention period: it stays until a manager deletes it or the community is deleted. An ended or full link stays in the list with its state.

### The API

40. Constants in `app/ApiRoutes.php`, mirrored in `constants.ts`: `PARAM_INVITE_LINK` (`inviteLink`), `PARAM_INVITE_LINK_TOKEN` (`inviteLinkToken`), `COMMUNITY_INVITE_LINKS`, `COMMUNITY_INVITE_LINK`, `COMMUNITY_INVITE_LINK_USES`, `INVITE_LINK`, `INVITE_LINK_MEMBERSHIP`.
41. The routes of the managers, in the signed-in group, inside `scopeBindings`:
    1. `GET communities/{community}/invite-links`, `invite-links:read`: every link, newest first, no pagination (the limit is 50).
    2. `POST communities/{community}/invite-links`, `invite-links:write`: 201 with the link and its `url`.
    3. `GET .../invite-links/{inviteLink}`, `invite-links:read`.
    4. `PATCH .../invite-links/{inviteLink}`, `invite-links:write`: the fields that the request holds. `access_groups` and `member_tags` replace the lists.
    5. `DELETE .../invite-links/{inviteLink}`, `invite-links:write`: 204.
    6. `GET .../invite-links/{inviteLink}/uses`, `invite-links:read`: `limit` and `offset` (ADR 0011), newest first, then by id. Each entry: `member` (`AuthorResource`) and `joined_at`.
42. The routes of the person, in the signed-in group:
    1. `GET invite-links/{inviteLinkToken}`, `communities:read`, `throttle:invite-links`: `community` (the public shape of fact 18, or the shape of a member for a member) and `state`: `join`, `member` or `blocked`. Else rule 33.
    2. `POST invite-links/{inviteLinkToken}/membership`, `communities:write`, `throttle:joins`: 201 with the community as a member sees it, as `MemberApiController::join` does. The refusals of rule 22.
43. The order of the refusals on a route of the managers: 404 (a link of another community, `scopeBindings`), then 403, then 400 for a wrong field, then the limit of rule 13.
44. New abilities `invite-links:read` ("Read the invite links of a community and who joined through them" [W]) and `invite-links:write` ("Create, change and delete the invite links of a community" [W]), in `JetstreamServiceProvider`, `API_TOKEN_PERMISSIONS` and the API README. Neither is in the default set. Reason: a token for the invitations must not open a door for anyone (ADR 0022 gives each area its pair).
45. `InviteLinkResource`: `cuid`, `name`, `url` (absolute, from `route('invite-links.show', ...)`), `status`, `active`, `expires_at`, `max_uses`, `uses_count`, `access_groups` (`cuid`, `name`, `fed_by_rule`), `member_tags` (`MemberTagResource`), `created_at`, `updated_at`. Never `token_hash`. The Zod schema is `invite-link.schema.ts` in `libs/common`. The resource returns every field also after a create (data rule 7).
46. Each controller method has its docblock and attributes for the OpenAPI document. Bruno: "Invite links - List", "- Create", "- Show", "- Update", "- Delete", "- Who joined", "- Open" (the read of the person) and "- Join".

### Personal data

47. `ExportUser` adds `invite_link_uses` to `memberships.json`, after `join_blocks`: for each row of the member, the community as the other lists give it, the cuid and the name of the link, and `joined_at`. The readme line of the file names them.
48. `ExportCommunity` adds `invite_links` to `community.json`, after `member_tags`: the cuid, the name, `status`, `active`, `expires_at`, `max_uses`, `uses_count`, the cuids of the groups and of the tags, `created_at`, and `uses` (the cuid of the member and `joined_at`). Never the token, the hash or the address. The readme line names them.
49. A deleted account loses its rows by the cascade. `DeleteAccountTest` proves it.
50. The policy, in both files. These are approved texts: the owner approves the words before the merge [Q, question 8]. Change no other word, and keep the words of #1390.
    1. Section 2, item 3, after "the invitations sent to your email address,": "the invite link through which you joined a community, if you used one, and the date,".
    2. Section 4, after the sentences on the tags: "The owner and the administrators of a community see who joined it through each of its invite links."
    3. Section 5, a new last item: "Joins through an invite link: {{retention.invite_link_uses}} after you joined, or until you leave the community, a manager deletes the link, or you delete your account."

### The screens

51. The settings page gets the section "Invite links" (`Components/Communities/InviteLinksManager.tsx`) directly after the members card, for a reader with `permissions.canManageInviteLinks` in a shared community. The prop comes from `CommunityController` (beside `canManageJoinPolicy`) and `community.schema.ts`.
52. The section lists the links in a `ul`. Each row shows: the name; a `Badge` with the state in words, "Active", "Off", "Ended" or "Full" [W]; "{N} joined" or "{N} of {M} joined" [W]; "Ends {date}" [W] in a `time` element in the zone of the account when an end is set; "Gives" [W] with the names of the groups and the `MemberTagList` of the tags; for a group with a rule, "{group}: a rule on an entitlement feeds this group, so this link does not give it." [W].
53. Each row has: "Copy link" (the clipboard, then the toast "Link copied"; on a failure, the toast "Could not copy the link" with the address, as `CommentSection.tsx:227-231`), a `Switch` "On" that saves at once with `SaveStatus` and puts the old state back with `role="alert"` on an error, "Edit", "Who joined" and "Delete". Each button names the link in its accessible name ("Copy the link {name}").
54. "New invite link" and "Edit" open a `Dialog` with: "Name" with the hint "Only the managers see the name." [W]; "Access groups" and "Tags" with the picker of #1390, where a group with a rule cannot be chosen and says why; "Ends" (a date and a time, optional, in the zone of the account, as `EventForm`); "Maximum number of joins" (optional); and under the groups and the tags, tied with `aria-describedby`: "Anyone who has this link can join this community and gets these groups and tags. Turn the link off, or set an end or a maximum, when you share it widely." [W]. After a create, the dialog shows the address with "Copy link".
55. "Delete" asks: "Delete the invite link "{name}"? It stops working at once. The {N} members who joined through it stay, with their groups and tags. The list of who joined goes. This cannot be undone." [W].
56. "Who joined" opens a `Dialog` with the members (avatar, name, date), `LoadMoreButton`, and the `EmptyState` "Nobody joined through this link, or they left." [W].
57. The page of a link is `Pages/InviteLinks/Show.tsx` in `AppLayout`, with the layout of a visitor of ADR 0044 point 14 for a visitor. The `h1` is "Join {community name}" [W], then the description of the community as plain text, then by state:
    1. A visitor: "Sign up to join" and "Sign in" (as `JoinCommunity.tsx:30-39`), and the line "After you confirm your email address, open this link again if you do not come back to this page." [W].
    2. `join`: a "Join" button with `aria-busy` and a guard, as `JoinCommunity.tsx`. After a success, a visit to the community home with the toast "Welcome to {name}".
    3. `member`: "You are already a member of this community." [W] and a link "Open the community".
    4. `blocked`: the text of the block (fact 1), and no button.
    5. `unusable`: the `h1` "This invite link does not work" [W] and the message of rule 33, with status 404, and no data of a community.
58. The page never shows the name of the link, its groups, its tags, its counts, its end, the member count or a member. Its props hold only the community in the shape of rule 42.1 and the state.

## The data model and the migration

1. One new migration. It changes no row that exists. `down()` drops the four tables.
2. `invite_links`: `id`, `cuid` (unique), `community_id` (constrained, `cascadeOnDelete`), `name` (string, 64), `token_hash` (string, 64, unique), `token` (text, the encrypted value), `active` (boolean, default true), `expires_at` (timestamp with time zone, nullable), `max_uses` (integer, nullable, a check `max_uses >= 1`), `uses_count` (integer, default 0, a check `uses_count >= 0`), timestamps. An index on `community_id`.
3. `invite_link_access_groups`: `id`, `invite_link_id` and `access_group_id` (both `cascadeOnDelete`), `created_at`, unique pair. `invite_link_member_tags`: the same with `member_tag_id`. If #1390 made its link tables with another shape (for example with timestamps), use its shape.
4. `invite_link_uses`: rule 36.
5. Model `InviteLink` with its docblock, `community()`, `accessGroups()`, `memberTags()`, `uses()`, `status()`, the casts (`token` encrypted, `expires_at` datetime, `active` boolean), and a factory with the states `off()`, `ended()`, `full()`. `Community` gets `inviteLinks()`.
6. A static `InviteLink::findByToken(string $token): ?InviteLink` that hashes the token first, with a test.

## The words

Each [W] text above is one value of `apps/knowii/src/Components/InviteLinks/invite-link-words.ts`. The server texts of rules 13, 14, 22 and 33 are in one PHP class or constants, mirrored where the client shows them. The screens say "invite link" and never "invitation link": the card "Pending invitations" already uses "Invitation links" for the links of the emails (fact 16), and this slice changes none of its words.

## Scope (in the commit)

1. Rules 1 to 58 and the migration.
2. New PHP files: the migration, `InviteLink`, `InviteLinkFactory`, the contracts and actions `CreatesInviteLinks`/`CreateInviteLink`, `UpdatesInviteLinks`/`UpdateInviteLink`, `DeletesInviteLinks`/`DeleteInviteLink`, `JoinsCommunitiesThroughInviteLinks`/`JoinCommunityThroughInviteLink`, `InviteLinkInputRules`, `InviteLinkResource`, `InviteLinkApiController` (the routes of the managers), `InviteLinkJoinApiController` (the routes of the person), the Inertia `InviteLinkController`.
3. Changed PHP files: `Community`, `CommunityPolicy`, `CommunityController`, `Landing`, `Analytics`, `Constants`, `ApiRoutes`, `routes/api.php`, `routes/web.php`, `AppServiceProvider` (the bindings and the limiter), `JetstreamServiceProvider`, `ExportUser`, `ExportCommunity`, `PruneExpiredData`, `Retention`, `config/retention.php`, `config/robots.php`.
4. `libs/common`: the constants, `invite-link.schema.ts`, the permission `canManageInviteLinks`, `knowiiApiClient.communities.inviteLinks` (`list`, `create`, `show`, `update`, `remove`, `uses`) and `knowiiApiClient.inviteLinks` (`open`, `join`), `API_TOKEN_PERMISSIONS` and the labels.
5. `apps/knowii`: `InviteLinksManager`, its dialogs, `invite-link-words.ts`, `Pages/InviteLinks/Show.tsx`, `Settings.tsx`.
6. Pest, new `tests/Feature/InviteLinkTest.php` (the actions and the join), `InviteLinkApiTest.php` (the routes) and `InviteLinkPageTest.php` (the page and the way in), and additions to the existing files. Each point is at least one test:
   1. The matrix of `manageInviteLinks`: the owner, an admin, a moderator with and without `manage_members`, a member, a person who is not a member.
   2. The fields: the name limits and cleaning, a group of another community, a group with a rule (refused at the save, not given at the join when the rule came later), 21 tags, an end in the past, `max_uses` 0 and 10001, a strict boolean for `active`, the 51st link, a personal community.
   3. The states of rule 12, each with a join: `active` joins, `off`, `ended` and `full` give rule 33 with the same body as an unknown token (compare the whole JSON).
   4. The join: a new member gets the role member, the groups (flag `manual`) and the tags, `uses_count` 1 and one row. A member: 422, nothing changes (groups, tags, count, rows). A person with a join block: 422, the block stays, nothing is counted. An account without a verified address: refused. A community by invitation and an open community both work. A link of a deleted community.
   5. Concurrency of rule 25: the last use taken by one of two people (`max_uses` 1, two joins in a row on a fresh model of the link), and two joins of one person.
   6. The events and the hooks of rule 23 (`Event::fake`, `Bus::fake` for the read of the entitlements when the community has rules).
   7. The way in: a visitor opens the page, the session holds it, signs up, confirms the address, and the answer goes to the page. An account without a verified address opens the page and goes to the address check, then back. A link of the address check with the landing of another community does not replace the page in the session (rule 29). The link of the address check after a visit of the page holds no `landing` and no token (rule 30). The `LandingTest` change of rule 31.
   8. Abuse: the limiter `invite-links` (the 21st read in a minute gets 429, for a visitor by IP), the HTML of the page holds no statistics script when the instance sets the values (rule 34, in `AnalyticsTest`), `robots.txt` holds `/invite/` and the page answers `noindex` (`RobotsTest`, `IndexingTest`), no log line holds the token (a spy on the log during a create and a join).
   9. No leak, as scans of the whole JSON and of the whole Inertia props: the page and the API read of the person never hold the name of the link, a group, a tag, a count, `token_hash`, or a member. A moderator never gets a link. The token is in no response but `url` for a manager.
   10. The record: the rows of `uses` with the order and the paging, the cascade of a delete of a link, `Community::removeUser` (rule 37, in `LeaveCommunityTest` and the removal tests), `DeleteAccountTest`, the prune step and its test (`RetentionTest`), the placeholder in the policy.
   11. Exports: `UserExportTest` (`invite_link_uses` with the name of the link), `CommunityExportTest` (`invite_links`, and no token, hash or address anywhere in the files).
   12. `ApiTokenAbilitiesTest`, `BrunoCollectionTest`, `OpenApiDocumentTest`, `ApiResourceArchitectureTest`, `HostedLegalTextsTest`, `LegalTemplatesTest`, `InstanceNeutralNamesTest` pass. `down()` runs in a test database.
7. Vitest, with axe: `invite-link.schema.spec.ts`, `InviteLinksManager.spec.tsx` (the list and each state in words, the copy and its failure, the switch and an error that puts the state back, the form and its errors, a group with a rule that cannot be chosen, the delete question with the count, the focus that returns to the button), the dialog "Who joined" (empty, a load of more), `Pages/InviteLinks/Show.spec.tsx` (each of the five states of rule 57, the busy "Join", an error with `role="alert"`).
8. Documents:
   1. A new ADR with the next free number (0061 on 2026-11-22 if #1390 did not take it): "Invite links: a shared link to join a community, with set access groups and tags". It extends ADR 0019 and ADR 0044 ("Who can join"), and holds rules 1 to 58 with their reasons, the refused options of "Questions for the owner", and the link to the ADR of #1390. Add it to `documentation/adr/README.md`, and a line "Extended by" in ADR 0019.
   2. `naming.md`: the row "invite link" (do not use: join link, share link, invitation link (for this)).
   3. `roles-and-permissions.md`: the rows "Manage the invite links" (owner and admins) and "Join through an invite link" (a signed-in person with a verified address, not with a join block), and one paragraph after the one of the invitations (line 161).
   4. `backend.md`: a part "Invite links" after "Joining" (line 121). The API README: the two abilities and the eight routes.
   5. `SELF_HOSTING.md` and `.env.example`: `RETENTION_INVITE_LINK_USES_DAYS`.
9. The browser check (definition of done 1), in both themes, at 360 px and at 1920 px, with the keyboard, without a CSP error in the console:
   1. As the owner: create "Course buyers" with one group and one tag, an end in 7 days and 100 joins. Copy the link. Turn it off and on. Edit it.
   2. In a private window, as a visitor: open the link, sign up, confirm the address in the same window, come back to the page, press "Join", land in the community. The member has the group and the tag.
   3. As that member: open the link again: "You are already a member". Leave the community, a manager removes nothing: open the link again and join again (the count is 2).
   4. As a removed member (a manager removed them): the page shows the text of the block and no button.
   5. Turn the link off: the page shows "This invite link does not work". The network panel shows no request to another host on the page.
10. Pictures for the owner, in both themes, at 360 px and 1920 px: the section with four links in the four states, the dialog of a new link (empty and with an error), the dialog after a create, "Who joined", the delete question, and the page in each of its five states.

## The cut

If the reviewer wants a smaller diff, the one clean cut is two commits:

1. L1, the server: rules 1 to 50 except the page route and rules 27 to 31 and 34, with the migration, the API, the exports, the retention, the policy and the ADR. After L1, a manager manages links and a person joins through the API. Footer `Refs #1391`.
2. L2, the screens and the way in: rules 27 to 31, 34, 51 to 58, the page route, `robots.txt`, the browser check and the pictures. Footer `Closes #1391`.

## NOT in this commit

1. A link with a role other than member. A link for one email address (that is an invitation, ADR 0019). A link that lifts a join block.
2. A new address for a link that keeps its settings and its count ("Reset the link"). Today a leaked link is turned off, and a new link is made.
3. Groups or tags given to a person who is already a member. A join on request, with an approval by a manager.
4. A notification or an email to the managers at a join. A daily report of the joins.
5. A QR code, a short address, a link per space, a link on the public page of a community.
6. The audit log (#934) and the limits per community (#1454). When #1454 adds a member limit, the join through a link counts like every other join.
7. Statistics per link beyond the count and the list of who joined (sources, campaigns).
8. Any change of the invitations by email, of `JoinCommunity`, or of the parts of #1390.

## How to work

1. `git fetch origin`, then branch `feat/1391-invite-links` from `origin/main`, and make sure that the commit of #1390 is in it. ONE commit (or the two of "The cut"), no push, no pull request. If the working tree holds changes of another person, stop and ask: no stash, no reset.
2. Docker on this Linux machine, from `~/wks/knowii`: `docker compose exec -T -u sail laravel.test <command> </dev/null`. Do not start, stop or restart containers. No `migrate:fresh` and no seeder on the development database. Run the new migration with `php artisan migrate`, and prove `down()` in a test database only.
3. PHP commands in the container: `composer format`, `composer lint`, `composer security:php`, and the whole Pest suite ONE time with `php artisan test --parallel`. Front-end commands on the host: `npx nx format --all`, `npm run lint:common`, `npm run tsc`, `npm run tsc:spec`, and the whole Vitest suite ONE time with `npm run test:all`. Write the summary of each full run to a file and read the file. Never run the whole Pest suite and the whole Vitest suite at the same time.
4. A piped test command hides a failure: use `bash -c "set -o pipefail; ..."`. Without `--parallel`, `php artisan test` takes ONE path. While you build, run one test file at a time.
5. Run `npm run build` on the host before the first Pest run on the branch and before each browser check.
6. Write the tests of scope 6.3, 6.4 (the member and the block), 6.7 (rule 29) and 6.9 before the code, and see them fail.
7. Use the toolkit blocks (`Dialog`, `Switch`, `Badge`, `SaveStatus`, `LoadMoreButton`, `EmptyState`, `Button`, `LinkButton`). Do not build a new block.
8. `InstanceNeutralNamesTest` reads `tests/` too. Never name a third-party community product, in the code, a test, a document or the commit. Use `example.com` addresses and `example` names.
9. The commit: a conventional type, one of the allowed scopes (all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build), a header of at most 100 characters that does not start with a capital letter. Suggested: `feat(api): invite links, a shared link to join a community with set access groups and tags`. Footer, after a blank line: `Closes #1391`. No trailer line that names a tool or a co-author.
10. If a rule cannot be built as written, skip that rule, build the rest, and say why in the report. Do not invent product rules.
11. If you find a weakness in the code of `main` while you work, do not write it in the commit, in a test name, in the ADR or in an issue. Handle it as `SECURITY.md` says, and tell the reviewer in private.

## Questions for the owner

Until the owner answers, the builder builds what each recommendation says. The owner approves the words, the policy sentences and the pictures before the merge.

1. The token at rest. A hash only (the address shows once, at the create, like an API token), or the hash to find the link and the token encrypted with the key of the application, so that the managers copy the link again from the list? Recommendation: the hash and the encrypted token. A link is shared again and again (a sales page, a newsletter, a course), and a copy of the database without the key of the application gives no working link.
2. Who manages the links. The owner and the admins only, or also the moderators with `manage_members`, who invite by email today? Recommendation: the owner and the admins. A link opens the community to anyone who holds it, as the setting "Who can join" does, and it can give paid groups. A moderator who wants a link asks an admin.
3. A group that a rule on an entitlement feeds. Refuse it on a link, or accept it with a required end and a required maximum? Recommendation: refuse it, and do not give it at the join when the rule came after the save. A paid group follows what a person owns: a link that gives it is the free pass of your note of 2026-11-15.
4. A person who is already a member opens a link. Nothing changes, or the groups and the tags of the link are added? Recommendation: nothing changes. Else every member who sees a leaked link gives themself its paid groups. A manager adds a group or a tag to a member by hand.
5. A person whom a manager removed. Can a link bring them back? Recommendation: no, with the text of the block. A link is a door for anyone, so it is "by themselves" in the sense of ADR 0044. An invitation by email or "May join again" lifts the block.
6. The record of who joined through which link. One row per join (the link, the member, the date), shown to the owner and the admins, in both exports, and kept 365 days at most (a value of the configuration), or until the member leaves, the link is deleted or the account is deleted? Or the count only? Recommendation: the rows. After a leak, the managers must find who used the link to take the groups away, and the audit log (#934) does not exist yet.
7. A person who confirms the address in another browser. Put the token in the link of the address check so that the person comes back to the page there, or not? Recommendation: not. The person who signs up is not always the owner of the address (ADR 0057), so the token would go to another person. The page tells a visitor to open the link again, and the link works many times.
8. The three sentences of the privacy policy (rule 50) and the words marked [W]. Approve them, or change them. They are needed before the merge.
9. The limits: a name of 2 to 64 characters, 50 links per community, a maximum of 10000 joins per link, 20 reads of a link a minute per account or per IP address. Recommendation: accept. They are constants and can grow later.
10. A link with groups and without an end or a maximum. Allow it, with the warning text of rule 54, or require an end or a maximum for every link that gives a group? Recommendation: allow it with the warning. Groups without a rule are often free groups (a cohort, a team), and the list shows each active link with what it gives.

## Your final report (max 400 words)

1. The commit hash (or the two hashes) and `git show --stat`.
2. The parts of #1390 that you used, with their names, and any part that you had to build.
3. The tests of scope 6.3, 6.4, 6.7 and 6.9: their failure before the code and their pass after it.
4. Each choice where the brief left room, each [W] word and each [Q] rule that you built in another way, and each rule that you did not build as written.
5. The results of Pest, PHPStan, Pint, the security scan, the type checks, Vitest and the format check, with the exact text of anything that failed.
6. The five steps of the browser check, each with what you saw.
7. The path of the HTML page with the pictures, and the number of pictures.
8. The text of the row for the register of processing. Proposal: "Joins through invite links. Data: the link, the member who joined through it, the date. Purpose: the managers of a community see who joined through each link, and act on a link that leaked. Controller: the owner of the community. Processor: the operator. Who reads it: the owner and the administrators of the community, and the member in the export. Kept the period of `invite_link_uses_days` after the join, or until the member leaves, the link is deleted or the account is deleted."
9. Anything of the scope not built, and why.
