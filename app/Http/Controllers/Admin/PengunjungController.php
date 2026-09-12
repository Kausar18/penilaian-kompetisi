<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatistikPengunjung;
use Illuminate\View\View;

class PengunjungController extends Controller
{
    public function index(StatistikPengunjung $pengunjung): View
    {
        return view('admin.pengunjung', ['data' => $pengunjung->lengkap()]);
    }
}
