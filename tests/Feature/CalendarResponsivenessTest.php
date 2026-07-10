<?php

namespace Tests\Feature;

use Tests\TestCase;

class CalendarResponsivenessTest extends TestCase
{
    public function test_full_calendar_markup_prevents_page_level_horizontal_overflow(): void
    {
        $view = view('partials._full_calendar', ['events' => []])->render();

        $this->assertStringContainsString('data-calendar-responsive', $view);
        $this->assertStringContainsString('overflow-x-hidden', $view);
        $this->assertStringContainsString('overflow-x-auto', $view);
    }
}
