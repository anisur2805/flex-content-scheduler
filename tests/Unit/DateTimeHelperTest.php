<?php

use Anisur\ContentScheduler\Helpers\DateTimeHelper;
use PHPUnit\Framework\TestCase;

class DateTimeHelperTest extends TestCase {
    public function test_is_past_returns_true_for_past_date(): void {
        $this->assertTrue( DateTimeHelper::is_past( gmdate( 'Y-m-d H:i:s', time() - 3600 ) ) );
    }

    public function test_is_past_returns_false_for_future_date(): void {
        $this->assertFalse( DateTimeHelper::is_past( gmdate( 'Y-m-d H:i:s', time() + 3600 ) ) );
    }

    public function test_format_for_display_returns_correct_string(): void {
        $this->assertNotEmpty( DateTimeHelper::format_for_display( '2026-01-01 00:00:00', 'UTC' ) );
    }

    public function test_to_utc_converts_correctly(): void {
        $this->assertSame( '2026-01-01 00:00:00', DateTimeHelper::to_utc( '2026-01-01 00:00:00', 'UTC' ) );
    }
}
