<?php

namespace Tests\Feature;

use App\Models\Barista;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian autentikasi Barista.
 *
 * Memastikan:
 *  1. Akun Barista yang ada di database dapat login.
 *  2. Password diverifikasi persis seperti sistem sebelumnya (Flask):
 *     `no_telp[-6:]` -> 6 karakter TERAKHIR dari no_telp secara mentah
 *     (TANPA membuang karakter non-digit).
 *  3. Setelah login berhasil, diarahkan ke Dashboard Barista.
 */
class BaristaLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Bantu membuat barista dan mengembalikan password "sebelumnya" (Flask).
     */
    private function makeBarista(array $attrs): Barista
    {
        return Barista::create($attrs);
    }

    private function flaskPassword(string $noTelp): ?string
    {
        // Replika persis dari modules/auth.py: get_barista_password()
        return strlen($noTelp) >= 6 ? substr($noTelp, -6) : null;
    }

    public function test_barista_dengan_nomor_bersih_dapat_login(): void
    {
        $barista = $this->makeBarista([
            'username' => 'barista_satu',
            'nama_lengkap' => 'Barista Satu',
            'no_telp' => '081234567890',
            'role' => 'barista',
        ]);

        $expected = $this->flaskPassword($barista->no_telp); // '567890'

        $response = $this->post('/login', [
            'username' => 'barista:' . $barista->username,
            'password' => $expected,
        ]);

        $response->assertRedirect(route('barista.dashboard'));
        $this->assertEquals('barista', session('role'));
        $this->assertEquals($barista->username, session('username'));
    }

    public function test_password_mengikuti_sistem_sebelumnya_termasuk_nomor_berformat(): void
    {
        // no_telp mengandung spasi & tanda hubung -> sistem lama (Flask)
        // menggunakan 6 karakter terakhir SECARA MENTAH: "6-7890".
        $barista = $this->makeBarista([
            'username' => 'barista_format',
            'nama_lengkap' => 'Barista Format',
            'no_telp' => '+62 812-3456-7890',
            'role' => 'barista',
        ]);

        $expected = $this->flaskPassword($barista->no_telp); // '6-7890'

        $response = $this->post('/login', [
            'username' => 'barista:' . $barista->username,
            'password' => $expected,
        ]);

        $response->assertRedirect(route('barista.dashboard'));
        $this->assertEquals('barista', session('role'));
    }

    public function test_password_salah_ditolak(): void
    {
        $barista = $this->makeBarista([
            'username' => 'barista_salah',
            'nama_lengkap' => 'Barista Salah',
            'no_telp' => '081234567890',
            'role' => 'barista',
        ]);

        $response = $this->post('/login', [
            'username' => 'barista:' . $barista->username,
            'password' => '000000',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertNull(session('username'));
    }

    public function test_username_tidak_ditemukan_ditolak(): void
    {
        $response = $this->post('/login', [
            'username' => 'barista:tidak_ada',
            'password' => '123456',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertNull(session('username'));
    }
}