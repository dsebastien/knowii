# Brief: the member directory, slice 1: a page that lists the members of a community (knowii-oss/knowii#148)

## Decided by the owner on 2026-11-20

1. Every recommendation of "Questions for the owner" is the decision: slice 1 merges before the "hide myself" setting, and the #1466 slice lands before the import of the live community; no bio excerpt (the headline of #1688 is the one line); the default sort is "A to Z"; a role badge only for the owner, the admins and the moderators; two people with a block still see each other; the setting "members, or managers only" is the second later slice.
2. The approved sentence of the privacy policy, in place of the one about the owner and the administrators: "The members of a community see its member list: your name, your username, your picture, your headline, your role and the date you joined." (the words "your headline" follow the decision on #1688).
3. Build after #1688 is merged, and refresh this brief first (point 5 of the section below).

## Decided after this draft (owner, 2026-11-20)

1. Member profiles get a headline (#1688), and the directory shows it. The headline lands before this slice.
2. So an entry of the directory holds one more field: the headline, under the rule of ADR 0020 (only when the member shows it). `MemberWithRoleResource` gives it under that rule, and a test proves that a hidden headline is not in the answer of the list, for a member and for a manager alike unless ADR 0020 says otherwise for managers.
3. This answers question 2 of "Questions for the owner": no bio excerpt, the headline is the one line. Where a rule or a fact below says that an entry holds no profile field, read it with this change: the headline is the only profile field of an entry.
4. The search stays a search by name in this slice.
5. Refresh this brief when #1688 is merged, before the build: its rules 8, 9 and 30, its tests 3.3, 3.4 and 3.11 and point 6 of "NOT in this commit" still assume six fields and no loaded relation, and it names #1423 for the headline in place of #1688.

State: DECIDED on 2026-11-20 (see "Decided by the owner"). The owner answers the "Questions for the owner" before the build starts.

You build the first slice of #148. Today a member finds another member only through a post, a mention, the online list or a search field in a form. After this slice, each community has a page "Members": a list of its members with a search by name, two sorts and "Load more". Each entry opens the member page that exists. The page is for the members of the community only. The API list of the members exists already: it gets one new parameter, `sort`. No new table, no new column, no new personal data. ONE commit on a local branch: a reviewer reads your diff afterwards. Paths are relative to `~/wks/knowii`.

The issue is larger than one commit. Its four comments of 2026-11-15 add filters, views, settings and a space type. They are the later slices, listed in "Later slices, in order".

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md` (architecture rules 1, 3, 4 and 6, data rule 6, front-end rules 2, 3, 8, 9, 10 and 12, the definition of done), `documentation/conventions/naming.md`.
2. The issue with its comments (`gh issue view 148 --repo knowii-oss/knowii --comments`) and the issues around it: #1466 (the privacy settings of a member, with "hide from the directory"), #1451 (the community defaults, with "directory: open to members or hidden"), #1389 (member tags), #1423 (the headline), #153 (following), #1465 (the member map), #1588 (lists that load after the page), #1602 (pills and icons), #1590 (the settings page).
3. ADRs: 0004, 0011 (lists and pagination), 0017, 0020 (points 1 to 4), 0022 (points 1 to 4), 0024 (point 2), 0027, 0028 (point 5), 0044 (what a visitor never gets), 0048, 0049.
4. `documentation/architecture/roles-and-permissions.md` (the paragraph "Visitors"), `documentation/architecture/frontend.md` ("UI components", "Toolkit" point 9, and "Events" point 2 for the list pattern), `documentation/api/README.md` (the abilities table and the paragraph "Without an account").
5. Server code: `app/Support/CommunityMemberQuery.php`, `app/Support/Pagination.php`, `app/Http/Controllers/API/MemberApiController.php`, `app/Http/Controllers/Inertia/MemberController.php`, `app/Http/Controllers/Inertia/CommunityController.php` (`about`), `app/Http/Controllers/Inertia/SpaceController.php` (lines 79 to 96, the first page of a list in the props), `app/Http/Resources/MemberWithRoleResource.php`, `app/Http/Resources/MemberProfileResource.php`, `app/Policies/CommunityPolicy.php`, `app/Support/NoAccess.php`, `app/Support/ProfileFieldVisibility.php`, `routes/web.php`, `routes/api.php` (lines 264 to 276), `app/Providers/AppServiceProvider.php` (the rate limiters).
6. Front-end code: `apps/knowii/src/Components/Spaces/CommunityShell.tsx`, `Components/Spaces/SpaceNavigation.tsx`, `Components/Communities/CommunityMembersManager.tsx`, `Components/Events/EventList.tsx`, `Pages/Members/Show.tsx`, `Pages/Communities/About.tsx`, `Components/ui/LinkTabs.tsx`, `Badge.tsx`, `EmptyState.tsx`, `libs/common/src/lib/types/member.schema.ts`, `libs/common/src/lib/api/knowii-api-client.ts` (line 1154).
7. Tests: `tests/Feature/CommunityMemberManagementTest.php` (lines 63 to 86), `MentionTest.php` (lines 65 to 98), `EmailAddressPrivacyTest.php`, `ProfileFieldVisibilityTest.php`, `NoAccessPageTest.php`, `ApiTokenAbilitiesTest.php`, `BrunoCollectionTest.php`, `OpenApiDocumentTest.php`.

## What exists today (facts, with the evidence)

Line numbers are those of `main` on 2026-11-20 (commit `a2482fa3`).

1. The API list. `GET /api/v1/communities/{community}/members` gives every member of the community to every member of the community (`routes/api.php:269`, `MemberApiController.php:39-47`, `CommunityPolicy.php:28-31`). Each entry holds six fields: `cuid`, `name`, `username`, `avatar_url`, `role` and `member_since` (`MemberWithRoleResource.php:29-36`). The order is the owner, the admins, the moderators, the members, each by username (`CommunityMemberQuery.php:64-69`).
2. The search of that list. `query` keeps the members whose username starts with the text, or whose name has a word that starts with it. It ignores the case, it does not ignore accents, and the wildcards of the input are literal (`CommunityMemberQuery.php:35-43`). It reads two columns only: `users.username` and `users.name`. It joins the members of one community, so it never finds an account outside the community (`CommunityMemberQuery.php:25-30`, `MentionTest.php:65-83`).
3. The pagination of that list. `limit` and `offset` (ADR 0011), 10 by default, 100 at most, with the count of all results in the metadata (`Pagination.php:21-30,40-47`, `Constants.php:261-263`). Every list of the code uses this class. No list of members uses a cursor.
4. The limits of that list. The token ability `communities:read`, which is in the default set of a new token (`routes/api.php:269`, ADR 0022 points 1 and 4). 60 requests a minute for each account, in a limiter that it shares with the Markdown preview (`AppServiceProvider.php:475-477`, `Constants.php:224`).
5. Who uses that list today. The member management of the settings, for the owner, the admins and the moderators with the right to manage members, 20 at a time with a search and "Load more" (`CommunityMembersManager.tsx:71-89,277-300`, `CommunityController.php:128-133`, `libs/common/src/lib/constants.ts:391`). The member field of a new conversation and of the host of an event, 8 suggestions (`Components/Messages/MemberPicker.tsx:60-75`). The mention list of the editor.
6. The team. The page "About" lists the owner, the admins and the moderators, 50 at most, with their role (`CommunityController.php:33,107-123`, `Pages/Communities/About.tsx:148-164`). The community home shows the member count and the link "See the team" (`Pages/Communities/Show.tsx:124-129`).
7. The online list. Under the navigation, "Online now" shows the members who are connected and who allow it (`CommunityShell.tsx:99-106`, `Components/Presence/OnlineMembers.tsx:27`, ADR 0027). The online status is never stored. The date of the last visit is in no resource (`UserResource.php:35`, ADR 0048).
8. The member page. `/community/{communitySlug}/member/{username}` shows the name, the username, the picture, "Member since", the buttons "Send a message" and "Block", and the profile fields that the member shows (`routes/web.php:70`, `MemberController.php:22-50`, `Pages/Members/Show.tsx`). A username of a person who is not a member of the community gives 404 (`MemberController.php:27-28`).
9. No screen lists all the members for a member. This is the gap of #148.
10. The profile fields. A member shows or hides the bio, the location, the phone and each link (`ProfileFieldVisibility.php:16-19`, ADR 0020). Only `MemberProfileResource` reads these fields, and it leaves a hidden field empty (`MemberProfileResource.php:28-46`). The list entry of fact 1 holds no profile field at all.
11. The email address. No resource gives the address of another person (`UserResource.php:30`, ADR 0020 point 1). `EmailAddressPrivacyTest.php:278,331` proves it for the member list, for a member and for a manager.
12. A deleted account, a member who left, a member whom a manager removed. The membership row is gone, so the list no longer holds the person (ADR 0024 point 2). The owner has no membership row: the query adds the owner, and `member_since` of the owner is the date of the community (`CommunityMemberQuery.php:28-33`).
13. A block. It is between two accounts, in every community, and it stops direct messages only (ADR 0028 point 5, `User.php:206-212`). Both people stay in every list, and each one still opens the member page of the other (`MemberController.php:35,46-47`).
14. A visitor. Every member page is behind the sign-in: a visitor goes to the sign-in page and comes back to the same address (`routes/web.php:51-55`). The API list needs an account (`routes/api.php:57`). ADR 0044 says that a visitor never gets a member list, the team, the online members or a profile field (`roles-and-permissions.md:106`, `documentation/api/README.md:87`).
15. A signed-in person who is not a member. A community page answers 403 with the page without access (`NoAccess.php:35-44`). The API list answers 403 (`CommunityMemberManagementTest.php:84-86`).
16. What does not exist. A headline (#1423 is open: `ProfileFieldVisibility.php:18` has no such field). Member tags (#1389 is open). A setting to hide oneself from the member list (#1466 is open). A community setting for the directory (#1451 is open). Connections (#153 is open, and it is about following). An index for a search without accents: no code uses `unaccent` or a trigram index.
17. The words of the privacy policy. One sentence names the member list: "The owner and the administrators of a community see its member list." (`deploy/knowii.net/legal/policy.md:44`, `resources/legal-templates/policy.md:43`). It is an approved text.
18. The toolkit. `Badge`, `EmptyState`, `LinkTabs`, `TextInput`, `Avatar` and `RelativeTime` are on `main` (`apps/knowii/src/Components/ui/index.ts`). `LoadMoreButton` and `Skeleton` are in the open pull request #1687, not on `main`.
19. The navigation of a community. `CommunityShell` shows the link "Messages" above the spaces, for members only, in the sidebar and in the drawer of a narrow screen (`CommunityShell.tsx:49-56,97,116,146-168`). `SpaceNavigation` holds only space groups and spaces.

## The cut (why this slice, and what waits)

1. The server already has the list, the search, the access check and the token ability. So the first slice is mostly a page: it is small on the server and it is useful alone.
2. The entry shows what the API already gives to every member: the picture, the name, the username, the role and the date of joining. It adds no data that a member cannot read today.
3. The proposal named a headline. It does not exist (fact 16). The entry gets it in the slice after #1423.
4. The entry shows no profile field in this slice. So ADR 0020 holds by construction, and a test guards it for the later slices.
5. The setting to hide oneself needs a column, a settings screen, a change of the privacy policy and a change of the data export. It is the next slice, and it is a gate for the launch (question 1).

## Rules

These rules are the recommended design. They become "decided" when the owner answers the questions at the end.

### Who opens the directory

1. New web route `GET /community/{communitySlug}/members`, named `members.index`, in the group of `routes/web.php:51-55` (signed in, verified address). New `MemberController::index()`.
2. A visitor goes to the sign-in page, and comes back to the directory after the sign-in.
3. An account without a verified address goes to the page of the address check.
4. A signed-in person who is not a member gets 403 with the page without access. Use `NoAccess::authorizeView($request, $community, $community, KnowiiNoAccessKind::Community)`, as `about()` does. The answer holds no member.
5. Every member of the community opens the directory, with any role. No community setting changes this in this slice.
6. A community that does not exist gives 404.
7. The API list keeps its access: 401 without an account, 403 for a person who is not a member, 403 for a token without `communities:read`. No new ability.

### What an entry holds

8. The directory uses `MemberWithRoleResource` as it is. An entry holds exactly `cuid`, `name`, `username`, `avatar_url`, `role` and `member_since`. Do not add a field to the resource.
9. An entry never holds an email address, a profile field (bio, location, phone, links), the online status, a date of a visit, an access group, an internal id, or a state of a block.
10. A member who hides every profile field has the same entry as any member.
11. The list holds the owner and every person with a membership row of this community, and nobody else. A person who left, a person whom a manager removed, a person with a pending invitation and a deleted account are not in it. The count follows.
12. A block changes nothing: each of the two people sees the other in the directory, and no entry tells that a block exists (ADR 0028 point 5: the blocked member is not told).
13. An access group changes nothing: a member is listed with or without a group, to every member.
14. The owner, the admins and the moderators get the same entries as a member. One addition: a person who can add members (`addCommunityMember`) gets the prop `canManageMembers`, and the page shows the link "Manage members" to the settings page. The management itself stays in the settings.

### The search

15. One search for the directory, the settings and the member fields: `CommunityMemberQuery::for()`. It keeps its rule of fact 2: the start of the username, or the start of a word of the name, without case, with accents as typed.
16. The search reads `users.username` and `users.name` only. A text that is equal to the email address of a member, or to a word of a bio or of a location, finds nobody (unless the name or the username starts with it).
17. The search never leaves the community: a person outside it with a name that matches is never in the result, and the count does not count them.
18. The search text is trimmed, and the server keeps its first 100 characters. New constant `Constants::MEMBER_SEARCH_MAX_LENGTH`, mirrored in `libs/common/src/lib/constants.ts` (data rule 6). The field has the same `maxLength`.
19. An empty search text gives every member.

### The sort

20. New enum `App\Enums\KnowiiMemberSort` with three values: `role` (the order of today), `name` and `newest`. Mirror it in `member.schema.ts` with a WARNING comment on both sides.
21. `CommunityMemberQuery::for(Community $community, string $search = '', KnowiiMemberSort $sort = KnowiiMemberSort::Role)`. `team()` keeps the order `role`.
22. `name`: `lower(users.name)` from A to Z, then `users.username`. `newest`: the date of joining from the newest to the oldest, then `users.username`. The date of joining of the owner is the date of the community, as today. The username is unique, so each order is total.
23. With `role`, a search keeps its extra rule of today: the usernames that start with the text come first. With `name` and `newest`, the sort alone gives the order.
24. The API takes `sort` as a query parameter. Without it, or with any other value, the order is `role`: no client of today changes. Add the `#[QueryParameter]` line for `sort`. Correct the line of `limit` on the way: it says "default 20", and the default is 10.
25. The page takes `sort` from its address. Without it, or with any other value, the order is `name`. The page never shows the order `role`.

### The pages of the list

26. Offset pagination with `Pagination`, as every list (fact 3). No cursor.
27. The page gives the first 20 members in its props. New constant `Constants::COMMUNITY_MEMBERS_PAGE_SIZE = 20`, with a WARNING comment that names the constant of `constants.ts:391`.
28. "Load more" calls the API list with `limit` 20, the next offset, the sort and the search text. The API keeps its maximum of 100.
29. A member who joins or leaves between two pages can move one entry. The client drops an entry whose `cuid` it already shows.

### The query and its cost

30. One count query and one page query for each request, whatever the number of members. The resource reads only columns of `users` and the two computed values, so no relation loads. A test proves that the page costs the same number of queries with 3 members and with 30.
31. No migration and no new index. The join uses the unique index `(community_id, user_id)` of `community_members`. The sort runs on the members of one community. Say in the report if a query plan on the demo data shows a problem.

### Rate limits and enumeration

32. New limiter `member-lists`: `Constants::MEMBER_LISTS_PER_MINUTE = 60` for each account. The web route `members.index` and the API list use it. The API list leaves the limiter of the Markdown preview, so that a search no longer uses the budget of the preview. The 61st request of a minute answers 429.
33. The directory gives no way to find an account outside the community (rules 11, 16 and 17). Inside the community, the list of all members is the feature itself, and the limiter bounds its cost.

### The screen

34. New page `apps/knowii/src/Pages/Members/Index.tsx`, in `AppLayout` and `CommunityShell` with the default content width. Browser title: "Members - <community name>". Breadcrumb: the community, then "Members".
35. Props, with a WARNING comment on both sides: `community`, `spaceGroups`, `members` (`items` and `pagination`, the shape of the lists in `SpaceController`), `sort`, `query`, `canManageMembers`.
36. The heading: `h1` "Members". Next to it, for `canManageMembers`, the link "Manage members".
37. The tools, in one row that wraps:
    1. A search field in a `search` landmark: `TextInput type="search"`, placeholder "Search by name or username", name "Search the members" (the words of the settings).
    2. The sort: `LinkTabs` with "A to Z" and "Newest", named "Sort the members". Each link is an address (`?sort=name`, `?sort=newest`) and keeps the search text.
38. The address holds the sort and the search text, so a reload, the back button and a shared link give the same list. A change of the search text visits the page 250 ms after the last key, with `replace`, `preserveState`, `preserveScroll` and `only` for the props that change. The field keeps the focus and its text. A link of the sort keeps the focus too (the events list does the same).
39. A status line under the tools, polite, always in the page: "128 members", "1 member", and with a search text "3 members found" or "No member found". After "Load more": "20 more loaded".
40. The list: a `ul` named "Members", with `aria-busy` while a visit runs. One column on a narrow container, two columns from `@3xl` (a container query, front-end rule 8). There is no switch between a list and a grid.
41. An entry, in this order: the `Avatar`, the name as the only link (to `members.show`, with `touch-target`), "@username" in the muted color, the role, and "Joined" with `RelativeTime`. The role shows for the owner, an admin and a moderator only, as a `Badge variant="neutral"` with the words of `COMMUNITY_ROLE_LABELS`. A plain member has no badge. Long names and usernames are cut with `truncate`.
42. The empty state. Without a search text the list is never empty: the owner is in it. With a search text and no result: `EmptyState` with the title "No member matches this search" and the button "Clear the search", which empties the field and puts the focus in it.
43. "Load more" shows only while `nextOffset` is not null. It is the `LoadMoreButton` of #1687 when that pull request is on `main`. Else it is a `Button variant="secondary"` with `loading`, as in `EventList.tsx:295`. After the load, the focus goes to the link of the first new entry.
44. A failed "Load more" shows the message of the API client in a `role="alert"` line above the button. The button stays: it is the retry. No toast for this error.
45. The first page comes with the page, so the page needs no skeleton. Do not add one.
46. `Pages/Members/Show.tsx`: the breadcrumb gets "Members", with the address of the directory, between the community and the name.
47. American spelling and sentence case, as on the existing screens. Use only the words of rules 34 to 46. Put them in one constant object, as `EVENT_WORDS`.

### The entry in the navigation

48. `CommunityShell` gets the link "Members" under "Messages" and above the spaces, in the sidebar and in the drawer. Icon: `FaUsers` of `react-icons/fa6`.
49. The link shows for a member only (`isMember`), and not in a personal community (`community.visibility === 'personal'`): a personal community has one member.
50. On the directory the link has `aria-current="page"` and the look of the open item, as "Messages". It is not marked on a member page.
51. Make one small link component in `CommunityShell.tsx` for "Messages" and "Members", so that the two links have one look. "Messages" keeps its unread count.
52. New constant `MEMBERS_URL = 'members.index'` in `libs/common/src/lib/constants.ts`.

### Keyboard, screen reader and the phone width

53. Tab order: the link "Manage members" (when it shows), the search field, the two sort links, each name, "Load more". Every one has a visible focus.
54. A screen reader hears the landmark "Search the members", the navigation "Sort the members" with the open sort as the current page, the list "Members" with its number of items, the role as text in the entry, the full date of "Joined" from `RelativeTime`, and each change of the status line.
55. The picture is decoration: the name is next to it. It adds no second link and no second name.
56. At 360 px: the search field takes the full width, the sort links go under it, the list has one column, and nothing scrolls sideways. At 1920 px: the list has two columns inside the page frame.
57. Both themes. No raw color: the tokens only.

## Scope (in the commit)

1. Rules 1 to 57.
2. Files:
   1. New: `app/Enums/KnowiiMemberSort.php`, `apps/knowii/src/Pages/Members/Index.tsx`, `apps/knowii/src/Pages/Members/Index.spec.tsx`, `tests/Feature/MemberDirectoryTest.php`, the ADR of point 7.1.
   2. Changed on the server: `app/Support/CommunityMemberQuery.php`, `app/Http/Controllers/Inertia/MemberController.php`, `app/Http/Controllers/API/MemberApiController.php`, `app/Constants.php`, `app/Providers/AppServiceProvider.php`, `routes/web.php`, `routes/api.php`.
   3. Changed on the client: `apps/knowii/src/Components/Spaces/CommunityShell.tsx` and its spec, `apps/knowii/src/Pages/Members/Show.tsx`, `libs/common/src/lib/constants.ts`, `libs/common/src/lib/types/member.schema.ts`, `libs/common/src/lib/api/knowii-api-client.ts` (`members.list` gets an optional `sort`).
   4. No migration. No new configuration value. No new dependency. No new block in `Components/ui`.
3. Pest, in the new `tests/Feature/MemberDirectoryTest.php`. One test for each line:
   1. Access of the page: a visitor goes to the sign-in page and comes back after the sign-in. An unverified account goes to the address check. A person who is not a member gets 403 with the page without access, and the answer holds no name of a member. A member, a moderator, an admin and the owner get 200 with the component `Members/Index`. An unknown community gives 404.
   2. Access of the API: 401 without an account, 403 for a person who is not a member, 403 for a token without `communities:read`, 200 for a token with it.
   3. The entry: the keys of an item are exactly the six of rule 8, in the props of the page and in the API. Give the member an email address, a bio, a location, a phone and a link that appear nowhere else, and assert that the whole answer does not hold these texts.
   4. A member who hides every profile field, and a member who shows them all: the same six keys.
   5. Who is listed: the owner one time with the role `owner`. A person who left, a removed person, a pending invitation, a deleted account (run the delete action) and a member of another community are not listed, and `totalCount` follows each case.
   6. A block: A blocks B. A sees B and B sees A, and the two answers are equal to the answers before the block.
   7. An access group: a member of a group and a member of no group are both listed, and the answer names no group.
   8. The search: the start of the username. The start of the second word of the name. Capital letters in the text. A text in the middle of a word finds nobody. `%` and `_` are literal. The email address of a member finds nobody. A word of the bio finds nobody. A person outside the community with the same name is not found. A text of 300 characters answers 200.
   9. The sort: `name` gives A to Z without case, with the username as the second key (two members with one name). `newest` gives the newest membership first, and the owner at the date of the community. The API without `sort`, and with `sort=unknown`, gives the order of today (the test of `CommunityMemberManagementTest.php:63` passes without a change). The page without `sort`, and with `sort=unknown`, gives `name`. With `name` and a search, a username match does not come first.
   10. The pagination: 25 members give 20 items and `nextOffset` 20 in the props. The API with `offset=20` gives the 5 others. No member is in both. `limit=500` gives 100 at most.
   11. The cost: the same number of queries for the page with 3 members and with 30.
   12. The limiter: the 61st request of a minute answers 429, on the page and on the API. A Markdown preview still answers 200 after 60 member lists.
   13. `canManageMembers`: true for the owner, an admin and a moderator with the right to manage members. False for a moderator without it and for a member.
   14. `ApiTokenAbilitiesTest`, `ApiResourceArchitectureTest`, `EmailAddressPrivacyTest`, `MentionTest` and `CommunityMemberManagementTest` pass without a change. Add the directory page to the pages that `EmailAddressPrivacyTest.php:284` opens.
4. Vitest:
   1. `Pages/Members/Index.spec.tsx`: the list and the fields of an entry. The badge for the three roles and no badge for a member. The link of the name. The status line in its four forms. The search (the visit after 250 ms, with the sort kept). The two sort links with the current one marked and the search text kept. The empty state and "Clear the search" with the focus in the field. "Load more" (the call with the sort and the text, the new entries, no double entry, the focus on the first new link, "20 more loaded", gone when `nextOffset` is null). The error line and the retry. "Manage members" only with `canManageMembers`. One axe check.
   2. `CommunityShell.spec.tsx`: "Members" shows for a member in the sidebar and in the drawer. It does not show for a visitor, for a person who is not a member, and in a personal community. It has `aria-current` on the directory only. "Messages" keeps its count.
   3. `member.schema` or the client spec: `members.list` sends `sort` only when it is given.
   4. Stories: none. No block of the toolkit is new or changed (ADR 0049). Say so in the report.
5. Bruno: `documentation/api/Members - List.bru` gets the `sort` parameter in its query and in its documentation. `BrunoCollectionTest` stays green. No new route in the API.
6. OpenAPI: `OpenApiDocumentTest` stays green, and `/docs/api` shows `sort` with its three values and its default.
7. Documents:
   1. New ADR, the next free number (0056 on 2026-11-20: check it, another open brief plans the same number): "The member directory: every member reads the member list of the community". Context: facts 1, 9, 14 and 17. Decision: rules 1 to 14, 15 to 17, 32 and 33. Consequences: an entry holds no profile field, so a field that enters an entry later needs the check of ADR 0020. A member cannot hide from the list until the slice of #1466. The sentence of the privacy policy. Alternatives: a new endpoint for the directory (refused: the list exists, and rule 4 of the architecture asks for one API), a directory for managers only (refused: it exists in the settings), a cursor (refused: every list uses offsets, and the sort by name has no natural cursor). Add its row to `documentation/adr/README.md`.
   2. `documentation/architecture/roles-and-permissions.md`: who opens the directory, what an entry holds, and what a visitor and a person who is not a member get.
   3. `documentation/architecture/frontend.md`: one paragraph "Member directory", in the form of "Events" point 2.
   4. `documentation/architecture/backend.md`: `CommunityMemberQuery` and its three sorts, and the limiter `member-lists`.
   5. `documentation/api/README.md`: the parameter `sort` of the member list.
   6. The privacy policy, only when the owner approved the sentence of question 4: the same change in `deploy/knowii.net/legal/policy.md` and in `resources/legal-templates/policy.md`. Without the approval, leave the two files alone and say it in the report.
8. The browser check, in the Docker development environment (definition of done 1), with the demo data:
   1. As a member, open a community. "Members" is under "Messages". Open it: the list, the count, "A to Z".
   2. Type the start of a name: the list and the status follow, the field keeps the focus, the address holds the text. Reload: the same list.
   3. Press "Newest": the order changes, the link keeps the focus, the search text stays.
   4. Search a text that matches nobody: the empty state. "Clear the search" gives the full list and the focus in the field.
   5. In a community with more than 20 members, press "Load more": the new entries, the focus on the first new name, no double entry.
   6. Open a member from the list, and come back with the breadcrumb.
   7. Do steps 1 to 5 with the keyboard only, in both themes, at 360 px and at 1920 px, with no sideways scroll and no CSP error in the console.
   8. As the owner: "Manage members" opens the settings. As a member: no such link.
   9. Signed out, open the address of the directory: the sign-in page, then the directory. As a signed-in person who is not a member: the page without access.
   10. In the personal community: no "Members" link.
9. Pictures for the owner, in both themes, at 360 px and at 1920 px. The owner approves these looks before the merge:
   1. The link "Members" in the sidebar and in the drawer (its place, its icon, its word).
   2. The directory with more than 20 members: the tools, the status line, the two columns at 1920 px and the one column at 360 px.
   3. One entry of each role, with the badge, "@username" and "Joined".
   4. The empty state of a search.

If the reviewer wants a smaller diff, the one clean cut is the limiter (rule 32 with test 3.12), as a second commit. The rest does not split.

## NOT in this commit

1. A headline, a bio, a location, a tag, the online status or "last seen" in an entry.
2. A setting for a member to hide from the list, and a community setting for the directory.
3. Filters, tabs, a switch between a list and a grid, a card with "Send a message" or "Copy the link".
4. A search without accents, a search in the middle of a word, a search in a profile field.
5. Live updates of the list when a member joins or leaves. A reload shows the change.
6. Any change of `MemberWithRoleResource`, `MemberProfileResource`, `MemberPicker`, the mention list, the page "About", the community home, or the member management of the settings (#1590, #1588).
7. The date of "Member since" on the member page.
8. The pills of the other screens (#1602), and a neutral fallback picture.

## Later slices, in order

1. The member setting "Show me in the member list" (#1466, points 1 and 4): a column, the settings screen, the privacy policy, the data export. A hidden member leaves the directory, the search, the member fields and the mention list, for members. The managers still see the person in the settings. The member page stays reachable from the posts of the person.
2. The community setting "Who sees the directory: members, or managers only" (#1451, point 2): a new policy ability, and the rule for what the member search gives when the directory is closed.
3. The headline in the entry and in the search, after #1423, with its switch of ADR 0020.
4. The filters "Recently joined" and "Online now". "Online now" reads the presence channel, and shows only the members who allow it (ADR 0027). Nothing new is stored (ADR 0048).
5. The filter by tag, after #1389. A member sees the public tags only.
6. The filter by access group, for the people who can see the access groups (`viewAccessGroups`).
7. The grid view with cards, the switch between the two views, and the default view and sort of the community (#1451).
8. The entry card: "Send a message" and "Copy the link of the profile".
9. The filter by location, on the text that the member shows (ADR 0020). "Near me" and the map come with #1465.
10. The member space type: a directory inside a space group, with the access of a space (a new space type, ADR 0012, with its own ADR).
11. The tab "My connections", after #153.

## How to work

1. `git fetch origin`, then branch `feat/148-member-directory-slice-1` from `origin/main`. ONE commit, no push, no pull request. Find the text when a line number moved.
2. Docker on this Linux machine, from `~/wks/knowii`: `docker compose exec -T -u sail laravel.test <command> </dev/null`. Do not start, stop or restart containers. No `migrate:fresh` and no seeder on the development database.
3. PHP commands in the container: `composer format`, `composer lint`, `composer security:php`, and the whole Pest suite with `php artisan test --parallel`. Front-end commands on the host: `npx nx format --all`, `npm run tsc`, `npm run tsc:spec`, `npx nx run-many -t lint typecheck test`. Never run the whole Pest suite and the whole Vitest suite at the same time.
4. A piped test command hides a failure: use `bash -c "set -o pipefail; ..."`. Without `--parallel`, `php artisan test` takes ONE path.
5. Write the tests of scope 3.1, 3.3 and 3.8 before the code.
6. Check first if the pull request #1687 is on `main` (rule 43). Do not copy its code.
7. Use `example.com` addresses. Never name a third-party community product, in the code, in a test, in the ADR or in the commit.
8. The commit: a conventional type (`feat`), one of the allowed scopes of `commitlint.config.js`, a header of at most 100 characters that does not start with a capital letter. Suggested: `feat(all): a member directory per community, with a search, two sorts and "Load more"`. Footer, after a blank line: `Refs #148`. Not `Closes`: the later slices stay open. No trailer line that names a tool or a co-author.
9. If a rule cannot be built as written, skip that rule, build the rest, and say why in the report. Do not invent product rules.
10. If you find a weakness in the code of `main` while you work, do not write it in the commit, in a test name, in the ADR or in an issue. Handle it as `SECURITY.md` says, and tell the reviewer in private.

## Questions for the owner

1. The order of the first two slices. Slice 1 gives no way for a member to hide from the list, and #1466 says that many members of the live community hide today. Can slice 1 merge first? Recommendation: yes. `main` holds no data of the live community, and every member can already read the list through the API. But the slice of #1466 is a gate: it lands before the import of the live community and before the launch.
2. The entry without a headline. Until #1423, show the start of the bio in the entry (when the member shows the bio), or no profile field? Recommendation: no profile field. The entry stays small, and the headline is the right field for one line.
3. The default sort of the page. "A to Z" or "Newest"? Recommendation: "A to Z". A member finds a person by name, and "Newest" is one click away.
4. The privacy policy (an approved text). The directory shows the member list to every member on a screen. Proposed sentence, in place of the one of fact 17: "The members of a community see its member list: your name, your username, your picture, your role and the date you joined." Recommendation: approve it for this commit, in the policy and in its template.
5. The role in the entry. A badge for the owner, the admins and the moderators only, or "Member" on every other entry too? Recommendation: the three roles only. A list of 600 "Member" badges says nothing.
6. A block and the directory. ADR 0028 covers the messages only. Do the two people still see each other in the directory? Recommendation: yes. The member page stays open to both today, and an entry that goes away tells the blocked person about the block.
7. The community setting "members, or managers only". The comment of 2026-11-15 puts it in the scope of #148, and #1451 holds it with the milestone LATER. Which one wins? Recommendation: later slice 2, and move point 2 of #1451 to the milestone of #148.

## Your final report (max 400 words)

1. The commit hash and `git show --stat`.
2. The tests of scope 3.1, 3.3 and 3.8: their failure before the code and their pass on the branch.
3. Each choice where the brief left room: the mechanism of rule 38, the look of the status line, and the button of rule 43.
4. The query plan of the two sorts on the demo data, and the number of queries of the page.
5. Results of Pest, PHPStan, Pint, the security scan, the type check, the lint, Vitest and the format check, with the exact failure text of anything that failed.
6. The ten steps of the browser check, each with what you saw, and the pictures of scope 9.
7. Anything of the scope not built, and why. The state of the privacy policy sentence (changed with the approval, or left alone).
