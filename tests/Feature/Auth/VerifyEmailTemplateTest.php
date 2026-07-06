<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyEmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_email_notification_uses_qyzen_email_view(): void
    {
        $user = User::factory()->create([
            'given_name' => 'New',
            'surname' => 'Student',
            'email' => 'new.student@example.com',
        ]);

        $mail = (new VerifyEmail)->toMail($user);

        $this->assertSame('Verify your email address', $mail->subject);
        $this->assertSame('emails.verify-email', $mail->view);
        $this->assertSame($user, $mail->viewData['user']);
        $this->assertStringContainsString('/email/verify/', $mail->viewData['verifyUrl']);
    }

    public function test_verify_email_template_matches_account_ready_style(): void
    {
        $user = User::factory()->create([
            'given_name' => 'New',
            'surname' => 'Student',
            'email' => 'new.student@example.com',
        ]);

        $html = view('emails.verify-email', [
            'user' => $user,
            'verifyUrl' => 'https://example.test/email/verify/1/hash',
        ])->render();

        $this->assertStringContainsString('Qyzen', $html);
        $this->assertStringContainsString('Verify your email address.', $html);
        $this->assertStringContainsString('Good day, New Student.', $html);
        $this->assertStringContainsString('Confirm email address', $html);
        $this->assertStringContainsString('https://example.test/email/verify/1/hash', $html);
    }
}
