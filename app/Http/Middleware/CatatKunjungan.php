<?php

namespace App\Http\Middleware;

use App\Models\Kunjungan;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Mencatat kunjungan halaman publik.
 *
 * Sengaja hanya dipasang di grup rute publik, jadi panel admin tidak ikut
 * tercatat. Panitia yang sedang login juga dilewati supaya angkanya tidak
 * digelembungkan oleh mereka sendiri.
 */
class CatatKunjungan
{
    /** User agent yang jelas-jelas robot — tidak dihitung sebagai pengunjung. */
    private const ROBOT = ['bot', 'crawl', 'spider', 'slurp', 'curl', 'wget', 'headless', 'monitor', 'preview', 'python-requests'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if ($this->layakDicatat($request, $response)) {
                $this->catat($request);
            }
        } catch (Throwable) {
            // statistik tidak boleh sampai menjatuhkan halaman publik
        }

        return $response;
    }

    private function layakDicatat(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $request->ajax() || Auth::check()) {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $ua = strtolower((string) $request->userAgent());

        if ($ua === '') {
            return false;
        }

        foreach (self::ROBOT as $tanda) {
            if (str_contains($ua, $tanda)) {
                return false;
            }
        }

        return true;
    }

    private function catat(Request $request): void
    {
        $sekarang = now();
        $tanggal = $sekarang->toDateString();

        Kunjungan::create([
            'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 190),
            'pengunjung' => Kunjungan::sidikJari($request->ip(), $request->userAgent(), $tanggal),
            'perangkat' => Kunjungan::tebakPerangkat($request->userAgent()),
            'referrer' => $this->hostPerujuk($request->headers->get('referer'), $request->getHost()),
            'tanggal' => $tanggal,
            'jam' => (int) $sekarang->format('G'),
        ]);
    }

    /** Simpan host-nya saja, bukan URL penuh (URL bisa memuat query pribadi). */
    private function hostPerujuk(?string $referer, string $hostSendiri): ?string
    {
        if (blank($referer)) {
            return null;
        }

        $host = parse_url($referer, PHP_URL_HOST);

        if (! $host || $host === $hostSendiri) {
            return null;
        }

        return mb_substr($host, 0, 190);
    }
}
