<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Canvas\Models\User;
use Canvas\Events\PostViewed;
use Canvas\Models\Post;
use Canvas\Models\Tag;
use Canvas\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class CanvasUiController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        return view('canvas-ui')->with([
            'config' => [
                'canvasPath' => config('canvas.path'),
                'user' => $request->user('canvas'),
                'timezone' => config('app.timezone'),
            ],
        ]);
    }

    public function serve($file)
    {
        if (Storage::exists('public/canvas/images/' . $file)) {
            $filePath = storage_path('app/private/public/canvas/images/' . $file);
            return response()->file($filePath);
        }

        return abort(404);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPosts(Request $request): LengthAwarePaginator
    {
        return Post::latest()->published()->with('user', 'topic')->paginate();
    }


    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function showPosts(Request $request)
    {
        $posts =  Post::latest()->published()->with('user', 'topic')->paginate(12);
        $page = $request->page;
        return view('posts', compact('posts','page'));
    }
    /**
     * @param \Illuminate\Http\Request $request
     * @param $slug
     */
    public function viewPost(Request $request, $slug)
    {
        $post = Post::with('user', 'tags', 'topic')->firstWhere('slug', $slug);

        if ($post) {
            event(new PostViewed($post));
            return view('post', compact('post'));
        }
    }
    /**
     * @param \Illuminate\Http\Request $request
     * @param $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function showPost(Request $request, $slug): JsonResponse
    {
        $post = Post::with('user', 'tags', 'topic')->firstWhere('slug', $slug);

        if ($post) {
            event(new PostViewed($post));
            return response()->json($post, 200);
        } else {
            return response()->json(null, 404);
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function getTags(Request $request): string
    {
        return Tag::all()->toJson();
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function showTag(Request $request, $slug): JsonResponse
    {
        $tag = Tag::firstWhere('slug', $slug);

        return $tag ? response()->json($tag, 200) : response()->json(null, 404);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPostsForTag(Request $request, $slug): JsonResponse
    {
        $tag = Tag::firstWhere('slug', $slug);

        return $tag ? response()->json($tag->posts()->with('topic', 'user')->paginate(), 200) : response()->json(null, 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function getTopics(Request $request): string
    {
        return Topic::all()->toJson();
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function showTopic(Request $request, $slug): JsonResponse
    {
        $topic = Topic::firstWhere('slug', $slug);

        return $topic ? response()->json($topic, 200) : response()->json(null, 404);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPostsForTopic(Request $request, $slug): JsonResponse
    {
        $topic = Topic::firstWhere('slug', $slug);

        return $topic ? response()->json($topic->posts()->with('topic', 'user')->paginate(), 200) : response()->json(null, 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showUser(Request $request, $id): JsonResponse
    {
        $user = User::with('posts')->find($id);

        return $user ? response()->json($user, 200) : response()->json(null, 404);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPostsForUser(Request $request, $id): JsonResponse
    {
        $user = User::find($id);

        return $user ? response()->json($user->posts()->published()->with('user', 'topic')->paginate(), 200) : response()->json(null, 200);
    }
}
