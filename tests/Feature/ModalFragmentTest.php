<?php
namespace Tests\Feature;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Task 08: create/edit views render as a bare form fragment under ?modal=1 (for AJAX modal
// injection) and as a full chrome page otherwise. No controller/route changes — the view decides.
class ModalFragmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        foreach (['admin','educator','student'] as $n) {
            Role::firstOrCreate(['name' => $n], ['description' => $n, 'is_active' => true]);
        }
        $u = User::factory()->create(['user_type' => 'admin', 'email_verified_at' => now()]);
        $u->roles()->attach(Role::where('name', 'admin')->value('id'));
        return $u;
    }

    public function test_fragment_is_bare_and_full_page_has_chrome(): void
    {
        $admin = $this->admin();

        $frag = $this->actingAs($admin)->get(route('admin.roles.create', ['modal' => 1]));
        $frag->assertOk();
        $f = $frag->getContent();
        $this->assertStringNotContainsString('id="sidebar"', $f);
        $this->assertStringNotContainsString('id="header"', $f);
        $this->assertStringContainsString('<form method="POST"', $f);
        $this->assertStringContainsString('data-modal-cancel', $f);

        $full = $this->actingAs($admin)->get(route('admin.roles.create'));
        $full->assertOk();
        $this->assertStringContainsString('id="sidebar"', $full->getContent());
    }
}
