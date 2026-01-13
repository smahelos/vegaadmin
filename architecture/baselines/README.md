# Architecture Baselines

This directory stores committed architecture baseline JSON snapshots used by guard tests and CI pipelines.

Files:
- controller_thinness_baseline.json – Baseline metrics for controller thinness enforcement (drift check, timestamp ignored).
- frontend_controller_eloquent_baseline.json – Frontend controllers' allowed Eloquent usages (hash/drift enforcement job).
- api_controller_eloquent_baseline.json – API controllers' allowed Eloquent usages (hash/drift enforcement job).
- provider_snapshot.json – Provider registration snapshot baseline (diff guard via artisan ddd:providers:snapshot --diff).
- legacy_report_latest.json – Snapshot of residual legacy artifacts & neutralized tests (regenerated via legacy report or unified command).

Maintenance Rules:
1. Regenerate only when an intentional architectural change is approved.
2. Include a short commit message referencing the reason for baseline update (WHY + ticket/ref).
3. Never edit these JSON files manually; always use the appropriate artisan command.
4. Keep changes minimal; investigate large diffs before committing.
5. For controller thinness: regenerate with
	docker exec INVOICE-php-fpm php artisan ddd:controller-thinness:baseline --save && \
	cp storage/app/metrics/controller_thinness_baseline.json architecture/baselines/controller_thinness_baseline.json
	then commit.
6. Controller thinness CI drift detection ignores only summary.timestamp; any structural/metric difference fails the build.
7. Frontend/API Eloquent baselines are regenerated in CI and hash compared; update by re-running respective artisan baseline command then copying file here.
8. Provider snapshot baseline refresh:
	docker exec INVOICE-php-fpm php artisan ddd:providers:snapshot --write
	# output artifact path may differ (check command output), then copy into architecture/baselines/provider_snapshot.json
9. Never manually edit JSON; always regenerate.
10. Unified regeneration command examples:
	# Dry-run (inspect deltas only, nothing written)
	docker exec INVOICE-php-fpm php artisan ddd:baselines:refresh --include-legacy --include-provider --dry-run --json-summary

	# Regenerate all (thinness + frontend/api + provider + legacy) and update versioned files
	docker exec INVOICE-php-fpm php artisan ddd:baselines:refresh --include-legacy --include-provider --write --update-versioned --json-summary > baselines_summary.json

	# Skip provider & legacy
	docker exec INVOICE-php-fpm php artisan ddd:baselines:refresh --write --update-versioned

	# Only frontend + api (skip thinness & legacy)
	docker exec INVOICE-php-fpm php artisan ddd:baselines:refresh --write --no-controller-thinness --include-provider=false --update-versioned

11. Legacy report baseline refresh manually:
	docker exec INVOICE-php-fpm php artisan ddd:legacy-report --json --write-baseline

12. JSON summary output (when --json-summary used) includes diff entries:
```json
{
	"status": "ok",
	"dry_run": false,
	"diff": {
		"controller_thinness": {"previous": 58, "current": 59, "delta": 1},
		"frontend": {"previous": 12, "current": 12, "delta": 0},
		"api": {"previous": 11, "current": 11, "delta": 0},
		"legacy_residuals": {"previous": 0, "current": 0, "delta": 0},
		"legacy_neutralized_tests": {"previous": 0, "current": 0, "delta": 0}
	},
	"timestamp": "2025-09-01T09:30:00+00:00"
}
```

13. Add a clear commit message when baselines shift (e.g. "baseline: +NewController thinness metrics" or "baseline: provider bindings updated for X").
