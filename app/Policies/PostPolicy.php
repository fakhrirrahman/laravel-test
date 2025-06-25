<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Determine if the given post can be updated by the user.
     */
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
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
    public function view(?User $user, Post $post): bool
    {
        if ($user && $user->id === $post->user_id) {
            return true;
        }

        return ! $post->is_draft && $post->published_at && $post->published_at <= now();
    }
}
