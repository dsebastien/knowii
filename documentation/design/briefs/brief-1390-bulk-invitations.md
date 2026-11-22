State: DECIDED on 2026-11-22 (see "Decided by the owner").

# Brief: bulk invitations from a CSV file, with access groups and tags, and a log (knowii-oss/knowii#1390)

## Decided by the owner on 2026-11-22

1. Every recommendation of "Questions for the owner" is the decision (questions 1 to 14).
2. The words of the privacy policy of part A rule 17 and part B rule 90 are approved word for word, and the legal basis of the invitation mail (section 3 item 4) changes to: "To send the invitations of a community: the legitimate interest of the community that invites you (art. 6(1)(f)); the owner of the community decides who is invited." Copy them exactly; change no other word of a legal text.
3. Build part A, then part B, as two commits on one branch. #1391 is built after this slice.

Nothing is built. The owner answers the "Questions for the owner" at the end. Until then, the builder builds what each recommendation, each [Q] rule and each [W] word says. [Q] marks a rule that a question can change. [W] marks a word of a screen, a mail or an error that the owner approves with the pictures. Each [W] word of a screen is one value in one words file (`invitation-words.ts`): a later change is one line. Paths are relative to `~/wks/knowii`.

You build two commits on one local branch: part A, "Invitations carry access groups and tags", then part B, "Invitations from a file, with a log". Part A works alone. Part B needs part A. A reviewer reads your diff afterwards.

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `documentation/conventions/rules.md` (Architecture 1 to 8 and 11, Data 1, 2, 6, 7, 8, 9 and 10, Front-end 2 to 4, 9, 11 and 12, the definition of done), `documentation/conventions/naming.md`.
2. The issues, with their comments: #1390, #1391 (invite links: they share parts with this slice, see "What #1391 shares"), #1242 (invitations), #1241 and #1386 (access groups, the entitlement rules), #1389 (member tags), #1396 (the import of a live community: it reuses part B), #1454 (limits per community), #1478 (bounces: not built), #934 (audit log: not built).
3. ADRs: 0019 (invitations), 0025, 0041 and 0047 (access groups), 0060 (member tags: "The contract for #1390 and #1391" is binding), 0054 (the mail queue, the budget, the hold), 0011 (pagination, errors), 0022 (abilities), 0030 (retention), 0044 ("Who can join", the join block), 0055 (instance values), 0024 (account deletion).
4. `documentation/design/notifications/email-groundwork.md`, sections 3.2 point 10, 3.5, 3.8 and 7.2 (what #1478 plans for the invitations).
5. `documentation/architecture/backend.md` ("Email delivery", the member tags part at line 434, the retention part at line 53), `documentation/architecture/roles-and-permissions.md` (line 43, lines 130 and 131, line 161), `documentation/api/README.md` (the abilities table).
6. The code listed in "What exists today".

## What exists today (facts, with the evidence)

Line numbers are those of `main` at `9317a167` (member tags T1 merged). Find the text when a line moved.

Invitations:

1. The table `community_invitations` holds `cuid`, `community_id` (cascade), `email`, `role` and timestamps, with a unique `(community_id, email)` that reads the case (`database/migrations/2024_08_29_081030_create_community_invitations_table.php:15-24`). The address is stored in lower case by the action (`app/Actions/CommunityMembers/InviteCommunityMember.php:40`).
2. `InviteCommunityMember` checks the gate `addCommunityMember`, validates `email` (`email:strict`, 255) and `role`, checks `CommunityRoles::canGrant`, refuses a member of the community, deletes the invitation of the same address and creates a new one in a transaction, then queues the mail on `default`, logs cuids only and sends `CommunityMembersChanged` (`InviteCommunityMember.php:31-66`).
3. A member is found with `Community::hasUserWithEmail`, which loads every member of the community and compares in PHP (`app/Models/Community.php:229-238`).
4. `CommunityInvitation::VALIDITY_DAYS` is 7, and `expiresAt()` counts from `created_at` (`app/Models/CommunityInvitation.php:41,84-92`). The invitation does not store who sent it (ADR 0019, `roles-and-permissions.md:161`).
5. `AcceptCommunityInvitation` compares the address of the account and of the invitation without the case, refuses an expired invitation, then in one transaction attaches the person when they are not a member, deletes the join block and deletes the invitation. After it, `CommunityMembersChanged` and `EntitlementReads::afterJoin` (`app/Actions/CommunityMembers/AcceptCommunityInvitation.php:24-51`). It gives no access group and no tag.
6. `CancelCommunityInvitation` needs `addCommunityMember` and `canGrant` of the role of the invitation (`CancelCommunityInvitation.php:22-34`).
7. The API: `GET`, `POST .../invitations` and `DELETE .../invitations/{communityInvitation}` in `scopeBindings`, with `communities:read` and `communities:write`, and the limiter `invitations` on the create (`routes/api.php:330-335`, `app/ApiRoutes.php:64-65,269-271`). The list returns every pending invitation, with no pagination (`CommunityInvitationApiController.php:29-36`). The resource holds `cuid`, `email`, `role`, `created_at`, `expires_at`, `is_expired` (`CommunityInvitationResource.php:20-30`), mirrored by `communityInvitationSchema` (`libs/common/src/lib/types/member.schema.ts:110-124`).
8. The limiter `invitations`: 10 a minute per user (`app/Providers/AppServiceProvider.php:484-487`, `app/Constants.php:202`).
9. The mail `App\Mail\CommunityInvitation` builds a temporary signed link to `community-invitations.accept` and names the community, the inviter, the operator, the date of expiry and the retention period (`app/Mail/CommunityInvitation.php:22-45`). Its footer says: "We got your email address from the person who invited you and use it only for this invitation. If you do nothing, we delete the invitation and your address :period after it expires." (`resources/views/emails/community-invitation.blade.php:26`).
10. The screen: `CommunityMembersManager` has the form "Invite by email" with a role, and the card "Pending invitations", which loads the whole list (`apps/knowii/src/Components/Communities/CommunityMembersManager.tsx:114-138,170-195,260-300,388-420`). The settings page shows it before `MemberTagsManager` and `AccessGroupsManager` (`apps/knowii/src/Pages/Communities/Settings.tsx:152,168,183`). The permission props come from `CommunityController.php:214-225`.

Who may do what:

11. `addCommunityMember`, `manageAccessGroupMembers` and `assignMemberTags` all pass for the owner, the admins and the moderators with `manage_members` (`app/Policies/CommunityPolicy.php:105-108,154-157,182-185`). `manageAccessGroups` and `manageMemberTags` are for the owner and the admins (lines 145 to 148, 172 to 175).
12. `CommunityRoles::canGrant` gives only a role lower than the own role: only the owner makes admins (`app/Support/CommunityRoles.php:164-168`).

Access groups and tags:

13. `AccessGroupMemberships::addManual` is the one writer of `access_group_members` for a membership by hand (`app/Support/AccessGroupMemberships.php:27-34`). It does not check that the person is a member: `AddAccessGroupMember::findMember` does (`app/Actions/AccessGroups/AddAccessGroupMember.php:54-70`).
14. The names of access groups are unique with the case: "Paid" and "paid" can both exist (`database/migrations/2026_11_09_239000_create_access_groups_tables.php:16-25`, ADR 0060 fact 1). A community has at most 50 groups (`Constants.php:211`).
15. `MemberTagAssignments::add` locks the membership row, refuses a person who is not a member (`ValidationException`), changes nothing the second time, and throws a `BusinessException` at the 21st tag of a member (`app/Support/MemberTagAssignments.php:31-56`). `MemberTag::findByName` cleans the name and compares without the case (`app/Models/MemberTag.php:116-124`). `MAX_MEMBER_TAGS_PER_MEMBER` is 20, and its comment says that an invitation holds as many at most (`Constants.php:220-221`).
16. `Community::removeUser` deletes the group and tag rows of a member who leaves or is removed (`Community.php:375-401`).

The join block:

17. A removal writes a row of `community_join_blocks`. An accepted invitation and a manager delete it (`app/Models/CommunityJoinBlock.php:11-16`, `AcceptCommunityInvitation.php:40-41`). `JoinCommunity` locks the community row and refuses a blocked person (`JoinCommunity.php:34-51`).

18. `GET .../join-blocks` gives the managers the removed people with the cuid, the name, the username, the picture and the date, never the address (`app/Http/Resources/CommunityJoinBlockResource.php:20-34`, `routes/api.php:297-298`).

Mail:

19. ADR 0054 point 1: only notification mail goes on the queue `mail`, with the hold and the limiter. `CommunityInvitation` stays on `default`, never held, with no limiter. `NotificationMail` sets the middleware `[HoldNotificationMail, RateLimited('mail')]` (`app/Mail/NotificationMail.php:80-83`). The limiter `mail` is one global key, 5 a second by default (`AppServiceProvider.php:526-530`).
20. `HoldNotificationMail` ends a job as done, without a send, while `ChecksMailBudgets::isHeld()` is true. It reads the quota refusal of the provider in the chain of exceptions and holds the budget (`app/Jobs/Middleware/HoldNotificationMail.php:38-80`). The reading is a private method.
21. `CountSentEmails` counts every sent mail in `mail_usage_months.sent_count`, whatever the queue (ADR 0054 point 8). `MailSettings::monthlyQuota()` and `holdPercent()` give the numbers (`app/Support/MailSettings.php:39,50`).
22. No bounce is tracked: no table `mail_deliveries` or `email_suppressions` exists. #1478 plans `bounced_at` on `community_invitations` (`email-groundwork.md:178`).

Data rules:

23. Retention: `retention.expired_invitations_days` (30) in `config/retention.php:16-17`, `Retention::invitationValidityDays()` and `expiredInvitationsDays()`, the placeholders `invitation` and `expired_invitations` (`app/Support/Retention.php:30-38,128-129`), the prune step `invitations` (`app/Actions/Retention/PruneExpiredData.php:42-45`), `retention:prune` every day at 03:40 (`routes/console.php:11-12`).
24. The community export lists each invitation with `email`, `role`, `created_at` (`app/Actions/Communities/ExportCommunity.php:153,194-203`, the readme line 723). The member export lists the invitations sent to the address of the account (`app/Actions/Users/ExportUser.php:105,313-323`, the readme line 953). `DeleteUser` deletes the invitations of the address (`app/Actions/DeleteUser.php:138`).
25. The policy (`deploy/knowii.net/legal/policy.md`, and the same text one line higher in `resources/legal-templates/policy.md`): section 2 item 3 names "the invitations sent to your email address" (line 23), item 7 "when a manager of a community invites you, they give us your email address" (line 27), section 3 item 4 gives "the contract" as the basis of the invitation mail (line 40), section 5 item 4 "Invitations: valid for {{retention.invitation}}, deleted {{retention.expired_invitations}} after they expire." (line 64). The data processing agreement names "the people it invites" as data subjects and "invitations" in the processing (`deploy/knowii.net/legal/dpa.md:19,23`).

The API and the platform:

26. A field that depends on a token ability exists: `EventResource` reads `currentAccessToken()` and checks `can('event-answers:read')` (`app/Http/Resources/EventResource.php:36,98-104`). A web session has every ability.
27. A file upload through the API exists: the course materials take `file` as multipart, with `file`, `max`, `mimetypes` and `extensions` rules (`CourseMaterialApiController.php:63-71`, `app/Actions/CourseMaterials/CreateCourseMaterial.php:57`).
28. `composer.json` holds no CSV library. PHP reads CSV with `str_getcsv` or `SplFileObject`.
29. A private channel per user exists (`routes/channels.php:51-53`, `Constants::WS_CHANNEL_USER`, `Constants.php:442`).
30. The toolkit has `Stepper`, `Checkbox`, `Dialog`, `Select`, `LoadMoreButton`, `EmptyState`, `Badge`, `SaveStatus`. It has no multiple `Combobox` and no file input block: the course materials and the avatar form use a plain `input type="file"` (`apps/knowii/src/Components/SpacePages/CourseMaterials.tsx:414`).
31. The next free ADR number is 0061 on 2026-11-22. Make sure that it is still free.

## The design

Each decision has its reason. "Refused" names an option that this design does not take.

The file:

1. The format is CSV, UTF-8, with a header row. Reason: every spreadsheet and every member export of another tool writes it.
2. The columns, by their header (trimmed, without the case, a space or a dash read as `_`): `email` (required), `name` (read and ignored, [Q, question 5]), `role` ([Q, question 6]), `access_groups`, `tags`. Another column is ignored, and the preview names it. A known column twice refuses the file.
3. In the cells `access_groups` and `tags`, the names are separated by `|`. Reason: a semicolon is the cell delimiter of the CSV files that some spreadsheets write, and a comma is the delimiter of the others.
4. Limits: 1000 data rows, 1 MiB, 20 columns, 1000 characters per cell ([Q, question 8]). Constants in `App\Constants`, mirrored in `constants.ts`.
5. The server reads the file. The client sends the file and shows what the server answers. The client never parses the file and never sends rows. Reason: never trust the client.
6. The file is never written to a disk, a queue, a cache or a log. The server reads it from the temporary file of the request, and PHP deletes that file at the end of the request. Reason: issue note "It is deleted after the import", and data rule 8.
7. A file can also give defaults to every row: a role, access groups and tags, chosen on the form. A row adds its own groups and tags to the defaults. The role of a row replaces the default role.

The flow:

8. Upload, then preview, then confirm, then the send, then the log. The preview is stored: an import in the state `preview` with one row per data row of the file. Reason: the manager confirms what the server checked, and the file goes up one time only. Refused: a preview that the client keeps and sends again at the confirm (the client would hold the truth).
9. A preview lives 24 hours. Then the prune step deletes it. A manager has at most one preview per community: a new upload deletes their older preview.
10. The confirm checks every valid row again, in one transaction, and creates the invitations with their groups and tags. A row that changed since the preview (the person joined, another manager invited them, a tag was deleted) becomes a refused row with its reason.
11. Each invitation of an import gets its mail through one queued job per row, on the mail queue, with the limiter `mail` and a hold of its own. The log says the result of each row: sent, held, failed, skipped.
12. The log is the import: who started it, when, the counts, and the result and the reason of each row. The counts are computed from the rows, never stored as counters.
13. "Invite again the people who did not join" is one action on an import: once, 3 days after the confirm at the earliest ([Q, question 11]). "Send the mails that were not sent" (the held and the failed rows) is a second action, at any time.

Who:

14. Upload, read and discard: the gate `addCommunityMember` (the owner, the admins, the moderators with `manage_members`). Each row needs `canGrant` of its role for the person who uploads, and again for the person who confirms.
15. Access groups on an invitation need `manageAccessGroupMembers`. Tags on an invitation need `assignMemberTags` (contract point 3). Today the three gates pass for the same people. The code checks each one, so a later change of one rule holds.
16. A token that sets access groups needs `groups:write` too, and a token that sets tags needs `member-tags:write` too, as fact 26 does for a read. Reason: ADR 0060 decision 29, a token that syncs tags must not give paid groups.
17. Only the person who uploaded a preview confirms it: they saw it. The owner and the admins can also discard it.
18. An access group that an entitlement rule feeds can be put on an invitation ([Q, question 13]). Reason: an invitation names one address, as a hand add does. At the join the membership is by hand (`manual`). Invite links decide their own rule in #1391.

The mails:

19. A single invitation stays as ADR 0054 point 1 says: level 0, on `default`, never held. The mail of an import goes on the mail queue with the limiter and a hold of its own ([Q, question 2]). Reason: a manager asks for it, not the person who gets it (design 7.2 point 1), and one file can hold 1000 mails. Without the limiter, 1000 mails on `default` go out at the speed of the worker, over the 10 a second of the provider, and a sign-in link waits behind them. Without the hold, a file can use the reserve that the sign-in links need.
20. The text is the text of the single invitation: the same mailable, the same footer.
21. Before the confirm, three checks refuse the whole import, and nothing changes: the budget holds mail now; the import needs more mails than the instance can send this month before the hold; the community passes 1000 mails from files in 24 hours ([Q, question 8]). The messages never show the numbers of the instance.
22. Addresses: compared in lower case. The same address twice in a file: the first row counts, the next rows are refused. An address of a member (the owner too): refused. An address with a valid pending invitation: refused, no second mail ([Q, question 4]). An address with an expired invitation: the import replaces it. An address of a person whom a manager removed (a join block): refused ([Q, question 3]). Reason: an accepted invitation lifts the block, and a file must not lift many removals by mistake. A manager invites such a person one at a time.
23. No check of the domain of an address (DNS) and no list of refused addresses yet. #1478 adds bounces. Until then, the limits of decision 4 and 21 limit the harm to the sender ([Q, question 12]).

At the join:

24. The access groups and the tags of an invitation apply when the person accepts it, in the transaction of the join (contract point 5). Groups through `AccessGroupMemberships::addManual`, tags through `MemberTagAssignments::add`. A tag past the limit of 20 is skipped with a log line: the join never fails for a tag.
25. A person who is a member already when they accept gets the groups and the tags too.
26. An invitation never shows a group or a tag to the person: not in the mail, not on a page before the join. A tag of the managers stays one after the join (ADR 0060 decision 14).

Privacy:

27. The record of an import keeps the address of each row, the result and the reason. It lives as long as an invitation: it is deleted `{{retention.invitation_imports}}` after the confirm (the 7 days of validity plus `expired_invitations_days`), and a preview `{{retention.invitation_import_previews}}` after the upload ([Q, question 9]). Reason: the mail tells the person that the address is deleted that long after the invitation ends. The record must not keep it longer.
28. The name of a row is never stored.
29. The community export lists the imports with their rows. The member export lists the rows with the address of the account. An account deletion deletes the rows with its address, compared without the case.
30. No log line, no broadcast, no job payload and no error text holds an address, except the answer of the API to a manager.

The cut:

31. Part A: an invitation carries access groups and tags, for one invitation (issue scope 5), with the join, the API, the form, the exports and the policy words of the groups and the tags.
32. Part B: the import (issue scope 1 to 4), with the file, the preview, the send, the log, the retention, the API, the pages and the ADR.

## What #1391 shares (part A builds it first)

1. `App\Support\InvitationGrants`: reads `access_groups` and `member_tags` (lists of cuids) for one community, checks the gates of decision 15 and the token abilities of decision 16, holds the limit of 20 tags, and gives the models. #1391 calls it for the form of a link.
2. `App\Support\JoinGrants::apply(User $member, Community $community, iterable $groups, iterable $tags)`: decision 24 inside the transaction of the caller. After the commit, the caller sends `AccessGroupsChanged` and `SpaceTreeChanged` one time when a group was given. #1391 calls it in the join through a link.
3. The form part `Components/Communities/InvitationGrantsPicker.tsx` [W: the name]: a button with the count and a `Dialog` with one `Checkbox` per access group and per tag. #1391 uses it in the form of a link.
4. The shape of the link tables (contract point 4): `community_invitation_access_groups` and `community_invitation_member_tags` here, `invite_link_access_groups` and `invite_link_member_tags` in #1391.
5. The sentence of the policy on what a manager chooses for the join (rule 23). #1391 adds "or a link".
6. Not shared: the CSV file, the imports, the mail checks (a link sends no mail), the log.

## Part A: invitations carry access groups and tags

ONE commit. Rules 1 to 28.

### Rules

The data model:

1. One new migration with two link tables (`naming.md`: both names, singular first): `community_invitation_access_groups` (`community_invitation_id`, `access_group_id`) and `community_invitation_member_tags` (`community_invitation_id`, `member_tag_id`). Both foreign keys `cascadeOnDelete`, with the reason in a comment: a deleted group or tag leaves the invitation (contract point 4), a deleted invitation takes its links. `created_at` only, a unique pair, an index on the second key. No `cuid`, as `member_tag_members`: no route names a row. The ADR says so (data rule 1). `down()` drops both.
2. `CommunityInvitation` gets `accessGroups()` and `memberTags()`.

The create:

3. `POST .../invitations` takes two optional fields: `access_groups` (a list of cuids of access groups of the community, at most 50) and `member_tags` (a list of cuids of tags of the community, at most 20). Duplicates count once.
4. `InvitationGrants` (shared point 1) checks: a cuid of another community or of nothing is a field error, "No access group of the community matches" or "No member tag of the community matches" [W]. Groups without `manageAccessGroupMembers`: 403 "You cannot give access groups" [W]. Tags without `assignMemberTags`: 403 "You cannot give member tags" [W]. A personal token without `groups:write` (or `member-tags:write`) and a non-empty list: 403 with the same text. An empty list or a missing field needs nothing.
5. The order of the refusals: 400 for `email` and `role`, the role hierarchy (403), the member (400), then rule 4.
6. A new invitation of the same address replaces the old one, as today: the new one has the groups and the tags of the new request only.
7. The log line adds the counts of groups and tags, never their names.

The read:

8. `CommunityInvitationResource` gets `access_groups` (each `cuid` and `name`) and `member_tags` (each `cuid`, `name`, `color`, `visibility`), sorted by name without the case. A reader who does not pass `viewMemberTags` gets an empty `member_tags`, and one who does not pass `viewAccessGroups` an empty `access_groups`. The list loads both for the whole page with two queries. `communityInvitationSchema` mirrors them.
9. `GET .../invitations` gets `limit` (default 10, maximum 1000) and `offset`, and `metadata.pagination` (ADR 0011 point 5 and its consequence 3: a route that is touched follows the guide) [Q, question 14]. The order stays: newest first, then the id.

The join:

10. `AcceptCommunityInvitation` loads the groups and the tags of the invitation before its transaction, and calls `JoinGrants::apply` inside it, after the attach and before the delete of the invitation.
11. `JoinGrants::apply` puts each group with `AccessGroupMemberships::addManual` and each tag with `MemberTagAssignments::add`. A `BusinessException` of the limit of 20 skips that tag and writes one log line with the cuids of the community, the tag and the member. Any other exception fails the join, as today.
12. After the commit: `AccessGroupsChanged` and `SpaceTreeChanged` one time each when a group was given, then `EntitlementReads::afterJoin` as today. No broadcast for a tag (ADR 0060 decision 20).
13. A person who is a member already gets the groups and the tags (decision 25), and keeps their role.
14. The mail and the page of the link name no group and no tag (decision 26).

The data rules:

15. `ExportCommunity`: each invitation gets `access_groups` and `member_tags` (the cuids). The readme line says so.
16. `ExportUser`: each invitation gets `access_groups` and `member_tags`, each with the cuid and the name, both visibilities of a tag (ADR 0060 decision 14: the person has a right to their data). The readme line says so.
17. The policy, both files, words to approve before the merge [Q, question 10]. Change no other word:
    1. Section 2, item 3, after "the invitations sent to your email address": ", with the access groups and the tags that they give when you join".
    2. Section 2, item 7, after "they give us your email address": ", and they can choose the access groups and the tags that you get when you join".
18. No retention change: the links go with the invitation.

The screens:

19. `InvitationGrantsPicker` (shared point 3): a secondary button "Access groups and tags" [W], with the count when it is not 0 ("Access groups and tags (3)"). It opens a `Dialog` with two fieldsets, "Access groups" (shown with `canManageAccessGroupMembers`) and "Member tags" (shown with `canAssignMemberTags`), one `Checkbox` per item, sorted by name. A tag shows its `Badge` color and, for a tag of the managers, the hidden words "managers only", as `MemberTagList`. The 21st tag cannot be checked, and a polite status says "An invitation holds at most 20 tags" [W]. "Done" closes it, and the focus goes back to the button.
20. With no group and no tag in the community, the button is not shown.
21. The form "Invite by email" gets the picker after the role. After a send, the picker is empty again, and the focus goes back in the email field, as today (Front-end rule 12).
22. Each row of "Pending invitations" shows the names of its groups as text and its tags with `MemberTagList`.
23. "Pending invitations" loads 20 at a time with `LoadMoreButton` ("Load more" [W]), the focus on the first new row and the status "20 more loaded" with the real number.
24. The live update `CommunityMembersChanged` loads the first page again, as today.

The API and the documents:

25. `InvitationGrants` and `JoinGrants` have their docblocks. The controller gets `BodyParameter` attributes for the two lists. Bruno: "Invitations - Create" shows both lists, "Invitations - List" shows `limit` and `offset`.
26. The API README: the two fields, the abilities of rule 4, the pagination.
27. `backend.md`: a paragraph "Invitations carry access groups and tags" (the two tables, the two classes, the join). `roles-and-permissions.md`: one line after line 131.
28. No ADR in part A: the ADR of part B holds both parts. Part A says in its commit body that the ADR comes with part B.

### Tests of part A

1. Pest, `CommunityMemberManagementTest.php` or a new `InvitationGrantsTest.php`:
   1. A create with groups and tags, by the owner, an admin and a moderator with `manage_members`. The resource gives both lists.
   2. A cuid of another community and a cuid of nothing (400, the field). 21 tags (400). A duplicate cuid counts once.
   3. A token with `communities:write` only and a list of groups: 403. With `groups:write` too: 201. The same for the tags. A token with an empty list needs nothing.
   4. A replace: the new invitation has the new lists only.
   5. The join: the groups by hand (`manual` true, `by_rule` false), the tags. A person who is a member already. A person with 20 tags: the join works, the 21st tag is skipped, one log line holds no name. A tag deleted before the join: the cascade, the join works. A group deleted: the same.
   6. A transaction test: a failure inside `JoinGrants` (a fake that throws) leaves no member, no group, no tag, and the invitation stays.
   7. The broadcasts: `AccessGroupsChanged` and `SpaceTreeChanged` one time with a group, none without.
   8. The mail: its text holds no name of a group or a tag.
   9. The list: the pagination, the order, the two queries for the lists with 3 and with 30 invitations (the same count).
   10. `CommunityExportTest` and `UserExportTest`: the new fields. `HostedLegalTextsTest` and `LegalTemplatesTest` with the new words. `BrunoCollectionTest`, `OpenApiDocumentTest`, `ApiTokenAbilitiesTest` pass. `down()` runs in a test database.
2. Vitest, with axe: `InvitationGrantsPicker.spec.tsx` (both fieldsets, one alone, the limit, the focus back), `CommunityMembersManager.spec.tsx` (the form sends the lists, the rows show them, the load more), the schema spec.

## Part B: invitations from a file, with a log

ONE commit, on top of part A. Rules 29 to 103.

### The data model

29. One new migration with two tables.
30. `invitation_imports`: `id`, `cuid` (unique), `community_id` (constrained, `cascadeOnDelete`), `user_id` (the person who uploaded, nullable, `nullOnDelete`: a deleted account leaves the record as "Deleted member"), `state` (string, a check on `preview`, `sending`, `done`), `default_role` (the role enum), `default_access_groups` and `default_member_tags` (jsonb lists of cuids), `ignored_columns` (jsonb list of strings), `confirmed_at`, `finished_at`, `resent_at` (the one "invite again"), timestamps. An index on `(community_id, created_at)`.
31. `invitation_import_rows`: `id`, `cuid` (unique), `invitation_import_id` (constrained, `cascadeOnDelete`), `row_number` (the record number in the file, the header is 1), `email` (string 255: the cell in lower case, cut to 255 characters, also when it is not a valid address, so that the manager sees what to fix), `role`, `access_group_names` and `member_tag_names` (jsonb lists of the cleaned names of the row with the defaults), `outcome` (string, a check on the values of rule 33), `reason` (nullable string, a check on the values of rule 52), `detail` (nullable string, 64: the name that was not found), `community_invitation_id` (nullable, `nullOnDelete`: an accepted or cancelled invitation leaves the row), `queued_at`, `sent_at`, timestamps. Unique `(invitation_import_id, row_number)`. An index on `lower(email)` for the account deletion and the member export.
32. Why names and cuids in jsonb and not link tables: a preview lives 24 hours and the confirm resolves every name and cuid again (rule 61). The lasting links are those of part A, with their foreign keys. The ADR says so (data rule 2).
33. `outcome`: `valid` and `refused` in a preview; `queued`, `sent`, `held`, `failed`, `skipped` and `refused` after the confirm. Enums `App\Enums\KnowiiInvitationImportState`, `KnowiiInvitationImportOutcome`, `KnowiiInvitationImportRefusal`, aligned with `libs/common`.
34. Models `InvitationImport` and `InvitationImportRow` with docblocks, `GeneratesCuid`, `#[UseResource]`, hidden ids, `getRouteKeyName()` on `cuid`, factories with the states. `Community::invitationImports()` (named after the route parameter, for the scoped binding).
35. Constants in `App\Constants`, mirrored in `constants.ts`: `MAX_INVITATION_IMPORT_ROWS` 1000, `MAX_INVITATION_IMPORT_KILOBYTES` 1024, `MAX_INVITATION_IMPORT_COLUMNS` 20, `MAX_INVITATION_IMPORT_CELL_LENGTH` 1000, `MAX_INVITATION_IMPORT_MAILS_PER_DAY` 1000 (per community, 24 hours), `INVITATION_IMPORT_RESEND_AFTER_DAYS` 3, `INVITATION_IMPORTS_PER_MINUTE` 5.

### The file (each rule is a test of `tests/Unit/InvitationCsvTest.php`)

36. `App\Support\InvitationCsv::read(string $path): InvitationCsvResult` is a final class with no query. It gives the header, the ignored columns and the records with their numbers, or one file error. It knows nothing of a community.
37. A file over 1 MiB: 400 on `file`, "The file is larger than 1 MB" [W]. The rule `file`, `max`, `extensions:csv`, and `mimetypes` with `text/csv`, `text/plain`, `application/csv`, `application/vnd.ms-excel` (a spreadsheet on Windows sends this type). The content rules below are the real check.
38. A byte order mark at the start is removed. Bytes that are not valid UTF-8, or a NUL byte: 400, "Save the file as CSV UTF-8, then upload it again" [W]. No guess of another encoding.
39. CRLF, LF and a lone CR all end a record.
40. The delimiter is a comma. When the header record holds no comma and holds a semicolon, the delimiter is a semicolon. Quotes follow RFC 4180: a quoted cell can hold the delimiter, a line break and `""`. No escape character (`escape: ''`).
41. The header is the first record that is not empty. Rule 2 of the design reads it. No column `email`: 400 "The file needs a column named email" [W]. A known column twice: 400 "The column {name} is in the file twice" [W]. More than 20 columns: 400. The ignored columns are kept for the preview, at most 20 names of 64 characters.
42. A record whose cells are all empty is skipped and not counted. More than 1000 other records: 400 "A file holds at most 1000 rows. Split it into several files." [W].
43. A record with more cells than the header: the extra cells are ignored when they are empty. Else the row is refused (`extra_cells`).
44. A cell is trimmed. A cell over 1000 characters refuses its row (`cell_too_long`).
45. The cell `name` is dropped by `InvitationCsv`: it never leaves the class.

### The checks of the rows

46. Contract `App\Contracts\InvitationImports\CreatesInvitationImports`, action `CreateInvitationImport::create(User $user, Community $community, iterable $records, array $defaults): InvitationImport`. It takes records, not a file, so that #1396 calls it with the rows of its own mapping, 1000 at a time. The API controller reads the file with `InvitationCsv`, then calls it.
47. It checks the gate `addCommunityMember` first, then the defaults: `default_role` (`member` when absent, `canGrant`), `access_groups` and `member_tags` through `InvitationGrants` (shared point 1). A default refused is a 403 or a 400 for the whole upload.
48. `email`: `mb_strtolower`, then the rules `email:strict` and `max:255`. A row that fails is refused (`invalid_email`).
49. `role`: empty means the default. `member`, `moderator` or `admin` without the case. Another value: `unknown_role`. Then `canGrant` for the uploader: `role_not_allowed`.
50. `access_groups`: the cell is split on `|`, each part trimmed with its spaces made one, the empty parts dropped. For each name: the group of the community with exactly that name, else the one group whose `lower(name)` is equal, else `unknown_access_group`. Two groups that differ only in the case and no exact match: `ambiguous_access_group`. `detail` holds the name, cut to 64 characters. The groups of the row join the defaults.
51. `tags`: split on `|`, each part through `MemberTag::findByName`, else `unknown_tag` with `detail`. Never a new tag (contract point 5) [Q, question 7]. The tags join the defaults. More than 20: `too_many_tags`. A non-empty cell of groups without the rights of decision 15 or 16: `access_groups_not_allowed`. The same for tags: `tags_not_allowed`.
52. A row has one reason, the first in this order: `extra_cells`, `cell_too_long`, `invalid_email`, `duplicate_in_file` (the same address on an earlier row; `detail` holds that row number), `unknown_role`, `role_not_allowed`, `access_groups_not_allowed`, `unknown_access_group`, `ambiguous_access_group`, `tags_not_allowed`, `unknown_tag`, `too_many_tags`, `already_member` (the owner too), `removed_by_manager` (an account with this address, compared without the case, has a join block in this community), `pending_invitation` (a pending invitation of this community that is not expired). After the confirm, a row that fails gets the reason that applies then.
53. Each reason has its text, given by the API as `reason_text`: "This is not a valid email address", "This address is on row {n} already", "This person is already a member", "A manager removed this person. Invite them one at a time if they may come back.", "This address has a pending invitation. It is valid until {date}.", "No access group is named {name}", "Two access groups have this name in another case. Write the name exactly.", "No member tag is named {name}", "An invitation holds at most 20 tags", "You cannot give this role", "Use member, moderator or admin", "You cannot give access groups", "You cannot give member tags", "This row has more cells than the header", "A cell holds more than 1000 characters" [W, all].
54. The checks run with a fixed number of queries per file, whatever the number of rows: the members by `lower(email)` in one query (the owner too), the pending invitations, the join blocks of the accounts with these addresses, the groups and the tags of the community, one time each. Never `hasUserWithEmail` per row. A test reads a file of 10 rows and one of 1000 rows: the same number of queries.
55. The rows are written with one insert per 500 rows. A new upload of the same person in the same community deletes their older import in `preview` first.
56. The answer says nothing about an address that rule 52 does not need: an address with an account and no block is a valid row, as an address without an account.

### The confirm

57. Contract `ConfirmsInvitationImports`, action `ConfirmInvitationImport`. Only the uploader: else 403 "Only the person who uploaded the file sends it" [W]. An import that is not in `preview`: 422 "This import is sent already" [W]. A preview older than the preview period answers 404, also before the prune step ran.
58. One transaction: lock the import row, then the community row (as `JoinCommunity`). Two confirms of the same import: the second gets 422.
59. Every valid row is checked again with rules 47 to 54, for the confirmer. A row that fails now is `refused` with its reason.
60. Then the checks of decision 21, for N, the rows that stay valid, each a 422 for the whole import, and nothing changes:
    1. `ChecksMailBudgets::isHeld()`: "This instance holds its mail now. Send the invitations after {date}." [W].
    2. When `MailSettings::monthlyQuota()` is not null: `floor(quota × holdPercent / 100)`, less `sent_count` of the month, less the rows `queued` of every import of the instance, is smaller than N: "This import needs {N} emails. This instance cannot send so many this month. Send fewer rows." [W]. No number of the instance in the text.
    3. The rows of the imports of this community with `queued_at` in the last 24 hours (by a confirm or a send again), plus N, pass 1000: "A community sends at most 1000 invitations from files in 24 hours. {M} more can go now." [W].
61. Then, for each valid row: the expired invitation of the address is deleted; the invitation is inserted with `on conflict (community_id, email) do nothing`; a row with no insert is `refused` (`pending_invitation`); else the links of its groups and tags are written (the names and cuids resolved again: one that is gone refuses the row), the row is `queued` with `community_invitation_id` and `queued_at`. The import is `sending` with `confirmed_at`.
62. After the commit: one job per queued row, one `CommunityMembersChanged`, one event of rule 75, one log line with the cuids and the counts. The answer is 202 with the import.

### The send

63. `App\Jobs\SendInvitationImportMail` holds the id of the row only, never the address. It runs on `MailSettings::queue()`.
64. Its middleware, in this order: `App\Jobs\Middleware\HoldInvitationImportMail`, then `RateLimited('mail')`. `retryUntil()`, `$maxExceptions` and `backoff()` as `NotificationMail`, with the same constants.
65. `HoldInvitationImportMail`: while `isHeld()` is true, the row is `held` and the job ends as done. A quota refusal of the provider holds the budget as ADR 0054 point 6 says, the row is `held`, and the job ends. The reading of the refusal moves from `HoldNotificationMail` to one shared static helper (for example `App\Support\MailQuotaRefusal::typeOf(Throwable): ?string`). `HoldNotificationMail` keeps its behavior, and its tests pass unchanged.
66. `handle()`: the invitation is gone (cancelled, accepted): the row is `skipped`. The address is a member now: the row is `skipped`, and the invitation is deleted. Else `created_at` of the invitation is set to now (the link is valid 7 days from the mail), then `Mail::to($email)->send(new CommunityInvitation($invitation, $inviter))`, then the row is `sent` with `sent_at`. The inviter that the mail names is the person who uploaded the file (`user_id`), else the owner of the community. `send()` runs inside the queued job, so the limiter and the hold apply (ADR 0054 consequence 6).
67. `failed()`: the row is `failed`.
68. After each row that ends: when no row of the import is `queued`, the import is `done` with `finished_at` (one conditional update, so two workers never both write it), then one `CommunityMembersChanged`.
69. `CountSentEmails` counts these mails as it counts every mail: nothing to add.

### Send again

70. Contract `ResendsInvitationImports`, action `ResendInvitationImport`, body `rows`: `not_sent` or `not_joined`.
71. `not_sent`: the rows `held` and `failed` whose invitation still exists. At any time.
72. `not_joined`: the rows `sent` whose invitation still exists. One time per import (`resent_at`), and not before 3 days after `confirmed_at`. Else 422: "You can invite these people again from {date}." or "You invited these people again on {date} already." [W].
73. Who: a person who passes `addCommunityMember` and `canGrant` for the role of every row of the set. Else 403.
74. The checks of rule 60 apply to the set. Then each row is `queued` again with a new `queued_at`, and one job per row. A set with no row: 422 "No row to send" [W].

### Live updates

75. `App\Events\InvitationImports\InvitationImportChanged` on the private channel of the user who confirmed (`WS_CHANNEL_USER`), with `community_cuid` and `import_cuid` only. Sent at the confirm, when the number of ended rows is a multiple of 25, and at the end. Other managers see the new state when they load the page. No polling (Architecture 8).

### The API

76. Constants in `ApiRoutes.php`, mirrored in `constants.ts`: `PARAM_INVITATION_IMPORT` (`invitationImport`), `COMMUNITY_INVITATION_IMPORTS`, `COMMUNITY_INVITATION_IMPORT`, `COMMUNITY_INVITATION_IMPORT_ROWS`, `COMMUNITY_INVITATION_IMPORT_CONFIRM`, `COMMUNITY_INVITATION_IMPORT_RESEND`. Seven routes in the signed-in group, inside `scopeBindings`:
    1. `POST .../invitation-imports` (multipart: `file`, `default_role`, `access_groups[]`, `member_tags[]`): 201 with the import in `preview`. `invitations:write`, `throttle:invitation-imports`.
    2. `GET .../invitation-imports`: the imports of the community, newest first, `limit` and `offset`. `invitations:read`.
    3. `GET .../invitation-imports/{invitationImport}`: one import. `invitations:read`.
    4. `GET .../invitation-imports/{invitationImport}/rows`: `outcome` (one value or several, comma separated), `limit` (default 10, maximum 1000) and `offset`, sorted by `row_number`. `invitations:read`.
    5. `POST .../invitation-imports/{invitationImport}/confirm`: 202. `invitations:write`, `throttle:invitation-imports`.
    6. `POST .../invitation-imports/{invitationImport}/resend`: 202. `invitations:write`, `throttle:invitation-imports`.
    7. `DELETE .../invitation-imports/{invitationImport}`: 204. A preview: the uploader, the owner, the admins. A sent import: the owner and the admins delete its record early; its invitations stay. `invitations:write`.
77. Every route checks `addCommunityMember` first: 403 for a member without the right, for a person who is not a member. An import of another community: 404.
78. New abilities `invitations:read` ("Read the imports of invitations and their rows, with the email addresses") and `invitations:write` ("Invite many people from a file") [Q, question 1]: in `JetstreamServiceProvider`, `API_TOKEN_PERMISSIONS`, the labels and the API README. Not in the default set: the rows hold the addresses of people who are not members. The routes of part A keep their abilities.
79. The limiter `invitation-imports`: 5 a minute per user.
80. `InvitationImportResource`: `cuid`, `state`, `default_role`, `default_access_groups` and `default_member_tags` (as in rule 8), `ignored_columns`, `counts` (`rows`, `valid`, `refused`, `queued`, `sent`, `held`, `failed`, `skipped`, `still_invited`: rows whose invitation still exists), `started_by` (`AuthorResource` or null), `created_at`, `confirmed_at`, `finished_at`, `resent_at`, `can_resend_not_joined_from`, `kept_until` (the preview end, or the date of rule 86). The counts come from one grouped query.
81. `InvitationImportRowResource`: `cuid`, `row_number`, `email`, `role`, `access_groups` and `member_tags` (the names), `outcome`, `reason`, `reason_text`, `detail`, `still_invited`, `sent_at`.
82. Zod schemas in `libs/common` (`invitation-import.schema.ts`), and `knowiiApiClient.communities.invitationImports` (`upload`, `list`, `get`, `rows`, `confirm`, `resend`, `remove`). The upload sends `FormData` through `apiRequest()`.
83. Each method has its docblock and attributes for the OpenAPI document. The upload documents `file` as `type: 'file'`, as fact 27. Bruno: "Invitation imports - Upload", "- List", "- Show", "- Rows", "- Confirm", "- Send again", "- Delete".

### The data rules

84. `config/retention.php`: `invitation_import_previews_hours` (env `RETENTION_INVITATION_IMPORT_PREVIEWS_HOURS`, default 24), with a comment: the record of a sent import follows the periods of the invitations. `Retention::invitationImportPreviewHours()` and `invitationImportsDays()` (validity plus `expired_invitations_days`). `Retention::periods()` gets `invitation_import_previews` ("24 hours") and `invitation_imports` ("37 days"). `SELF_HOSTING.md` and `.env.example` list the new value.
85. `PruneExpiredData` gets the step `invitation_imports`: an import in `preview` with `created_at` older than the preview period, and an import with `confirmed_at` older than `invitationImportsDays()`. The rows go by the cascade. `--dry-run` counts them. `RetentionTest` covers both, and a row 1 second inside each limit stays.
86. A sent import is deleted `invitationImportsDays()` after its confirm, whatever its state, also `sending`.
87. `ExportCommunity`: `invitation_imports` in `community.json`, after `invitations`: each import with its state, the cuid of the uploader, the dates, and its rows (`row_number`, `email`, `role`, the names, `outcome`, `reason`). The readme line says so.
88. `ExportUser`: `invitation_import_rows` in `memberships.json`, after `invitations`: each row whose `lower(email)` is the lower-case address of the account, with the community as the other lists give it, the date, `outcome` and `reason`. No name of another person. The readme line says so.
89. `DeleteUser`: deletes the rows whose `lower(email)` is the lower-case address of the account. `user_id` of the imports goes null by the foreign key.
90. The policy, both files, words to approve before the merge [Q, question 10]. Change no other word:
    1. Section 2, item 7, at the end: "A manager can also invite many people at once with a file. We read the file one time and do not keep it. The record of that import keeps your address, whether the invitation went out, and why a row was refused."
    2. Section 5, item 4, at the end: "The record of an import from a file: deleted {{retention.invitation_imports}} after the invitations were sent, or {{retention.invitation_import_previews}} after the upload when nobody sent them."
    3. Section 3, item 4, only if the owner says so in question 10: the basis of the invitation mail.
91. The log lines of the actions and the jobs hold cuids and counts. A test spies on the log for a whole import (upload, confirm, send, a hold, a failure) and finds no address and no cell.
92. A test with fake disks for every configured disk finds no file after an upload, a confirm and a send.

### The screens

93. A new web route `/community/{communitySlug}/settings/invitation-imports`, named `invitation-imports.index`, and `/community/{communitySlug}/settings/invitation-imports/{importCuid}`, named `invitation-imports.show`, in the signed-in group next to the settings. A controller `Inertia\InvitationImportController` gives the community, the permissions, the groups and the tags for the picker, and for `show` the import. A person without `addCommunityMember` gets the page without access, as the settings do.
94. The card "Pending invitations" of the settings gets the link "Invite from a file" [W] (a `Link` with the look of a secondary button) to the index page, for a reader with `canAddCommunityMembers`.
95. The index page: `h1` "Invite from a file" [W]. `Stepper` with "Choose a file", "Check", "Send" [W], at step 1. The form: "CSV file" (a plain `input type="file"` with `accept=".csv,text/csv"`, as fact 30), "Role" (`Select`, the roles that the reader can give, default "Member"), `InvitationGrantsPicker` for the defaults, and "Check the file" [W]. Under the form, the help: "One row per person. The column email is required. The columns role, access_groups and tags are optional. Separate several names in a cell with |. A file holds at most 1000 rows." [W], and the link "Download a template" [W] to a static file `public/templates/invitations.csv` (header `email,name,role,access_groups,tags` and one row with `grace@example.com`). Then the list "Earlier imports" [W]: the date, who, the state, the counts in words, each a link to its page, with `LoadMoreButton`, and an `EmptyState` "No import yet" [W].
96. A file error shows under the file field with `role="alert"` and `aria-describedby`. A success goes to the page of the import.
97. The import page in `preview` (step 2): a summary in words ("950 rows can be invited. 50 rows are refused." [W]), the ignored columns ("Not used: name, phone" [W]), then the list "Refused rows" first (row number, address, reason text), then "Rows to invite" (row number, address, role, groups, tags), each a real list with its name, 50 at a time with `LoadMoreButton`. The actions: "Send {N} invitations" [W] (the primary button, `loading` while it waits) and "Discard" [W] (asks: "Discard this import? Nothing is sent." [W]). With 0 valid rows, "Send" is not shown.
98. After "Send" (step 3): the counts in a polite status region, updated by the event of rule 75 (a load of the import, never of every row). "You can leave this page. The invitations go out at the pace that the mail provider accepts." [W]. A refusal of rule 60 shows its text with `role="alert"` and the page stays at step 2.
99. In `done`: the counts in words, the lists "Not sent", "Refused", "Sent" (each with `LoadMoreButton`), and the actions "Send the mails that were not sent" [W] (with held or failed rows) and "Invite again the people who did not join" [W] (from `can_resend_not_joined_from`, one time; before that date the button is replaced by a sentence with the date). "Delete this record" [W] for the owner and the admins.
100. Every word in `invitation-words.ts`. Each outcome is text, never a color alone. Each page passes axe in its spec in each state. At 360 px nothing scrolls sideways: a row of a list wraps, it is not a table that scrolls.

### Documents

101. A new ADR, 0061 (check that it is free): "Invitations carry access groups and tags, and many invitations come from a file". It holds the design decisions 1 to 32, the shared points, the rules 30 to 32, 52, 60, 63 to 66 and 84 to 90, and these alternatives: a client preview (decision 8), a library for CSV (rule 11 of the architecture: PHP reads RFC 4180), the import on `default` with no limiter (decision 19), the creation of a tag from a file (contract point 5), the name stored to greet the person, a record kept longer than the invitation (the words of the mail). It amends ADR 0019 (point 5: an import renews its invitation and does not replace it; the import stores who uploaded) and ADR 0054 (point 1: the mail of an import goes on the mail queue with the limiter and a hold).
102. `backend.md`: a part "Invitations from a file" (the classes, the states, the jobs, the checks of the mail, the prune). `roles-and-permissions.md`: the rows "Invite from a file" and "Send the invitations of a file again". `frontend.md`: the two pages and the picker. The API README: the routes, the two abilities, the limits, the reasons.
103. `documentation/conventions/naming.md`: the row "invitation import" (do not use: bulk invite, mass invite, CSV import alone).

### Tests of part B

1. Unit, `InvitationCsvTest.php`: rules 37 to 45 one by one: a BOM, CRLF, LF, a lone CR, a semicolon file, a quoted cell with a comma, a quoted line break, `""`, a file in Windows-1252 (refused), a NUL byte, no `email` column, a column twice, 21 columns, 1000 and 1001 rows, empty records, extra cells, a cell of 1001 characters, a header in another case with spaces.
2. Feature, `InvitationImportTest.php` (the actions):
   1. Each reason of rule 52, and the order when a row has two problems.
   2. An address in another case than the account, than a pending invitation, than an earlier row. The owner as a row.
   3. A pending invitation that is valid (refused) and one that is expired (valid, replaced at the confirm).
   4. A removed person (refused) and a person who left by themselves (valid: no block).
   5. The role hierarchy: a moderator with `manage_members` uploads `moderator` and `admin` rows (refused), an admin uploads `admin` (refused), the owner uploads `admin` (valid).
   6. Groups: exact, another case, "Paid" and "paid" both (ambiguous), unknown. Tags: another case, a tag of the managers, unknown, 21 with the defaults.
   7. The defaults with and without the rights, with a token without `groups:write` or `member-tags:write`.
   8. The query count of rule 54 for 10 and 1000 rows.
   9. A second upload deletes the first preview.
   10. The confirm: another manager (403), twice (422), the re-check (a person who joined meanwhile, a tag deleted meanwhile), a single invitation of the same address created between the preview and the confirm (the row is refused, no error, the transaction holds), the three checks of rule 60, each with nothing changed after it.
   11. The jobs: sent, held by `isHeld()`, a quota refusal (the budget holds, the row is `held`), a failure after the retries (`failed`), a cancelled invitation (`skipped`), a person who joined meanwhile (`skipped`), the new `created_at`, the payload holds no address, the mail is the mail of a single invitation (its subject and its footer), the import becomes `done` one time with two workers.
   12. `HoldNotificationMail`: its tests pass unchanged after the move of the reading.
   13. Send again: `not_sent` at any time, `not_joined` before 3 days (422), after (202), a second time (422), the role rule, the checks of rule 60.
   14. The join of an invitation of an import gives its groups and tags (part A again, through an import).
3. Feature, `InvitationImportApiTest.php`: every route for the owner, an admin, a moderator with and without `manage_members`, a member, a person who is not a member, and an import of another community (404). A token without the abilities. The resources and the pagination. The limiter.
4. Data: `RetentionTest` (rule 85), `CommunityExportTest`, `UserExportTest`, `DeleteAccountTest` (a row with the address in another case goes, the import stays with no uploader), the log spy of rule 91, the disks of rule 92, `HostedLegalTextsTest` and `LegalTemplatesTest` with the new words, `BrunoCollectionTest`, `OpenApiDocumentTest` (the multipart body), `ApiTokenAbilitiesTest`, `ApiResourceArchitectureTest`, `InstanceNeutralNamesTest`. `down()` runs in a test database.
5. Vitest, with axe in each state: the index page (the form, a file error, the earlier imports, the empty state), the import page (preview with refused and valid rows, 0 valid rows, the sending state with a status update, done with each action, the dates of "invite again"), the schemas, the API client (the `FormData`).

## The browser check (definition of done 1)

Run `npm run build` on the host first. Then in both themes, at 360 px and 1920 px, with the keyboard, with no CSP error in the console. Use `example.com` addresses and the demo data only. `MAIL_MAILER` is `log` on the development machine: read the mails in the log.

1. Part A: invite one address with two groups and a tag of the managers. Accept it with a new account of that address: the member has the groups and the tag, the navigation shows the spaces of the groups.
2. Part B: download the template, fill 12 rows in a spreadsheet application of this machine, with one row of each reason that you can make, and save it as CSV UTF-8. Then save the same rows with semicolons. Upload both: the same preview.
3. Confirm. Watch the counts change. Read the log of the mails: 1 mail per valid row, the same text as a single invitation.
4. Do not change the mail settings or the mail budget of the development environment: the Pest tests of part B (2.10 and 2.11) prove the hold and the checks of rule 60.
5. As a moderator without `manage_members`: no link "Invite from a file", and the page without access.
6. Run `php artisan retention:prune --dry-run` (it changes nothing): the step `invitation_imports` is in its output.

Pictures for the owner, in both themes, at 360 px and 1920 px, on ONE HTML page `~/Downloads/knowii-2026-11-22-invitation-imports/index.html`, never in the repository: the invitation form with the picker, a pending invitation with groups and tags, the index page (empty and with imports), a preview with refused rows, the sending state, a done import with the two actions, the dialog of the picker.

## NOT in these commits

1. The update of existing members from a file (comment 1 of the issue), a mapping step of columns, an editor of the rows in the browser, an end date of the access.
2. A custom text of the invitation, a name in the mail, a pre-filled sign-up form.
3. Bounces and refused addresses (#1478). A check of the domain.
4. The import of the export of another tool (#1396): it calls `CreateInvitationImport` with its own rows.
5. Limits set by the operator per community (#1454): the constants are the values until then.
6. The audit log (#934). A record of who opened a link.
7. Invite links (#1391), beyond the shared points that part A builds.
8. A new toolkit block. A change of the abilities of the routes of the single invitation.
9. A CSV file that the platform writes (a download of the refused rows): no cell of a member file is written back to a spreadsheet.

## How to work

1. `git fetch origin`, then branch `feat/1390-bulk-invitations` from `origin/main`. Two commits, no push, no pull request. If the working tree holds changes of another person, or another helper works on another branch in it, stop and ask: no stash, no reset, no switch of their branch.
2. Docker on this Linux machine, from `~/wks/knowii`: `docker compose exec -T -u sail laravel.test <command> </dev/null`. Do not start, stop or restart containers. No `migrate:fresh` and no seeder on the development database. Run the new migrations with `php artisan migrate`, and prove `down()` in a test database only.
3. PHP commands in the container: `composer format`, `composer lint`, `composer security:php`, and the whole Pest suite ONE time per commit with `php artisan test --parallel`. Front-end commands on the host: `npx nx format --all`, `npm run lint:common`, `npm run tsc`, `npm run tsc:spec`, and the whole Vitest suite ONE time per commit with `npm run test:all`. Write the summary of each full run to a file and read the file. Never run the whole Pest suite and the whole Vitest suite at the same time.
4. A piped test command hides a failure: use `bash -c "set -o pipefail; ..."`. Without `--parallel`, `php artisan test` takes ONE path. While you build, run one test file at a time.
5. Write these tests before the code, and see them fail: part A tests 1.3, 1.5 and 1.6; part B the reasons of rule 52, the re-check of the confirm, the checks of rule 60, the log spy and the disks.
6. `composer security:php` reads the upload: a cell goes into a query only as a binding. If a path is safe by design, the annotation goes at the check, with the reason.
7. `InstanceNeutralNamesTest` reads `tests/` too. Never name a third-party community product, in the code, a test, the template file, a document or a commit. Use `example.com` addresses and `example` names.
8. The commits: a conventional type, one of the allowed scopes (all, a11y, code, deps, docs, i18n, qa, release, sec, ui, ux, auth, blog, api, websockets, build), a header of at most 100 characters that does not start with a capital letter. Suggested: `feat(api): invitations carry access groups and member tags that apply at the join (#1390 part A)` with the footer `Refs #1390`, and `feat(api): invitations from a CSV file, with a preview, a paced send and a log (#1390 part B)` with the footer `Closes #1390`. No trailer line that names a tool or a co-author.
9. If a rule cannot be built as written, skip that rule, build the rest, and say why in the report. Do not invent product rules.
10. If you find a weakness in the code of `main` while you work, do not write it in a commit, a test name, the ADR or an issue. Handle it as `SECURITY.md` says, and tell the reviewer in private.

## Questions for the owner

Until the owner answers, the builder builds what each recommendation says. The owner approves the [W] words, the policy words and the pictures before the merge.

1. The abilities. The import routes get a new pair, `invitations:read` and `invitations:write`, not in the default set of a token. Recommendation: yes. The rows hold the addresses of people who are not members, and a default token of a manager must not read them. The routes of the single invitation keep `communities:read` and `communities:write` in this slice; a later issue can move them.
2. The mail of an import goes on the mail queue, with the limiter and the hold, unlike a single invitation, which stays level 0. Recommendation: yes. A file of 1000 rows on `default` goes faster than the provider accepts, and can use the reserve of the sign-in links.
3. An address of a person whom a manager removed is refused in a file. Recommendation: yes. An accepted invitation lifts the block, and a file must not lift many removals by mistake. The manager invites such a person one at a time.
4. An address with a valid pending invitation is refused in a file, and an expired one is replaced. Recommendation: yes. A second mail within 7 days to a person who did not answer is what "Invite again" is for, with its wait of 3 days.
5. The column `name`. Recommendation: read it and ignore it. Store no name: the platform has no use for it before the person writes their own name at the sign-up. The preview says that the column is not used.
6. A `role` per row, with a role for the whole file as the default. Recommendation: yes, each row checked with the role hierarchy. The issue lists the role per row, and the move needs a few moderators.
7. An unknown name of a tag or of an access group in a file. Recommendation: a refused row, never a new tag or group (the recommendation of ADR 0060). A typo must not create a tag for hundreds of people.
8. The limits: 1000 rows and 1 MB per file, 1000 mails from files per community in 24 hours, a preview kept 24 hours. Recommendation: accept. All are constants. #1454 turns the daily number into a setting of the operator. For the move of several thousand people, the command of #1396 sends several files over several days.
9. How long the record of an import lives. Recommendation: as long as an invitation (37 days after the send today), and 24 hours for a preview that nobody sent. The invitation mail promises that the address is deleted that long after the invitation ends. The audit log (#934) is the place for a longer record without addresses.
10. The words of the privacy policy (part A rule 17, part B rule 90), and the legal basis. Section 3 item 4 gives "the contract" as the basis of the invitation mail, but a person who is not a member has no contract with the operator. Recommendation: approve the words of rules 17 and 90. For the basis, write "To send the invitations of a community: the legitimate interest of the community that invites you (art. 6(1)(f)); the owner of the community decides who is invited." The owner of the community is the controller of that data (the data processing agreement already names the people it invites). This change goes in part B only after your approval word for word.
11. "Invite again the people who did not join": one time per import, 3 days after the send at the earliest. Recommendation: yes. One reminder is normal; more becomes mail that people did not ask for.
12. Part B before the bounces of #1478. Recommendation: build it now with the limits of question 8. Until #1478, a hard bounce is not recorded, so a file with many dead addresses can harm the reputation of the sender. The preview refuses invalid addresses, and the daily cap limits the harm. Ask for a clean file for the move.
13. An access group that an entitlement rule feeds can be put on an invitation (the lesson of #1391 is about links). Recommendation: yes for an invitation by email: it names one person, as a hand add does, and the membership is by hand. #1391 decides for links.
14. The list of pending invitations gets pagination (`limit` 10 by default), a change of an existing route. Recommendation: yes. A file adds up to 1000 pending invitations, and the screen and an API client must not load them all at once. ADR 0011 asks for it when a route is touched.

## Your final report (max 400 words)

1. The two commit hashes and `git show --stat` of each.
2. The tests of "How to work" point 5: their failure before the code and their pass after it.
3. The query counts of rule 54 (10 and 1000 rows) and of part A rule 8 (3 and 30 invitations).
4. How the hold works for an import (rules 64 and 65), and how `HoldNotificationMail` shares the reading of the refusal.
5. Each choice where the brief left room, each [W] word and each [Q] rule that you built in another way, and each rule that you did not build as written.
6. The results of Pest, PHPStan, Pint, the security scan, the type checks, Vitest and the format check, for each commit, with the exact text of anything that failed.
7. The six steps of the browser check, each with what you saw, and the path of the HTML page with the number of pictures.
8. The text of the row for the register of processing. Proposal: "Imports of invitations. Data: the email address, the role, the names of the access groups and tags for the join, the result and the reason of each row, who uploaded the file and when. Purpose: a community invites many people at once. Controller: the owner of the community. Processor: the operator. Who reads it: the owner, the admins and the moderators who manage members. Kept: 24 hours for an import that nobody sent, else as long as an invitation. The file itself is not kept."
9. Anything of the scope not built, and why.
