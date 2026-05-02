<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseService
{
    private $baseUrl;

    private $key;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.supabase.url'), '/');
        $this->key = config('services.supabase.service_role_key');

        if (! $this->baseUrl || ! $this->key) {
            Log::error('Supabase configuration missing. Check .env file.');
            throw new \Exception('Supabase URL or Service Role Key is not configured.');
        }
    }

    private function headers()
    {
        return [
            'apikey' => $this->key,
            'Authorization' => 'Bearer '.$this->key,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ];
    }

    private function buildUrl(string $path): string
    {
        $path = ltrim($path, '/');

        return "{$this->baseUrl}/rest/v1/{$path}";
    }

    // ─── NEWS OPERATIONS ─────────────────────────────────────────────────────

    /**
     * Get all news articles
     */
    public function getNews()
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(10)
                ->get($this->buildUrl('news?select=*&order=created_at.desc'));

            if ($response->failed()) {
                Log::error('Supabase getNews failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \Exception('Supabase API returned status '.$response->status());
            }

            $data = $response->json();

            return is_array($data) ? $data : [];
        } catch (\Exception $e) {
            Log::error('Supabase getNews exception', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get a single news article by ID
     */
    public function getNewsById($id)
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(10)
                ->get($this->buildUrl("news?id=eq.{$id}"));

            if ($response->failed()) {
                Log::error('Supabase getNewsById failed', [
                    'id' => $id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to fetch news by ID');
            }

            $data = $response->json();

            return (is_array($data) && count($data) > 0) ? $data[0] : null;
        } catch (\Exception $e) {
            Log::error('Supabase getNewsById exception', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a new news article
     */
    public function createNews($data)
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(10)
                ->post($this->buildUrl('news'), $data);

            if ($response->failed()) {
                Log::error('Supabase createNews failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to create news: '.$response->body());
            }

            $result = $response->json();

            // Supabase returns an array with one object
            return is_array($result) && count($result) > 0 ? $result[0] : $result;
        } catch (\Exception $e) {
            Log::error('Supabase createNews exception', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update a news article
     */
    public function updateNews($id, $data)
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(10)
                ->patch($this->buildUrl("news?id=eq.{$id}"), $data);

            if ($response->failed()) {
                Log::error('Supabase updateNews failed', [
                    'id' => $id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to update news');
            }

            $result = $response->json();

            return is_array($result) && count($result) > 0 ? $result[0] : $result;
        } catch (\Exception $e) {
            Log::error('Supabase updateNews exception', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete a news article
     */
    public function deleteNews($id)
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(10)
                ->delete($this->buildUrl("news?id=eq.{$id}"));

            if ($response->failed()) {
                Log::error('Supabase deleteNews failed', [
                    'id' => $id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to delete news');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Supabase deleteNews exception', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Search news articles
     */
    public function searchNews($query)
    {
        try {
            $encodedQuery = urlencode($query);

            $response = Http::withHeaders($this->headers())
                ->timeout(10)
                ->get($this->buildUrl("news?or=(title.ilike.*{$encodedQuery}*,excerpt.ilike.*{$encodedQuery}*)&order=created_at.desc"));

            if ($response->failed()) {
                Log::error('Supabase searchNews failed', [
                    'query' => $query,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Search failed');
            }

            $data = $response->json();

            return is_array($data) ? $data : [];
        } catch (\Exception $e) {
            Log::error('Supabase searchNews exception', [
                'query' => $query,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get news articles by category
     */
    public function getNewsByCategory($category)
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(10)
                ->get($this->buildUrl("news?category=eq.{$category}&order=created_at.desc"));

            if ($response->failed()) {
                Log::error('Supabase getNewsByCategory failed', [
                    'category' => $category,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to fetch news by category');
            }

            $data = $response->json();

            return is_array($data) ? $data : [];
        } catch (\Exception $e) {
            Log::error('Supabase getNewsByCategory exception', [
                'category' => $category,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get news articles by tag
     */
    public function getNewsByTag($tag)
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(10)
                ->get($this->buildUrl("news?tag=eq.{$tag}&order=created_at.desc"));

            if ($response->failed()) {
                Log::error('Supabase getNewsByTag failed', [
                    'tag' => $tag,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to fetch news by tag');
            }

            $data = $response->json();

            return is_array($data) ? $data : [];
        } catch (\Exception $e) {
            Log::error('Supabase getNewsByTag exception', [
                'tag' => $tag,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
