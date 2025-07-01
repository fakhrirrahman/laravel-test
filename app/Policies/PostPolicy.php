<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PostPolicy
{
    public function create(User $user): Response
    {
        return Response::allow();
    }

    /**
     * Determine if the given post can be updated by the user.
     */
    public function update(User $user, Post $post): Response
    {
        return $user->id === $post->user_id
            ? Response::allow()
            : Response::deny('You do not own this post.');
    }

    /**
     * Determine if the given post can be deleted by the user.
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    /**
     * Determine if the given post can be viewed by the user.
     */
    public function view(?User $user, Post $post): Response
    {
        // Return 404 if the post is draft or scheduled
        if ($post->is_draft || ! $post->published_at || $post->published_at > now()) {
            return Response::denyWithStatus(404);
        }

        // Allow everyone to view published posts
        return Response::allow();
    }
}
