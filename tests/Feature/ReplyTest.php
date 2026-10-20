<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Reply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_詳細画面にポストとリプライが表示される(): void
    {
        $post = Post::factory()->create(['title' => '詳細テスト用のタイトル']);
        Reply::factory()->for($post)->create(['content' => '最初のリプライ']);

        $response = $this->actingAs(User::factory()->create())->get(route('posts.show', $post));

        $response->assertOk();
        $response->assertSee('詳細テスト用のタイトル');
        $response->assertSee('最初のリプライ');
    }

    public function test_ログインしていないと詳細画面を開けない(): void
    {
        $post = Post::factory()->create();

        $this->get(route('posts.show', $post))->assertRedirect('/login');
    }

    public function test_リプライを送ると一覧に増える(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)->post(route('replies.store', $post), ['content' => 'こんにちは']);

        $response->assertRedirect(route('posts.show', $post));
        $this->assertDatabaseHas('replies', [
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'こんにちは',
        ]);
    }

    public function test_本文が空だとエラーになりリプライは増えない(): void
    {
        $post = Post::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->from(route('posts.show', $post))
            ->post(route('replies.store', $post), ['content' => '']);

        $response->assertRedirect(route('posts.show', $post));
        $response->assertSessionHasErrors('content');
        $this->assertDatabaseCount('replies', 0);
    }

    public function test_自分のリプライは削除できる(): void
    {
        $user = User::factory()->create();
        $reply = Reply::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete(route('replies.destroy', $reply));

        $response->assertRedirect(route('posts.show', $reply->post_id));
        $this->assertModelMissing($reply);
    }

    public function test_他人のリプライは削除できない(): void
    {
        $reply = Reply::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('replies.destroy', $reply))
            ->assertForbidden();

        $this->assertModelExists($reply);
    }

    public function test_削除ボタンは自分のリプライにだけ表示される(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $mine = Reply::factory()->for($post)->for($user)->create();
        $others = Reply::factory()->for($post)->create();

        $response = $this->actingAs($user)->get(route('posts.show', $post));

        $response->assertSee(route('replies.destroy', $mine), false);
        $response->assertDontSee(route('replies.destroy', $others), false);
    }

    public function test_ポストを削除するとリプライも消える(): void
    {
        $post = Post::factory()->create();
        Reply::factory()->for($post)->create();

        $this->actingAs($post->user)->delete(route('posts.destroy', $post));

        $this->assertDatabaseCount('replies', 0);
    }

    public function test_本文が280字ちょうどなら保存できる(): void
    {
        $post = Post::factory()->create();
        $content = str_repeat('あ', 280);

        $this->actingAs(User::factory()->create())
            ->post(route('replies.store', $post), ['content' => $content])
            ->assertRedirect(route('posts.show', $post));

        $this->assertDatabaseHas('replies', ['post_id' => $post->id, 'content' => $content]);
    }

    public function test_本文が281字だとエラーになり保存されない(): void
    {
        $post = Post::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->from(route('posts.show', $post))
            ->post(route('replies.store', $post), ['content' => str_repeat('あ', 281)]);

        $response->assertRedirect(route('posts.show', $post));
        $response->assertSessionHasErrors(['content' => 'The content field must not be greater than 280 characters.']);
        $this->assertDatabaseCount('replies', 0);
    }

    public function test_ログインしていないとリプライを送れず消せない(): void
    {
        $post = Post::factory()->create();
        $reply = Reply::factory()->for($post)->create();

        $this->post(route('replies.store', $post), ['content' => 'こんにちは'])->assertRedirect('/login');
        $this->delete(route('replies.destroy', $reply))->assertRedirect('/login');

        $this->assertDatabaseCount('replies', 1);
        $this->assertModelExists($reply);
    }

    public function test_存在しないポストにはリプライを送れない(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/posts/999999/replies', ['content' => 'こんにちは'])
            ->assertNotFound();

        $this->assertDatabaseCount('replies', 0);
    }

    public function test_リプライが0件のポストでは空の案内が出る(): void
    {
        $post = Post::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('リプライ（0）')
            ->assertSee('まだリプライがありません。');
    }

    public function test_一覧の編集と削除は本人のポストにだけ出て返信は誰のポストにも出る(): void
    {
        $user = User::factory()->create();
        $mine = Post::factory()->for($user)->create();
        $others = Post::factory()->create();

        $response = $this->actingAs($user)->get(route('posts.index'));

        $response->assertOk();
        $response->assertSee(route('posts.show', $mine), false);
        $response->assertSee(route('posts.show', $others), false);
        $response->assertSee(route('posts.edit', $mine), false);
        $response->assertSee('action="'.route('posts.destroy', $mine).'"', false);
        $response->assertDontSee(route('posts.edit', $others), false);
        $response->assertDontSee('action="'.route('posts.destroy', $others).'"', false);
    }
}
