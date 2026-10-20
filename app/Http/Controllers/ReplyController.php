<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Reply;
use Illuminate\Http\Request;

class ReplyController extends Controller
{
    public function store(Request $request, Post $post)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:280',
        ]);

        $post->replies()->create($validated + ['user_id' => $request->user()->id]);

        return redirect()->route('posts.show', $post);
    }

    public function destroy(Reply $reply)
    {
        $this->authorize('delete', $reply);

        $reply->delete();

        return redirect()->route('posts.show', $reply->post_id);
    }
}
