# Brief: a headline on the member profile, with its visibility switch (knowii-oss/knowii#1688)

State: DRAFT. The owner answers the "Questions for the owner" before the build starts.

You build #1688. A headline is one short line that a member writes about themselves ("Product designer, writes about note-taking"). After this slice, a member writes it on the profile form, chooses who sees it with the switch of ADR 0020, and the member page shows it under the name. The member list of the API gives it under the same rule, so that the member directory (#148, the next slice) has its one line from the start. The headline is new personal data: the export, the deletion and the privacy policy follow. ONE commit on a local branch: a reviewer reads your diff afterwards. Paths are relative to `~/wks/knowii`.

## Decided (sources)

1. Profiles get a headline, and the member directory shows it (owner, 2026-11-20, #1688, and `brief-148-member-directory-slice-1.md`, "Decided after this draft").
2. Plain text, one line, optional. No Markdown, no links, no line breaks (#1688, scope 1).
3. A switch like the other profile fields, shown by default (#1688, scope 3, ADR 0020 points 1 and 2).
4. "Shown" means: to the members of the communities of the member. There is no choice per community (ADR 0020 point 3).
5. A hidden field is empty for every other person. Only the member reads all their choices (ADR 0020 point 4). The ADR gives no exception to a manager, so the owner, an admin and a moderator get what a member gets.
6. A visitor never gets a profile field (ADR 0044, ADR 0020 consequence 2).
7. The export holds the headline, the deletion of the account removes it (ADR 0024), and the privacy policy names it. The sentence of the policy is an approved text (#1688, scope 6, data rule 10).
8. The headline lands before the first slice of the directory (#1688, "Order").

## Read first

1. `AGENTS.md`, `CONTRIBUTING.md`, `SECURITY.md`, `documentation/conventions/rules.md` (architecture rules 2, 5 and 6, data rules 5, 6, 7 and 10, front-end rules 2, 3, 4 and 8, the definition of done), `documentation/conventions/naming.md`.
2. The issues: #1688, #1423 (its two comments), #148 with the brief of its first slice, #1076, #1467, #1466.
3. ADRs: 0020 (all of it), 0024 (points 2, 3 and 5), 0011, 0022 (points 1 and 4), 0044, 0049.
4. `documentation/architecture/backend.md` (the paragraph of the member data export, line 63), `documentation/architecture/frontend.md` ("UI components" and "Toolkit"), `documentation/runbooks/member-data-export.md`.
5. The code and the tests that the facts below name.

## What exists today (facts, with the evidence)

Line numbers are those of `main` on 2026-11-20 (commit `a2482fa3`). The model to copy is the location: one line of text, a limit, a switch. This list is your checklist.

### The table and the model

1. The columns. `user_profiles` holds `bio` (text), `location` (string) and `phone` (string), all nullable (`database/migrations/2024_09_28_132059_create_user_profiles_table.php:25-27`). `field_visibility` is a nullable `jsonb` column (`2026_11_09_235000_add_field_visibility_to_user_profiles.php:14-16`). The newest migration is `2026_11_18_120000_add_deletion_signed_in_at_to_users.php`.
2. The model. `app/Models/UserProfile.php:30-33` holds the `@property` lines, and lines 71-109 the `where...` lines. The fillable list holds no profile field (lines 143-150): the action writes with `forceFill`. The only cast is `field_visibility` (lines 175-180). `fieldVisibility()` gives every choice with its default (lines 187-193). `showsField()` is true only when the field is shown and has a value (lines 198-203).
3. The choices. `app/Support/ProfileFieldVisibility.php:16-19` lists the fields. Lines 26-32 give the defaults: shown, except the phone. Lines 40-50 put the stored choices on top of the defaults and drop unknown keys. So a profile that stored its choices before this slice gets the default for a new field.
4. The limits. `app/Constants.php:33,35,37` (`MAX_LENGTH_USER_BIO` 512, `MAX_LENGTH_USER_LOCATION` 128, `MAX_LENGTH_USER_PHONE` 48), mirrored in `libs/common/src/lib/constants.ts:132-134`.

### The update

5. One action updates the profile: `app/Actions/Fortify/UpdateUserProfileInformation.php`, behind the Fortify contract (`app/Providers/FortifyServiceProvider.php:52`). The rules of the fields are on lines 36-38, the rules of the choices on lines 43-44, the error bag on line 47, the shape of the input on line 49.
6. The save. Lines 85-89 write `bio`, `location` and `phone`. A request without one of these keys stores null for it. Lines 94-97 show the other model: the time zone changes only when the request holds its key. Lines 99-103 merge the choices, and a request without choices keeps them.
7. An account without a verified address changes nothing (lines 56-58).
8. The route. The profile form sends `PUT /user/profile-information`, the Fortify route `user-profile-information.update` (`libs/common/src/lib/constants.ts:53`). The application has no web controller for it. The API has no route that updates the profile: `routes/api.php:87-110` holds `GET /user`, the avatar, the online status, the time zone and the notification settings only.
9. The text of a request. `bootstrap/app.php` keeps the default middleware of the framework, which trims a text and turns an empty text into null. The action is also called without a request (`tools/screen-atlas/atlas-seed.php:109`), so a rule that holds everywhere lives in the action.
10. A model for the cleaning of a one-line text: `app/Actions/Auth/SignInWithOpenIdConnect.php:154` (the name that comes from the sign-in provider).

### The resources and the props

11. `UserProfileResource` is for the member only. It gives every field, never null, and every choice (`app/Http/Resources/UserProfileResource.php:22-26`). `UserResource` puts it in `profile`, only for the member themself (`UserResource.php:38`). `GET /api/v1/user` answers with it (`app/Http/Controllers/API/UserApiController.php:44`, `routes/api.php:89`, ability `user:read`).
12. `MemberProfileResource` is what the other members get. A hidden field is an empty text, a hidden link is left out (`MemberProfileResource.php:28-35,42-45`). Only the member page uses it (`app/Http/Controllers/Inertia/MemberController.php:27,41`). It has no branch for the member themself: on their own member page, a member sees what the others see.
13. `MemberWithRoleResource` holds six fields and no profile field (`MemberWithRoleResource.php:29-36`). Three places use it: the member list of the API and the answer of a role change (`app/Http/Controllers/API/MemberApiController.php:44-46,61-63`), and the team of the page "About" (`app/Http/Controllers/Inertia/CommunityController.php:119`). Its users come from `CommunityMemberQuery::for()`, which selects columns of `users` and loads no relation (`app/Support/CommunityMemberQuery.php:24-33`).
14. `AuthorResource` holds the cuid, the name, the username and the picture (`AuthorResource.php:23-29`). Posts, comments, messages and the public pages use it. It holds no profile field.
15. The shared prop `userProfile` is the profile of the signed-in member, read from the model (`app/Http/Middleware/HandleInertiaRequests.php:75-76`). The profile form reads it. A new column reaches the form through it.
16. No broadcast event, no notification and no mail holds a profile field.

### The export, the deletion, the search

17. The export. `app/Actions/Users/ExportUser.php:154-173` builds the profile of `account.json`: the fields on lines 157-165, the choices on line 170. The README of the export describes the file on line 905: "your account and your profile, with the choice of who sees each profile field". `JsonExportFile::FORMAT_VERSION` is 1 (`app/Support/JsonExportFile.php:19`). `tests/Feature/UserExportTest.php:45,136-139` prove the fields.
18. The deletion. `app/Actions/DeleteUser.php:119-120` deletes the profile row in the transaction of the deletion (ADR 0024 point 2). The foreign key also cascades (ADR 0024 point 3). `tests/Feature/DeleteAccountTest.php:53-71` prove it.
19. The search. No search reads a profile field. The member search reads `users.username` and `users.name` (`CommunityMemberQuery.php:35-43`). The code has no full-text column.

### The screens

20. The profile form: `apps/knowii/src/Pages/Profile/Partials/UpdateProfileInformationForm.tsx`. The type of the form data is on lines 36-40, the switch `VisibleToMembers` on lines 45-66, the form data on lines 79-90, the helper `visibilityToggle` on lines 92-100. The location field is on lines 324-339: a `TextInput` with `maxLength`, the switch, then `InputError`. The order of the fields is name, email, username, bio, location, phone, links.
21. The words of the form. The description says: "Bio, location and phone: you choose below." (lines 204-205). `UpdateProfileInformationForm.spec.tsx:50` asserts this text, and line 12 holds the fixture of the profile.
22. The save status. A toast says "Profile updated successfully." (line 188). No field of the form has a character counter. The browser stops the typing at `maxLength`.
23. The member page: `apps/knowii/src/Pages/Members/Show.tsx`. The header is on lines 72-80: the `Avatar` of size `lg`, the name as `h1`, then "@username" in the muted color. The bio is on line 121. The page has no spec file.
24. The widths at 360 px, from the classes: the page frame has 16 px of padding on each side (`apps/knowii/src/styles.css:201-203`), the card has 24 px (`Show.tsx:71`), the picture takes 48 px and the gap 16 px (`Components/ui/Avatar.tsx:9`, `Show.tsx:72`). So the text next to the picture has about 216 px: about 27 characters a line at the normal text size. An entry of the directory (#148: a 40 px picture, small text) has about 35 characters a line.
25. The places that show a member with a second line today. In each one the second line is "@username": the team of "About" (`Pages/Communities/About.tsx:162`), the online list (`Components/Presence/OnlineListPanel.tsx:63`), the member field of a conversation and of an event host (`Components/Messages/MemberPicker.tsx:149`), the member management (`Components/Communities/CommunityMembersManager.tsx:305`), the members of an access group (`Components/Communities/AccessGroupsManager.tsx:327`) and the mention list of the editor (`Components/ui/MarkdownEditor.tsx:785-799`). No member card exists (#1467 is open).

### The schemas, the API documents, the demo data

26. `libs/common/src/lib/types/user.schema.ts`: `userProfileSchema` on lines 26-36, `PROFILE_VISIBILITY_FIELDS` on line 44, `resolveProfileFieldVisibility` on lines 51-60 (every field is shown, except the phone). `user.schema.spec.ts:8-19` proves the defaults.
27. `libs/common/src/lib/types/member.schema.ts`: `memberProfileSchema` on lines 6-17 (the phone has a default, for a page that was built before the field), `communityMemberSchema` on lines 61-69.
28. Bruno: `documentation/api/Users - Me.bru:18` describes the answer of `GET /user`. `Members - List.bru`, `Members - Search.bru` and `Members - Change role.bru` answer with the entry of fact 13. `BrunoCollectionTest` checks that each route has a request. `OpenApiDocumentTest` checks the routes, the success answers and the request bodies.
29. The factory gives a profile no optional field (`database/factories/UserProfileFactory.php:28-36`). The seeder gives none (`database/seeders/DatabaseSeeder.php:20,35`). The seed of the screen atlas gives a bio and a location to three accounts, through the update action (`tools/screen-atlas/atlas-seed.php:324-327`).

### The legal texts and the moderation

30. The privacy policy, section 2, point 2: "Profile: what you choose to add (bio, location, phone number, links to your sites and social accounts), and which of these fields other members see. Your phone number is hidden by default." (`deploy/knowii.net/legal/policy.md:22`, `resources/legal-templates/policy.md:21`). Both are approved texts. The hosted text has `updated: 2026-11-18` in its head (line 3).
31. Two other sentences already cover the headline, with no change: "Members of a community see what you post in it, and the profile fields you choose to show." (hosted line 44, template line 43) and "Account and profile: while your account exists." (hosted line 60, template line 59). The DPA names "the profile fields that each member fills in" (`deploy/knowii.net/legal/dpa.md:24`), with no list of fields.
32. `tests/Feature/HostedLegalTextsTest.php` has one test for each approved sentence. No test reads the sentence of fact 30. `LegalTemplatesTest.php` checks the placeholders of the template.
33. The content policy lists what a member is not allowed to post (`deploy/knowii.net/legal/content-policy.md`, section 2). It names no field. No policy and no action lets a manager change or clear a profile field of another member: the code has none for the bio.

### The tests that guard the profile fields today

34. `tests/Feature/ProfileFieldVisibilityTest.php:23-83` (the defaults, the form, the booleans, a form without choices, the own profile in the API). `ProfileInformationTest.php:8-75`. `PublicCommunityPageTest.php:27-28` (the keys that a visitor never gets) and lines 41 and 147 (secret texts in the profile of an author). `PublicSpacesTest.php:39`. `CommunityResourceApiControllerTest.php:310` (the private fields of a curator). `EmailAddressPrivacyTest.php:58`. `ApiResourceArchitectureTest.php`.

## Rules

These rules are the recommended design. They become "decided" when the owner answers the questions at the end. The number 80 of rule 1 is question 1.

### The value

1. The limit: 80 characters. New constant `Constants::MAX_LENGTH_USER_HEADLINE = 80`, mirrored as `MAX_LENGTH_USER_HEADLINE` in `libs/common/src/lib/constants.ts` (data rule 6). The reason: at 360 px, 80 characters take three lines next to the picture of the member page and a little more than two lines in an entry of the directory (fact 24). 100 characters add a line in both places.
2. The server counts characters as `mb_strlen` does: one emoji of one code point counts for one. 80 pass, 81 fail with an error on `headline` in the bag `updateProfileInformation`, and nothing is saved.
3. The server cleans the text before it counts, in this order:
   1. It removes the characters that change the direction of the text (U+202A to U+202E and U+2066 to U+2069).
   2. Each run of white space becomes one space. White space is: a space, a tab, a line break (`\r`, `\n`), a no-break space, the line separator U+2028 and the paragraph separator U+2029. So "a\r\n\r\nb" becomes "a b".
   3. It removes every other control character (the category Cc: U+0000, U+0007, U+001B, U+007F and the like).
   4. It removes the spaces at the start and at the end.
4. The cleaning keeps the zero width joiner (U+200D) and the variation selectors. An emoji made of several code points stays whole, and each of its code points counts.
5. The cleaning does not touch HTML or Markdown. "<b>Hi</b> & **you**" is stored as typed. Do not use `strip_tags`: it removes honest text such as "I <3 notes".
6. An empty text, and a text that is empty after the cleaning, is stored as null. The column never holds an empty text.
7. A value that is not a text (an array, a number) fails with an error on `headline`.
8. The key is optional in the request, as the time zone (fact 6). A request without the key `headline` keeps the stored headline. A request with `headline` null, or empty, clears it.
9. An account without a verified address changes nothing (fact 7).
10. Put the cleaning in one small pure class in `app/Support`, with a name that follows `naming.md`, and call it in the action before the validation. The action never logs the headline (ADR 0024 point 5).
11. The sign-up form gets no headline. `CreateNewUser` does not change.

### The switch (ADR 0020)

12. `headline` joins `ProfileFieldVisibility::fields()` and `PROFILE_VISIBILITY_FIELDS`, with the default "shown". Keep the two WARNING comments.
13. A profile with stored choices from before this slice shows its headline (fact 3). No migration of the choices.
14. The form sends `field_visibility.headline` as a boolean. Another value fails with an error on `field_visibility.headline`. A request without choices keeps them.
15. Hidden and empty look the same from outside: the answer holds the key `headline` with an empty text in both cases.

### Who reads it, and where

16. The member themself: always. In the profile form, in `GET /api/v1/user` (`data.profile.headline`, an empty text when there is none, and `data.profile.field_visibility.headline`), and in the export. On their own member page the member sees what the others see (fact 12).
17. A member of a community of the member: on the member page (`member.headline`) and in the member list of the API, only when the member shows it.
18. The owner, an admin and a moderator: the same as a member. A hidden headline is in no answer for them.
19. A signed-in person who shares no community with the member: nothing. The member page gives 404 or the page without access, as today.
20. A visitor: nothing, on every public page and public API answer. `AuthorResource` does not change.
21. No broadcast event, no notification, no mail and no log line holds the headline.
22. No new token ability. `user:read` reads the own profile, `communities:read` reads the member list, as today.

### The API shapes

23. `UserProfileResource`: `'headline' => $this->headline ?? ''`, after `username`. The choices come from `fieldVisibility()` with no change.
24. `MemberProfileResource`: `headline`, with `showsField('headline')`, an empty text when hidden. Mirror it in `memberProfileSchema` as `z.string().default('')`.
25. `MemberWithRoleResource`: a seventh field `headline`, with the same rule. Mirror it in `communityMemberSchema` as `z.string().default('')`. This is what the slice leaves ready for #148.
26. `CommunityMemberQuery::for()` loads the profile for rule 25 with one query for the whole page (`with('profile:id,user_id,headline,field_visibility')`). The count of queries of the member list is the same for 3 members and for 30.
27. `userProfileSchema` gets `headline: z.string().nullable()`. The shared prop gives null when there is no headline.
28. The profile update has no API route today (fact 8). This slice adds none: see question 5.

### The profile form

29. A new field "Headline" between the username and the bio: a `TextInput` of type `text` with the id `headline`, `maxLength={MAX_LENGTH_USER_HEADLINE}`, an icon of `react-icons/fa` as the other fields, and the same `disabled` rule.
30. Under the field, in the order of the location: the hint "One line about you. It shows under your name.", tied to the field with `aria-describedby`, then the switch `visibilityToggle('headline', 'Headline')`, then `InputError` for `form.errors.headline`.
31. No character counter: no field has one (fact 22). The browser field counts UTF-16 units, so an emoji takes two or more of the 80 in the field. The field is never more generous than the server.
32. The description of the form becomes: "Headline, bio, location and phone: you choose below." The rest of the sentence stays.
33. "Reset" puts the stored headline back, as for the other fields. The toast of the save does not change.

### The member page

34. `Pages/Members/Show.tsx` shows the headline under the name: a `p` between the `h1` and "@username", in the normal text color and the normal text size. No icon, no label.
35. The page shows the text as text. "<b>Hi</b>" shows these seven characters, a web address is not a link, and Markdown is not rendered.
36. The line wraps, and a long word without a space breaks (`wrap-anywhere`). Nothing scrolls sideways at 360 px. The page never cuts the headline.
37. Without a headline, or with a hidden one, the page has no empty paragraph: the header is the header of today.
38. The browser title and the breadcrumb do not change.

### Where it does not show in this slice

39. The six lists of fact 25 keep "@username" as their second line. They do not show the headline in this slice: see question 3. The team of "About" and the member management receive the field in their data (rule 25) and ignore it.
40. The entry of the directory is the next slice (#148). No search reads the headline.

### The personal data duties

41. The export: `account.json` gets `profile.headline` (the text or null) after `username`, and `profile.field_visibility.headline` comes with rule 12. The README sentence of fact 17 already covers it. `format_version` stays 1: a new key is an addition.
42. The deletion: the profile row goes, so the headline goes (fact 18). No code change. A test proves that no row of `user_profiles` holds the text after the deletion.
43. The privacy policy: the sentence of question 2, in both files, only with the approval of the owner. Without the approval, leave the two files and the test alone and say it in the report.
44. The content policy: no change. Its rules cover every text that a member writes.
45. No retention period: the headline lives as long as the account (data rule 8 does not apply). No sub-processor receives it (data rule 9 does not apply).

## Scope (in the commit)

1. Rules 1 to 45.
2. Files:
   1. New: the migration `add_headline_to_user_profiles` with the date of the build, after fact 1 (`$table->string('headline')->nullable()`, a comment that names #1688 and ADR 0020, and a `down()` that drops the column). The class of rule 10 and its unit test. `tests/Feature/ProfileHeadlineTest.php`. `apps/knowii/src/Pages/Members/Show.spec.tsx`.
   2. Changed on the server: `app/Constants.php`, `app/Models/UserProfile.php` (the `@property` line and the `whereHeadline` line), `app/Support/ProfileFieldVisibility.php`, `app/Actions/Fortify/UpdateUserProfileInformation.php` (the rule, the shape of line 49, the save), `app/Http/Resources/UserProfileResource.php`, `MemberProfileResource.php`, `MemberWithRoleResource.php`, `app/Support/CommunityMemberQuery.php`, `app/Actions/Users/ExportUser.php`, `tools/screen-atlas/atlas-seed.php` (a headline for the three accounts of fact 29).
   3. Changed on the client: `libs/common/src/lib/constants.ts`, `libs/common/src/lib/types/user.schema.ts` and its spec, `libs/common/src/lib/types/member.schema.ts`, `apps/knowii/src/Pages/Profile/Partials/UpdateProfileInformationForm.tsx` and its spec, `apps/knowii/src/Pages/Members/Show.tsx`.
   4. No change: `UserProfileFactory` (a test sets the headline with a state), `DatabaseSeeder`, `DeleteUser`, `AuthorResource`, `UserResource`, the routes, the token abilities, `Components/ui`.
   5. No new configuration value. No new dependency.
3. Pest, in `tests/Feature/ProfileHeadlineTest.php` unless a line names another file. One test for each line:
   1. The cleaning, as a unit test with a data set: a line break, `\r\n\r\n`, a tab, a no-break space, U+2028, several spaces, spaces at both ends, U+0000 and U+001B, U+202E, an emoji with a zero width joiner (unchanged), "<b>Hi</b> & **you**" (unchanged), a text of spaces only (empty).
   2. The form saves a headline, and the stored text is the cleaned text.
   3. The limit: 80 characters pass. 81 fail with the error on `headline`, and the stored headline does not change. 80 emoji pass. 80 characters with spaces around them pass.
   4. An empty text, null, and a text of spaces only store null.
   5. An array fails with the error on `headline`.
   6. A request without the key keeps the headline. A request with the key and null clears it.
   7. An account without a verified address changes nothing.
   8. The default: a member with a headline and no stored choice shows it. A member with stored choices that hold no `headline` key shows it.
   9. The switch: `field_visibility.headline` false hides it, true shows it again, "maybe" fails with the error on `field_visibility.headline`, and a request without choices keeps the choice.
   10. `GET /api/v1/user`: `data.profile.headline` and `data.profile.field_visibility.headline`, for a shown and for a hidden headline. Without a headline the value is an empty text.
   11. The member page, shown: `member.headline` holds the text for a member, a moderator, an admin and the owner.
   12. The member page, hidden: `member.headline` is empty for the same four people, and the whole answer does not hold the text. Use a text that appears nowhere else.
   13. The own member page: the member gets the empty text for a hidden headline.
   14. The member list of the API, shown and hidden, for a member and for the owner: the key is in each item, the hidden text is not in the whole answer. The same for the answer of a role change and for the `team` prop of "About".
   15. The cost: the member list uses the same number of queries with 3 members and with 30.
   16. A person who is not a member of the community gets no headline from the member page and from the member list (403 or 404, and the answer does not hold the text).
   17. A visitor: add `headline` to the keys of `PublicCommunityPageTest.php:27-28`, and a secret headline to the author of `PublicCommunityPageTest.php:41,147` and of `PublicSpacesTest.php:39`. Add it to the curator of `CommunityResourceApiControllerTest.php:310`.
   18. The export, in `UserExportTest.php`: `profile.headline` holds the text, `profile.field_visibility` holds the key `headline`, and a member without a headline gets null.
   19. The deletion, in `DeleteAccountTest.php`: after the deletion, no row of `user_profiles` holds the text of the headline.
   20. `ProfileInformationTest`: the column exists, and the resource of the profile holds the key.
   21. The privacy policy, only with the approval: one test in `HostedLegalTextsTest.php` for the sentence of question 2, with the number of the issue and the date of the approval in its name, as the tests next to it. `LegalTemplatesTest` passes.
   22. `ProfileFieldVisibilityTest`, `EmailAddressPrivacyTest`, `ApiResourceArchitectureTest`, `ApiTokenAbilitiesTest`, `CommunityMemberManagementTest`, `MentionTest`, `OpenApiDocumentTest` and `BrunoCollectionTest` pass. Change an assertion in them only when it lists the exact keys of an entry, and say it in the report.
4. Vitest:
   1. `UpdateProfileInformationForm.spec.tsx`: the field with its label, its `maxLength` and its hint. The switch with its name for a screen reader, on by default, off when the stored choice is false. The sent data holds `headline` and `field_visibility.headline`. The error under the field. The field is disabled for an unverified address. The new description text. One axe check.
   2. `Pages/Members/Show.spec.tsx` (new): the headline between the name and the username. No paragraph without a headline. "<b>Hi</b>" is text, and the page holds no `b` element. A web address in the headline is not a link. One axe check.
   3. `user.schema.spec.ts`: `headline` is in the fields, shown by default, and a stored false stays false.
   4. Stories: none. No block of the toolkit is new or changed (ADR 0049). Say so in the report.
5. Bruno: the documentation text of `Users - Me.bru` names the headline in the profile. The documentation text of `Members - List.bru`, `Members - Search.bru` and `Members - Change role.bru` names `headline` and its rule ("empty unless the member shows it"). No new request: no route is new.
6. OpenAPI: `OpenApiDocumentTest` stays green, and `/docs/api` shows `headline` in the answer of `GET /v1/user` and in the entry of the member list.
7. Documents:
   1. No new ADR: the structural decision is ADR 0020, and its consequence 1 names this case. Add to ADR 0020 a short dated section at the end, "Update of <date> (#1688)": the headline joins the fields of point 1 with the default "shown", and `MemberWithRoleResource` applies the choices too, so an entry of a member list holds the headline only when the member shows it. Change no other line of the ADR.
   2. `documentation/api/README.md` and `documentation/architecture/backend.md`: search for `field_visibility`, "bio" and "profile field", and add the headline to each sentence that lists the profile fields or the fields of a member entry. Do not touch `documentation/reviews/`.
   3. The privacy policy: rule 43. With the approval, change the sentence in both files and set `updated` in the head of both files to the date of the change.
8. The browser check, in the Docker development environment (definition of done 1):
   1. As a member, open the profile settings. Write a headline, save, reload: the headline is there, and the toast showed.
   2. Paste a text of two lines with spaces at both ends, save, reload: one line, no space at the ends.
   3. Type until the field stops at 80 characters. Paste an emoji.
   4. Open your member page: the headline is under your name.
   5. As another member of the community, open that member page: the same headline.
   6. Turn the switch off, save. Your member page and the page for the other member show no headline. `GET /api/v1/user` still gives it to you.
   7. Save "<b>Hi</b> https://example.com": the member page shows these characters, and nothing is a link.
   8. Save 80 letters "W" without a space: the member page wraps the word, with no sideways scroll at 360 px.
   9. Do steps 1 to 6 with the keyboard only, in both themes, at 360 px and at 1920 px, with no CSP error in the console.
   10. Signed out, open a public page of the community where the member wrote a post: no headline in the page and in its data.
9. Pictures for the owner. The owner approves these looks before the merge:
   1. The profile form with the field "Headline", its hint and its switch: both themes at 360 px, one theme at 1920 px.
   2. The profile form with the error of a headline that is too long (send it with a changed request).
   3. The member page with a headline of 80 characters: both themes at 360 px, one theme at 1920 px.
   4. The member page with a short headline, and the member page without a headline, at 360 px.

If the reviewer wants a smaller diff, the one clean cut is rules 25 and 26 with tests 3.14 and 3.15 (the member list). They then move to the slice of #148.

## NOT in this commit

1. The headline in an entry of the directory, in the lists of fact 25, in the mention list, in a search, in a member card or in a profile dialog (#148, #1467).
2. An API route that updates the profile (question 5).
3. An action for a manager on the profile of another member (question 4).
4. A character counter, in this form or in the toolkit.
5. A headline on the sign-up form, in a notification, in a mail or on a public page.
6. A choice per community, and a public profile for visitors (ADR 0020, consequence 2 and alternative 2).
7. Any change of the rule of the bio, of the location or of the phone. A request without the key `bio` still clears the bio: leave it.
8. The import of headlines from another system (#1396).

## How to work

1. `git fetch origin`, then branch `feat/1688-profile-headline` from `origin/main`. ONE commit, no push, no pull request. Find the text when a line number moved.
2. Docker on this Linux machine, from `~/wks/knowii`: `docker compose exec -T -u sail laravel.test <command> </dev/null`. Do not start, stop or restart containers. No `migrate:fresh` and no seeder on the development database. Run the new migration with `php artisan migrate`.
3. PHP commands in the container: `composer format`, `composer lint`, `composer security:php`, and the whole Pest suite with `php artisan test --parallel`. Front-end commands on the host: `npx nx format --all`, `npm run tsc`, `npm run tsc:spec`, `npx nx run-many -t lint typecheck test`. Never run the whole Pest suite and the whole Vitest suite at the same time.
4. A piped test command hides a failure: use `bash -c "set -o pipefail; ..."`. Without `--parallel`, `php artisan test` takes ONE path.
5. Write the tests of scope 3.1, 3.12, 3.14 and 3.17 before the code.
6. Use `example.com` addresses. Never name a third-party community product, in the code, in a test, in the ADR or in the commit.
7. The commit: a conventional type (`feat`), one of the allowed scopes of `commitlint.config.js`, a header of at most 100 characters that does not start with a capital letter. Suggested: `feat(all): a headline on the member profile, with its visibility switch`. Footer, after a blank line: `Refs #1688`. Not `Closes`: the directory entry comes with #148. No trailer line that names a tool or a co-author.
8. If a rule cannot be built as written, skip that rule, build the rest, and say why in the report. Do not invent product rules.
9. If you find a weakness in the code of `main` while you work, do not write it in the commit, in a test name, in the ADR or in an issue. Handle it as `SECURITY.md` says, and tell the reviewer in private.

## Questions for the owner

1. The maximum length. The issue says "about 100". Recommendation: 80. At 360 px, 80 characters are three lines on the member page and about two lines in an entry of the directory, and the example of the issue has 42 characters. 100 adds one line in each entry of the directory on a phone. The column takes 255 characters, so a later change of the number needs no migration.
2. The privacy policy (an approved text). Proposed sentence for section 2, point 2, in `deploy/knowii.net/legal/policy.md:22` and in `resources/legal-templates/policy.md:21`, with the one new word "headline": "Profile: what you choose to add (headline, bio, location, phone number, links to your sites and social accounts), and which of these fields other members see. Your phone number is hidden by default." No other sentence changes (fact 31). Recommendation: approve it for this commit, with the new `updated` date.
3. The headline in the search, in the mention list and in the six lists that show "@username" as their second line (fact 25). The issue names "the other places that show a member with a second line". In each of these places the second line is the username, which tells two people with one name apart. Recommendation: none of them in this slice. The entry of the directory (#148) is the first list that shows the headline and sets its look. The team of "About" follows with that look. The mention list and the pickers keep the username. The search by headline is a later slice of #148.
4. A moderator who clears a headline. Today no manager can change a profile field of another member, the bio included (fact 33). Recommendation: no such action in this slice, and no change of the content policy. Open one issue for one action "Clear a profile text" for the owner and the admins of a community, for the headline, the bio, the location and the links together, with a notification to the member. Until then, a report goes to the operator, as for a bio.
5. The API of the profile update. The issue says that the profile update accepts the headline "with the Bruno requests". The profile update is the web route of the form: the API has no route for it (fact 8), and architecture rule 4 asks for one. Recommendation: a separate issue for `PUT /api/v1/user/profile` (ability `user:write`) that calls the same action, right after this slice. This slice puts the headline in the action, so the new route gets it at no cost.

## Your final report (max 400 words)

1. The commit hash and `git show --stat`.
2. The tests of scope 3.1, 3.12, 3.14 and 3.17: their failure before the code and their pass on the branch.
3. Each choice where the brief left room: the name and the place of the cleaning class, the icon of the field, the classes of the headline on the member page.
4. The number of queries of the member list before and after rule 26.
5. Results of Pest, PHPStan, Pint, the security scan, the type check, the lint, Vitest and the format check, with the exact failure text of anything that failed.
6. The ten steps of the browser check, each with what you saw, and the pictures of scope 9.
7. Each assertion of an existing test that you changed, and why (scope 3.22).
8. Anything of the scope not built, and why. The state of the privacy policy sentence (changed with the approval, or left alone).
