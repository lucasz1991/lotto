<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_can_be_rendered(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($user);

        $this->get(route('admin.index'))->assertOk();
        $this->get(route('admin.settings'))->assertOk();
        $this->get(route('admin.history'))->assertOk();
        $this->get(route('admin.recommendations'))->assertOk();
        $this->get(route('admin.number-check'))->assertOk();
    }
}
