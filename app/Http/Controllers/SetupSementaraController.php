<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Jalan pintas sekali-pakai untuk membuat akun admin pertama di server
 * yang tidak punya akses SSH / artisan (mis. shared hosting cPanel dasar).
 *
 * Aktif hanya kalau env SETUP_TOKEN diisi dan cocok dengan {token} di URL.
 * Kosongkan lagi SETUP_TOKEN setelah dipakai.
 */
class SetupSementaraController extends Controller
{
    public function create(Request $request, string $token): View
    {
        $this->pastikanTokenValid($token);

        return view('setup-sementara.buat-admin', ['token' => $token]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $this->pastikanTokenValid($token);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'peran' => 'admin',
        ]);

        return redirect()->route('login')->with('sukses', 'Akun admin berhasil dibuat. Silakan masuk.');
    }

    private function pastikanTokenValid(string $token): void
    {
        $rahasia = config('app.setup_token');

        abort_if(blank($rahasia) || ! hash_equals($rahasia, $token), 404);
    }
}
