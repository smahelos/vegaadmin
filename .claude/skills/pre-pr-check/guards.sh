#!/usr/bin/env bash
# Runs the architecture guards CI runs (.github/workflows/static-analysis.yml) against the
# working tree and reports each one; exit code 1 when any guard fails.
set -uo pipefail

cd "$(git rev-parse --show-toplevel)"
app=(docker exec BILLEX-app php artisan)
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

guard "ddd:guard" "${app[@]}" ddd:guard
guard "provider snapshot" "${app[@]}" ddd:providers:snapshot --diff --format=human

if ((${#failed[@]})); then
    printf '\nFailed guards: %s\n' "$(IFS=', '; echo "${failed[*]}")"
    exit 1
fi
printf '\nAll guards passed.\n'
