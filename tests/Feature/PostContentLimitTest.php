<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostContentLimitTest extends TestCase
{
    use RefreshDatabase;

    private function updateWith(Post $post, string $content)
    {
        return $this->actingAs($post->user)
            ->from(route('posts.edit', $post))
            ->put(route('posts.update', $post), [
                'title' => $post->title,
                'category_id' => $post->category_id,
                'content' => $content,
            ]);
    }

    public function test_140字ちょうどの本文は保存できる(): void
    {
        $post = Post::factory()->create();
        $content = str_repeat('あ', 140);

        $this->updateWith($post, $content)->assertRedirect(route('posts.index'));

        $this->assertSame($content, $post->fresh()->content);
    }

    public function test_140字を超えるとエラーになり保存されない(): void
    {
        $post = Post::factory()->create(['content' => '元の本文']);

        $response = $this->updateWith($post, str_repeat('あ', 141));

        $response->assertRedirect(route('posts.edit', $post));
        $response->assertSessionHasErrors(['content' => '本文は 140 文字以内で入力してください。']);
        $this->assertSame('元の本文', $post->fresh()->content);
    }

    public function test_本文が空のときのエラーも日本語で出る(): void
    {
        $post = Post::factory()->create(['content' => '元の本文']);

        $this->updateWith($post, '')
            ->assertSessionHasErrors(['content' => '本文を入力してください。']);

        $this->assertSame('元の本文', $post->fresh()->content);
    }
}
