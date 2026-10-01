<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('The EduPulse demo dataset requires a MySQL connection.');
        }

        if (DB::table('users')->exists()) {
            throw new RuntimeException('Demo data was not loaded because the users table is not empty.');
        }

        $path = dirname(base_path()).DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'seed.sql';
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("The demo SQL file is unavailable at {$path}.");
        }

        $sql = file_get_contents($path);
        if (! is_string($sql) || trim($sql) === '') {
            throw new RuntimeException('The demo SQL file is empty or unreadable.');
        }

        $statements = $this->statements($sql);

        DB::transaction(function () use ($statements): void {
            foreach ($statements as $statement) {
                DB::unprepared($statement);
            }
        });

        $this->command?->info('EduPulse demo data loaded successfully.');
    }

    /** @return list<string> */
    private function statements(string $sql): array
    {
        $withoutComments = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        $statements = preg_split('/;\s*(?:\r\n|\n|\r|$)/', trim($withoutComments)) ?: [];

        return array_values(array_filter(array_map('trim', $statements), function (string $statement): bool {
            return $statement !== ''
                && strcasecmp($statement, 'START TRANSACTION') !== 0
                && strcasecmp($statement, 'COMMIT') !== 0;
        }));
    }
}
