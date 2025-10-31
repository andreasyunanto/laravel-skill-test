<?php

namespace Tests\Feature\Post;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_active_posts_without_drafts_or_scheduled()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        // published post
        Post::factory()->create([
            'is_draft' => 0,
            'published_at' => now()->subHour(),
            'user_id' => $user->id,
        ]);

        // draft
        Post::factory()->create(['is_draft' => 1, 'user_id' => $user->id]);

        // scheduled
        Post::factory()->create([
            'is_draft' => 0,
            'published_at' => now()->addDay(),
            'user_id' => $user->id,
        ]);

        $res = $this->getJson('/posts');

        $res->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
            ]);

        // only 1 published post in data
        $this->assertCount(1, $res->json('data'));
    }

    public function test_show_published_post_returns_json()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $post = Post::factory()->create([
            'is_draft' => 0,
            'published_at' => now()->subHour(),
            'user_id' => $user->id,
        ]);

        $res = $this->getJson("/posts/{$post->id}");
        $res->assertStatus(200)
            ->assertJsonFragment(['id' => $post->id]);
    }

    public function test_show_draft_or_scheduled_returns_404()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $draft = Post::factory()->create(['is_draft' => 1, 'user_id' => $user->id]);
        $scheduled = Post::factory()->create(['is_draft' => 0, 'published_at' => now()->addDay(), 'user_id' => $user->id]);

        $this->getJson("/posts/{$draft->id}")->assertStatus(404);
        $this->getJson("/posts/{$scheduled->id}")->assertStatus(404);
    }

    public function test_store_requires_auth_and_valid_data()
    {
        $user = User::factory()->create();

        // unauthenticated
        $this->postJson('/posts', [])->assertStatus(401);

        // authenticated but invalid data
        $this->actingAs($user)
            ->postJson('/posts', ['title' => ''])
            ->assertStatus(422);

        // valid create as draft
        $res = $this->actingAs($user)
            ->postJson('/posts', [
                'title' => 'Hello',
                'content' => 'Body body',
                'is_draft' => 1,
            ]);

        $res->assertStatus(201)
            ->assertJsonFragment(['title' => 'Hello']);
        $this->assertDatabaseHas('posts', ['title' => 'Hello', 'user_id' => $user->id]);
    }

    public function test_update_only_user_can_update()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => 0,
            'title' => 'Original',
        ]);

        // other user tries update
        $this->actingAs($other)
            ->patchJson("/posts/{$post->id}", ['title' => 'Hacked', 'content' => 'Body body', 'is_draft' => 0])
            ->assertStatus(403);

        // user updates
        $this->actingAs($user)
            ->patchJson("/posts/{$post->id}", ['title' => 'Updated', 'is_draft' => 0])
            ->assertStatus(200)
            ->assertJsonFragment(['title' => 'Updated']);
    }

    public function test_delete_only_user_can_delete()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->actingAs($other)->deleteJson("/posts/{$post->id}")->assertStatus(403);

        $this->actingAs($user)->deleteJson("/posts/{$post->id}")->assertStatus(200);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
}
