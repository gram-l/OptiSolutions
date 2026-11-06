<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminMiddleware;
use App\Models\admin_models\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminWebAccessTest extends TestCase
{
    public static function adminPages(): array
    {
        return [['/admin_acc/sidebar'], ['/admin_acc/appointments'], ['/admin_acc/doctors'], ['/admin_acc/patients']];
    }

    #[DataProvider('adminPages')]
    public function test_guests_cannot_open_admin_pages(string $path): void
    {
        $this->get($path)->assertRedirect('/auth/login');
    }

    #[DataProvider('adminPages')]
    public function test_signed_in_staff_cannot_open_admin_pages_by_url(string $path): void
    {
        $staff = new User(['name' => 'Staff', 'email' => 'staff@example.test', 'user_role' => 'Staff']);
        $staff->forceFill(['user_id' => 2, 'status' => 'active']);
        $this->actingAs($staff)->get($path)->assertRedirect('/auth/login')->assertSessionHas('unauthorized');
        $this->assertGuest();
    }

    public function test_every_registered_admin_web_route_requires_authentication_and_admin_role(): void
    {
        $checked = 0;
        foreach (app('router')->getRoutes() as $route) {
            if (!str_starts_with($route->uri(), 'admin_acc/')) continue;
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth', $middleware, $route->uri());
            $this->assertContains('admin', $middleware, $route->uri());
            $checked++;
        }
        $this->assertGreaterThan(4, $checked);
    }

    public function test_admin_role_passes_the_role_check(): void
    {
        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.test', 'user_role' => 'Admin']);
        $this->actingAs($admin);
        $response = (new AdminMiddleware)->handle(Request::create('/admin_acc/doctors'), fn () => response('Allowed'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Allowed', $response->getContent());
    }
}
