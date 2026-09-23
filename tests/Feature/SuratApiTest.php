<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuratApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test getting the list of surat.
     */
    public function test_can_get_surat_list(): void
    {
        $response = $this->getJson('/api/surat/jenis');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data'
        ]);
    }
}
