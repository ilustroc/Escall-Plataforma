<?php

namespace Tests\Unit;

use App\Services\ImportService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ImportRulesTest extends TestCase
{
    #[DataProvider('numbers')]
    public function test_legacy_number_normalization_is_deterministic(string $source, mixed $expected): void
    {
        $this->assertSame($expected, ImportService::number($source));
    }

    public static function numbers(): array
    {
        return [
            'Peruvian decimal' => ['S/ 1.250,50', '1250.50'],
            'English decimal' => ['1,250.50', '1250.50'],
            'simple decimal' => ['250,75', '250.75'],
            'blank' => ['', null],
            'invalid source' => ['not-a-number', 'INVALID_NUMBER'],
        ];
    }

    #[DataProvider('dates')]
    public function test_legacy_date_normalization_accepts_only_known_formats(mixed $source, ?string $expected): void
    {
        $this->assertSame($expected, ImportService::date($source));
    }

    public static function dates(): array
    {
        return [
            'ISO' => ['2026-09-13', '2026-09-13'],
            'Peruvian slash' => ['13/09/2026', '2026-09-13'],
            'Excel serial' => [46300, '2026-10-05'],
            'invalid calendar date' => ['99/99/2026', null],
        ];
    }
}
