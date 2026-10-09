<?php

namespace App\Services;

use App\Models\PostalCodeGeocode;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PostalCodeGeocodeService
{
    public function getApiKey(): ?string
    {
        return config('services.google_maps.key');
    }

    public function geocode(string $postalCode): ?PostalCodeGeocode
    {
        $postalCode = preg_replace('/\D/', '', $postalCode);

        if (! preg_match('/^\d{7}$/', $postalCode)) {
            return null;
        }

        $cached = PostalCodeGeocode::query()
            ->where('postal_code', $postalCode)
            ->first();

        if ($cached && $cached->latitude !== null && $cached->longitude !== null) {
            return $cached;
        }

        $apiKey = $this->getApiKey();

        if (! $apiKey) {
            return $cached;
        }

        try {
            $response = Http::timeout(8)
                ->retry(1, 200)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $postalCode,
                    'components' => 'country:JP|postal_code:' . $postalCode,
                    'language' => 'ja',
                    'region' => 'jp',
                    'key' => $apiKey,
                ]);

            $payload = $response->json();
            $status = $payload['status'] ?? 'HTTP_' . $response->status();
            $firstResult = $payload['results'][0] ?? null;
            $location = $firstResult['geometry']['location'] ?? null;

            return PostalCodeGeocode::query()->updateOrCreate(
                ['postal_code' => $postalCode],
                [
                    'formatted_address' => $firstResult['formatted_address'] ?? null,
                    'latitude' => $location['lat'] ?? null,
                    'longitude' => $location['lng'] ?? null,
                    'status' => $status,
                    'geocoded_at' => now(),
                ],
            );
        } catch (\Throwable $e) {
            Log::warning('Postal code geocoding failed', [
                'postal_code' => $postalCode,
                'message' => $e->getMessage(),
            ]);

            return $cached;
        }
    }
}
