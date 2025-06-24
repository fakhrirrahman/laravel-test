<?php

namespace App\Http\Controllers;

use App\Models\Post;

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
}
