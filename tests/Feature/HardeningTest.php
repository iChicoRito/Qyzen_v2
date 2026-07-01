<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Stage J1–J3: security response headers, CSP nonce, and rate-limiter registration.
class HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_present_on_web_responses(): void
    {
        $response = $this->get('/'); // welcome page, guest

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotNull($response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_csp_includes_a_script_nonce_and_layout_uses_it(): void
    {
        $user = $this->makeAdmin();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));
        $response->assertOk();

        $csp = $response->headers->get('Content-Security-Policy');
        // extract the nonce from the CSP and confirm the rendered HTML carries it on a script tag.
        $this->assertMatchesRegularExpression("/script-src[^;]*'nonce-([A-Za-z0-9]+)'/", $csp);
        preg_match("/'nonce-([A-Za-z0-9]+)'/", $csp, $m);
        $this->assertStringContainsString('nonce="'.$m[1].'"', $response->getContent());
    }

    public function test_quiz_write_routes_carry_a_throttle_limiter(): void
    {
        // The named limiter is registered and attached (route middleware includes throttle:quiz-writes).
        $route = collect(app('router')->getRoutes())->first(
            fn ($r) => $r->getName() === 'student.take-quiz.submit'
        );
        $this->assertNotNull($route);
        $this->assertContains('throttle:quiz-writes', $route->gatherMiddleware());
    }

    private function makeAdmin(): User
    {
        Role::create(['name' => 'admin', 'description' => 'admin', 'is_active' => true]);
        $user = User::factory()->create(['user_type' => 'admin', 'email_verified_at' => now()]);
        $user->roles()->attach(Role::where('name', 'admin')->value('id'));

        return $user;
    }
}
