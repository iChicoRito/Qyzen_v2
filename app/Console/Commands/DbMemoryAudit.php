<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

/**
 * Read-only InnoDB memory / buffer-pool audit.
 *
 * Reports the memory-vs-disk hit rate, whether the buffer pool is big enough
 * for the working set, and the disk-spill signals that config tuning targets.
 * Re-run after changing my.ini to confirm an improvement.
 *
 * ponytail: plain SHOW STATUS/VARIABLES math, no perfschema views or new deps —
 * upgrade to sys.* views only if this stops answering the question.
 */
class DbMemoryAudit extends Command
{
    protected $signature = 'db:memory-audit';

    protected $description = 'Audit InnoDB buffer-pool (memory) efficiency: hit rate, sizing, disk-spill signals';

    public function handle(): int
    {
        $pdo = DB::connection()->getPdo();
        $db = DB::connection()->getDatabaseName();

        $kv = fn (string $sql) => $pdo->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
        $var = $kv('SHOW GLOBAL VARIABLES');
        $status = $kv('SHOW GLOBAL STATUS');

        $s = fn (string $k) => (float) ($status[$k] ?? 0);
        $v = fn (string $k) => $var[$k] ?? null;

        $this->line('');
        $this->info("InnoDB memory audit — `{$db}` @ ".date('Y-m-d H:i:s'));
        $this->line('MySQL '.$v('version').'  |  uptime '.$this->dur($s('Uptime')));
        $this->line(str_repeat('=', 64));

        $poolBytes = (float) $v('innodb_buffer_pool_size');
        $dataBytes = (float) $pdo->query(
            'SELECT COALESCE(SUM(data_length+index_length),0)
               FROM information_schema.tables
              WHERE table_schema = '.$pdo->quote($db)
        )->fetchColumn();
        $poolFitsData = $dataBytes > 0 && $poolBytes >= $dataBytes;

        // --- 1. Headline: memory-vs-disk hit rate ------------------------------
        $reqs = $s('Innodb_buffer_pool_read_requests');
        $disk = $s('Innodb_buffer_pool_reads');
        $this->section('1. Buffer-pool hit rate (memory vs disk)');
        if ($reqs <= 0) {
            $this->warn('  No read activity recorded yet — exercise the app '
                .'(browse pages or run `php artisan test`) then re-run.');
        } else {
            $hit = (1 - $disk / $reqs) * 100;
            $this->row('logical reads (served from memory+disk)', number_format($reqs));
            $this->row('physical reads (had to hit disk)', number_format($disk));
            $this->row('HIT RATE', sprintf('%.4f%%  (%s)', $hit, $this->verdictHit($hit, $poolFitsData)));
        }

        // --- 2. Is the pool big enough? ---------------------------------------
        $pagesTotal = $s('Innodb_buffer_pool_pages_total');
        $pagesFree = $s('Innodb_buffer_pool_pages_free');
        $pagesDirty = $s('Innodb_buffer_pool_pages_dirty');
        $waitFree = $s('Innodb_buffer_pool_wait_free');

        $this->section('2. Buffer-pool sizing');
        $this->row('innodb_buffer_pool_size', $this->mb($poolBytes));
        $this->row('total data + index size', $this->mb($dataBytes));
        $this->row('pool covers dataset', $dataBytes > 0
            ? sprintf('%.1fx  (%s)', $poolBytes / $dataBytes,
                $poolBytes >= $dataBytes ? 'fits entirely — good' : 'TOO SMALL — data spills to disk')
            : 'n/a (empty schema)');
        $this->row('pages free / total', number_format($pagesFree).' / '.number_format($pagesTotal));
        $this->row('pages dirty', number_format($pagesDirty));
        $this->row('waits for free page', number_format($waitFree)
            .($waitFree > 0 ? '  ← pressure: pool undersized' : '  (none — no pressure)'));
        $this->row('instances', (string) $v('innodb_buffer_pool_instances'));

        // --- 3. Disk temp tables ----------------------------------------------
        $tmp = $s('Created_tmp_tables');
        $tmpDisk = $s('Created_tmp_disk_tables');
        $this->section('3. Temp tables spilling to disk');
        $this->row('tmp tables (total / on-disk)', number_format($tmp).' / '.number_format($tmpDisk));
        $this->row('on-disk ratio', $tmp > 0
            ? sprintf('%.1f%%  (%s)', $tmpDisk / $tmp * 100,
                $tmpDisk / max($tmp, 1) > 0.25 ? 'HIGH — raise tmp_table_size' : 'ok')
            : 'n/a');
        $this->row('tmp_table_size / max_heap_table_size',
            $this->mb((float) $v('tmp_table_size')).' / '.$this->mb((float) $v('max_heap_table_size')));

        // --- 4. Table-open cache ----------------------------------------------
        $tcHit = $s('Table_open_cache_hits');
        $tcMiss = $s('Table_open_cache_misses');
        $this->section('4. Table-open cache');
        $this->row('hits / misses', number_format($tcHit).' / '.number_format($tcMiss));
        $this->row('hit rate', ($tcHit + $tcMiss) > 0
            ? sprintf('%.2f%%', $tcHit / ($tcHit + $tcMiss) * 100) : 'n/a');
        $this->row('opened_tables vs table_open_cache',
            number_format($s('Opened_tables')).' vs '.number_format((float) $v('table_open_cache'))
            .($s('Opened_tables') > (float) $v('table_open_cache')
                ? '  ← churn: raise table_open_cache' : ''));

        // --- 5. Full-scan signals ---------------------------------------------
        $this->section('5. Full-scan / disk-read signals');
        $this->row('Select_full_join (joins w/o index)', number_format($s('Select_full_join')));
        $this->row('Select_scan (full table scans)', number_format($s('Select_scan')));
        $this->row('Handler_read_rnd_next (rows read sequentially)', number_format($s('Handler_read_rnd_next')));

        // --- 6. Largest tables (what fills the pool) --------------------------
        $this->section('6. Largest tables (data+index)');
        $rows = $pdo->query(
            'SELECT table_name AS name,
                    ROUND((data_length+index_length)/1048576,2) AS mb,
                    table_rows AS approx_rows
               FROM information_schema.tables
              WHERE table_schema = '.$pdo->quote($db).'
              ORDER BY (data_length+index_length) DESC
              LIMIT 12'
        )->fetchAll(PDO::FETCH_ASSOC);
        $this->table(['table', 'MB', '~rows'], array_map(
            fn ($r) => [$r['name'], $r['mb'], number_format((float) $r['approx_rows'])],
            $rows
        ));

        $this->line('');
        $this->info('Done. See docs/audits/DB_MEMORY_AUDIT.md for the plain-English report.');

        return self::SUCCESS;
    }

    private function section(string $t): void
    {
        $this->line('');
        $this->line("<comment>{$t}</comment>");
    }

    private function row(string $label, string $value): void
    {
        $this->line('  '.str_pad($label, 42).$value);
    }

    private function mb(float $bytes): string
    {
        return number_format($bytes / 1048576, 2).' MB';
    }

    private function dur(float $secs): string
    {
        $h = intdiv((int) $secs, 3600);
        $m = intdiv((int) $secs % 3600, 60);

        return "{$h}h {$m}m";
    }

    private function verdictHit(float $hit, bool $poolFitsData): string
    {
        // A low cumulative rate while the pool already holds the whole dataset
        // is one-time warmup, not undersizing — don't advise enlarging the pool.
        if ($hit < 99.0 && $poolFitsData) {
            return 'warming up — pool already fits dataset, converges to ~100% with use';
        }

        return match (true) {
            $hit >= 99.9 => 'excellent — almost all from memory',
            $hit >= 99.0 => 'good',
            $hit >= 95.0 => 'marginal — enlarge buffer pool',
            default => 'POOR — disk-bound, enlarge buffer pool',
        };
    }
}
