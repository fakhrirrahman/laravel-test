<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_index_shows_only_published_posts()
    {
        $author = User::factory()->create();
        Post::factory()->create([
            'user_id' => $author->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);
        Post::factory()->create([
            'user_id' => $author->id,
            'is_draft' => true,
        ]);

        $response = $this->get('/posts'); // pakai web route

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_store_creates_post_successfully()
    {
        $user = $this->authenticate();

        $payload = [
            'title' => 'Judul Post',
            'content' => 'Isi konten',
            'is_draft' => false,
            'published_at' => now()->addDay(),
        ];

        $response = $this->post('/posts', $payload); // pakai route web

        $response->assertStatus(201); // tetap bisa JSON
        $this->assertDatabaseHas('posts', [
            'title' => 'Judul Post',
            'user_id' => $user->id,
        ]);
    }

    public function test_store_requires_authentication()
    {
        $response = $this->post('/posts', []); // route web
        $response->assertRedirect(); // diarahkan ke login
    }

    public function test_show_draft_post_allowed_for_owner()
    {
        $user = $this->authenticate();

        $post = Post::factory()->create([
            'is_draft' => true,
            'user_id' => $user->id,
        ]);

        $response = $this->get("/posts/{$post->id}");
        $response->assertOk();
    }

    public function test_update_post_by_author()
    {
        $user = $this->authenticate();

        $post = Post::factory()->create(['user_id' => $user->id]);

        $payload = [
            'title' => 'Updated Title',
            'content' => 'Updated Content',
            'is_draft' => false,
            'published_at' => now()->addDay(),
        ];

        $response = $this->put("/posts/{$post->id}", $payload);

        $response->assertOk();
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_destroy_post_by_author()
    {
        $user = $this->authenticate();

        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->delete("/posts/{$post->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
}
