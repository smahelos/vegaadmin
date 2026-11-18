# Architecture Baselines

This directory stores committed architecture baseline JSON snapshots used by guard tests and CI pipelines.

Files:
- provider_snapshot.json – Provider registration snapshot baseline (diff guard via artisan ddd:providers:snapshot --diff).
- legacy_report_latest.json – Snapshot of residual legacy artifacts & neutralized tests (regenerated via ddd:legacy-report).

The architecture tests in `tests/Feature/Architecture` read these committed files directly (not `storage/app/metrics`, which is git-ignored).

Commands below run artisan in the `BILLEX-app` container, which bind-mounts the project, so `--write` output lands in the working tree:

    ARTISAN='docker exec BILLEX-app php artisan'

Maintenance Rules:
1. Regenerate only when an intentional architectural change is approved.
2. Include a short commit message referencing the reason for baseline update (WHY + ticket/ref), e.g. "baseline: provider bindings updated for X".
3. Never edit these JSON files manually; always use the appropriate artisan command.
4. Keep changes minimal; investigate large diffs before committing.
5. Provider snapshot baseline refresh (writes directly to architecture/baselines/provider_snapshot.json):
	eval $ARTISAN ddd:providers:snapshot --write
6. Legacy report baseline refresh:
	eval $ARTISAN ddd:legacy-report --json --write-baseline

Controllers have no baseline: the Larastan rule `EloquentOutsideControllersRule` lists the controllers that still call Eloquent in `phpstan.neon` (`.claude/rules/architecture.md`, Architecture guards).
