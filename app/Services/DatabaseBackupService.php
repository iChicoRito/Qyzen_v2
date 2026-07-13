<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

// Task 05: full schema+data SQL export, streamed table-by-table/row-by-row so the whole dump is
// never built in memory. Driver-branches the CREATE TABLE source because Hostinger shared hosting
// has no shell_exec/mysqldump binary (mysql in production) while the test suite runs against the
// SQLite in-memory DB (phpunit.xml sets DB_CONNECTION=sqlite). Task 2 reuses lines() to write
// scheduled backups to disk, so this stays a plain generator with no HTTP/response concerns.
class DatabaseBackupService
{
    /**
     * @return \Generator<int, string>
     */
    public function lines(): \Generator
    {
        $driver = DB::connection()->getDriverName();

        yield match ($driver) {
            'mysql' => "SET FOREIGN_KEY_CHECKS=0;\n\n",
            'sqlite' => "PRAGMA foreign_keys=OFF;\n\n",
            default => throw new RuntimeException("DatabaseBackupService does not support the [{$driver}] driver."),
        };

        // schemaQualified: false — both MySQL and SQLite otherwise prefix names with the
        // database/schema (e.g. "qyzen_v2.tbl_users" / "main.tbl_users"), which breaks a plain
        // backtick-wrap in SHOW CREATE TABLE / INSERT INTO below and the sqlite_master lookup.
        foreach (Schema::getTableListing(schemaQualified: false) as $table) {
            yield "-- Table: {$table}\n";

            // Any unreadable table aborts the dump. The command removes the partial file so an
            // incomplete export can never consume a valid retention slot.
            yield "DROP TABLE IF EXISTS `{$table}`;\n";
            yield $this->createTableStatement($driver, $table).";\n\n";
            yield from $this->insertStatements($table);

            yield "\n";
        }

        yield $driver === 'mysql'
            ? "SET FOREIGN_KEY_CHECKS=1;\n"
            : "PRAGMA foreign_keys=ON;\n";
    }

    private function createTableStatement(string $driver, string $table): string
    {
        return match ($driver) {
            'mysql' => DB::select("SHOW CREATE TABLE `{$table}`")[0]->{'Create Table'},
            'sqlite' => DB::selectOne('SELECT sql FROM sqlite_master WHERE type = ? AND name = ?', ['table', $table])->sql,
            default => throw new RuntimeException("DatabaseBackupService does not support the [{$driver}] driver."),
        };
    }

    /**
     * @return \Generator<int, string>
     */
    private function insertStatements(string $table): \Generator
    {
        $pdo = DB::connection()->getPdo();
        $query = DB::table($table);

        // Not every table has a surrogate `id` (e.g. cache/cache_locks/password_reset_tokens key
        // on a non-`id` column) — every tbl_* table does, but order deterministically only when
        // the column exists so the export doesn't blow up on framework-owned tables.
        if (Schema::hasColumn($table, 'id')) {
            $query->orderBy('id');
        }

        foreach ($query->cursor() as $row) {
            $row = (array) $row;

            if ($row === []) {
                continue;
            }

            $columns = implode(', ', array_map(fn ($column) => "`{$column}`", array_keys($row)));
            $values = implode(', ', array_map(
                fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value),
                array_values($row)
            ));

            yield "INSERT INTO `{$table}` ({$columns}) VALUES ({$values});\n";
        }
    }
}
