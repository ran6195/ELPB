#!/bin/bash
# Crea (o ricrea) il database dei test copiando solo la struttura del database locale.
# Il nome è quello dell'.env con il suffisso _test: i test rifiutano di girare su altri database.
#
# Uso: ./tests/create-test-db.sh   (da backend/)

set -e
cd "$(dirname "$0")/.."

env_value() { grep -E "^$1=" .env | head -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'; }

DB_HOST=$(env_value DB_HOST)
DB_PORT=$(env_value DB_PORT)
DB_NAME=$(env_value DB_DATABASE)
DB_USER=$(env_value DB_USERNAME)
export MYSQL_PWD=$(env_value DB_PASSWORD)
TEST_DB="${DB_NAME}_test"

case "$DB_HOST" in
    localhost|127.0.0.1) ;;
    *) echo "✗ DB_HOST=$DB_HOST: il database dei test si crea solo in locale"; exit 1 ;;
esac

ARGS=(-h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USER")

mysql "${ARGS[@]}" -e "DROP DATABASE IF EXISTS \`$TEST_DB\`; CREATE DATABASE \`$TEST_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysqldump "${ARGS[@]}" --no-data --skip-add-drop-table --skip-comments --set-gtid-purged=OFF "$DB_NAME" | mysql "${ARGS[@]}" "$TEST_DB"

echo "✓ Database $TEST_DB creato con la struttura di $DB_NAME"
