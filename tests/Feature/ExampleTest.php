<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertSame(1, substr_count($response->getContent(), 'class="hero home-hero"'));
        $this->assertSame(1, substr_count($response->getContent(), '<main>'));
        $this->get('/index')->assertRedirect('/');
    }
}
