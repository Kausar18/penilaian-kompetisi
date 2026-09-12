<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_root_terbuka_untuk_tamu(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_halaman_login_dapat_diakses(): void
    {
        $this->get('/login')->assertOk();
    }
}
