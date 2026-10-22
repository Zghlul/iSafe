<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_root_displays_the_storefront(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Katalog')
            ->assertSee('Cara cari unit');
    }
}
