<?php

namespace App\Services\WooCommerce;

use Illuminate\Support\Facades\Http;

class Client
{
    private string $baseUrl;
    private string $key;
    private string $secret;

    public function __construct(string $baseUrl, string $key, string $secret)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->key = $key;
        $this->secret = $secret;
    }

    public function get(string $endpoint, array $query = [])
    {
        $url = $this->buildUrl($endpoint);
        $query = $this->withAuthQuery($query);
        return Http::timeout(30)
            ->retry(3, 500)
            ->get($url, $query);
    }

    public function post(string $endpoint, array $data = [])
    {
        $url = $this->buildUrl($endpoint) . '?' . http_build_query($this->withAuthQuery());
        return Http::timeout(60)
            ->retry(3, 500)
            ->post($url, $data);
    }

    public function put(string $endpoint, array $data = [])
    {
        $url = $this->buildUrl($endpoint) . '?' . http_build_query($this->withAuthQuery());
        return Http::timeout(60)
            ->retry(3, 500)
            ->put($url, $data);
    }

    private function buildUrl(string $endpoint): string
    {
        $endpoint = ltrim($endpoint, '/');
        return $this->baseUrl . '/wp-json/wc/v3/' . $endpoint;
    }

    private function withAuthQuery(array $query = []): array
    {
        $query['consumer_key'] = $this->key;
        $query['consumer_secret'] = $this->secret;
        return $query;
    }
}


