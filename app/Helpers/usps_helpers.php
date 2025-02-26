<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

//$credentials = getUSPSCredentials($Connect);

if (!function_exists('USPSCredentials')) {
    /**
     * Retrieve AWS credentials for a given store.
     *
     * @param string $store The store identifier.
     * @return object|null Credentials object or null if not found.
     */
    function USPSCredentials()
    {
        try {
            // retrieve USPS credentials
            $id = 1;

            $credentials = (array) DB::table('tblapis')->where('id', $id)->first();

            if (!$credentials) {
                Log::error("No keys found for the given client ID: {$id}");
                return null;
            }

            return $credentials;
        } catch (\Exception $e) {
            Log::error("Error retrieving credentials: " . $e->getMessage());
            return null;
        }
    }
}







