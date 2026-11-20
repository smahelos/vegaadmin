---
name: ship
description: >-
  Ship a Billex branch: documentation, review, checks, commits, a second agent's review, push, pull request and CI.
disable-model-invocation: true
argument-hint: "[step to start from]"
---

# Ship

Running `/ship` authorizes the commits, the push and the pull request of the current branch, without approving each one. Everything else that needs the user still does: decisions on findings, changes to `docs/prd/` and `docs/design/` (committed only with the user's explicit consent), unrelated changes in the working tree (they stay out of the branch).

Start from the step the user names; otherwise from step 1. Store logs and reports in `dev_docs/ai/tmp/ship-<branch>/`.

## Templates

- **Branch:** `ls-<topic>`, kebab-case English, created from `main`. On `main`, create it before the first commit.
- **Commit:** `<type>: <imperative sentence, lowercase>`, type one of `feat`, `fix`, `docs`, `test`, `refactor`, `chore`. The body says why and what changed, never the history of how the change came about. One purpose per commit, and every commit passes the tests. End with the agent's `Co-Authored-By` line. Stage files by name.
- **Pull request title:** `#<number> - <title of the main commit>`, where `<number>` is the real number of the pull request.
- **Pull request body:**

  ```markdown
  ## Summary
  <what the branch does and why, two or three sentences>

  ## Changes
  - `<commit subject>` — <why, one line> (one bullet per commit)

  ## Decisions
  - <decision the user made, with the reason> (omit the section when there are none)

  ## Test plan
  - [x] <check run locally, with counts>
  - [ ] CI: <what CI confirms>

  ## Documentation
  <documents changed, or why none needed a change>

  🤖 Generated with [Claude Code](https://claude.com/claude-code)
  ```

  The footer is the attribution line of the agent that opens the pull request.

## Steps

1. **Documentation.** Apply [the documentation policy](../../../docs/DOCUMENTATION.md) to the whole branch diff, `docs/TECH_DEBT.md` included: for changed existing code, find every document describing it and update, extend or delete what no longer holds; document new code where the policy asks for it; for a change with no documentation impact, say so in one line. Done when every document that mentions a changed class, route, rule or behaviour has been checked.
2. **Review.** Skip for a small change (a few-line fix, documentation only, a Dependabot bump) and say so; when unsure whether a change is small, ask. Otherwise review the branch against `main` together with uncommitted changes: Claude runs `/code-review high`, Codex reviews by the Code Review Rules in `AGENTS.md`. Verify each finding against the code and fix its cause the way the rules and the architecture require, not with a patch; when the correct fix needs a rule change, propose the rule change. Findings that change behaviour or scope, or that you would dismiss, go to the user with the cause, the correct fix and the alternatives.
3. **Checks.** Run [pre-pr-check](../pre-pr-check/SKILL.md). Fix every failure before committing.
4. **Commit** by the templates.
5. **Second agent.** The other agent reviews every commit of the branch:
   - Claude runs Codex: `node "$(ls -d ~/.claude/plugins/cache/openai-codex/codex/*/ | sort -V | tail -1)scripts/codex-companion.mjs" review --wait --base main --scope branch` (no Docker; it runs no tests).
   - Codex runs Claude: `claude -p --allowedTools "Read,Grep,Glob,Bash(git diff:*),Bash(git log:*),Bash(git show:*)" "Review every commit of the current branch against main (git diff main...HEAD) by the Code Review Rules in AGENTS.md. Change no files. List findings most severe first, each with file:line, the input or state that triggers it and the proposed fix. Answer NO FINDINGS when there are none."`

   When the review cannot run (plugin or CLI missing, an error), stop and tell the user; the branch does not go out unreviewed. Handle findings as in step 2, then run the checks for the fixes and commit them by the templates.
6. **Push and pull request.**
   - Push: `git -c credential.helper='!gh auth git-credential' push -u origin <branch>`.
   - Write the body by the template to `dev_docs/ai/tmp/ship-<branch>/pr.md`.
   - Open: `gh pr create --base main --title "<title of the main commit>" --body-file dev_docs/ai/tmp/ship-<branch>/pr.md`. When the branch already has an open pull request (`gh pr view`), the push updates it: add the new commits and decisions to its body instead.
   - Prefix the title with the number it received: `gh api -X PATCH repos/smahelos/vegaadmin/pulls/<number> -f title="#<number> - <title>"` (`gh pr edit` fails on Projects classic; a body change goes the same way with `-F body=@<file>`).
7. **CI and bot review.** Wait for CI with `gh pr checks <number> --watch`. Read the review bot's comments in `gh api repos/smahelos/vegaadmin/issues/<number>/comments` and inline in `gh api repos/smahelos/vegaadmin/pulls/<number>/comments`. Report the CI result and each finding with a proposed next step; the merge is the user's call. When local checks left a point for CI to confirm, tick it in the body once CI confirms it.
