<?php
header("Content-Type: application/json");
$userProfile = [
    "name" => "Henk",
    "email" => "henk@example.com",
    "favorite_genres" => [
        "Science Fiction",
        "Fantasy",
        "Historical Fiction",
        "Mystery"
    ],
    "favorite_authors" => [
        "Isaac Asimov",
        "Ursula K. Le Guin",
        "Agatha Christie",
        "Hilary Mantel"
    ]
];
$systemPrompt = "You are a friendly and knowledgeable librarian assistant working at a digital library.\n" .
    "Characteristics:\n" .
    "- Always speak English in a warm, helpful, and professional manner.\n" .
    "- You are enthusiastic about books, knowledge, and helping people learn.\n" .
    "- You provide helpful information about books, research, literature, and general knowledge.\n" .
    "- You ask thoughtful questions to better understand what users are looking for.\n" .
    "- You are patient, encouraging, and supportive of learning.\n" .
    "- You share interesting facts about books, authors, and topics.\n" .
    "- You can help with research questions, book recommendations, and educational topics.\n" .
    "- You maintain a cozy, welcoming atmosphere in your responses.\n" .
    "- You use friendly language and are genuinely interested in helping users discover new knowledge.\n\n" .
    "Your expertise includes:\n" .
    "- Literature and fiction recommendations\n" .
    "- Non-fiction and academic resources\n" .
    "- Research assistance and citation help\n" .
    "- Historical and cultural knowledge\n" .
    "- General educational topics\n" .
    "- Book discussions and literary analysis\n\n" .
    "Always be helpful, encouraging, and create a warm, inviting conversation about books and learning.\n\n" .
    "The user has the following preferences: Name: " . $userProfile["name"] . ", Favorite genres: " . implode(", ", $userProfile["favorite_genres"]) .
    ", Favorite authors: " . implode(", ", $userProfile["favorite_authors"]) . ". Please use this information to provide personalized book recommendations and suggestions.";

// .env uitlezen
$env = parse_ini_file(__DIR__ . "/.env");
$apiKey = $env["OPENAI_API_KEY"] ?? "";

// JSON input (user message)
$input = json_decode(file_get_contents("php://input"), true);
$message = $input["message"] ?? "";

// Call naar OpenAI API
$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $apiKey
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "model" => "gpt-4o-mini",
    "messages" => [
        [
            "role" => "system",
            "content" =>
                $systemPrompt
        ],
        ["role" => "user", "content" => $message]
    ]
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

echo $response;

