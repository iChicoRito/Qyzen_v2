<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use App\Services\UserOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountOnboardingCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_onboarding_sends_a_six_digit_code_and_stores_only_its_hash(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        app(UserOnboardingService::class)->send($user);

        $user->refresh();
        $this->assertTrue($user->must_change_password);
        $this->assertNotNull($user->email_verification_code);
        $this->assertNotNull($user->email_verification_code_expires_at);
        $this->assertTrue($user->email_verification_code_expires_at->isBetween(now()->addDays(7)->subMinute(), now()->addDays(7)->addMinute()));

        Notification::assertSentTo($user, AccountCreatedNotification::class, function (AccountCreatedNotification $notification) use ($user) {
            $this->assertMatchesRegularExpression('/^\d{6}$/', $notification->verificationCode);
            $this->assertTrue(Hash::check($notification->verificationCode, $user->fresh()->email_verification_code));

            $mail = $notification->toMail($user);
            $this->assertSame('emails.account-created', $mail->view);
            $this->assertSame($notification->verificationCode, $mail->viewData['verificationCode']);
            $this->assertArrayNotHasKey('temporaryPassword', $mail->viewData);

            return true;
        });
    }

    public function test_offline_onboarding_keeps_a_usable_temporary_password_without_a_verification_code(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $temporaryPassword = app(UserOnboardingService::class)->send($user, false);

        $user->refresh();
        $this->assertTrue(Hash::check($temporaryPassword, $user->password));
        $this->assertNull($user->email_verification_code);
        $this->assertNull($user->email_verification_code_expires_at);
        Notification::assertNothingSent();
    }

    public function test_email_onboarding_does_not_create_or_replace_a_password(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => 'ExistingPass1!']);

        app(UserOnboardingService::class)->send($user);

        $this->assertTrue(Hash::check('ExistingPass1!', $user->fresh()->password));
    }

    public function test_resending_email_onboarding_rotates_the_verification_code(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $onboarding = app(UserOnboardingService::class);

        $onboarding->send($user);
        $firstCode = Notification::sent($user, AccountCreatedNotification::class)->first()->verificationCode;
        $onboarding->send($user);
        $secondCode = Notification::sent($user, AccountCreatedNotification::class)->last()->verificationCode;

        $this->assertNotSame($firstCode, $secondCode);
        $this->assertFalse(Hash::check($firstCode, $user->fresh()->email_verification_code));
        $this->assertTrue(Hash::check($secondCode, $user->fresh()->email_verification_code));
        Notification::assertSentTo($user, AccountCreatedNotification::class, 2);
    }
}
