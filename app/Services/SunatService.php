<?php

namespace App\Services;

use GuzzleHttp\Client;

class SunatService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(['timeout' => 30]);
    }

    public function consultarRUC(string $ruc)
    {
        $base = config('services.decolecta.api_ruc') ?: 'https://api.decolecta.com/v1/sunat/ruc/full';
        return $this->request("{$base}?numero={$ruc}");
    }
    public function consultarDNI(string $dni)
    {
        $base = config('services.decolecta.api_dni') ?: 'https://api.decolecta.com/v1/reniec/dni';
        return $this->request("{$base}?numero={$dni}");
    }
    public function consultarTC(string $fecha)
    {
        $base = config('services.decolecta.api_tc') ?: 'https://api.decolecta.com/v1/tipo-cambio/sunat';
        return $this->request("{$base}?date={$fecha}");
    }

    private function request(string $url): array
    {
        try {
            $token = config('services.decolecta.api_token');
            $response = $this->client->get($url, [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json',
                ]
            ]);

            return [
                'success' => true,
                'data' => json_decode($response->getBody(), true)
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
