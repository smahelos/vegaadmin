---
name: ship
description: >-
  Ship a Billex branch: documentation, review, checks, commits, a second agent's review, push, pull request and CI.
disable-model-invocation: true
argument-hint: "[step to start from]"
---

# Ship

Running `/ship` authorizes the commits, the push and the pull request of the current branch, without approving each one. Everything else that needs the user still does: decisions (below), changes to `docs/prd/` and `docs/design/` (committed only with the user's explicit consent), unrelated changes in the working tree (they stay out of the branch).

Start from the step the user names; otherwise from step 1. Store logs and reports in `dev_docs/ai/tmp/ship-<branch>/`.

## Decisions

Stop and ask the user when a finding or a failure needs a decision: the fix changes behaviour, scope, a public contract or the architecture, the correct fix needs a rule change, two fixes are both correct, you would leave a finding that is more than a nit, or a finding you fixed comes back. Present the cause, the correct fix by the rules and the architecture, and every option with its pros and cons (through the AskUserQuestion tool when the agent has it), then continue from the same step with the answer. Fix everything else without asking.

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
2. **Review.** Skip for a small change (a few-line fix, documentation only, a Dependabot bump) and say so; when unsure whether a change is small, ask. Otherwise review the branch against `main` together with uncommitted changes: Claude runs `/code-review high`, Codex reviews by the Code Review Rules in `AGENTS.md`. Verify each finding against the code and fix its cause the way the rules and the architecture require, not with a patch; a finding that needs a decision stops the workflow (Decisions).
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
7. **CI and review loop.** Repeat until CI is green and the review bot's latest review holds only nits you would leave as they are:
   - Wait for CI with `gh pr checks <number> --watch`; the bot's review is its `review` check. Read the bot's comments posted after the last push in `gh api repos/smahelos/vegaadmin/issues/<number>/comments` and inline in `gh api repos/smahelos/vegaadmin/pulls/<number>/comments`.
   - Fix each CI failure and finding as in step 2, run the checks for the fixes, commit by the templates, push, and add the commits to the body (step 6).

   Then tick the points of the test plan that CI confirmed and report the rounds: what each fixed, the nits left with the reason for each, and the next step. The merge is the user's call.
