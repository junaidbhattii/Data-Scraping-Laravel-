<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use DateTime;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;


class MapController extends Controller
{
    //
    public function index(Request $request, $query)
    {
        set_time_limit(90000);
        $initialTime = new DateTime();
        // // $apiKey = "Enter Your Api Key";

        $response = Http::get("https://maps.googleapis.com/maps/api/place/nearbysearch/json?&keyword=" . $query . "&location=31.5048281,74.3235425&radius=150000&key=" . $apiKey . "");

        if ($response->successful()) {
            $results = $response->json()['results'];
            // return $nextPage;
            while (isset($response->json()['next_page_token'])) {

                usleep(500000); // Sleep for 0.5 seconds (500 milliseconds)

                // Make another request to get the next page of results
                $nextPageToken = $response->json()['next_page_token'];
                $response = $this->getPlaces($request->input('query'), $apiKey, $nextPageToken);
                if ($response->successful()) {
                    $results = array_merge($results, $response->json()['results']);
                } else {
                    // Handle error when fetching the next page
                    $errorCode = $response->status();
                    $errorMessage = $response['error_message'] ?? 'Unknown error';
                    return response()->json(['error' => $errorMessage], $errorCode);
                }
            }

        } else {
            // Handle errors
            $errorCode = $response->status();
            $errorMessage = $response['error_message'] ?? 'Unknown error';
            return response()->json(['error' => $errorMessage], $errorCode);
        }
        $places = [];

        foreach ($results as $result) {
            $place = $result['place_id'];

            $response = Http::get("https://maps.googleapis.com/maps/api/place/details/json?place_id=" . $place . "&key=AIzaSyDRnWPFPLvEgKnTwxWOJDAIH8Yyek00cmM");
            $placeData = [
                'name' => $result['name'],
                'address' => $result['vicinity'], // 'vicinity' is a nearby address
                'rating' => $result['rating'] ?? null, // Rating may not be available for all places
                'opening_hours' => $result['opening_hours']['weekday_text'] ?? null, // Opening hours may not be available for all places
                'website' => isset($response["result"]["website"]) ? $response["result"]["website"] : null, // Website link may not be available for all places
            ];

            $places[] = $placeData;
        }
        $endTime = new DateTime();
        $executionTime = $endTime->getTimestamp() - $initialTime->getTimestamp();
        return response()->json(['place'=>$places,'total result'=>count($places),'time'=>$executionTime]);
    }

    public function getPlaces($query, $apiKey, $nextPageToken)
    {

        $params = [
            'keyword' => urlencode($query),
            'location' => '31.5048281,74.3235425',
            'radius' => 150000,
            'key' => $apiKey,
        ];

        if ($nextPageToken !== null) {
            // If next_page_token is available, include it in the request
            $params['pagetoken'] = $nextPageToken;
        }
        return Http::get("https://maps.googleapis.com/maps/api/place/nearbysearch/json", $params);
    }
}
