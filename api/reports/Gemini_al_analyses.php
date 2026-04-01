<?php

function analyzeGrowerData($prompt,$growerData) {
    $apiKey = "AIzaSyDFP7HW8KG2yZxMTVadL-WsEpunFtsQyK0";
    // Using gemini-1.5-flash for speed and cost-efficiency with data analysis
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $apiKey;

    // Convert your PHP data array to a JSON string for the prompt
    $jsonContext = json_encode($growerData, JSON_PRETTY_PRINT);

    // $prompt = "You are an expert agronomist:
    
    // Please provide:
    // 1. A high-level summary of performance.
    // 2. Any growers at risk of low yield.
    // 3. One actionable recommendation for next week.
    // Format the response in clean Markdown.";
    $prompt.=". Analyze the following grower data in JSON format 

       $jsonContext

    ";

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Set to true in production with valid certs

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        return "Error: " . curl_error($ch);
    }

    curl_close($ch);
    $result = json_decode($response, true);



    return $result['candidates'][0]['content']['parts'][0]['text'] ?? "No analysis generated.";
}

// Example Data from your Grower System
// $growerStats = [
//     ["name" => "Farm Alpha", "yield_est" => 4.5, "moisture_level" => "Low", "last_spray" => "2024-05-10"],
//     ["name" => "Green Valley", "yield_est" => 5.2, "moisture_level" => "Optimal", "last_spray" => "2024-05-12"],
//     ["name" => "Delta Fields", "yield_est" => 3.1, "moisture_level" => "Critical", "last_spray" => "2024-04-28"]
// ];

//echo analyzeGrowerData($prompt,$growerData);