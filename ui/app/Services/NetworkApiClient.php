<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class NetworkApiClient
{
    public function __construct(
        private string $baseUrl,
        private string $networkName,
        private string $apiKey = '',
    ) {}

    // ── Private helpers ───────────────────────────────────────────────────────

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        $pending = Http::timeout(10);

        if (app()->environment('local')) {
            $pending = $pending->withoutVerifying();
        }

        if ($this->apiKey !== '') {
            $pending = $pending->withToken($this->apiKey);
        }

        return $pending;
    }

    // ── Public API ────────────────────────────────────────────────────────────

    public function getDomains(): Collection
    {
        $response = $this->http()->get("{$this->baseUrl}/api/domains");

        if ($response->failed()) {
            return collect();
        }

        return collect($response->json())->map(function ($domain) {
            $domain['network'] = $this->networkName;
            return $domain;
        });
    }

    public function createContent(int $domainId, int $userId, string $title, string $body): array
    {
        $response = $this->http()
            ->withHeaders(['X-User-Id' => $userId])
            ->post("{$this->baseUrl}/api/domains/{$domainId}/content", [
                'title' => $title,
                'body'  => $body,
            ]);

        return [
            'success' => $response->successful(),
            'status'  => $response->status(),
            'data'    => $response->json(),
        ];
    }
}
