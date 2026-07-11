<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardResponsiveTest extends TestCase
{
    public function test_dashboard_kpis_stack_on_small_screens_without_label_truncation(): void
    {
        foreach (['student', 'educator'] as $role) {
            $html = file_get_contents(resource_path("views/{$role}/dashboard.blade.php"));
            $this->assertStringContainsString('grid-cols-1 sm:grid-cols-2 xl:grid-cols-4', $html);
        }

        $admin = file_get_contents(resource_path('views/admin/dashboard.blade.php'));
        $this->assertStringContainsString('repeat(1, minmax(0, 1fr))', $admin);
        $this->assertStringNotContainsString('text-secondary-foreground truncate', file_get_contents(resource_path('views/components/stat-card.blade.php')));
    }
}
