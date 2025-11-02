<?php

namespace App\Http\Controllers\Post;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
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
     * POST /posts
     * Only authenticated users
     */
    public function store(StorePostRequest $request)
    {
        if ($request->wantsJson()) {
            $data = $request->validated();
            $data['user_id'] = $request->user()->id;

            // If status is published and published_at is null, set to now
            if ((int) ($data['is_draft'] ?? 0) === 0 && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            $post = Post::create($data);

            return response()->json($post->refresh(), Response::HTTP_CREATED);
        }
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
     * PATCH /posts/{post}
     * Only author can update
     */
    public function update(UpdatePostRequest $request, Post $post)
    {
        if ($request->wantsJson()) {

            $this->authorize('update', $post);

            $data = $request->validated();

            // If changing to published without published_at, set it
            if (empty($post->published_at) && (int) ($data['is_draft'] ?? 0) === 0) {
                $data['published_at'] = now();
            }

            $post->update($data);

            return response()->json($post);
        }
    }

    /**
     * GET /posts/{post}
     * Return 404 if draft or scheduled.
     */
    public function show(Post $post)
    {
        if ($post->isDraft() || $post->isScheduled()) {
            return response()->json(['message' => 'Post not found.'], Response::HTTP_NOT_FOUND);
        }

        $post->load('user');

        return response()->json($post);
    }

    /**
     * DELETE /posts/{post}
     * Only author can delete
     */
    public function destroy(Request $request, Post $post)
    {
        if ($request->wantsJson()) {

            $this->authorize('delete', $post);
            $post->delete();

            return response()->json(['message' => 'deleted'], Response::HTTP_OK);
        }
    }
}
