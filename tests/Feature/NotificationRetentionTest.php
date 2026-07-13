<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_deletes_educator_and_student_notifications_at_least_three_days_old(): void
    {
        Carbon::setTestNow('2026-07-10 12:00:00');
        $educator = User::factory()->educator()->create();
        $student = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $oldEducatorRead = $this->makeNotification($educator, true, now()->subDays(4));
        $oldEducatorUnread = $this->makeNotification($educator, false, now()->subDays(4));
        $recentEducatorRead = $this->makeNotification($educator, true, now()->subDays(2));
        $boundaryEducatorRead = $this->makeNotification($educator, true, now()->subDays(3));
        $oldStudentRead = $this->makeNotification($student, true, now()->subDays(10));
        $oldStudentUnread = $this->makeNotification($student, false, now()->subDays(4));
        $oldAdmin = $this->makeNotification($admin, true, now()->subDays(10));

        $exitCode = Artisan::call('notifications:prune');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Deleted 5 notification(s).', Artisan::output());
        $this->assertDatabaseMissing('tbl_notifications', ['id' => $oldEducatorRead->id]);
        $this->assertDatabaseMissing('tbl_notifications', ['id' => $oldEducatorUnread->id]);
        $this->assertDatabaseHas('tbl_notifications', ['id' => $recentEducatorRead->id]);
        $this->assertDatabaseMissing('tbl_notifications', ['id' => $boundaryEducatorRead->id]);
        $this->assertDatabaseMissing('tbl_notifications', ['id' => $oldStudentRead->id]);
        $this->assertDatabaseMissing('tbl_notifications', ['id' => $oldStudentUnread->id]);
        $this->assertDatabaseHas('tbl_notifications', ['id' => $oldAdmin->id]);
    }

    public function test_command_reports_zero_when_nothing_is_eligible(): void
    {
        Carbon::setTestNow('2026-07-10 12:00:00');
        $admin = User::factory()->admin()->create();
        $this->makeNotification($admin, true, now()->subYears(2));

        Artisan::call('notifications:prune');

        $this->assertStringContainsString('Deleted 0 notification(s).', Artisan::output());
    }

    public function test_command_is_registered_to_run_daily(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'notifications:prune'));

        $this->assertNotNull($event);
        $this->assertSame('0 0 * * *', $event->expression);
    }

    private function makeNotification(User $user, bool $isRead, Carbon $createdAt): Notification
    {
        $notification = Notification::create([
            'recipient_user_id' => $user->id,
            'actor_user_id' => $user->id,
            'event_type' => 'assessment_created',
            'title' => 'Retention test',
            'message' => 'Retention test',
            'is_read' => $isRead,
            'read_at' => $isRead ? $createdAt : null,
            'updated_at' => $createdAt,
        ]);

        DB::table('tbl_notifications')->where('id', $notification->id)->update([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        return $notification->refresh();
    }
}
