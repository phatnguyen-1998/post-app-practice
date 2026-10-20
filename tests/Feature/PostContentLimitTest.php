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

    public function test_編集画面に残り文字数が表示される(): void
    {
        $post = Post::factory()->create(['content' => str_repeat('あ', 40)]);

        $this->actingAs($post->user)
            ->get(route('posts.edit', $post))
            ->assertOk()
            ->assertSee('残り 100 文字');
    }

    public function test_上限を超えた本文を再表示したときは文字数オーバーが表示される(): void
    {
        $post = Post::factory()->create();

        $this->updateWith($post, str_repeat('あ', 145));

        $this->actingAs($post->user)
            ->withSession(['_old_input' => ['content' => str_repeat('あ', 145)]])
            ->get(route('posts.edit', $post))
            ->assertSee('5 文字オーバー');
    }
}
