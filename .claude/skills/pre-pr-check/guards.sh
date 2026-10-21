#!/usr/bin/env bash
# Runs the architecture guards CI runs (.github/workflows/architecture_guard.yml) against the
# working tree and reports each one; exit code 1 when any guard fails.
set -uo pipefail

cd "$(git rev-parse --show-toplevel)"
app=(docker exec BILLEX-app php artisan)
metrics=storage/app/metrics
failed=()

guard() {
    local name="$1"
    shift
    echo "== $name"
    if "$@"; then
        echo "OK"
    else
        echo "FAILED"
        failed+=("$name")
    fi
}

same_json() {
    local filter="$1" committed="$2" current="$3"
    diff <(jq -S "$filter" "$committed") <(jq -S "$filter" "$current")
}

guard "ddd:guard" "${app[@]}" ddd:guard
guard "provider snapshot" "${app[@]}" ddd:providers:snapshot --diff --format=human

guard "controller thinness baseline" bash -c "
    ${app[*]} ddd:controller-thinness:baseline --save >/dev/null &&
    $(declare -f same_json); same_json 'del(.summary.timestamp)' \
        architecture/baselines/controller_thinness_baseline.json $metrics/controller_thinness_baseline.json"

guard "frontend Eloquent baseline" bash -c "
    ${app[*]} ddd:frontend-eloquent:baseline --save --path=$metrics >/dev/null &&
    $(declare -f same_json); same_json '.controllers' \
        architecture/baselines/frontend_controller_eloquent_baseline.json $metrics/frontend_controller_eloquent_baseline.json"

guard "API Eloquent baseline" bash -c "
    ${app[*]} ddd:api-eloquent:baseline --save --path=$metrics >/dev/null &&
    $(declare -f same_json); same_json '.controllers' \
        architecture/baselines/api_controller_eloquent_baseline.json $metrics/api_controller_eloquent_baseline.json"

if ((${#failed[@]})); then
    printf '\nFailed guards: %s\n' "$(IFS=', '; echo "${failed[*]}")"
    exit 1
fi
printf '\nAll guards passed.\n'
