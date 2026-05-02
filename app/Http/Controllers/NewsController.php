<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NewsController extends Controller
{
    private SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * GET /api/news
     */
    public function index()
    {
        try {
            // Cache for 5 minutes
            $news = Cache::remember('news.all', 300, function () {
                return $this->supabase->getNews();
            });

            return response()->json($news)
                ->header('Cache-Control', 'public, max-age=300');
        } catch (\Throwable $e) {
            Log::error('News index failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch news',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/news/{id}
     */
    public function show($id)
    {
        try {
            $news = Cache::remember("news.{$id}", 300, function () use ($id) {
                return $this->supabase->getNewsById($id);
            });

            if (! $news) {
                return response()->json([
                    'error' => 'News article not found',
                ], 404);
            }

            return response()->json($news);
        } catch (\Throwable $e) {
            Log::error('News show failed', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch news article',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/news
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'title' => 'required|string|max:255',
                'excerpt' => 'nullable|string',
                'content' => 'nullable|string',
                'image' => 'nullable|url',
                'tag' => 'nullable|string|max:50',
                'category' => 'nullable|string|max:50',
                'author' => 'nullable|string|max:100',
            ]);

            $data['created_at'] = now()->toISOString();
            $data['updated_at'] = now()->toISOString();

            $news = $this->supabase->createNews($data);

            // Clear cache
            Cache::forget('news.all');

            return response()->json($news, 201);
        } catch (\Throwable $e) {
            Log::error('News store failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to create news',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/news/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'excerpt' => 'nullable|string',
                'content' => 'nullable|string',
                'image' => 'nullable|url',
                'tag' => 'nullable|string|max:50',
                'category' => 'nullable|string|max:50',
                'author' => 'nullable|string|max:100',
            ]);

            $data['updated_at'] = now()->toISOString();

            $news = $this->supabase->updateNews($id, $data);

            if (! $news) {
                return response()->json([
                    'error' => 'News article not found',
                ], 404);
            }

            // Clear caches
            Cache::forget('news.all');
            Cache::forget("news.{$id}");

            return response()->json($news);
        } catch (\Throwable $e) {
            Log::error('News update failed', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update news',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/news/{id}
     */
    public function destroy($id)
    {
        try {
            $this->supabase->deleteNews($id);

            // Clear caches
            Cache::forget('news.all');
            Cache::forget("news.{$id}");

            return response()->json([
                'message' => 'News deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('News delete failed', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to delete news',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/news/search?q=
     */
    public function search(Request $request)
    {
        try {
            $query = $request->input('q', '');

            return response()->json(
                $this->supabase->searchNews($query)
            );
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Search failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/news/category/{category}
     */
    public function byCategory($category)
    {
        try {
            return response()->json(
                $this->supabase->getNewsByCategory($category)
            );
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Category fetch failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/news/tag/{tag}
     */
    public function byTag($tag)
    {
        try {
            return response()->json(
                $this->supabase->getNewsByTag($tag)
            );
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Tag fetch failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}