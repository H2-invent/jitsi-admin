#!/usr/bin/env bash
set -e

FORCE=false
PROCESSES=8
TESTSUITE="Development"
VALID_TESTSUITES=("onlyRepeater" "Complete" "Development")

usage() {
    echo "Usage: $0 [-f] [-p <2-16>] [-t <name>]"
    echo ""
    echo "  -f, --force           Rebuild the main test database, dump it, and recreate worker databases"
    echo "  -p, --processes <num> Number of parallel test databases/processes (2-16, default: 8)"
    echo "  -t, --testsuite <nm>  Test suite to run: ${VALID_TESTSUITES[*]} (default: Development)"
    exit 1
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --force|-f)
            FORCE=true
            shift
            ;;
        --processes|-p)
            if [[ -z "$2" || "$2" =~ ^- ]]; then
                echo "Error: --processes requires a value"
                usage
            fi
            case "$2" in
                ''|*[!0-9]*)
                    echo "Error: --processes must be a positive integer between 2 and 16"
                    usage
                    ;;
            esac
            if [[ "$2" -lt 2 || "$2" -gt 16 ]]; then
                echo "Error: --processes must be between 2 and 16, got $2"
                usage
            fi
            PROCESSES="$2"
            shift 2
            ;;
        --testsuite|-t)
            if [[ -z "$2" || "$2" =~ ^- ]]; then
                echo "Error: --testsuite requires a value"
                usage
            fi
            VALID=false
            for suite in "${VALID_TESTSUITES[@]}"; do
                if [[ "$2" == "$suite" ]]; then
                    VALID=true
                    break
                fi
            done
            if ! $VALID; then
                echo "Error: --testsuite must be one of: ${VALID_TESTSUITES[*]}"
                usage
            fi
            TESTSUITE="$2"
            shift 2
            ;;
        -h|--help)
            usage
            ;;
        *)
            echo "Error: Unknown option: $1"
            usage
            ;;
    esac
done

clear;
DUMP_FILE="/tmp/test_full.sql"

rebuild_master() {
    echo "Dropping test database..."
    ddev exec bash -c 'mysql -u db -pdb -e "DROP DATABASE IF EXISTS test"'

    echo "Creating test database..."
    ddev exec bash -c 'mysql -u db -pdb -e "CREATE DATABASE test"'

    echo "Running migrations..."
    ddev exec php bin/console doctrine:migrations:migrate -n --env=test

    echo "Loading fixtures..."
    ddev exec php bin/console doctrine:fixtures:load -n --env=test

    echo "Done! Test database is ready."

    echo "Dumping master test database"
    ddev exec mysqldump -h db -u db -pdb test > "$DUMP_FILE"
}

if $FORCE; then
    rebuild_master
fi

if $FORCE || [[ ! -f "$DUMP_FILE" ]]; then
    echo "Creating $PROCESSES worker databases from master dump..."
    for i in $(seq 1 "$PROCESSES"); do
        echo "  Creating and importing test_$i..."
        ddev exec mysql -h db -u db -pdb -e "DROP DATABASE IF EXISTS test_$i; CREATE DATABASE test_$i;"
        ddev exec mysql -h db -u db -pdb test_$i < "$DUMP_FILE" &
    done
    wait
    echo "Worker databases ready."
fi

echo "Running test suite '$TESTSUITE' with $PROCESSES processes"
ddev exec vendor/bin/paratest --processes="$PROCESSES" --configuration=phpunit.xml --testsuite="$TESTSUITE"

