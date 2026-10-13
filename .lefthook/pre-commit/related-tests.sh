#!/usr/bin/env bash
# Run only the tests related to staged files.
# Falls back to the full suite when a file with global impact is staged.
set -euo pipefail

mapfile -t staged < <(git diff --cached --name-only)

if [ ${#staged[@]} -eq 0 ]; then
    exit 0
fi

full_suite_pattern='^(config/|database/|routes/|bootstrap/|lang/|composer\.lock$|phpunit\.xml$|tests/Pest\.php$|tests/TestCase\.php$)'

for file in "${staged[@]}"; do
    if [[ $file =~ $full_suite_pattern ]] || { [[ $file == tests/* ]] && [[ $file != *Test.php ]]; }; then
        echo "Global file staged ($file): running full suite."
        exec php artisan test --parallel
    fi
done

declare -A tests=()
app_changed=false

for file in "${staged[@]}"; do
    if [[ $file == tests/*Test.php ]]; then
        tests["$file"]=1
    elif [[ $file == app/*.php ]]; then
        app_changed=true
        class_path="${file#app/}"
        class_path="${class_path%.php}"
        fqcn="App\\${class_path//\//\\}"
        basename="${class_path##*/}"

        while IFS= read -r test; do
            tests["$test"]=1
        done < <(grep -rlF --include='*Test.php' "$fqcn" tests || true)

        while IFS= read -r test; do
            tests["$test"]=1
        done < <(find tests -name "*${basename}*Test.php")
    fi
done

if [ "$app_changed" = true ]; then
    tests["tests/Architecture"]=1
fi

existing=()
for test in "${!tests[@]}"; do
    if [ -e "$test" ]; then
        existing+=("$test")
    fi
done

if [ ${#existing[@]} -eq 0 ]; then
    echo "No related tests."
    exit 0
fi

# Paratest only accepts a single path, so the subset runs sequentially.
exec php artisan test --compact "${existing[@]}"
