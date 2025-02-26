<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

if (!function_exists('EbayCredentials')) {
    /**
     * Retrieve eBay credentials as an array from the database.
     *
     * @return array|null Credentials array or null if not found.
     */
    function EbayCredentials()
    {
        try {
            $id = 3;

            $credentials = DB::table('tblapis')
                ->where('id', $id)
                ->select(['client_id', 'client_secret', 'access_token', 'refresh_token', 'expires_in'])
                ->first();

            if (!$credentials) {
                Log::error("No keys found for the given client ID: {$id}");
                return [];
            }

            return (array) $credentials;
        } catch (\Exception $e) {
            Log::error("Error retrieving credentials: " . $e->getMessage());
            return [];
        }
    }

}

/**
 * Retrieve an access token using the authorization code.
 *
 * @param string $authorizationCode
 * @return string|null Access token or null if an error occurs.
 */

if (!function_exists('EbayCredentials')) {
    function getAccessToken($authorizationCode)
    {
        // Hardcoded URLs
        $tokenUrl = 'https://api.ebay.com/identity/v1/oauth2/token';
        $redirectUri = 'https://test.tecniquality.com/apis/ebay-callback';

        // Retrieve credentials
        $credentials = EbayCredentials();

        if (!$credentials) {
            Log::error('Failed to retrieve credentials for token request.');
            return null;
        }

        // Prepare request data
        $data = [
            'grant_type' => 'authorization_code',
            'code' => $authorizationCode,
            'redirect_uri' => $redirectUri,
        ];

        // Generate Basic Auth header
        $authHeader = base64_encode("{$credentials['client_id']}:{$credentials['client_secret']}");

        try {
            // Send the POST request to obtain the access token
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $authHeader,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->asForm()->post($tokenUrl, $data);

            $results = $response->json();

            if ($response->successful() && isset($results['access_token'], $results['refresh_token'])) {
                // Save tokens to the database
                saveTokens($results);
                return $results['access_token'];
            } else {
                Log::error("Error obtaining access token: " . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error("Error during token request: " . $e->getMessage());
            return null;
        }
    }
}


/**
 * Save the access and refresh tokens to the database.
 *
 * @param array $tokens
 * @return void
 */

if (!function_exists('EbayCredentials')) {

    function saveTokens(array $tokens)
    {
        try {
            DB::table('tblapis')
                ->where('id', 3)
                ->update([
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'expires_in' => $tokens['expires_in'],
                    'updated_at' => now(),
                ]);
        } catch (\Exception $e) {
            Log::error("Error saving tokens: " . $e->getMessage());
        }
    }

}

if (!function_exists('refreshEbayAccessToken')) {
    function refreshEbayAccessToken($credentials)
    {
        // Fetch API credentials from the database
        $apiRecord = DB::table('tblapis')->where('api_name', 'EBAY')->first();
    
        if (!$apiRecord || !$apiRecord->refresh_token) {
            Log::error("Ebay OAuth Error: No refresh token found in tblapis.");
            return null;
        }
    
        $clientId = $credentials['client_id'];
        $clientSecret = $credentials['client_secret'];
        $tokenUrl = 'https://api.ebay.com/identity/v1/oauth2/token';
    
        try {
            // Send request to get new access token
            $response = Http::asForm()->withHeaders([
                'Authorization' => 'Basic ' . base64_encode("{$clientId}:{$clientSecret}"),
            ])->post($tokenUrl, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $apiRecord->refresh_token,
                'scope' => implode(' ', [
                    'https://api.ebay.com/oauth/api_scope',
                    'https://api.ebay.com/oauth/api_scope/sell.marketing.readonly',
                    'https://api.ebay.com/oauth/api_scope/sell.inventory.readonly',
                    'https://api.ebay.com/oauth/api_scope/sell.account.readonly',
                    'https://api.ebay.com/oauth/api_scope/sell.fulfillment.readonly',
                ]),
            ]);
    
            $results = $response->json();
    
            if ($response->successful() && isset($results['access_token'])) {
                $newAccessToken = $results['access_token'];
                $expiresIn = $results['expires_in'] ?? '';
                $refreshTokenExpiresIn = $results['refresh_token_expires_in'] ?? '';
    
                // Update tblapis with the new access token
                DB::table('tblapis')
                    ->where('api_name', 'EBAY')
                    ->update([
                        'access_token' => $newAccessToken,
                        'updated_at' => now(),
                    ]);

                // Save the access token to a file
                $filePath = "/home/u298641722/public_html/ims/Admin/modules/orders/tokens.json";
                
                try {
                    file_put_contents($filePath, json_encode([
                        'access_token' => $newAccessToken,
                        'expires_in' => $expiresIn,
                        'refresh_token' => $apiRecord->refresh_token,
                        'refresh_token_expires_in' => $refreshTokenExpiresIn,
                        'token_type' => 'User Access Token',
                        'expiration_time' => time() + $expiresIn,
                    ], JSON_PRETTY_PRINT));
                } catch (\Exception $e) {
                    Log::error("Failed to save tokens.json: " . $e->getMessage());
                }

                return $newAccessToken;
            } else {
                Log::error("Ebay OAuth Token Refresh Failed", ['response' => $results]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error("Ebay OAuth Error: " . $e->getMessage());
            return null;
        }
    }
}