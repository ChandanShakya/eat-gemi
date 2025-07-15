<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecommendController extends Controller
{
    /**
     * Get location recommendations using Gemini AI
     */
    public function getRecommendations(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:500',
        ]);

        $prompt = $request->query('prompt');
        $geminiApiKey = config('services.gemini.api_key');

        if (! $geminiApiKey) {
            return response()->json([
                'message' => 'Gemini API key not configured',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            // Enhanced prompt for comprehensive location search with mandatory menu requirement
            $searchPrompt = "Based on this request: '{$prompt}', provide 5 relevant locations with detailed information. 

            MANDATORY REQUIREMENTS:
            - If the location is a restaurant, cafe, or food establishment, you MUST include a menu. Use menu_table for structured data or menu_image_url for image links.
            - For restaurants without available online menus, search Google Photos/Images and provide estimated menu_image_url links
            - Include comprehensive location details: name, full address, type/category, price range, description, contact info
            - Add latitude/longitude coordinates if possible
            - For non-food locations, provide relevant service details instead of menu

            Format as JSON with this structure:
            {
              \"locations\": [
                {
                  \"name\": \"Location Name\",
                  \"address\": \"Full address with city, country\",
                  \"type\": \"restaurant/cafe/hotel/shop/etc\",
                  \"price_range\": \"$/$$/$$$/$$$$ or equivalent\",
                  \"description\": \"Detailed description\",
                  \"phone\": \"contact number if available\",
                  \"website\": \"website URL if available\",
                  \"lat\": \"latitude\",
                  \"lng\": \"longitude\",
                  \"menu_table\": [
                    {\"item\": \"Dish name\", \"price\": \"\$X.XX\", \"description\": \"Brief description\"}
                  ],
                  \"menu_image_url\": \"URL to menu image from Google Photos/Images\",
                  \"rating\": \"X.X/5\",
                  \"hours\": \"Opening hours\",
                  \"features\": [\"wifi\", \"outdoor_seating\", \"delivery\", \"etc\"]
                }
              ]
            }";

            // Call Gemini API
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$geminiApiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $searchPrompt,
                            ],
                        ],
                    ],
                ],
            ]);

            if ($response->failed()) {
                Log::error('Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'message' => 'Failed to get recommendations. Please try again later.',
                ], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            $data = $response->json();

            // Extract the generated text
            $generatedText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Try to parse JSON from the response
            $locations = $this->parseLocationsFromResponse($generatedText);

            // Try to parse JSON from the response
            $locations = $this->parseLocationsFromResponse($generatedText);

            return response()->json([
                'message' => 'Recommendations retrieved successfully',
                'query' => $prompt,
                'restaurants' => $locations, // Keep 'restaurants' key for frontend compatibility
                'raw_response' => $generatedText,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting recommendations', [
                'error' => $e->getMessage(),
                'prompt' => $prompt,
            ]);

            // Return mock data for development/testing
            $mockLocations = $this->getMockLocationData($prompt);

            return response()->json([
                'message' => 'Recommendations retrieved successfully (mock data)',
                'query' => $prompt,
                'restaurants' => $mockLocations, // Keep 'restaurants' key for frontend compatibility
                'note' => 'Using mock data - Gemini API temporarily unavailable',
            ]);
        }
    }

    /**
     * Parse locations from Gemini API response and ensure menus for restaurants
     */
    private function parseLocationsFromResponse($responseText)
    {
        // Try to extract JSON from the response
        if (preg_match('/\{.*\}/s', $responseText, $matches)) {
            $jsonString = $matches[0];
            
            try {
                $data = json_decode($jsonString, true);
                
                if (isset($data['locations']) && is_array($data['locations'])) {
                    $locations = [];
                    
                    foreach ($data['locations'] as $location) {
                        $processedLocation = $this->processLocation($location);
                        $locations[] = $processedLocation;
                    }
                    
                    return $locations;
                }
            } catch (\Exception $e) {
                Log::error('JSON parsing error', ['error' => $e->getMessage()]);
            }
        }
        
        // Fallback: parse as text and create structured data
        return $this->parseTextResponse($responseText);
    }

    /**
     * Process individual location and ensure menu for restaurants
     */
    private function processLocation($location)
    {
        $processedLocation = [
            'name' => $location['name'] ?? 'Unknown Location',
            'address' => $location['address'] ?? '',
            'type' => $location['type'] ?? 'location',
            'price_range' => $location['price_range'] ?? '',
            'description' => $location['description'] ?? '',
            'phone' => $location['phone'] ?? '',
            'website' => $location['website'] ?? '',
            'lat' => $location['lat'] ?? null,
            'lng' => $location['lng'] ?? null,
            'rating' => $location['rating'] ?? '',
            'hours' => $location['hours'] ?? '',
            'features' => $location['features'] ?? [],
        ];

        // For restaurants, ensure menu is included
        $isRestaurant = $this->isRestaurantType($location['type'] ?? '');
        
        if ($isRestaurant) {
            // Check if menu data exists
            $hasMenuTable = isset($location['menu_table']) && !empty($location['menu_table']);
            $hasMenuImage = isset($location['menu_image_url']) && !empty($location['menu_image_url']);
            
            if ($hasMenuTable) {
                $processedLocation['menu_table'] = $location['menu_table'];
            }
            
            if ($hasMenuImage) {
                $processedLocation['menu_image_url'] = $location['menu_image_url'];
            }
            
            // If no menu found, try to generate Google Photos search URL
            if (!$hasMenuTable && !$hasMenuImage) {
                $processedLocation['menu_image_url'] = $this->generateGooglePhotosMenuUrl($location['name'] ?? '');
                $processedLocation['menu_note'] = 'Menu image from Google Photos search';
            }
        }

        return $processedLocation;
    }

    /**
     * Check if location type is restaurant/food related
     */
    private function isRestaurantType($type)
    {
        $foodTypes = ['restaurant', 'cafe', 'bar', 'bistro', 'diner', 'pizzeria', 'bakery', 'food', 'eatery'];
        $type = strtolower($type);
        
        foreach ($foodTypes as $foodType) {
            if (strpos($type, $foodType) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate Google Photos search URL for restaurant menu
     */
    private function generateGooglePhotosMenuUrl($restaurantName)
    {
        $searchQuery = urlencode($restaurantName . ' menu');
        return "https://www.google.com/search?tbm=isch&q={$searchQuery}";
    }

    /**
     * Parse text response as fallback
     */
    private function parseTextResponse($responseText)
    {
        // Simple text parsing - split by lines and create basic structure
        $lines = explode("\n", $responseText);
        $locations = [];
        $currentLocation = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Look for location names (assuming they start with numbers or bullet points)
            if (preg_match('/^[\d\.\*\-\s]*(.+)/', $line, $matches)) {
                if ($currentLocation) {
                    $locations[] = $this->processLocation($currentLocation);
                }
                
                $currentLocation = [
                    'name' => trim($matches[1]),
                    'description' => '',
                    'type' => 'restaurant', // Default assumption
                ];
            } elseif ($currentLocation && !empty($line)) {
                $currentLocation['description'] .= $line . ' ';
            }
        }
        
        if ($currentLocation) {
            $locations[] = $this->processLocation($currentLocation);
        }
        
        return $locations;
    }

    /**
     * Get mock location data for testing
     */
    private function getMockLocationData($prompt)
    {
        $mockLocations = [
            [
                'name' => 'Sushi Zen Restaurant',
                'address' => '123 Main Street, Downtown',
                'type' => 'restaurant',
                'price_range' => '$$$',
                'description' => 'Authentic Japanese sushi experience with fresh daily ingredients and traditional preparation methods.',
                'phone' => '+1-555-0123',
                'website' => 'https://sushizen.example.com',
                'lat' => '40.7128',
                'lng' => '-74.0060',
                'rating' => '4.5/5',
                'hours' => 'Mon-Sun: 11:30 AM - 10:00 PM',
                'features' => ['dine-in', 'takeout', 'delivery', 'sake_bar'],
                'menu_table' => [
                    ['item' => 'California Roll', 'price' => '$12.99', 'description' => 'Fresh crab, avocado, cucumber'],
                    ['item' => 'Salmon Sashimi', 'price' => '$18.99', 'description' => '6 pieces of fresh salmon'],
                    ['item' => 'Chirashi Bowl', 'price' => '$24.99', 'description' => 'Assorted sashimi over sushi rice']
                ],
                'menu_image_url' => 'https://www.google.com/search?tbm=isch&q=sushi+zen+menu'
            ],
            [
                'name' => 'Bella Vista Italiana',
                'address' => '456 Food Avenue, Little Italy',
                'type' => 'restaurant',
                'price_range' => '$$',
                'description' => 'Traditional Italian cuisine with homemade pasta and wood-fired pizza in a cozy atmosphere.',
                'phone' => '+1-555-0456',
                'website' => 'https://bellavista.example.com',
                'lat' => '40.7589',
                'lng' => '-73.9851',
                'rating' => '4.3/5',
                'hours' => 'Tue-Sun: 5:00 PM - 11:00 PM',
                'features' => ['outdoor_seating', 'wine_bar', 'romantic'],
                'menu_table' => [
                    ['item' => 'Margherita Pizza', 'price' => '$16.99', 'description' => 'Fresh mozzarella, basil, tomato sauce'],
                    ['item' => 'Fettuccine Alfredo', 'price' => '$19.99', 'description' => 'Homemade pasta with creamy alfredo sauce'],
                    ['item' => 'Tiramisu', 'price' => '$8.99', 'description' => 'Classic Italian dessert with coffee and mascarpone']
                ],
                'menu_image_url' => 'https://www.google.com/search?tbm=isch&q=bella+vista+italiana+menu'
            ]
        ];

        return $mockLocations;
    }

    /**
     * Parse restaurant data from Gemini response
     */
    private function parseRestaurantsFromResponse(string $response): array
    {
        // First, try to extract JSON from markdown code blocks
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $response, $matches)) {
            $jsonString = $matches[1];
            $data = json_decode($jsonString, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['restaurants']) && is_array($data['restaurants'])) {
                return $data['restaurants'];
            }
        }
        
        // Try to extract JSON array directly from the response
        if (preg_match('/\[.*\]/s', $response, $matches)) {
            $jsonString = $matches[0];
            $restaurants = json_decode($jsonString, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($restaurants)) {
                return $restaurants;
            }
        }
        
        // Try to extract any JSON object that contains restaurants
        if (preg_match('/\{[^}]*"restaurants"\s*:\s*\[[^\]]*\][^}]*\}/s', $response, $matches)) {
            $jsonString = $matches[0];
            $data = json_decode($jsonString, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['restaurants']) && is_array($data['restaurants'])) {
                return $data['restaurants'];
            }
        }

        // Fallback: create structured data from text
        $lines = explode("\n", $response);
        $restaurants = [];
        $currentRestaurant = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Try to identify restaurant entries
            if (preg_match('/^\d+\.?\s*(.+)/', $line, $matches)) {
                if (! empty($currentRestaurant)) {
                    $restaurants[] = $currentRestaurant;
                }
                $currentRestaurant = [
                    'name' => trim($matches[1]),
                    'address' => '',
                    'cuisine' => '',
                    'price_range' => '',
                    'menu_url' => '',
                ];
            } elseif (! empty($currentRestaurant)) {
                // Try to categorize the line
                if (strpos(strtolower($line), 'address') !== false ||
                    strpos(strtolower($line), 'location') !== false) {
                    $currentRestaurant['address'] = $line;
                } elseif (strpos(strtolower($line), 'cuisine') !== false) {
                    $currentRestaurant['cuisine'] = $line;
                } elseif (strpos(strtolower($line), 'price') !== false ||
                         strpos($line, '$') !== false) {
                    $currentRestaurant['price_range'] = $line;
                } elseif (strpos(strtolower($line), 'menu') !== false ||
                         strpos(strtolower($line), 'http') !== false) {
                    $currentRestaurant['menu_url'] = $line;
                }
            }
        }

        if (! empty($currentRestaurant)) {
            $restaurants[] = $currentRestaurant;
        }

        return $restaurants;
    }

    /**
     * Get alternative recommendations after visiting a place
     */
    public function getAlternatives(Request $request)
    {
        $request->validate([
            'visited_place_name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
        ]);

        $visitedPlace = $request->input('visited_place_name');
        $city = $request->input('city');
        $geminiApiKey = config('services.gemini.api_key');

        try {
            $prompt = "I just visited {$visitedPlace} in {$city}. Suggest 2-3 alternative restaurants in the same city with similar cuisine or atmosphere. For each restaurant, provide: name, address, cuisine type, what makes it special, and if possible a menu link.";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$geminiApiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
            ]);

            if ($response->failed()) {
                return response()->json([
                    'message' => 'Failed to get alternative recommendations',
                ], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            $data = $response->json();
            $generatedText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            $alternatives = $this->parseRestaurantsFromResponse($generatedText);

            return response()->json([
                'message' => 'Alternative recommendations retrieved successfully',
                'visited_place' => $visitedPlace,
                'city' => $city,
                'alternatives' => $alternatives,
                'raw_response' => $generatedText,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting alternatives', [
                'error' => $e->getMessage(),
                'visited_place' => $visitedPlace,
                'city' => $city,
            ]);

            return response()->json([
                'message' => 'An error occurred while getting alternatives',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
