<?php

namespace App\Http\Controllers\Post;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        //
    }

    /**
     * GET /posts
     * Paginated list (20 per page) of active posts (published only). JSON response.
     */
    public function index(Request $request)
    {
        $posts = Post::active()
            ->with('user')
            ->orderBy('published_at', 'desc')
            ->paginate(20);

        return response()->json($posts);
    }

    /**
     * GET /posts/create
     * Not required — return simple string as allowed.
     */
    public function create()
    {
        return 'posts.create';
    }

    /**
     * GET /posts/{post}/edit
     * Not required — return simple string.
     */
    public function edit(Post $post)
    {
        return 'posts.edit';
    }

    /**
     * GET /posts/{post}
     * Return 404 if draft or scheduled.
     */
    public function show($id)
    {
        $post = Post::with(['user'])->find($id);

        if (! $post || $post->isDraft() || $post->isScheduled()) {
            return response()->json([
                'message' => 'Post not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json($post);
    }
}
