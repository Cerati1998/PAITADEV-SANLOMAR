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
        $url = env('API_RUC') . "?numero={$ruc}" ?? "https://api.decolecta.com/v1/sunat/ruc/full?numero={$ruc}";
        return $this->request($url);
    }
    public function consultarDNI(string $dni)
    {
        $url = env('API_DNI') . "?numero={$dni}" ?? "https://api.decolecta.com/v1/reniec/dni={$dni}";
        return $this->request($url);
    }
    public function consultarTC(string $fecha)
    {
        $url = env('API_TC') . "?date={$fecha}" ?? "https://api.apis.net.pe/v1/tipo-cambio-sunat?fecha={$fecha}";
        return $this->request($url);
    }

    private function request(string $url): array
    {
        try {
            $token = env('API_TOKEN');
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
