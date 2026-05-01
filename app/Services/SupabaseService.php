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
        $this->baseUrl = rtrim(env('SUPABASE_URL'), '/');
        $this->key = env('SUPABASE_SERVICE_ROLE_KEY');   // ← Important change

        if (!$this->baseUrl || !$this->key) {
            Log::error('Supabase configuration missing. Check .env file.');
            throw new \Exception('Supabase URL or Service Role Key is not configured.');
        }
    }

    private function headers()
    {
        return [
            'apikey'        => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type'  => 'application/json',
            'Prefer'        => 'return=representation'
        ];
    }

    private function buildUrl(string $path): string
    {
        $path = ltrim($path, '/');
        return "{$this->baseUrl}/rest/v1/{$path}";
    }

    // ─── NEWS OPERATIONS ─────────────────────────────────────────────────────

    public function getNews()
    {
        $response = Http::withHeaders($this->headers())
            ->get($this->buildUrl('news?select=*&order=created_at.desc'));

        if ($response->failed()) {
            Log::error('Supabase getNews failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        }

        return $response->json();
    }

    public function getNewsById($id)
    {
        $response = Http::withHeaders($this->headers())
            ->get($this->buildUrl("news?id=eq.{$id}"));

        $data = $response->json();
        return $data[0] ?? null;
    }
    public function createNews($data)
    {
        $response = Http::withHeaders($this->headers())
            ->post($this->buildUrl('news'), $data);
    
        if ($response->failed()) {
            Log::error('Supabase createNews failed', [
                'status' => $response->status(),
                'body'   => $response->body()
            ]);
            throw new \Exception('Failed to create news: ' . $response->body());
        }
    
        $data = $response->json();
    
        // Supabase usually returns an array with one object
        return is_array($data) && count($data) > 0 ? $data[0] : $data;
    }

    public function updateNews($id, $data)
    {
        $response = Http::withHeaders($this->headers())
            ->patch($this->buildUrl("news?id=eq.{$id}"), $data);

        return $response->json();
    }

    public function deleteNews($id)
    {
        return Http::withHeaders($this->headers())
            ->delete($this->buildUrl("news?id=eq.{$id}"))
            ->json();
    }

    public function searchNews($query)
    {
        $encodedQuery = urlencode($query);
        return Http::withHeaders($this->headers())
            ->get($this->buildUrl("news?or=(title.ilike.*{$encodedQuery}*,excerpt.ilike.*{$encodedQuery}*)&order=created_at.desc"))
            ->json();
    }

    public function getNewsByCategory($category)
    {
        return Http::withHeaders($this->headers())
            ->get($this->buildUrl("news?category=eq.{$category}&order=created_at.desc"))
            ->json();
    }

    public function getNewsByTag($tag)
    {
        return Http::withHeaders($this->headers())
            ->get($this->buildUrl("news?tag=eq.{$tag}&order=created_at.desc"))
            ->json();
    }
}