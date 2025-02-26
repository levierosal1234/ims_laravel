<?php

namespace App\Http\Controllers\Ebay;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

require app_path('Helpers/ebay_helpers.php');

class EbayController extends Controller
{
    protected $apiEndpoint = 'https://api.ebay.com/ws/api.dll';
    protected $exchangeApiKey = 'f5d29ab775a644eca3f13e4c'; // Replace with actual API key
    /**
     * Fetch orders from eBay API
     */
    public function fetchOrders(Request $request)
    {
        $serverconfig = env('EBAY_SERVER_CONFIG', 'LOCAL');
        $pageNumber = $request->input('page', 1);
        $credentials = EbayCredentials();

        if (!$credentials || empty($credentials['access_token'])) {
            Log::error('Failed to retrieve a valid access token.');
            return response()->json(['error' => 'Access token not found'], 500);
        }

        $accessToken = $credentials['access_token'];

        try {
            // Send API request
            $response = $this->sendEbayRequest($accessToken, $pageNumber);


            if (!$response) {
                Log::info("Raw eBay API Response:", ['response' => json_encode($response, JSON_PRETTY_PRINT)]);
                return response()->json(['error' => 'Failed to retrieve orders'], 500);
            }

            // Handle API errors
            if (!empty($response['Errors'])) {
                return $this->handleEbayErrors($response['Errors'], $serverconfig, $credentials, $request);
            }

            // Process the orders if the response is successful
            $processedOrders = $this->processOrders($response, $accessToken);

            return response()->json([
                'message' => 'Orders fetched and processed successfully',
                'processed_orders' => $processedOrders
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        } catch (\Exception $e) {
            Log::error('Exception in fetchOrders: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Send API request to eBay
     */
    private function sendEbayRequest($accessToken, $pageNumber)
    {
        $createTimeFrom = (new \DateTime('-1 days', new \DateTimeZone('UTC')))->format(DATE_ATOM);
        $createTimeTo = (new \DateTime('now', new \DateTimeZone('UTC')))->format(DATE_ATOM);

        $requestBody = '<?xml version="1.0" encoding="utf-8"?>
        <GetOrdersRequest xmlns="urn:ebay:apis:eBLBaseComponents">
            <RequesterCredentials>
                <eBayAuthToken>' . $accessToken . '</eBayAuthToken>
            </RequesterCredentials>
            <CreateTimeFrom>' . $createTimeFrom . '</CreateTimeFrom>
            <CreateTimeTo>' . $createTimeTo . '</CreateTimeTo>
            <OrderRole>Buyer</OrderRole>
            <DetailLevel>ReturnAll</DetailLevel>
            <Pagination>
                <EntriesPerPage>100</EntriesPerPage>
                <PageNumber>' . $pageNumber . '</PageNumber>
            </Pagination>
        </GetOrdersRequest>';

        return $this->sendRequest($requestBody, 'GetOrders');
    }

    /**
     * Handle eBay API errors
     */
    private function handleEbayErrors($errors, $serverconfig, $credentials, $request)
    {
        foreach ($errors as $error) {
            if ($error['ErrorCode'] == '931') { // Invalid auth token
                Log::error('eBay API error: Invalid auth token.');

                if ($serverconfig === 'LIVE') {
                    Log::info('Attempting to refresh eBay access token...');
                    $newAccessToken = refreshEbayAccessToken($credentials);

                    if (!$newAccessToken) {
                        Log::error('Failed to refresh eBay access token.');
                        return response()->json(['error' => 'Failed to refresh access token'], 500);
                    }

                    // Retry with the new token
                    return $this->fetchOrders($request);
                }

                return response()->json(['error' => 'Invalid eBay access token'], 401);
            }

            if ($error['ErrorCode'] == '932') { // Hard expired token
                Log::error('eBay API error: Auth token is hard expired.');
                return response()->json(['error' => 'Auth token is hard expired, please reauthorize the application'], 401);
            }
        }
    }

    /**
     * Send HTTP request to eBay API
     */
    private function sendRequest($requestBody, $apiCallName)
    {
        // Create headers dynamically with the API call name
        $apiHeaders = [
            'X-EBAY-API-SITEID: 0', // Replace with your actual Site ID
            'X-EBAY-API-COMPATIBILITY-LEVEL: 967', // API compatibility level
            'X-EBAY-API-CALL-NAME: ' . $apiCallName, // Dynamic API call name
            'Content-Type: text/xml',
        ];
    
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->apiEndpoint);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $apiHeaders);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    
        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);
    
        if ($error) {
            Log::error('cURL Error: ' . $error);
            return null;
        }
    
        Log::info("Raw XML Response for API Call: $apiCallName", ['response' => $response]);
    
        $xml = simplexml_load_string($response);
        if (!$xml) {
            Log::error('Invalid XML Response from eBay');
            return null;
        }
    
        return json_decode(json_encode($xml), true); // Convert XML to JSON array
    }

    /**
     * Process the orders retrieved from eBay
     */
    private function processOrders($response, $accessToken)
    {
        if (empty($response['OrderArray']['Order'])) {
            Log::info('No orders found in response.');
            return [];
        }
    
        $orders = $response['OrderArray']['Order'];
        $processedOrders = [];
        $exchangeRates = $this->fetchExchangeRates($this->exchangeApiKey); // Fetch exchange rates
    
        foreach ($orders as $order) {
            echo "<pre>";
            print_r($order);
            echo "</pre>";
            $currency = $order['AmountPaid']['@currencyID'] ?? 'USD';
            $ebayorderid = $order['OrderID'] ?? null;
            $amountPaid = $order['AmountPaid']['value'] ?? 0;
            $amountPaidInUSD = $this->convertToUSD($amountPaid, $currency, $exchangeRates);
    
            // Fetch item details
            $items = [];
    
            if (!empty($order['TransactionArray']['Transaction'])) {
                // Ensure transactions are always an array
                $transactions = $order['TransactionArray']['Transaction'];
            
                if (!isset($transactions[0])) { // If it's a single object, wrap it in an array
                    $transactions = [$transactions];
                }
            
                Log::info("Raw Transaction Data for Order ID: " . ($order['OrderID'] ?? 'Unknown'), ['transactions' => $transactions]);
            
                foreach ($transactions as $transaction) {
                    if (!is_array($transaction) || !isset($transaction['Item'])) {
                        Log::error("fetchItemDetails: Transaction is not structured correctly", ['transaction' => $transaction]);
                        continue; // Skip incorrect structures
                    }
            
                    $itemId = $transaction['Item']['ItemID'] ?? null;
            
                    if (!$itemId) {
                        Log::error("fetchItemDetails: Item ID is missing for Transaction ID: " . ($transaction['TransactionID'] ?? 'Unknown'));
                        continue; // Skip this transaction if no item ID is found
                    }
            
                    $itemDetails = $this->fetchItemDetails($itemId, $accessToken);

                    echo "<br>Item Details: " . $ebayorderid . "<br>";
                    echo "<pre>";
                    print_r($itemDetails);
                    echo "</pre>";
            
                    Log::info("Fetched Item Details for Item ID: $itemId", ['item_details' => $itemDetails]);
            
                    $items[] = [
                        'transaction_id' => $transaction['TransactionID'] ?? null,
                        'item_id' => $itemId,
                        'title' => $transaction['Item']['Title'] ?? null,
                        'quantity_purchased' => $transaction['QuantityPurchased'] ?? null,
                        'item_details' => $itemDetails
                    ];
                }
            }
    
            $processedOrder = [
                'order_id' => $order['OrderID'] ?? null,
                'order_status' => $order['OrderStatus'] ?? null,
                'paid_time' => $order['PaidTime'] ?? null,
                'amount_paid' => $amountPaidInUSD,
                'created_time' => $order['CreatedTime'] ?? null,
                'shipping_cost' => $order['ShippingServiceSelected']['ShippingServiceCost']['value'] ?? null,
                'subtotal' => $order['Subtotal']['value'] ?? null,
                'total' => $order['Total']['value'] ?? null,
                'seller_user_id' => $order['SellerUserID'] ?? null,
                'seller_email' => $order['SellerEmail'] ?? null,
                'shipped_time' => $order['ShippedTime'] ?? null,
                'shipping_address' => isset($order['ShippingAddress']) ? json_encode($order['ShippingAddress']) : null,
                'items' => $items
            ];
    
            // Placeholder for saving product details to `tblproduct`
            // DB::table('tblproduct')->updateOrInsert([...]);
    
            $processedOrders[] = $processedOrder;
        }
    
        Log::info('Successfully processed ' . count($processedOrders) . ' orders.');
        return $processedOrders;
    }
    

    private function fetchItemDetails($itemId, $accessToken)
    {
        if (!$itemId) {
            Log::error("fetchItemDetails: Item ID is missing.");
            return null;
        }
    
        $requestBody = '<?xml version="1.0" encoding="utf-8"?>
        <GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
            <RequesterCredentials>
                <eBayAuthToken>' . $accessToken . '</eBayAuthToken>
            </RequesterCredentials>
            <ItemID>' . $itemId . '</ItemID>
            <DetailLevel>ReturnAll</DetailLevel>
        </GetItemRequest>';
    
        $response = $this->sendRequest($requestBody, 'GetItem');
    
        if (!$response) {
            Log::error("fetchItemDetails: No response received from eBay for Item ID: $itemId");
            return null;
        }
    
        Log::info("Raw response from eBay for Item ID: $itemId", ['response' => $response]);
    
        return $response;
    }
    

    private function fetchExchangeRates($apiKey)
    {
        $url = "https://v6.exchangerate-api.com/v6/$apiKey/latest/USD";
        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if ($data && isset($data['conversion_rates'])) {
            return $data['conversion_rates'];
        } else {
            Log::error("Error fetching exchange rates: " . json_encode($data));
            return [];
        }
    }

    private function convertToUSD($amount, $currency, $exchangeRates)
    {
        if ($currency == 'USD') {
            return number_format($amount, 2, '.', '');
        } elseif (isset($exchangeRates[$currency])) {
            return number_format($amount / $exchangeRates[$currency], 2, '.', '');
        } else {
            Log::error("Exchange rate for currency $currency not found.");
            return $amount; // Return original amount if conversion is not possible
        }
    }
}
