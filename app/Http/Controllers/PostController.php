<?php

namespace App\Http\Controllers;

use App\Http\Requests\Post\PostStoreRequest;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
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
        return response()->json('posts.create');
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
        if ($post->is_draft || $post->published_at > now()) {
            abort(404);
        }

        return response()->json($post->load('user'));
    }
}
