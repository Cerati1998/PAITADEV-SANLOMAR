<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ExternalDispatchApiService
{
    protected $client;
    protected $baseUrl;
    protected $bearerToken;

    public function __construct()
    {
        $this->client = new Client(['timeout' => 30]);
        $this->baseUrl = env('EXTERNAL_DISPATCH_API_URL'); // Solo la base URL
        $this->bearerToken = env('EXTERNAL_DISPATCH_API_TOKEN');
    }

    /**
     * Obtener contenedores despachados por semana
     */
    public function getContainersByWeek(int $week): array
    {
        // Construir la URL completa con el parámetro week
        $url = "{$this->baseUrl}/SIGLA/controller/api.php?op=GetPerWeek&week={$week}";
        
        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'Authorization' => "Bearer {$this->bearerToken}",
                    'Accept' => 'application/json',
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'data' => $data ?? []
            ];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Error al consultar API de despachos', [
                'url' => $url,
                'week' => $week,
                'message' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ];
        } catch (\Throwable $e) {
            Log::error('Error inesperado en API de despachos', [
                'url' => $url,
                'week' => $week,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ];
        }
    }
}