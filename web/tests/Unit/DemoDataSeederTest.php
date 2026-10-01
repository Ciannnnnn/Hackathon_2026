<?php

namespace Tests\Unit;

use Database\Seeders\DemoDataSeeder;
use ReflectionMethod;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    public function test_demo_sql_is_split_without_breaking_semicolons_inside_text(): void
    {
        $sql = file_get_contents(dirname(base_path()).'/database/seed.sql');
        $this->assertIsString($sql);

        $method = new ReflectionMethod(DemoDataSeeder::class, 'statements');
        $statements = $method->invoke(new DemoDataSeeder, $sql);

        $this->assertNotEmpty($statements);
        $this->assertStringStartsWith('SET @demo_password_hash', $statements[0]);
        $this->assertStringStartsWith('INSERT INTO quiz_attempt_answers', $statements[array_key_last($statements)]);
        $this->assertTrue(collect($statements)->contains(
            fn (string $statement): bool => str_contains($statement, 'Hannah is progressing steadily; a brief normalization review'),
        ));
        $this->assertFalse(collect($statements)->contains(
            fn (string $statement): bool => in_array(strtoupper($statement), ['START TRANSACTION', 'COMMIT'], true),
        ));
    }
}
