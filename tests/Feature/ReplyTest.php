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
}
