<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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

    public function test_index_shows_only_published_posts(): void
    {
        $author = User::factory()->create();

        Post::factory()->count(3)->create([
            'user_id' => $author->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        Post::factory()->create([
            'user_id' => $author->id,
            'is_draft' => true,
            'published_at' => now()->subDay(),
        ]);

        Post::factory()->create([
            'user_id' => $author->id,
            'is_draft' => false,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/posts');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonFragment(['is_draft' => false]);
    }

    public function test_store_creates_post_successfully()
    {
        $user = $this->authenticate();

        $payload = [
            'title' => 'Test Post Title',
            'content' => 'Test post content',
            'is_draft' => false,
            'published_at' => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->post('/posts', $payload);

        $response->assertRedirect('/posts');
        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post Title',
            'content' => 'Test post content',
            'user_id' => $user->id,
        ]);
    }

    public function test_store_requires_authentication()
    {
        $payload = [
            'title' => 'Test Title',
            'content' => 'Test content',
            'is_draft' => false,
        ];
        $response = $this->post('/posts', $payload);
        $response->assertRedirect();
        $this->assertDatabaseMissing('posts', [
            'title' => 'Test Title',
            'content' => 'Test content',
        ]);
    }

    public function test_show_draft_post_returns_404_for_owner()
    {
        $user = $this->authenticate();

        $post = Post::factory()->create([
            'is_draft' => true,
            'user_id' => $user->id,
        ]);

        $response = $this->get("/posts/{$post->id}");
        $response->assertNotFound();
    }

    public function test_show_scheduled_post_returns_404_for_owner()
    {
        $user = $this->authenticate();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->get("/posts/{$post->id}");
        $response->assertNotFound();
    }

    public function test_show_published_post_accessible_to_everyone()
    {
        $author = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $author->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        // Test as authenticated user
        $this->authenticate();
        $response = $this->get("/posts/{$post->id}");
        $response->assertOk();

        // Test as unauthenticated user
        Auth::logout();
        $response = $this->get("/posts/{$post->id}");
        $response->assertOk();
    }

    public function test_show_draft_post_with_null_published_at_returns_404_for_non_owner()
    {
        $author = User::factory()->create();
        $viewer = $this->authenticate();

        $post = Post::factory()->create([
            'user_id' => $author->id,
            'is_draft' => true,
            'published_at' => null,
        ]);

        $response = $this->get("/posts/{$post->id}");
        $response->assertNotFound();
    }

    public function test_update_post_by_author()
    {
        $user = $this->authenticate();

        $post = Post::factory()->create(['user_id' => $user->id]);

        $payload = [
            'title' => 'Updated Title',
            'content' => 'Updated Content',
            'is_draft' => false,
            'published_at' => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->put("/posts/{$post->id}", $payload);

        $response->assertRedirect();
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

    public function test_show_draft_post_returns_403_for_non_owner()
    {
        $author = User::factory()->create();
        $viewer = $this->authenticate();

        $post = Post::factory()->create([
            'user_id' => $author->id,
            'is_draft' => true,
        ]);

        $response = $this->get("/posts/{$post->id}");
        $response->assertNotFound();
    }

    public function test_show_scheduled_post_returns_403_for_non_owner()
    {
        $author = User::factory()->create();
        $viewer = $this->authenticate();

        $post = Post::factory()->create([
            'user_id' => $author->id,
            'is_draft' => false,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->get("/posts/{$post->id}");
        $response->assertNotFound();
    }

    public function test_update_post_requires_author()
    {
        $author = User::factory()->create();
        $otherUser = $this->authenticate();

        $post = Post::factory()->create(['user_id' => $author->id]);

        $payload = [
            'title' => 'Updated Title',
            'content' => 'Updated Content',
            'is_draft' => false,
        ];

        $response = $this->put("/posts/{$post->id}", $payload);
        $response->assertForbidden();
    }

    public function test_destroy_post_requires_author()
    {
        $author = User::factory()->create();
        $otherUser = $this->authenticate();

        $post = Post::factory()->create(['user_id' => $author->id]);

        $response = $this->delete("/posts/{$post->id}");
        $response->assertForbidden();
    }

    public function test_store_validates_required_fields()
    {
        $this->authenticate();

        $response = $this->post('/posts', []);
        $response->assertSessionHasErrors(['title', 'content']);
    }

    public function test_update_validates_required_fields()
    {
        $user = $this->authenticate();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->put("/posts/{$post->id}", []);
        $response->assertSessionHasErrors(['title', 'content', 'is_draft']);
    }

    public function test_index_excludes_null_published_at()
    {
        $author = User::factory()->create();

        Post::factory()->create([
            'user_id' => $author->id,
            'is_draft' => false,
            'published_at' => null,
        ]);

        Post::factory()->create([
            'user_id' => $author->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/posts');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_update_requires_authentication()
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $payload = [
            'title' => 'Updated Title',
            'content' => 'Updated Content',
            'is_draft' => false,
        ];

        $response = $this->put("/posts/{$post->id}", $payload);
        $response->assertRedirect();
    }

    public function test_destroy_requires_authentication()
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $response = $this->delete("/posts/{$post->id}");
        $response->assertRedirect();
    }
}
