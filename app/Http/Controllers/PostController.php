<?php

namespace App\Http\Controllers;

use App\Http\Requests\Post\PostStoreRequest;
use App\Http\Requests\Post\PostUpdateRequest;
use App\Models\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {

        $posts = Post::with('user')->published()->paginate(20);

        return response()->json($posts);
    }

    public function create()
    {
        return 'posts.create';
    }

    public function store(PostStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', Post::class);

        Post::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'content' => $request->content,
            'is_draft' => $request->boolean('is_draft', true),
            'published_at' => $request->published_at,
        ]);

        return redirect('/posts');
    }

    public function show(Post $post): JsonResponse
    {
        Gate::authorize('view', $post);

        return response()->json($post->load('user'));
    }

    public function edit()
    {
        return 'posts.edit';
    }

    public function update(PostUpdateRequest $request, Post $post): RedirectResponse
    {
        Gate::authorize('update', $post);

        $post->update($request->validated());

        return redirect('/posts');
    }

    public function destroy(Post $post)
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully.']);
    }
}
