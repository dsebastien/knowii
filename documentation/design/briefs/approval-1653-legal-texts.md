# Approval: the move of the hosted legal texts (#1653, part B)

Yes or no for each point of section 6.

## 1. Files that move

`git mv`, same file names, from `resources/markdown/` to `deploy/knowii.net/legal/`:

1. `policy.md`
2. `terms.md`
3. `dpa.md`
4. `imprint.md`
5. `content-policy.md`
6. `security.md`

## 2. Word changes

None: no word, no date, no front matter line. Proof: `git diff -M100% --stat origin/main` shows six renames with zero changed lines.

## 3. Words that come from configuration today

1. The sub-processor tables (`{{subprocessors}}` in the policy, `{{subprocessors.community}}` in the DPA) come from `config/legal.php`. That list names the providers of one instance, so it leaves the code. A new file `deploy/knowii.net/legal/subprocessors.json` holds the same five entries, each text copied character for character. The code keeps the table header and the sentence for optional features. Same final words.
2. The retention periods (`{{retention.*}}`), the session cookie name and the label of the sign-in provider still come from the configuration of the instance.
3. Proof: the HTML of the six pages, rendered before and after with the same configuration, is identical (one hash per page in the report of the builder).

## 4. New neutral templates (never served)

In `resources/legal-templates/`, each with `draft: true`. `{{operator}}`, `{{contact}}` and `{{site}}` are filled from configuration. A `[TODO: ...]` mark is text that the operator writes.

1. `policy.md`: the identity block and the supervisory authority become placeholders and marks. The sentences about statistics and the bot check are marked "keep only if you use it".
2. `terms.md`: the operator, the contact and the site become placeholders. The price, the law and the courts (sections 6 and 11) become marks.
3. `dpa.md`: the identity of the processor becomes placeholders. The country in section 4.1 and all of section 10 become marks.
4. `imprint.md`: only the headings stay; each body is a mark. The "Source code" section stays.
5. `content-policy.md`: the operator and the contact become placeholders. The languages sentence of section 4 becomes a mark.
6. `security.md`: what the code does stays. What one server set-up does (country, host, network, backups) becomes marks.
7. `subprocessors.example.json`: two example entries with invented names.

## 5. What reads differently on the live site

1. In the six texts: nothing, when the deploy copies the folder to `storage/app/legal/` before the new release starts. Without the copy, the pages answer 404 and their links are hidden.
2. Not changed, for a later decision: the terms (introduction and 7.3) and the security page (introduction and "Encryption" 1) name `knowii.net`. The decided address is `https://app.knowii.net`. That is a word change.
3. `SECURITY.md`, line 3, names the old path of the security page. Proposed: only the path becomes `deploy/knowii.net/legal/security.md`.

## 6. Your answer

1. The move of section 1, with no word change: yes / no
2. The sub-processors in `subprocessors.json`, same words: yes / no
3. The seven templates of section 4 (you read their text before the merge): yes / no
4. The path in `SECURITY.md`: yes / no
