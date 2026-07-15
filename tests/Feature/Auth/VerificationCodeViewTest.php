<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class VerificationCodeViewTest extends TestCase
{
    public function test_verification_code_view_has_six_accessible_digit_inputs_and_keyboard_helpers(): void
    {
        $response = $this->view('auth.verification-code', [
            'email' => 'john.doe@example.com',
            'errors' => new ViewErrorBag,
        ]);
        $content = (string) $response;

        $response
            ->assertSee('Verify My Account')
            ->assertSee('Enter the six-digit verification code sent to your email address:', false)
            ->assertSee('john.doe@example.com')
            ->assertSee('text-primary', false)
            ->assertSee('name="code"', false)
            ->assertSee('data-verification-digit', false)
            ->assertSee('addEventListener(\'paste\'', false)
            ->assertSee('addEventListener(\'keydown\'', false)
            ->assertSee('focus()', false);

        $this->assertSame(6, preg_match_all('/<input\\b[^>]*\\bdata-verification-digit\\b[^>]*>/s', $content));
        $this->assertSame(6, substr_count($content, 'inputmode="numeric"'));
        $this->assertSame(6, substr_count($content, 'aria-label="Verification digit'));
    }
}
