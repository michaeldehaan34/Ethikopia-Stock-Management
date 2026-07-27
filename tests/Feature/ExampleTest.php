<?php

namespace Tests\Feature;

use App\Models\Barista;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian Role & Permission.
 *
 * Memastikan:
 *  1. Manager hanya dapat mengakses route bertanda 'role:manager'.
 *  2. Barista hanya dapat mengakses route bertanda 'role:barista,manager'
 *     dan DITOLAK (403) saat mencoba route 'role:manager'.
 *  3. User yang belum login tidak dapat membuka dashboard
 *     (di-redirect ke /login).
 *  4. Semua middleware (session.auth + role) berjalan.
 */
class ExampleTest extends TestCase
{
    use RefreshDatabase;

    private function loginAs(string $role): void
    {
        if ($role === 'manager') {
            $account = Manager::create([
                'username' => 'mgr_permission',
                'nama_lengkap' => 'Manager Permission',
                'no_telp' => '081234567890',
                'role' => 'manager',
            ]);
            $name = $account->username;
        } else {
            $account = Barista::create([
                'username' => 'bar_permission',
                'nama_lengkap' => 'Barista Permission',
                'no_telp' => '081234567890',
                'role' => 'barista',
            ]);
            $name = $account->nama_lengkap;
        }

        $this->withSession([
            'user_id' => $account->id,
            'username' => $account->username,
            'role' => $role,
            'name' => $name,
        ]);
    }

    public function test_root_mengarahkan_user_belum_login_ke_login(): void
    {
        // Requirement #3: user belum login tidak bisa ke dashboard.
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_dashboard_manager_menolak_user_belum_login(): void
    {
        // Requirement #3: tanpa session -> redirect ke login.
        $response = $this->get(route('manager.dashboard'));

        $response->assertRedirect('/login');
    }

    public function test_dashboard_barista_menolak_user_belum_login(): void
    {
        // Requirement #3: tanpa session -> redirect ke login.
        $response = $this->get(route('barista.dashboard'));

        $response->assertRedirect('/login');
    }

    public function test_manager_dapat_mengakses_menu_manager(): void
    {
        // Requirement #1: Manager boleh akses route 'role:manager'.
        $this->loginAs('manager');

        $response = $this->get(route('manager.dashboard'));
        $response->assertStatus(200);

        $response = $this->get(route('manager.master-bahan'));
        $response->assertStatus(200);
    }

    public function test_barista_ditolak_akses_menu_manager(): void
    {
        // Requirement #2: Barista DITOLAK (403) saat akses route manager-only.
        $this->loginAs('barista');

        $response = $this->get(route('manager.dashboard'));
        $response->assertForbidden(); // 403

        $response = $this->get(route('manager.master-bahan'));
        $response->assertForbidden(); // 403
    }

    public function test_barista_dapat_mengakses_menu_barista(): void
    {
        // Requirement #2: Barista boleh akses route 'role:barista,manager'.
        $this->loginAs('barista');

        $response = $this->get(route('barista.dashboard'));
        $response->assertStatus(200);

        $response = $this->get(route('barista.stok-masuk'));
        $response->assertStatus(200);
    }

    public function test_manager_dapat_mengakses_menu_barista(): void
    {
        // Manager memiliki akses penuh (route 'role:barista,manager').
        $this->loginAs('manager');

        $response = $this->get(route('barista.dashboard'));
        $response->assertStatus(200);
    }
}