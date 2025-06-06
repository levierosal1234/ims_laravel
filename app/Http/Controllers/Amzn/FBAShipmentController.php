<?php

namespace App\Http\Controllers\Amzn;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

require base_path('app/Helpers/aws_helpers.php');

class FBAShipmentController extends Controller
{
    public function addItemToShipment(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'shipmentID' => 'required|string',
                'product' => 'required|array'
            ]);

            // Get the authenticated user
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $user = User::find($user->id)->fresh(); // ensure fresh data
            $username = $user->name ?? 'System';

            $shipmentID = $request->shipmentID;
            $product = $request->product;

            // Get dateshipped from existing shipment
            $dateshipped = DB::table('tblfbashipmenthistory')
                ->where('shipmentID', $shipmentID)
                ->value('dateshipped');

            if (!$dateshipped) {
                return response()->json(['error' => 'Invalid shipment ID or no shipment date found.'], 404);
            }

            // Insert the product into the shipment
            DB::table('tblfbashipmenthistory')->insert([
                'ProductName' => $product['ProductTitle'] ?? '',
                'ASIN' => $product['ASINviewer'] ?? '',
                'FNSKU' => $product['FNSKUviewer'] ?? '',
                'MSKU' => $product['MSKUviewer'] ?? '',
                'Serialnumber' => $product['serialnumber'] ?? '',
                'shipmentID' => $shipmentID,
                'dateshipped' => $dateshipped,
                'Location' => 'SHIPMENT',
                'store' => 'Renovar Tech',
                'row_show' => 1,
                'processby' => $username
            ]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Failed to add item to shipment', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json(['error' => 'Failed to add item to shipment.'], 500);
        }
    }

    public function deleteShipmentItem(Request $request)
    {
        $request->validate([
            'ID' => 'required|integer',
        ]);

        DB::table('tblfbashipmenthistory')
            ->where('ID', $request->ID)
            ->delete();

        return response()->json(['message' => 'Item removed from shipment.']);
    }

    public function fetch_shipment(Request $request)
    {
        $shipments = DB::table('tblfbashipmenthistory')
            ->select(
                'shipmentID',
                DB::raw('MAX(dateshipped) as latest_shipped'),
                DB::raw('MAX(store) as store'),
                DB::raw('COUNT(*) as item_count')
            )
            ->where('row_show', 1)
            ->whereNotNull('shipmentID')
            ->groupBy('shipmentID')
            ->orderByDesc('latest_shipped')
            ->get();

        // Attach items to each shipment
        foreach ($shipments as $shipment) {
            $shipment->items = DB::table('tblfbashipmenthistory')
                ->where('shipmentID', $shipment->shipmentID)
                ->where('row_show', 1)
                ->get([
                    'ID',
                    'ProductName',
                    'ASIN',
                    'FNSKU',
                    'MSKU',
                    'Serialnumber',
                    'shipmentID',
                    'Location',
                    'dateshipped'
                ]);
        }

        return response()->json($shipments);
    }

    public function package_dimension_fetcher(Request $request)
    {
        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string'
        ]);

        $store = $request->input('store', 'Renovar Tech');
        $nextToken = $request->input('nextToken', null);
        $destinationMarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA17YTXZSKB');

        // Fetch ASINs from tblfbashipmenthistory where shipmentID matches
        $asins = DB::table('tblfbashipmenthistory')
            ->where('shipmentID', $shipmentID)
            ->pluck('asin'); // Fetch only the 'asin' column

        $data_additionale = [];

        // Process each ASIN
        foreach ($asins as $asin) {
            // Query tblasin for dimension data
            $asinData = DB::table('tblasin')
                ->where('asin', $asin)
                ->select(
                    'white_length',
                    'white_width',
                    'white_height',
                    'white_lbs',
                    'dimension_length',
                    'dimension_width',
                    'dimension_height',
                    'lbs'
                )
                ->first();

            // Format the data as required
            $formattedData = [
                'asin' => $asin,
                'shipmentID' => $shipmentID,
                'retail_box' => [
                    'retail_length' => $asinData ? $asinData->dimension_length : null,
                    'retail_width' => $asinData ? $asinData->dimension_width : null,
                    'retail_height' => $asinData ? $asinData->dimension_height : null,
                    'retail_lbs' => $asinData ? $asinData->lbs : null,
                ],
                'white_box' => [
                    'white_length' => $asinData ? $asinData->white_length : null,
                    'white_width' => $asinData ? $asinData->white_width : null,
                    'white_height' => $asinData ? $asinData->white_height : null,
                    'white_lbs' => $asinData ? $asinData->white_lbs : null
                ],
                'processed_at' => now(),
            ];

            // Add to results array
            $data_additionale[] = $formattedData;
        }

        return response()->json([
            'success' => true,
            'message' => 'Processed ASINs successfully',
            'data' => $data_additionale
        ]);
    }

    public function cancel_inboundplan(Request $request)
    {
        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string'
        ]);
        $data_additionale = []; // data that is to be passed to jsonCreation
        $store = $request->input('store', 'Renovar Tech');
        $nextToken = $request->input('nextToken', null);
        $destinationmarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA17YTXZSKB');
        $inboundplanid = $request->input ('inboundplanid', null);

        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans/' . $inboundplanid . '/cancellation';
        $customParams = [];

        $companydetails = $this->fetchCompanyDetails();

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Generate JSON payload
        $jsonData = $this->JsonCreation('step1', $companydetails, 'ATVPDKIKX0DER', $shipmentID, $data_additionale);

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }


        $credentials = AWSCredentials($store);
        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                'body' => $jsonData
            ]);

            // Make the HTTP request (change GET to POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->post($url);

            // Log the curl information (response details)
            $curlInfo = $response->handlerStats(); // This will give you cURL-like information

            Log::info('Curl Info:', $curlInfo);

            if ($response->successful()) {
                $data = $response->json(); // Parse JSON response

                $inboundplanid = $data['inboundPlanId'] ?? null;

                // ✅ Insert into tblfbainboundplans if inboundPlanId exists
                if ($inboundplanid) {
                    DB::table('tblfbainboundplans')->insert([
                        'shipmentID' => $shipmentID,
                        'inboundplanid' => $inboundplanid,
                        'store' => $store,
                        'destinationMarketplaceID' => $destinationmarketplace,
                        'created_time' => now(),
                        'updated_time' => now()
                    ]);
                }

                // Extract operationId
                $operationId = $data['operationId'] ?? null;

                // If operationId exists, call getOperationStatus()
                if ($operationId) {
                    Log::info("Tracking operation: {$operationId}");

                    // Call the operation status function
                    $operationStatusResponse = $this->getOperationStatus($store, $destinationmarketplace, $operationId);

                    // Return the operation response
                    return response()->json([
                        'success' => true,
                        'operationId' => $operationId,
                        'data' => $data,
                        'operationStatus' => $operationStatusResponse->getData(true), // Get operation tracking response
                        'logs' => $curlInfo,
                    ]);
                }

                // If no operationId, return success response but indicate missing operation tracking
                return response()->json([
                    'success' => true,
                    'message' => 'Operation initiated but no operationId returned.',
                    'data' => $data,
                    'logs' => $curlInfo,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but error.',
                'headers' => $headers,
                'error' => $response->json(),
                'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }

        // prodduces inboundplanid
    }

    /*
        public function package_dimension_fetcher(Request $request)
        {
            $request->validate([
                'store' => 'nullable|string',
                'destinationMarketplace' => 'nullable|string',
                'nextToken' => 'nullable|string',
                'shipmentID' => 'nullable|string'
            ]);

            $store = $request->input('store', 'Renovar Tech');
            $nextToken = $request->input('nextToken', null);
            $destinationMarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
            $shipmentID = $request->input('shipmentID', 'FBA17YTXZSKB');

            // Fetch ASINs from tblshiphistory where shipmentID matches
            $asins = DB::table('tblshiphistory')
                ->where('shipmentID', $shipmentID)
                ->pluck('asin'); // Fetch only the 'asin' column

            $data_additionale = [];

            // Process each ASIN
            foreach ($asins as $asin) {
                // Call amazon_catalog_asin and pass the asin
                $data = $this->amazon_catalog_asin($asin, $store, $destinationMarketplace, $shipmentID);

                // Collect the results
                $data_additionale[] = [
                    'asin' => $asin,
                    'shipmentID' => $shipmentID,
                    'data' => $data, // Store returned data
                    'processed_at' => now(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Processed ASINs successfully',
                'data' => $data_additionale
            ]);
        }
    */
    public function fetchinboundplans(Request $request)
    {
        $shipmentID = $request->input('shipmentID');
    
        $plans = DB::table('tblfbainboundplans')
            ->where('shipmentID', $shipmentID)
            ->get();
    
        return response()->json([
            'success' => true,
            'message' => '✅ Fetched inbound plans.',
            'data' => $plans
        ]);
    }
    
    public function amazon_catalog_asin($asin, $store, $destinationmarketplace)
    {

        $data_additionale = []; // data that is to be passed to jsonCreation
        $nextToken = null;

        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans';

        $customParams = [
            'marketplaceIds' => $destinationmarketplace,
            'includedData' => "attributes,classifications,dimensions,identifiers,images,productTypes,salesRanks,summaries,relationships,vendorDetails"
        ];

        $companydetails = $this->fetchCompanyDetails();

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Generate JSON payload
        $jsonData = [];

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }


        $credentials = AWSCredentials($store);
        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}{$asin}{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                'body' => $jsonData
            ]);

            // Make the HTTP request (change GET to POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->get($url);

            // Log the curl information (response details)
            $curlInfo = $response->handlerStats(); // This will give you cURL-like information

            Log::info('Curl Info:', $curlInfo);

            // Return the response with logs included
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json(),
                    'logs' => $curlInfo, // Add the log details here
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but error.',
                'headers' => $headers,
                'error' => $response->json(),
                // 'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }

        // prodduces inboundplanid
    }

    public function step1_createShipment(Request $request)
    {
        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string'
        ]);
        $data_additionale = []; // data that is to be passed to jsonCreation
        $store = $request->input('store', 'Renovar Tech');
        $nextToken = $request->input('nextToken', null);
        $destinationmarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA17YTXZSKB');

        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans';
        $customParams = [];

        $companydetails = $this->fetchCompanyDetails();

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Generate JSON payload
        $jsonData = $this->JsonCreation('step1', $companydetails, 'ATVPDKIKX0DER', $shipmentID, $data_additionale);

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }


        $credentials = AWSCredentials($store);
        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                'body' => $jsonData
            ]);

            // Make the HTTP request (change GET to POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->post($url);

            // Log the curl information (response details)
            $curlInfo = $response->handlerStats(); // This will give you cURL-like information

            Log::info('Curl Info:', $curlInfo);

            if ($response->successful()) {
                $data = $response->json(); // Parse JSON response

                $inboundplanid = $data['inboundPlanId'] ?? null;

                // ✅ Insert into tblfbainboundplans if inboundPlanId exists
                if ($inboundplanid) {
                    DB::table('tblfbainboundplans')->insert([
                        'shipmentID' => $shipmentID,
                        'inboundplanid' => $inboundplanid,
                        'store' => $store,
                        'destinationMarketplaceID' => $destinationmarketplace,
                        'created_time' => now(),
                        'updated_time' => now()
                    ]);
                }

                // Extract operationId
                $operationId = $data['operationId'] ?? null;

                // If operationId exists, call getOperationStatus()
                if ($operationId) {
                    Log::info("Tracking operation: {$operationId}");

                    // Call the operation status function
                    $operationStatusResponse = $this->getOperationStatus($store, $destinationmarketplace, $operationId);

                    // Return the operation response
                    return response()->json([
                        'success' => true,
                        'operationId' => $operationId,
                        'data' => $data,
                        'operationStatus' => $operationStatusResponse->getData(true), // Get operation tracking response
                        'logs' => $curlInfo,
                    ]);
                }

                // If no operationId, return success response but indicate missing operation tracking
                return response()->json([
                    'success' => true,
                    'message' => 'Operation initiated but no operationId returned.',
                    'data' => $data,
                    'logs' => $curlInfo,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but error.',
                'headers' => $headers,
                'error' => $response->json(),
                'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }

        // prodduces inboundplanid
    }

    public function step2a_generate_packing(Request $request)
    {
        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string',
            'inboundplanid' => 'nullable|string'
        ]);
        $data_additionale = []; // data that is to be passed to jsonCreation
        $store = $request->input('store', 'Renovar Tech');
        $nextToken = $request->input('nextToken', null);
        $destinationmarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA17YTXZSKB');
        $inboundplanid = $request->input('inboundplanid', 'wfcef22641-f04e-414d-ae7a-17c8d29caf61');


        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans/' . $inboundplanid . '/packingOptions';
        $customParams = [];

        $companydetails = $this->fetchCompanyDetails();

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Generate JSON payload
        $jsonData = $this->JsonCreation('step2a', $companydetails, 'ATVPDKIKX0DER', $shipmentID, $data_additionale);

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }


        $credentials = AWSCredentials($store);
        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                'body' => json_decode($jsonData, true) // Decode before logging
            ]);

            // Make the HTTP request (POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->post($url);

            // Log the cURL information (response details)
            $curlInfo = $response->handlerStats();
            Log::info('Curl Info:', $curlInfo);

            // Check if request was successful
            if ($response->successful()) {
                $data = $response->json(); // Parse JSON response

                // Extract operationId
                $operationId = $data['operationId'] ?? null;

                // If operationId exists, call getOperationStatus()
                if ($operationId) {
                    Log::info("Tracking operation: {$operationId}");

                    // Call the operation status function
                    $operationStatusResponse = $this->getOperationStatus($store, $destinationmarketplace, $operationId);

                    // Return the operation response
                    return response()->json([
                        'success' => true,
                        'operationId' => $operationId,
                        'data' => $data,
                        'operationStatus' => $operationStatusResponse->getData(true), // Get operation tracking response
                        'logs' => $curlInfo,
                    ]);
                }

                // If no operationId, return success response but indicate missing operation tracking
                return response()->json([
                    'success' => true,
                    'message' => 'Operation initiated but no operationId returned.',
                    'data' => $data,
                    'logs' => $curlInfo,
                ]);
            }

            // If request failed
            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but API returned an error.',
                'headers' => $headers,
                'error' => $response->json(),
                'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }
    }

    public function step2b_list_packing_options(Request $request)
    {
        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string'
        ]);
        $data_additionale = []; // data that is to be passed to jsonCreation
        $store = $request->input('store', 'Renovar Tech');
        $nextToken = $request->input('nextToken', null);
        $destinationmarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA17YTXZSKB');
        $inboundplanid = $request->input('inboundplanid', 'wfbf5acd47-f457-482c-a27a-2ceecca234f1');


        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans/' . $inboundplanid . '/packingOptions';
        $customParams = [];

        $companydetails = $this->fetchCompanyDetails();

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Generate JSON payload
        $jsonData = $this->JsonCreation('step2a', $companydetails, 'ATVPDKIKX0DER', $shipmentID, $data_additionale);

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }


        $credentials = AWSCredentials($store);
        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                // 'body' => json_decode($jsonData, true) // Decode before logging
            ]);

            // Make the HTTP request (POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                // ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->get($url);

            // Log the cURL information (response details)
            $curlInfo = $response->handlerStats();
            Log::info('Curl Info:', $curlInfo);

            // Check if request was successful
            if ($response->successful()) {
                $data = $response->json(); // Parse JSON response

                // Extract operationId
                $operationId = $data['operationId'] ?? null;

                // If operationId exists, call getOperationStatus()
                if ($operationId) {
                    Log::info("Tracking operation: {$operationId}");

                    // Call the operation status function
                    $operationStatusResponse = $this->getOperationStatus($store, $destinationmarketplace, $operationId);

                    // Return the operation response
                    return response()->json([
                        'success' => true,
                        'operationId' => $operationId,
                        'data' => $data,
                        'operationStatus' => $operationStatusResponse->getData(true), // Get operation tracking response
                        'logs' => $curlInfo,
                    ]);
                }

                // If no operationId, return success response but indicate missing operation tracking
                return response()->json([
                    'success' => true,
                    'message' => 'Operation Step 2b Success.',
                    'data' => $data,
                    'logs' => $curlInfo,
                ]);
            }

            // If request failed
            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but API returned an error.',
                'headers' => $headers,
                'error' => $response->json(),
                'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }
    }

    public function step2c_list_items_by_packing_options(Request $request)
    {
        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string'
        ]);
        $data_additionale = []; // data that is to be passed to jsonCreation
        $store = $request->input('store', 'Renovar Tech');
        $nextToken = $request->input('nextToken', null);
        $destinationmarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA17YTXZSKB');
        $inboundplanid = $request->input('inboundplanid', 'wfbf5acd47-f457-482c-a27a-2ceecca234f1');
        $packingGroupId = $request->input('packingGroupId', 'pg81f6f672-a181-4a8b-9e8b-f57f552cfc01');
        $packingOptionId = $request->input('packingOptionId', 'poe99bf0d7-171b-414b-a350-02d4ed88c348'); // from process 2b

        DB::table('tblfbainboundplans')
            ->where('inboundplanid', $inboundplanid)
            ->where('shipmentID', $shipmentID)
            ->update([
                'packingGroupId' => $packingGroupId,
                'packingOptionId' => $packingOptionId,
                'updated_time' => now() // update timestamp
            ]);


        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans/' . $inboundplanid . '/packingGroups/' . $packingGroupId . '/items';
        $customParams = [];

        $companydetails = $this->fetchCompanyDetails();

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Generate JSON payload
        $jsonData = $this->JsonCreation('step2a', $companydetails, 'ATVPDKIKX0DER', $shipmentID, $data_additionale);

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }


        $credentials = AWSCredentials($store);
        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                // 'body' => json_decode($jsonData, true) // Decode before logging
            ]);

            // Make the HTTP request (POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                // ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->get($url);

            // Log the cURL information (response details)
            $curlInfo = $response->handlerStats();
            Log::info('Curl Info:', $curlInfo);

            // Check if request was successful
            if ($response->successful()) {
                $data = $response->json(); // Parse JSON response

                // Extract operationId
                $operationId = $data['operationId'] ?? null;

                // If operationId exists, call getOperationStatus()
                if ($operationId) {
                    Log::info("Tracking operation: {$operationId}");

                    // Call the operation status function
                    $operationStatusResponse = $this->getOperationStatus($store, $destinationmarketplace, $operationId);

                    // Return the operation response
                    return response()->json([
                        'success' => true,
                        'operationId' => $operationId,
                        'data' => $data,
                        'operationStatus' => $operationStatusResponse->getData(true), // Get operation tracking response
                        'logs' => $curlInfo,
                    ]);
                }

                // If no operationId, return success response but indicate missing operation tracking
                return response()->json([
                    'success' => true,
                    'message' => 'Operation Step 2c Success.',
                    'data' => $data,
                    'logs' => $curlInfo,
                ]);
            }

            // If request failed
            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but API returned an error.',
                'headers' => $headers,
                'error' => $response->json(),
                'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }
    }

    public function step2d_confirm_packing_option(Request $request)
    {
        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string'
        ]);
        $data_additionale = []; // data that is to be passed to jsonCreation
        $store = $request->input('store', 'Renovar Tech');
        $nextToken = $request->input('nextToken', null);
        $destinationmarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA17YTXZSKB');
        $inboundplanid = $request->input('inboundplanid', 'wfbf5acd47-f457-482c-a27a-2ceecca234f1');// from process 1
        $packingGroupId = $request->input('packingGroupId', 'pg81f6f672-a181-4a8b-9e8b-f57f552cfc01');// from process 2b
        $packingOptionId = $request->input('packingOptionId', 'poe99bf0d7-171b-414b-a350-02d4ed88c348'); // from process 2b


        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans/' . $inboundplanid . '/packingOptions/' . $packingOptionId . '/confirmation';
        $customParams = [];

        $companydetails = $this->fetchCompanyDetails();

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Generate JSON payload
        $jsonData = $this->JsonCreation('step2a', $companydetails, 'ATVPDKIKX0DER', $shipmentID, $data_additionale);

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }


        $credentials = AWSCredentials($store);
        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                // 'body' => json_decode($jsonData, true) // Decode before logging
            ]);

            // Make the HTTP request (POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                // ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->post($url);

            // Log the cURL information (response details)
            $curlInfo = $response->handlerStats();
            Log::info('Curl Info:', $curlInfo);

            // Check if request was successful
            if ($response->successful()) {
                $data = $response->json(); // Parse JSON response

                // Extract operationId
                $operationId = $data['operationId'] ?? null;

                // If operationId exists, call getOperationStatus()
                if ($operationId) {
                    Log::info("Tracking operation: {$operationId}");

                    // Call the operation status function
                    $operationStatusResponse = $this->getOperationStatus($store, $destinationmarketplace, $operationId);

                    // Return the operation response
                    return response()->json([
                        'success' => true,
                        'operationId' => $operationId,
                        'data' => $data,
                        'operationStatus' => $operationStatusResponse->getData(true), // Get operation tracking response
                        'logs' => $curlInfo,
                    ]);
                }

                // If no operationId, return success response but indicate missing operation tracking
                return response()->json([
                    'success' => true,
                    'message' => 'Operation Step 2d Success.',
                    'data' => $data,
                    'logs' => $curlInfo,
                ]);
            }

            // If request failed
            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but API returned an error.',
                'headers' => $headers,
                'error' => $response->json(),
                'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }
    }

    public function step3a_packing_information(Request $request)
    {
        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string'
        ]);
        $data_additionale = []; // data that is to be passed to jsonCreation
        $store = $request->input('store', 'Renovar Tech');
        $nextToken = $request->input('nextToken', null);
        $destinationmarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA17YTXZSKB');
        $inboundplanid = $request->input('inboundplanid', 'wfbf5acd47-f457-482c-a27a-2ceecca234f1');// from process 1
        $packingGroupId = $request->input('packingGroupId', 'pg81f6f672-a181-4a8b-9e8b-f57f552cfc01');// from process 2b
        $packingOptionId = $request->input('packingOptionId', 'poe99bf0d7-171b-414b-a350-02d4ed88c348'); // from process 2b


        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans/' . $inboundplanid . '/packingInformation/';
        $customParams = [];

        $companydetails = $this->fetchCompanyDetails();

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }
        $data_additionale['packingGroupId'] = $packingGroupId;
        // Generate JSON payload
        $jsonData = $this->JsonCreation('step3a', $companydetails, 'ATVPDKIKX0DER', $shipmentID, $data_additionale);

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }


        $credentials = AWSCredentials($store);
        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                'body' => json_decode($jsonData, true) // Decode before logging
            ]);

            // Make the HTTP request (POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->post($url);

            // Log the cURL information (response details)
            $curlInfo = $response->handlerStats();
            Log::info('Curl Info:', $curlInfo);

            // Check if request was successful
            if ($response->successful()) {
                $data = $response->json(); // Parse JSON response

                // Extract operationId
                $operationId = $data['operationId'] ?? null;

                // If operationId exists, call getOperationStatus()
                if ($operationId) {
                    Log::info("Tracking operation: {$operationId}");

                    // Call the operation status function
                    $operationStatusResponse = $this->getOperationStatus($store, $destinationmarketplace, $operationId);

                    // Return the operation response
                    return response()->json([
                        'success' => true,
                        'operationId' => $operationId,
                        'data' => $data,
                        'operationStatus' => $operationStatusResponse->getData(true), // Get operation tracking response
                        'logs' => $curlInfo,
                    ]);
                }

                // If no operationId, return success response but indicate missing operation tracking
                return response()->json([
                    'success' => true,
                    'message' => 'Operation Step 3a Success.',
                    'data' => $data,
                    'logs' => $curlInfo,
                ]);
            }

            // If request failed
            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but API returned an error.',
                'headers' => $headers,
                'error' => $response->json(),
                'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }
    }

    public function step4a_placement_option(Request $request)
    {
        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string'
        ]);
        $data_additionale = []; // data that is to be passed to jsonCreation
        $store = $request->input('store', 'Renovar Tech');
        $nextToken = $request->input('nextToken', null);
        $destinationmarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA17YTXZSKB');
        $inboundplanid = $request->input('inboundplanid', 'wfbf5acd47-f457-482c-a27a-2ceecca234f1');// from process 1
        $packingGroupId = $request->input('packingGroupId', 'pg81f6f672-a181-4a8b-9e8b-f57f552cfc01');// from process 2b
        $packingOptionId = $request->input('packingOptionId', 'pgfadeaafb-3918-48d2-8f32-13a48dc9f69e'); // from process 2b


        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans/' . $inboundplanid . '/placementOptions';

        $customParams = [];

        $companydetails = $this->fetchCompanyDetails();

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Generate JSON payload
        $jsonData = $this->JsonCreation('step4a', null, 'ATVPDKIKX0DER', null, $data_additionale);

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }

        $credentials = AWSCredentials($store);

        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                // 'body' => json_decode($jsonData, true) // Decode before logging
            ]);

            // Make the HTTP request (POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                // ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->post($url);

            // Log the cURL information (response details)
            $curlInfo = $response->handlerStats();
            Log::info('Curl Info:', $curlInfo);

            // Check if request was successful
            if ($response->successful()) {
                $data = $response->json(); // Parse JSON response

                // Extract operationId
                $operationId = $data['operationId'] ?? null;

                // If operationId exists, call getOperationStatus()
                if ($operationId) {
                    Log::info("Tracking operation: {$operationId}");

                    // Call the operation status function
                    $operationStatusResponse = $this->getOperationStatus($store, $destinationmarketplace, $operationId);

                    // Return the operation response
                    return response()->json([
                        'success' => true,
                        'operationId' => $operationId,
                        'data' => $data,
                        'operationStatus' => $operationStatusResponse->getData(true), // Get operation tracking response
                        'logs' => $curlInfo,
                    ]);
                }

                // If no operationId, return success response but indicate missing operation tracking
                return response()->json([
                    'success' => true,
                    'message' => 'Operation Step 4a Success.',
                    'data' => $data,
                    'logs' => $curlInfo,
                ]);
            }

            // If request failed
            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but API returned an error.',
                'headers' => $headers,
                'error' => $response->json(),
                'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }
    }

    public function step4b_list_placement_option(Request $request)
    {
        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string'
        ]);
        $data_additionale = []; // data that is to be passed to jsonCreation
        $store = $request->input('store', 'All Renewed');
        $nextToken = $request->input('nextToken', null);
        $destinationmarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA4EA5THYYCU');
        $inboundplanid = $request->input('inboundplanid', 'wfd8036e16-c026-46bf-a372-63cf7e9607fb');// from process 1
        $packingGroupId = $request->input('packingGroupId', 'pg81f6f672-a181-4a8b-9e8b-f57f552cfc01');// from process 2b
        $packingOptionId = $request->input('packingOptionId', 'pgfadeaafb-3918-48d2-8f32-13a48dc9f69e'); // from process 2b


        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans/' . $inboundplanid . '/placementOptions';

        $customParams = [];

        $companydetails = $this->fetchCompanyDetails();

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Generate JSON payload
        $jsonData = $this->JsonCreation('step4a', null, 'ATVPDKIKX0DER', null, $data_additionale);

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }

        $credentials = AWSCredentials($store);

        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                // 'body' => json_decode($jsonData, true) // Decode before logging
            ]);

            // Make the HTTP request (POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                // ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->get($url);

            // Log the cURL information (response details)
            $curlInfo = $response->handlerStats();
            Log::info('Curl Info:', $curlInfo);

            // Check if request was successful
            if ($response->successful()) {
                $data = $response->json(); // Parse JSON response

                // Extract operationId
                $operationId = $data['operationId'] ?? null;

                // If operationId exists, call getOperationStatus()
                if ($operationId) {
                    Log::info("Tracking operation: {$operationId}");

                    // Call the operation status function
                    $operationStatusResponse = $this->getOperationStatus($store, $destinationmarketplace, $operationId);

                    // Return the operation response
                    return response()->json([
                        'success' => true,
                        'operationId' => $operationId,
                        'data' => $data,
                        'operationStatus' => $operationStatusResponse->getData(true), // Get operation tracking response
                        'logs' => $curlInfo,
                    ]);
                }

                // If no operationId, return success response but indicate missing operation tracking
                return response()->json([
                    'success' => true,
                    'message' => 'Operation Step 4b Success.',
                    'data' => $data,
                    'logs' => $curlInfo,
                ]);
            }

            // If request failed
            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but API returned an error.',
                'headers' => $headers,
                'error' => $response->json(),
                'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }
    }

    public function step4c_get_shipment(Request $request)
    {
        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string'
        ]);
        $data_additionale = []; // data that is to be passed to jsonCreation
        $store = $request->input('store', 'All Renewed');
        $nextToken = $request->input('nextToken', null);
        $destinationmarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA4EA5THYYCU');
        $inboundplanid = $request->input('inboundplanid', 'wfd8036e16-c026-46bf-a372-63cf7e9607fb');// from process 1
        $packingGroupId = $request->input('packingGroupId', 'pg81f6f672-a181-4a8b-9e8b-f57f552cfc01');// from process 2b
        $packingOptionId = $request->input('packingOptionId', 'pgfadeaafb-3918-48d2-8f32-13a48dc9f69e'); // from process 2b
        $shipmentIdfromAPI = $request->input('shipmentidfromapi', 'sh82013eed-8bd2-4642-aaae-80e7177e4d31');

        DB::table('tblfbainboundplans')
        ->where('inboundplanid', $inboundplanid)
        ->where('shipmentID', $shipmentID)
        ->update([
            'shipmentidfromapi' => $shipmentIdfromAPI,
            'updated_time' => now() // update timestamp
        ]);


        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans/' . $inboundplanid . '/shipments/' . $shipmentIdfromAPI;

        $customParams = [];

        $companydetails = $this->fetchCompanyDetails();

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Generate JSON payload
        $jsonData = $this->JsonCreation('step4a', null, 'ATVPDKIKX0DER', null, $data_additionale);

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }

        $credentials = AWSCredentials($store);

        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                // 'body' => json_decode($jsonData, true) // Decode before logging
            ]);

            // Make the HTTP request (POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                // ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->get($url);

            // Log the cURL information (response details)
            $curlInfo = $response->handlerStats();
            Log::info('Curl Info:', $curlInfo);

            // Check if request was successful
            if ($response->successful()) {
                $data = $response->json(); // Parse JSON response

                // Extract operationId
                $operationId = $data['operationId'] ?? null;

                // If operationId exists, call getOperationStatus()
                if ($operationId) {
                    Log::info("Tracking operation: {$operationId}");

                    // Call the operation status function
                    $operationStatusResponse = $this->getOperationStatus($store, $destinationmarketplace, $operationId);

                    // Return the operation response
                    return response()->json([
                        'success' => true,
                        'operationId' => $operationId,
                        'data' => $data,
                        'operationStatus' => $operationStatusResponse->getData(true), // Get operation tracking response
                        'logs' => $curlInfo,
                    ]);
                }

                // If no operationId, return success response but indicate missing operation tracking
                return response()->json([
                    'success' => true,
                    'message' => 'Operation Step 4c Success.',
                    'data' => $data,
                    'logs' => $curlInfo,
                ]);
            }

            // If request failed
            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but API returned an error.',
                'headers' => $headers,
                'error' => $response->json(),
                'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }
    }

    public function step5a_transportation_options(Request $request)
    {
        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string'
        ]);
        $data_additionale = []; // data that is to be passed to jsonCreation
        $store = $request->input('store', 'All Renewed');
        $nextToken = $request->input('nextToken', null);
        $destinationmarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA4EA5THYYCU');
        $inboundplanid = $request->input('inboundplanid', 'wfd8036e16-c026-46bf-a372-63cf7e9607fb');// from process 1
        $packingGroupId = $request->input('packingGroupId', 'pg81f6f672-a181-4a8b-9e8b-f57f552cfc01');// from process 2b
        $packingOptionId = $request->input('packingOptionId', 'pgfadeaafb-3918-48d2-8f32-13a48dc9f69e');
        $shipDate = $request->input('shipDate', null);
        $packageWeight = $request->input('packageWeight', null);
        $packageLength = $request->input('packageLength', null);
        $packageWidth = $request->input('packageWidth', null);
        $packageHeight = $request->input('packageHeight', null);
        $totalDeclaredValue = $request->input('totalDeclaredValue', null);
        $shipmentidfromapi = $request->input('shipmentidfromapi', null);
        $placementOptionId = $request->input('placementOptionId', null);

        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans/' . $inboundplanid . '/transportationOptions';

        $customParams = [];

        $parsedDate = Carbon::parse($shipDate)->setTimezone('UTC');
        $data_additionale['shipDate'] = $parsedDate->format('Y-m-d\TH:i:s\Z');

        $data_additionale['placementOptionId'] = $placementOptionId;
        $data_additionale['packageWeight'] = $packageWeight;
        $data_additionale['packageLength'] = $packageLength;
        $data_additionale['packageWidth'] = $packageWidth;
        $data_additionale['packageHeight'] = $packageHeight;
        $data_additionale['totalDeclaredValue'] = $totalDeclaredValue;
        $data_additionale['shipmentidfromapi'] = $shipmentidfromapi;

        DB::table('tblfbainboundplans')
        ->where('inboundplanid', $inboundplanid)
        ->where('shipmentID', $shipmentID)
        ->update([
            'placementOptionId' => $placementOptionId,
            'updated_time' => now() // update timestamp
        ]);

        $companydetails = $this->fetchCompanyDetails();

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Generate JSON payload
        $jsonData = $this->JsonCreation('step5a', $companydetails, 'ATVPDKIKX0DER', null, $data_additionale);

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }

        $credentials = AWSCredentials($store);

        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                'body' => json_decode($jsonData, true) // Decode before logging
            ]);

            // Make the HTTP request (POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->post($url);

            // Log the cURL information (response details)
            $curlInfo = $response->handlerStats();
            Log::info('Curl Info:', $curlInfo);

            if ($response->successful()) {
                $data = $response->json();

                // Extract operationId
                $operationId = $data['operationId'] ?? null;

                // If operationId exists, call getOperationStatus()
                if ($operationId) {
                    Log::info("Tracking operation: {$operationId}");

                    // Call the operation status function
                    $operationStatusResponse = $this->getOperationStatus($store, $destinationmarketplace, $operationId);

                    // Return the operation response
                    return response()->json([
                        'success' => true,
                        'operationId' => $operationId,
                        'data' => $data,
                        'operationStatus' => $operationStatusResponse->getData(true), // Get operation tracking response
                        'logs' => $curlInfo,
                    ]);
                }

                // If no operationId, return success response but indicate missing operation tracking
                return response()->json([
                    'success' => true,
                    'message' => 'Operation Step 5a Success.',
                    'data' => $data,
                    'logs' => $curlInfo,
                ]);
            }

            // If request failed
            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but API returned an error.',
                'headers' => $headers,
                'error' => $response->json(),
                'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }
    }

    public function step5b_generate_delivery_options(Request $request)
    {
        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string'
        ]);

        $data_additionale = []; // data that is to be passed to jsonCreation
        $store = $request->input('store', 'All Renewed');
        $nextToken = $request->input('nextToken', null);
        $destinationmarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA4EA5THYYCU');
        $inboundplanid = $request->input('inboundplanid', 'wfd8036e16-c026-46bf-a372-63cf7e9607fb');// from process 1
        $packingGroupId = $request->input('packingGroupId', 'pg81f6f672-a181-4a8b-9e8b-f57f552cfc01');// from process 2b
        $packingOptionId = $request->input('packingOptionId', 'pgfadeaafb-3918-48d2-8f32-13a48dc9f69e');
        $shipDate = $request->input('shipDate', null);
        $packageWeight = $request->input('packageWeight', null);
        $packageLength = $request->input('packageLength', null);
        $packageWidth = $request->input('packageWidth', null);
        $packageHeight = $request->input('packageHeight', null);
        $totalDeclaredValue = $request->input('totalDeclaredValue', null);
        $shipmentidfromapi = $request->input('shipmentidfromapi', null);

        DB::table('tblfbainboundplans')
        ->where('inboundplanid', $inboundplanid)
        ->where('shipmentID', $shipmentID)
        ->update([
            'totalDeclaredValue' => $totalDeclaredValue,
            'updated_time' => now()
        ]);



        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans/' . $inboundplanid . '/shipments/' . $shipmentidfromapi . '/deliveryWindowOptions';

        $customParams = [];

        $data_additionale['shipmentidfromapi'] = $shipmentidfromapi;

        $companydetails = $this->fetchCompanyDetails();

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Generate JSON payload
        $jsonData = $this->JsonCreation('step5b', null, 'ATVPDKIKX0DER', null, $data_additionale);

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }

        $credentials = AWSCredentials($store);

        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                'body' => json_decode($jsonData, true) // Decode before logging
            ]);

            // Make the HTTP request (POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->post($url);

            // Log the cURL information (response details)
            $curlInfo = $response->handlerStats();
            Log::info('Curl Info:', $curlInfo);

            if ($response->successful()) {
                $data = $response->json();

                // Extract operationId
                $operationId = $data['operationId'] ?? null;

                // If operationId exists, call getOperationStatus()
                if ($operationId) {
                    Log::info("Tracking operation: {$operationId}");

                    // Call the operation status function
                    $operationStatusResponse = $this->getOperationStatus($store, $destinationmarketplace, $operationId);

                    // Return the operation response
                    return response()->json([
                        'success' => true,
                        'operationId' => $operationId,
                        'data' => $data,
                        'operationStatus' => $operationStatusResponse->getData(true), // Get operation tracking response
                        'logs' => $curlInfo,
                    ]);
                }

                // If no operationId, return success response but indicate missing operation tracking
                return response()->json([
                    'success' => true,
                    'message' => 'Operation Step 5b Success.',
                    'data' => $data,
                    'logs' => $curlInfo,
                ]);
            }

            // If request failed
            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but API returned an error.',
                'headers' => $headers,
                'error' => $response->json(),
                'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }
    }

    public function step5c_transportation_options_view(Request $request)
    {
        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string'
        ]);
        $data_additionale = []; // data that is to be passed to jsonCreation
        $store = $request->input('store', 'All Renewed');
        $nextToken = $request->input('nextToken', null);
        $destinationmarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA4EA5THYYCU');
        $inboundplanid = $request->input('inboundplanid', 'wfd8036e16-c026-46bf-a372-63cf7e9607fb');// from process 1
        $packingGroupId = $request->input('packingGroupId', 'pg81f6f672-a181-4a8b-9e8b-f57f552cfc01');// from process 2b
        $packingOptionId = $request->input('packingOptionId', 'pgfadeaafb-3918-48d2-8f32-13a48dc9f69e');
        $shipDate = $request->input('shipDate', null);
        $packageWeight = $request->input('packageWeight', null);
        $packageLength = $request->input('packageLength', null);
        $packageWidth = $request->input('packageWidth', null);
        $packageHeight = $request->input('packageHeight', null);
        $totalDeclaredValue = $request->input('totalDeclaredValue', null);
        $shipmentidfromapi = $request->input('shipmentidfromapi', null);
        $placementOptionId = $request->input('placementOptionId', null);

        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans/' . $inboundplanid . '/transportationOptions';

        $customParams = [
            'placementOptionId' => $placementOptionId,
        ];

        if (isset($nextToken) && !empty($nextToken)) {
            $customParams['paginationToken'] = $nextToken;
        }

        $companydetails = $this->fetchCompanyDetails();

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Generate JSON payload
        $jsonData = $this->JsonCreation('step4a', null, 'ATVPDKIKX0DER', null, $data_additionale);

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }

        $credentials = AWSCredentials($store);

        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}?{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                // 'body' => json_decode($jsonData, true) // Decode before logging
            ]);

            // Make the HTTP request (POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                // ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->get($url);

            // Log the cURL information (response details)
            $curlInfo = $response->handlerStats();
            Log::info('Curl Info:', $curlInfo);

            // Check if request was successful
            if ($response->successful()) {
                $data = $response->json(); // Parse JSON response

                // Extract operationId
                $operationId = $data['operationId'] ?? null;

                // If operationId exists, call getOperationStatus()
                if ($operationId) {
                    Log::info("Tracking operation: {$operationId}");

                    // Call the operation status function
                    $operationStatusResponse = $this->getOperationStatus($store, $destinationmarketplace, $operationId);

                    // Return the operation response
                    return response()->json([
                        'success' => true,
                        'operationId' => $operationId,
                        'data' => $data,
                        'operationStatus' => $operationStatusResponse->getData(true), // Get operation tracking response
                        'logs' => $curlInfo,
                    ]);
                }

                // If no operationId, return success response but indicate missing operation tracking
                return response()->json([
                    'success' => true,
                    'message' => 'Operation Step 5c Success.',
                    'data' => $data,
                    'logs' => $curlInfo,
                ]);
            }

            // If request failed
            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but API returned an error.',
                'headers' => $headers,
                'error' => $response->json(),
                'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }
    }
    public function step6a_list_delivery_window_options(Request $request)
    {
        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string'
        ]);

        $data_additionale = []; // data that is to be passed to jsonCreation
        $store = $request->input('store', 'All Renewed');
        $nextToken = $request->input('nextToken', null);
        $destinationmarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA4EA5THYYCU');
        $inboundplanid = $request->input('inboundplanid', 'wf2be07b3a-417f-4f67-9560-88fae47d2273');// from process 1
        $packingGroupId = $request->input('packingGroupId', 'pg81f6f672-a181-4a8b-9e8b-f57f552cfc01');// from process 2b
        $packingOptionId = $request->input('packingOptionId', 'pgfadeaafb-3918-48d2-8f32-13a48dc9f69e');
        $shipDate = $request->input('shipDate', null);
        $packageWeight = $request->input('packageWeight', null);
        $packageLength = $request->input('packageLength', null);
        $packageWidth = $request->input('packageWidth', null);
        $packageHeight = $request->input('packageHeight', null);
        $totalDeclaredValue = $request->input('totalDeclaredValue', null);
        $shipmentidfromapi = $request->input('shipmentidfromapi', 'sh03561f97-adab-469c-8925-7fb0b702e061');
        $placementOptionId = $request->input('placementOptionId', null);
        $customParams = [];

        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans/' . $inboundplanid . '/shipments/' . $shipmentidfromapi . '/deliveryWindowOptions';

        if (isset($nextToken) && !empty($nextToken)) {
            $data_additionale['nextToken'] = $nextToken;
        }

        $companydetails = $this->fetchCompanyDetails();

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Generate JSON payload
        $jsonData = $this->JsonCreation('step4a', null, 'ATVPDKIKX0DER', null, $data_additionale);

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }

        $credentials = AWSCredentials($store);

        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}?{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                // 'body' => json_decode($jsonData, true) // Decode before logging
            ]);

            // Make the HTTP request (POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                // ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->get($url);

            // Log the cURL information (response details)
            $curlInfo = $response->handlerStats();
            Log::info('Curl Info:', $curlInfo);

            // Check if request was successful
            if ($response->successful()) {
                $data = $response->json(); // Parse JSON response

                // Extract operationId
                $operationId = $data['operationId'] ?? null;

                // If operationId exists, call getOperationStatus()
                if ($operationId) {
                    Log::info("Tracking operation: {$operationId}");

                    // Call the operation status function
                    $operationStatusResponse = $this->getOperationStatus($store, $destinationmarketplace, $operationId);

                    // Return the operation response
                    return response()->json([
                        'success' => true,
                        'operationId' => $operationId,
                        'data' => $data,
                        'operationStatus' => $operationStatusResponse->getData(true), // Get operation tracking response
                        'logs' => $curlInfo,
                    ]);
                }

                // If no operationId, return success response but indicate missing operation tracking
                return response()->json([
                    'success' => true,
                    'message' => 'Operation Step 5c Success.',
                    'data' => $data,
                    'logs' => $curlInfo,
                ]);
            }

            // If request failed
            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but API returned an error.',
                'headers' => $headers,
                'error' => $response->json(),
                'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }
    }

    public function step6b_confirm_placement_option(Request $request)
    {
        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string'
        ]);
        $data_additionale = []; // data that is to be passed to jsonCreation
        $store = $request->input('store', 'All Renewed');
        $nextToken = $request->input('nextToken', null);
        $destinationmarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA4EA5THYYCU');
        $inboundplanid = $request->input('inboundplanid', 'wf9a0893d7-de79-489e-8dcd-9da76ff37120');// from process 1
        $packingGroupId = $request->input('packingGroupId', 'pg81f6f672-a181-4a8b-9e8b-f57f552cfc01');// from process 2b
        $packingOptionId = $request->input('packingOptionId', 'pgfadeaafb-3918-48d2-8f32-13a48dc9f69e');
        $shipDate = $request->input('shipDate', null);
        $packageWeight = $request->input('packageWeight', null);
        $packageLength = $request->input('packageLength', null);
        $packageWidth = $request->input('packageWidth', null);
        $packageHeight = $request->input('packageHeight', null);
        $totalDeclaredValue = $request->input('totalDeclaredValue', null);
        $shipmentidfromapi = $request->input('shipmentidfromapi', null);
        $placementOptionId = $request->input('placementOptionId', 'ple0753d0e-5f18-4b1b-809a-fd946abf268e');
        $customParams = [];

        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans/' . $inboundplanid . '/placementOptions/' . $placementOptionId . '/confirmation';

        DB::table('tblfbainboundplans')
        ->where('inboundplanid', $inboundplanid)
        ->where('shipmentID', $shipmentID)
        ->update([
            'placementOptionId' => $placementOptionId,
            'updated_time' => now() // update timestamp
        ]);


        if (isset($nextToken) && !empty($nextToken)) {
            $data_additionale['nextToken'] = $nextToken;
        }

        $companydetails = $this->fetchCompanyDetails();

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Generate JSON payload
        $jsonData = $this->JsonCreation('step4a', null, 'ATVPDKIKX0DER', null, $data_additionale);

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }

        $credentials = AWSCredentials($store);

        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                'body' => json_decode($jsonData, true) // Decode before logging
            ]);

            // Make the HTTP request (POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->post($url);

            // Log the cURL information (response details)
            $curlInfo = $response->handlerStats();
            Log::info('Curl Info:', $curlInfo);

            // Check if request was successful
            if ($response->successful()) {
                $data = $response->json(); // Parse JSON response

                // Extract operationId
                $operationId = $data['operationId'] ?? null;

                // If operationId exists, call getOperationStatus()
                if ($operationId) {
                    Log::info("Tracking operation: {$operationId}");

                    // Call the operation status function
                    $operationStatusResponse = $this->getOperationStatus($store, $destinationmarketplace, $operationId);

                    // Return the operation response
                    return response()->json([
                        'success' => true,
                        'operationId' => $operationId,
                        'data' => $data,
                        'operationStatus' => $operationStatusResponse->getData(true), // Get operation tracking response
                        'logs' => $curlInfo,
                    ]);
                }

                // If no operationId, return success response but indicate missing operation tracking
                return response()->json([
                    'success' => true,
                    'message' => 'Operation Step 5c Success.',
                    'data' => $data,
                    'logs' => $curlInfo,
                ]);
            }

            // If request failed
            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but API returned an error.',
                'headers' => $headers,
                'error' => $response->json(),
                'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }
    }

    public function step7a_confirm_delivery_window_options(Request $request)
    {
        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string'
        ]);
        $data_additionale = []; // data that is to be passed to jsonCreation
        $store = $request->input('store', 'All Renewed');
        $nextToken = $request->input('nextToken', null);
        $destinationmarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA4EA5THYYCU');
        $inboundplanid = $request->input('inboundplanid', 'wfd8036e16-c026-46bf-a372-63cf7e9607fb');// from process 1
        $packingGroupId = $request->input('packingGroupId', 'pg81f6f672-a181-4a8b-9e8b-f57f552cfc01');// from process 2b
        $packingOptionId = $request->input('packingOptionId', 'pgfadeaafb-3918-48d2-8f32-13a48dc9f69e');
        $shipDate = $request->input('shipDate', null);
        $packageWeight = $request->input('packageWeight', null);
        $packageLength = $request->input('packageLength', null);
        $packageWidth = $request->input('packageWidth', null);
        $packageHeight = $request->input('packageHeight', null);
        $totalDeclaredValue = $request->input('totalDeclaredValue', null);
        $shipmentidfromapi = $request->input('shipmentidfromapi', null);
        $placementOptionId = $request->input('placementOptionId', null);
        $deliveryWindowOptionId = $request->input('deliveryWindowOptionId', null); // step 7a new
        $customParams = [];

        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans/' . $inboundplanid . '/shipments/' . $shipmentidfromapi . '/deliveryWindowOptions/' . $deliveryWindowOptionId . '/confirmation';

        if (isset($nextToken) && !empty($nextToken)) {
            $data_additionale['nextToken'] = $nextToken;
        }

        $companydetails = $this->fetchCompanyDetails();

        DB::table('tblfbainboundplans')
        ->where('inboundplanid', $inboundplanid)
        ->where('shipmentID', $shipmentID)
        ->update([
            'deliveryWindowOptionId' => $deliveryWindowOptionId,
            'updated_time' => now() // update timestamp
        ]);

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Generate JSON payload
        $jsonData = $this->JsonCreation('step4a', null, 'ATVPDKIKX0DER', null, $data_additionale);

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }

        $credentials = AWSCredentials($store);

        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}?{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                'body' => json_decode($jsonData, true) // Decode before logging
            ]);

            // Make the HTTP request (POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->post($url);

            // Log the cURL information (response details)
            $curlInfo = $response->handlerStats();
            Log::info('Curl Info:', $curlInfo);

            // Check if request was successful
            if ($response->successful()) {
                $data = $response->json(); // Parse JSON response

                // Extract operationId
                $operationId = $data['operationId'] ?? null;

                // If operationId exists, call getOperationStatus()
                if ($operationId) {
                    Log::info("Tracking operation: {$operationId}");

                    // Call the operation status function
                    $operationStatusResponse = $this->getOperationStatus($store, $destinationmarketplace, $operationId);

                    // Return the operation response
                    return response()->json([
                        'success' => true,
                        'operationId' => $operationId,
                        'data' => $data,
                        'operationStatus' => $operationStatusResponse->getData(true), // Get operation tracking response
                        'logs' => $curlInfo,
                    ]);
                }

                // If no operationId, return success response but indicate missing operation tracking
                return response()->json([
                    'success' => true,
                    'message' => 'Operation Step 5c Success.',
                    'data' => $data,
                    'logs' => $curlInfo,
                ]);
            }

            // If request failed
            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but API returned an error.',
                'headers' => $headers,
                'error' => $response->json(),
                'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }
    }

    public function step8a_confirm_transportation_options(Request $request)
    {
        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string'
        ]);
        $data_additionale = []; // data that is to be passed to jsonCreation
        $store = $request->input('store', 'All Renewed');
        $nextToken = $request->input('nextToken', null);
        $destinationmarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA4EA5THYYCU');
        $inboundplanid = $request->input('inboundplanid', 'wf9a0893d7-de79-489e-8dcd-9da76ff37120');// from process 1
        $packingGroupId = $request->input('packingGroupId', 'pg81f6f672-a181-4a8b-9e8b-f57f552cfc01');// from process 2b
        $packingOptionId = $request->input('packingOptionId', 'pgfadeaafb-3918-48d2-8f32-13a48dc9f69e');
        $shipDate = $request->input('shipDate', null);
        $packageWeight = $request->input('packageWeight', null);
        $packageLength = $request->input('packageLength', null);
        $packageWidth = $request->input('packageWidth', null);
        $packageHeight = $request->input('packageHeight', null);
        $totalDeclaredValue = $request->input('totalDeclaredValue', null);
        $shipmentidfromapi = $request->input('shipmentidfromapi', 'sh4ab195b0-c4b0-4662-9aec-32f90f5168e6');
        $placementOptionId = $request->input('placementOptionId', null);
        $deliveryWindowOptionId = $request->input('deliveryWindowOptionId', 'w799c9518-a6fa-4a83-b2bd-862aae48218e');
        $transportationOptionId = $request->input('transportationOptionId', 'toa6542bb7-5f89-4e91-9a58-0c07fd3c659c');
        $customParams = [];

        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans/' . $inboundplanid . '/transportationOptions/confirmation';

        DB::table('tblfbainboundplans')
        ->where('inboundplanid', $inboundplanid)
        ->where('shipmentID', $shipmentID)
        ->update([
            'transportationOptionId' => $transportationOptionId,
            'updated_time' => now() // update timestamp
        ]);

        if (isset($nextToken) && !empty($nextToken)) {
            $data_additionale['nextToken'] = $nextToken;
        }

        $data_additionale['shipmentidfromapi'] = $shipmentidfromapi;
        $data_additionale['transportationOptionId'] = $transportationOptionId;

        $companydetails = $this->fetchCompanyDetails();

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Generate JSON payload
        $jsonData = $this->JsonCreation('step8a', $companydetails, 'ATVPDKIKX0DER', null, $data_additionale);

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }

        $credentials = AWSCredentials($store);

        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}?{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                'body' => json_decode($jsonData, true) // Decode before logging
            ]);

            // Make the HTTP request (POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->post($url);

            // Log the cURL information (response details)
            $curlInfo = $response->handlerStats();
            Log::info('Curl Info:', $curlInfo);

            // Check if request was successful
            if ($response->successful()) {
                $data = $response->json(); // Parse JSON response

                // $boxId = $data['trackingDetails']['spdTrackingDetail']['spdTrackingItems'][0]['boxId'];
                // $trackingId = $data['trackingDetails']['spdTrackingDetail']['spdTrackingItems'][0]['trackingId'];
                // $shipmentConfirmationId = $data['shipmentConfirmationId'];

                // Extract operationId
                $operationId = $data['operationId'] ?? null;

                // If operationId exists, call getOperationStatus()
                if ($operationId) {
                    Log::info("Tracking operation: {$operationId}");

                    // Call the operation status function
                    $operationStatusResponse = $this->getOperationStatus($store, $destinationmarketplace, $operationId);

                    // Return the operation response
                    return response()->json([
                        'success' => true,
                        'operationId' => $operationId,
                        'data' => $data,
                        'operationStatus' => $operationStatusResponse->getData(true), // Get operation tracking response
                        'logs' => $curlInfo,
                    ]);
                }

                // If no operationId, return success response but indicate missing operation tracking
                return response()->json([
                    'success' => true,
                    'message' => 'Operation Step 5c Success.',
                    'data' => $data,
                    'logs' => $curlInfo,
                ]);
            }

            // If request failed
            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but API returned an error.',
                'headers' => $headers,
                'error' => $response->json(),
                'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }
    }

    public function step9a_get_shipment(Request $request)
    {
        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string'
        ]);
        $data_additionale = []; // data that is to be passed to jsonCreation
        $store = $request->input('store', 'All Renewed');
        $nextToken = $request->input('nextToken', null);
        $destinationmarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA4EA5THYYCU');
        $inboundplanid = $request->input('inboundplanid', 'wf813528cd-0315-405e-893e-7103412770f0');// from process 1
        $packingGroupId = $request->input('packingGroupId', 'pg81f6f672-a181-4a8b-9e8b-f57f552cfc01');// from process 2b
        $packingOptionId = $request->input('packingOptionId', 'pgfadeaafb-3918-48d2-8f32-13a48dc9f69e'); // from process 2b
        $shipmentIdfromAPI = $request->input('shipmentidfromapi', 'sh2d562535-fd40-4bd3-8e67-3e0b9446ae03');


        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans/' . $inboundplanid . '/shipments/' . $shipmentIdfromAPI;

        $customParams = [];

        $companydetails = $this->fetchCompanyDetails();

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Generate JSON payload
        $jsonData = $this->JsonCreation('step4a', null, 'ATVPDKIKX0DER', null, $data_additionale);

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }

        $credentials = AWSCredentials($store);

        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                // 'body' => json_decode($jsonData, true) // Decode before logging
            ]);

            // Make the HTTP request (POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                // ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->get($url);

            // Log the cURL information (response details)
            $curlInfo = $response->handlerStats();
            Log::info('Curl Info:', $curlInfo);

            // Check if request was successful
            if ($response->successful()) {
                $data = $response->json(); // Parse JSON response

                // Extract operationId
                $operationId = $data['operationId'] ?? null;

                // If operationId exists, call getOperationStatus()
                if ($operationId) {
                    Log::info("Tracking operation: {$operationId}");

                    // Call the operation status function
                    $operationStatusResponse = $this->getOperationStatus($store, $destinationmarketplace, $operationId);

                    // Return the operation response
                    return response()->json([
                        'success' => true,
                        'operationId' => $operationId,
                        'data' => $data,
                        'operationStatus' => $operationStatusResponse->getData(true), // Get operation tracking response
                        'logs' => $curlInfo,
                    ]);
                }

                // If no operationId, return success response but indicate missing operation tracking
                return response()->json([
                    'success' => true,
                    'message' => 'Operation Step 9a Success.',
                    'data' => $data,
                    'logs' => $curlInfo,
                ]);
            }

            // If request failed
            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but API returned an error.',
                'headers' => $headers,
                'error' => $response->json(),
                'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }
    }

    public function step10a_print_label(Request $request)
    {
        $customParams = [];

        $request->validate([
            'store' => 'nullable|string',
            'destinationMarketplace' => 'nullable|string',
            'nextToken' => 'nullable|string',
            'shipmentID' => 'nullable|string'
        ]);
        $data_additionale = []; // data that is to be passed to jsonCreation
        $store = $request->input('store', 'All Renewed');
        $nextToken = $request->input('nextToken', null);
        $destinationmarketplace = $request->input('destinationMarketplace', 'ATVPDKIKX0DER');
        $shipmentID = $request->input('shipmentID', 'FBA4EA5THYYCU');
        $inboundplanid = $request->input('inboundplanid', 'wfd8036e16-c026-46bf-a372-63cf7e9607fb');// from process 1
        $packingGroupId = $request->input('packingGroupId', 'pg81f6f672-a181-4a8b-9e8b-f57f552cfc01');// from process 2b
        $packingOptionId = $request->input('packingOptionId', 'pgfadeaafb-3918-48d2-8f32-13a48dc9f69e');
        $shipDate = $request->input('shipDate', null);
        $packageWeight = $request->input('packageWeight', null);
        $packageLength = $request->input('packageLength', null);
        $packageWidth = $request->input('packageWidth', null);
        $packageHeight = $request->input('packageHeight', null);
        $totalDeclaredValue = $request->input('totalDeclaredValue', null);
        $shipmentidfromapi = $request->input('shipmentidfromapi', null);
        $placementOptionId = $request->input('placementOptionId', null);
        $shipmentconfirmationid = $request->input('shipmentconfirmationid', null);
        $transportationOptionId = $request->input('transportationOptionId', null);
        $transportationOptionId = $request->input('shipmentconfirmationid', null);



        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/inboundPlans/' . $inboundplanid . '/labels';

        if (isset($nextToken) && !empty($nextToken)) {
            $data_additionale['nextToken'] = $nextToken;
        }

        $data_additionale['shipmentidfromapi'] = $shipmentidfromapi;
        $data_additionale['transportationOptionId'] = $transportationOptionId;

        $companydetails = $this->fetchCompanyDetails();

        if (!$companydetails) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Generate JSON payload
        $jsonData = $this->JsonCreation('step4a', $companydetails, 'ATVPDKIKX0DER', null, $data_additionale);

        // Check if JSON encoding failed
        if ($jsonData === false) {
            Log::error('JSON Encoding Failed:', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'JSON encoding error'], 500);
        }

        $credentials = AWSCredentials($store);

        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            // Build headers using the helper function
            $headers = buildHeaders($credentials, $accessToken, 'POST', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            // Ensure Content-Type is set
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            // Log the headers
            Log::info('Request headers:', $headers);

            // Build query string using the helper function
            $queryString = buildQueryString($nextToken, $customParams);

            // Construct the full URL
            $url = "{$endpoint}{$path}?{$queryString}";

            // Log the request details (headers, body, etc.) for debugging
            Log::info('Request details:', [
                'url' => $url,
                'headers' => $headers,
                'queryString' => $queryString,
                'body' => json_decode($jsonData, true) // Decode before logging
            ]);

            // Make the HTTP request (POST)
            $response = Http::timeout(50)
                ->withHeaders($headers)
                ->withBody($jsonData, 'application/json') // Ensure JSON is properly sent
                ->post($url);

            // Log the cURL information (response details)
            $curlInfo = $response->handlerStats();
            Log::info('Curl Info:', $curlInfo);

            // Check if request was successful
            if ($response->successful()) {
                $data = $response->json(); // Parse JSON response

                // Extract operationId
                $operationId = $data['operationId'] ?? null;

                // If operationId exists, call getOperationStatus()
                if ($operationId) {
                    Log::info("Tracking operation: {$operationId}");

                    // Call the operation status function
                    $operationStatusResponse = $this->getOperationStatus($store, $destinationmarketplace, $operationId);

                    // Return the operation response
                    return response()->json([
                        'success' => true,
                        'operationId' => $operationId,
                        'data' => $data,
                        'operationStatus' => $operationStatusResponse->getData(true), // Get operation tracking response
                        'logs' => $curlInfo,
                    ]);
                }

                // If no operationId, return success response but indicate missing operation tracking
                return response()->json([
                    'success' => true,
                    'message' => 'Operation Step 5c Success.',
                    'data' => $data,
                    'logs' => $curlInfo,
                ]);
            }

            // If request failed
            return response()->json([
                'success' => false,
                'message' => 'Successfully sent but API returned an error.',
                'headers' => $headers,
                'error' => $response->json(),
                'body-payload' => json_decode($jsonData, true), // Decode JSON before returning
                'logs' => $curlInfo,
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null, // If logs exist, return them
            ], 500);
        }
    }

    protected function fetchCompanyDetails()
    {
        return DB::table('tblcompanydetails')->where('id', 1)->first();
    }

    protected function JsonCreation($action, $companydetails, $marketplaceID, $shipmentID, $data_additionale)
    {
        $systemconfig = 'test';
        $final_json_construct = [];

        if ($action == 'step1') {
            // Convert object to array for safety
            $companydetails = (array) $companydetails;


            // 🔍 **Query Database for Shipment Items**
            $shipmentItems = DB::table('tblfbashipmenthistory')
                ->where('shipmentID', $shipmentID)
                ->get(); // Fetch all matching records

            // 🔹 **Check if there are items before proceeding**
            if ($shipmentItems->isEmpty()) {
                return json_encode(["error" => "No items found for Shipment ID: " . $shipmentID], JSON_UNESCAPED_SLASHES);
            }

            // 🔹 **Convert Database Results to Expected JSON Structure**
            $itemsArray = $shipmentItems->map(function ($item) {
                return [
                    "labelOwner" => "SELLER",
                    "msku" => $item->MSKU ?? "Unknown",
                    "prepOwner" => "SELLER",
                    "quantity" => $item->quantity ?? 1
                ];
            })->toArray();

            // 🔹 **Build Final JSON**
            $final_json_construct = [
                "name" => $companydetails['Name'] ?? 'Unknown',
                "sourceAddress" => [
                    "name" => $companydetails['Name'] ?? '',
                    "companyName" => $companydetails['CompanyName'] ?? '',
                    "addressLine1" => $companydetails['StreetAddress'] ?? '',
                    "addressLine2" => '',
                    "city" => $companydetails['City'] ?? '',
                    "countryCode" => $companydetails['CountryCode'] ?? '',
                    "stateOrProvinceCode" => $companydetails['State'] ?? '',
                    "postalCode" => $companydetails['ZIPCode'] ?? '',
                    "phoneNumber" => $companydetails['Contact'] ?? ''
                ],
                "destinationMarketplaces" => [
                    $marketplaceID
                ],
                "items" => $itemsArray
            ];

        } elseif ($action == 'step2a') {
            $final_json_construct = [];
        } else if ($action == 'step3a') {

            // 🔍 **Query Database for Shipment Items**
            $shipmentItems = DB::table('tblfbashipmenthistory')
                ->where('shipmentID', $shipmentID)
                ->get(); // Fetch all matching records

            // 🔹 **Check if there are items before proceeding**
            if ($shipmentItems->isEmpty()) {
                return json_encode(["error" => "No items found for Shipment ID: " . $shipmentID], JSON_UNESCAPED_SLASHES);
            }

            // 🔹 **Convert Database Results to Expected JSON Structure**
            $itemsArray = $shipmentItems->map(function ($item) {
                return [
                    "labelOwner" => "SELLER",
                    "msku" => $item->MSKU ?? "Unknown",
                    "prepOwner" => "SELLER",
                    "quantity" => $item->quantity ?? 1
                ];
            })->toArray();

            $final_json_construct = [
                "packageGroupings" => [
                    [
                        "packingGroupId" => $data_additionale['packingGroupId'] ?? null,
                        "boxes" => [
                            [
                                "weight" => [
                                    "unit" => "LB",
                                    "value" => 48
                                ],
                                "dimensions" => [
                                    "unitOfMeasurement" => "IN",
                                    "length" => 24,
                                    "width" => 5,
                                    "height" => 18
                                ],
                                "quantity" => 1,
                                "items" => $itemsArray,
                                "contentInformationSource" => "BOX_CONTENT_PROVIDED"
                            ]
                        ]
                    ]
                ]
            ];
        } elseif ($action == 'step5a') {
            $companydetails = (array) $companydetails;

            $final_json_construct = [
                "placementOptionId" => $data_additionale['placementOptionId'],
                "shipmentTransportationConfigurations" => [
                    [
                        "shipmentId" => $data_additionale['shipmentidfromapi'],
                        "readyToShipWindow" => [
                            "start" => $data_additionale['shipDate']
                        ],
                        "contactInformation" => [
                            "email" => $companydetails['ContactEmail'],
                            "name" => $companydetails['Name'],
                            "phoneNumber" => $companydetails['Contact']
                        ],
                        "palletInformation" => [
                            "pallets" => [
                                [
                                    "weight" => [
                                        "unit" => "lbs",
                                        "value" => $data_additionale['packageWeight']
                                    ],
                                    "dimensions" => [
                                        "unitOfMeasurement" => "in",
                                        "length" => $data_additionale['packageLength'],
                                        "width" => $data_additionale['packageWidth'],
                                        "height" => $data_additionale['packageHeight']
                                    ],
                                    "quantity" => 1,
                                    "stackable" => true
                                ]
                            ],
                            "freightClass" => "NONE",
                            "declaredValue" => [
                                "code" => "USD",
                                "amount" => $data_additionale['totalDeclaredValue']
                            ]
                        ]
                    ]
                ]
            ];

        } elseif ($action == 'step5b') {
            $companydetails = (array) $companydetails;

            $final_json_construct = [
                "shipmentDeliveryWindowConfigurations" => [
                    [
                        "shipmentId" => $data_additionale['shipmentidfromapi']
                    ]
                ]
            ];

        } else if ($action == 'step8a') {
            $companydetails = (array) $companydetails;

            $final_json_construct = [
                "transportationSelections" => [
                    [
                        "shipmentId" => $data_additionale['shipmentidfromapi'] ?? '', // fallback if null
                        "transportationOptionId" => $data_additionale['transportationOptionId'] ?? '',
                        "contactInformation" => [
                            "phoneNumber" => $companydetails['Contact'] ?? '',
                            "email" => $companydetails['ContactEmail'] ?? '',
                            "name" => $companydetails['Name'] ?? ''
                        ]
                    ]
                ]
            ];
        }

        // Ensure JSON encoding before returning
        return json_encode($final_json_construct, JSON_UNESCAPED_SLASHES);
    }

    private function getOperationStatus($store, $destinationmarketplace, $operationid)
    {
        $endpoint = 'https://sellingpartnerapi-na.amazon.com';
        $canonicalHeaders = "host:sellingpartnerapi-na.amazon.com";
        $path = '/inbound/fba/2024-03-20/operations/' . $operationid;
        $nextToken = '';
        $customParams = [];
        $maxRetries = 20; // Maximum number of retries before stopping
        $retryInterval = 5; // Time in seconds between each retry

        $credentials = AWSCredentials($store);
        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'No credentials found for the given store.',
            ], 500);
        }

        $accessToken = fetchAccessToken($credentials, $returnRaw = false);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access token.',
            ], 500);
        }

        try {
            $headers = buildHeaders($credentials, $accessToken, 'GET', 'execute-api', 'us-east-1', $path, $nextToken, $customParams, $endpoint, $canonicalHeaders);
            $headers['Content-Type'] = 'application/json';
            $headers['accept'] = 'application/json';

            $queryString = buildQueryString($nextToken, $customParams);
            $url = "{$endpoint}{$path}{$queryString}";

            $attempt = 0;
            do {
                // Log the request details
                Log::info("Attempt {$attempt}: Checking operation status", ['url' => $url, 'headers' => $headers]);

                // Make the HTTP request
                $response = Http::timeout(50)->withHeaders($headers)->get($url);
                $curlInfo = $response->handlerStats();

                // Log response
                Log::info("Attempt {$attempt}: Response received", [
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'logs' => $curlInfo
                ]);

                // If the request was successful
                if ($response->successful()) {
                    $data = $response->json();
                    $status = $data['operationStatus'] ?? 'UNKNOWN';

                    // Return if status is SUCCESS or FAILED
                    if ($status === 'SUCCESS' || $status === 'FAILED') {
                        return response()->json([
                            'success' => true,
                            'status' => $status,
                            'data' => $data,
                            'logs' => $curlInfo
                        ]);
                    }
                } else {
                    // If there's an error response, log it and break
                    Log::error("Attempt {$attempt}: API Error", ['error' => $response->json()]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Error fetching operation status.',
                        'error' => $response->json(),
                        'logs' => $curlInfo
                    ], $response->status());
                }

                // Wait before retrying
                sleep($retryInterval);
                $attempt++;

            } while ($attempt < $maxRetries);

            // If max retries exceeded
            return response()->json([
                'success' => false,
                'message' => 'Maximum retries reached. Operation still in progress.',
            ], 408); // HTTP 408: Request Timeout

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the API request.',
                'error' => $e->getMessage(),
                'logs' => $curlInfo ?? null,
            ], 500);
        }
    }

    // is a magical world of gumball

}

