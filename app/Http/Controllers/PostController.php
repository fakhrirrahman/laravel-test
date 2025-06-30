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

    public function show(Post $post): JsonResponse
    {
        if (! Gate::allows('view', $post)) {
            abort(404, 'Post not found');
        }

        return response()->json($post->load('user'));
    }

    public function edit()
    {
        return 'posts.edit';
    }

    public function update(PostUpdateRequest $request, Post $post): RedirectResponse
    {
        $response = Gate::inspect('update', $post);

        if (! $response->allowed()) {
            abort(403, $response->message());
        }

        $post->update([
            'title' => $request->validated('title'),
            'content' => $request->validated('content'),
            'is_draft' => $request->boolean('is_draft', true),
            'published_at' => $request->validated('published_at'),
        ]);

        return redirect()->back();
    }

    public function destroy(Post $post)
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully.']);
    }
}
