<?php

namespace App\Http\Controllers;

use App\Http\Requests\Post\PostStoreRequest;
use App\Http\Requests\Post\PostUpdateRequest;
use App\Models\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $posts = Post::with('user')
            ->where('is_draft', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->paginate(20);

        return response()->json($posts);
    }

    public function create()
    {
        return 'posts.create';
    }

    public function store(PostStoreRequest $request)
    {
        $post = Post::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'content' => $request->content,
            'is_draft' => $request->boolean('is_draft', true),
            'published_at' => $request->published_at,
        ]);

        return response()->json($post->load('user'), 201);
    }

    public function show(Post $post)
    {
        if ($post->is_draft || $post->published_at === null || $post->published_at > now()) {
            $this->authorize('view', $post);
        }

        return response()->json($post->load('user'));
    }

    public function edit()
    {
        return 'posts.edit';
    }

    public function update(PostUpdateRequest $request, Post $post)
    {
        $this->authorize('update', $post);

        $post->update([
            'title' => $request->title,
            'content' => $request->content,
            'is_draft' => $request->is_draft,
            'published_at' => $request->published_at,
        ]);

        return response()->json($post->load('user'));
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully.']);
    }
}
