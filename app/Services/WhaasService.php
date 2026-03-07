<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhaasService
{
    protected string $apiUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.whaas.url');
        $this->apiKey = config('services.whaas.key');
    }

    public function sendMessage(string $to, string $message): bool
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->post("{$this->apiUrl}/messages", [
                    'to' => $to,
                    'text' => $message,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Error enviando mensaje por Whaas: " . $e->getMessage());
            return false;
        }
    }
}