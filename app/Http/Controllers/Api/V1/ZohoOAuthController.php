<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ZohoOAuthController extends Controller
{
    public function handleCallback(Request $request): JsonResponse
    {
        $code = (string) $request->query('code', '');

        if ($code === '') {
            return response()->json([
                'success' => false,
                'message' => 'Missing authorization code.',
            ], 422);
        }

        $response = Http::asForm()->post('https://accounts.zoho.in/oauth/v2/token', [
            'grant_type' => 'authorization_code',
            'client_id' => (string) env('ZOHO_CLIENT_ID'),
            'client_secret' => (string) env('ZOHO_CLIENT_SECRET'),
            'redirect_uri' => 'https://peersunity.com/api/v1/zoho/callback',
            'code' => $code,
        ]);

        if (! $response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to exchange authorization code for tokens.',
                'zoho_response' => $response->json(),
            ], $response->status());
        }

        $zohoPayload = $response->json();

        return response()->json([
            'success' => true,
            'zoho_response' => [
                'access_token' => $zohoPayload['access_token'] ?? null,
                'refresh_token' => $zohoPayload['refresh_token'] ?? null,
                'expires_in' => $zohoPayload['expires_in'] ?? null,
            ],
        ]);
    }
}
