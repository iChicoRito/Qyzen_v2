<?php

namespace Tests\Feature;

use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

// Task 05 (task 2): the scheduled/on-demand backup command streams DatabaseBackupService::lines()
// to storage/app/private/backups and prunes to the 7 most recent files. Asserts real files land
// on Storage::disk('local') (no mocks) and that retention actually deletes, not just skips writes.
class BackupDatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_command_writes_a_backup_file_with_real_content(): void
    {
        Artisan::call('backup:database');

        $files = Storage::disk('local')->files('backups');

        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.sql', $files[0]);

        $content = Storage::disk('local')->get($files[0]);

        $this->assertStringContainsString('CREATE TABLE', $content);
        $this->assertStringContainsString('tbl_users', $content);
    }

    public function test_command_returns_success(): void
    {
        $exitCode = Artisan::call('backup:database');

        $this->assertSame(0, $exitCode);
    }

    // Regression: pruneOldBackups() used to sort by filesystem mtime, which only has 1-second
    // resolution — nine sequential calls in a single test very likely land in the same second,
    // so a naive mtime sort degenerates to filesystem-enumeration order (effectively random) on
    // ties. Diffing the directory listing around each call captures true write order directly;
    // asserting the survivors equal the last-7-written proves retention is correct regardless of
    // whether this run's mtimes happened to tie.
    public function test_retention_keeps_the_seven_most_recently_written_backups(): void
    {
        $writtenInOrder = [];

        for ($i = 0; $i < 9; $i++) {
            $before = Storage::disk('local')->files('backups');
            Artisan::call('backup:database');
            $after = Storage::disk('local')->files('backups');

            $new = array_values(array_diff($after, $before));
            $this->assertCount(1, $new, "Expected exactly one new backup file on iteration {$i}.");
            $writtenInOrder[] = $new[0];
        }

        $remaining = Storage::disk('local')->files('backups');
        sort($remaining);

        $expectedSurvivors = array_slice($writtenInOrder, -7);
        sort($expectedSurvivors);

        $this->assertCount(7, $remaining);
        $this->assertSame($expectedSurvivors, $remaining);
    }

    // Regression: an exception mid-export used to leave a partial/corrupt .sql file on disk (the
    // try/finally only closed the handle) and skip pruning for that cycle, so the corrupt file
    // would survive and occupy a retention slot on the next successful run.
    public function test_write_failure_deletes_the_partial_file_before_rethrowing(): void
    {
        $this->app->bind(DatabaseBackupService::class, fn () => new class extends DatabaseBackupService
        {
            public function lines(): \Generator
            {
                yield "-- Table: fake\n";

                throw new RuntimeException('simulated mid-export failure');
            }
        });

        try {
            Artisan::call('backup:database');
            $this->fail('Expected the simulated export failure to propagate.');
        } catch (RuntimeException $e) {
            $this->assertSame('simulated mid-export failure', $e->getMessage());
        }

        $this->assertSame([], Storage::disk('local')->files('backups'));
    }

    // Regression: pruneOldBackups() used to sort survivors by the embedded hrtime(true) sequence
    // alone. hrtime(true) is CLOCK_MONOTONIC, which resets to ~0 on every reboot — each Hostinger
    // cron invocation is a separate process, so a pre-reboot backup's (large) sequence would
    // outrank a genuinely newer post-reboot backup's (small, post-reset) sequence, pruning the
    // newer file and keeping the stale one. Sorting on the full filename instead (wall-clock date
    // first, sequence only a same-second tiebreaker) must survive this: a later calendar date
    // always outranks an earlier one regardless of how the sequence compares.
    public function test_retention_survives_a_monotonic_sequence_reset_across_reboots(): void
    {
        // Seven "pre-reboot" backups: large hrtime sequences, but an earlier calendar date.
        for ($i = 0; $i < 7; $i++) {
            $sequence = sprintf('%020d', 900000000000000000 + $i);
            Storage::disk('local')->put("backups/qyzen-backup-2026-01-01_00000{$i}-{$sequence}.sql", 'pre-reboot');
        }

        // One "post-reboot" backup: later calendar date, but the clock reset gave it a tiny
        // sequence — smaller than every pre-reboot sequence above.
        $postRebootSequence = sprintf('%020d', 1000);
        $postRebootFile = "backups/qyzen-backup-2026-01-02_000000-{$postRebootSequence}.sql";
        Storage::disk('local')->put($postRebootFile, 'post-reboot');

        // Triggers pruning against the 8 seeded files plus the real backup this call writes (9
        // total candidates going into a KEEP-7 prune).
        Artisan::call('backup:database');

        $remaining = Storage::disk('local')->files('backups');

        $this->assertCount(7, $remaining);
        $this->assertContains($postRebootFile, $remaining, 'The genuinely newer post-reboot backup must survive.');

        $survivingPreRebootFiles = array_filter($remaining, fn (string $f) => str_contains($f, '2026-01-01'));
        $this->assertCount(5, $survivingPreRebootFiles, 'Exactly 2 of the 7 large-sequence-but-older-dated pre-reboot files must have been pruned to make room for the newer-dated file.');
    }

    // Regression: pruneOldBackups() used to glob backups/ unconditionally, so any unrelated file
    // that ever landed in that directory got swept into the mtime ranking and could be silently
    // deleted once it aged out of the top 7.
    public function test_retention_never_touches_files_outside_its_own_naming_pattern(): void
    {
        Storage::disk('local')->put('backups/README.txt', 'not a backup, leave me alone');

        for ($i = 0; $i < 9; $i++) {
            Artisan::call('backup:database');
        }

        $this->assertTrue(Storage::disk('local')->exists('backups/README.txt'));

        $backupFiles = array_values(array_filter(
            Storage::disk('local')->files('backups'),
            fn (string $file) => basename($file) !== 'README.txt'
        ));

        $this->assertCount(7, $backupFiles);
    }
}
