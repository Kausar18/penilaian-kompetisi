<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password as tanyaSandi;
use function Laravel\Prompts\text;

/**
 * Membuat / memperbarui akun panel lewat CLI.
 *
 * Hanya bisa dijalankan dari terminal server — tidak ada rute HTTP yang
 * memicunya, jadi tidak menambah permukaan serangan aplikasi.
 *
 *   php artisan admin:buat
 *   php artisan admin:buat --name="Nama Reviewer" --username=juri1 --password=rahasia123
 *   php artisan admin:buat --username=deva --password=barulagi123                 # ganti sandi
 *   php artisan admin:buat --username=deva --name="Nama Baru"                     # ganti nama tampilan saja
 *   php artisan admin:buat --peran=reviewer --username=juri1 --name="..." --password=...
 *
 * `username` dipakai untuk login (singkat, tanpa spasi); `name` adalah
 * nama lengkap yang ditampilkan di aplikasi.
 */
class BuatAdmin extends Command
{
    protected $signature = 'admin:buat
        {--name= : Nama lengkap (tampil di aplikasi)}
        {--username= : Username untuk login}
        {--password= : Kata sandi (min 8 karakter). Opsional saat memperbarui akun yang sudah ada}
        {--peran=admin : Peran akun: admin atau reviewer}';

    protected $description = 'Membuat akun admin/reviewer baru, atau memperbarui nama / sandi / peran akun yang sudah ada';

    public function handle(): int
    {
        $peran = strtolower((string) $this->option('peran'));
        if (! in_array($peran, ['admin', 'reviewer'], true)) {
            $this->error('Peran hanya boleh "admin" atau "reviewer".');

            return self::FAILURE;
        }

        $username = $this->option('username') ?: text(
            label: 'Username',
            required: true,
        );

        $adaSebelumnya = User::where('username', $username)->first();

        $name = $this->option('name')
            ?: $adaSebelumnya?->name
            ?: text(label: 'Nama lengkap', required: true);

        // Sandi wajib untuk akun baru; opsional saat memperbarui akun yang ada
        // (dibiarkan kosong = sandi lama dipertahankan).
        $sandi = $this->option('password');
        if ($sandi === null && ! $adaSebelumnya) {
            $sandi = tanyaSandi(label: 'Kata sandi', required: true);
        }
        $gantiSandi = $sandi !== null && $sandi !== '';

        $aturan = [
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/'],
        ];
        $data = ['name' => $name, 'username' => $username];

        if ($gantiSandi) {
            $aturan['password'] = ['required', 'string', 'min:8'];
            $data['password'] = $sandi;
        }

        $validator = Validator::make($data, $aturan, [
            'username.regex' => 'Username hanya boleh huruf, angka, titik, garis bawah, atau strip.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $pesan) {
                $this->error($pesan);
            }

            return self::FAILURE;
        }

        if ($adaSebelumnya) {
            $ubah = ['name' => $name, 'peran' => $peran];
            if ($gantiSandi) {
                $ubah['password'] = Hash::make($sandi);
            }
            $adaSebelumnya->update($ubah);

            $this->info("Akun \"{$username}\" diperbarui (peran: {$peran}"
                .($gantiSandi ? ', sandi diganti' : '').').');

            return self::SUCCESS;
        }

        User::create([
            'name' => $name,
            'username' => $username,
            'password' => Hash::make($sandi),
            'peran' => $peran,
        ]);

        $this->info("Akun {$peran} dibuat: {$username} ({$name}).");

        return self::SUCCESS;
    }
}
