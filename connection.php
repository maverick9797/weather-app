<?php
// Allow CORS for any domain (You can restrict this to your frontend domain for security)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Database connection details
$serverName = "sql100.infinityfree.com";
$userName = "if0_39385539";
$password = "ii9yqOB7T5CxqsO";
$databaseName = "if0_39385539_weatherapps";

// Create connection to the database
$conn = mysqli_connect($serverName, $userName, $password, $databaseName);
if (!$conn) {
    http_response_code(500);
    die(json_encode(["error" => "Database connection failed: " . mysqli_connect_error()]));
}

// Create the table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS tbl_weather (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city VARCHAR(100) NOT NULL,
    Humidity VARCHAR(10),
    temp_data VARCHAR(10),
    weather VARCHAR(100),
    windspeed VARCHAR(10),
    pressure VARCHAR(10),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
if (!mysqli_query($conn, $createTable)) {
    http_response_code(500);
    die(json_encode(["error" => "Failed to create table: " . mysqli_error($conn)]));
}

// Get city name from request
$city = isset($_GET['city']) ? trim($_GET['city']) : "Auburn";
if (empty($city)) {
    http_response_code(400);
    die(json_encode(["error" => "City name cannot be empty"]));
}

// Fetch the latest weather data
$query = "SELECT * FROM tbl_weather WHERE city = '" . mysqli_real_escape_string($conn, $city) . "' ORDER BY timestamp DESC LIMIT 1";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

// Check if data is older than 2 hours or not found
if (!$row || (strtotime($row['timestamp']) < strtotime('-2 hours'))) {
    $apiKey = "2f74ba525fdbeb187bd774860a10c27f";
    $url = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($city) . "&units=metric&appid=" . $apiKey;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    if ($response === FALSE) {
        http_response_code(500);
        die(json_encode(["error" => "Error fetching weather data: " . curl_error($ch)]));
    }
    curl_close($ch);

    $data = json_decode($response, true);
    if (!isset($data['main']['temp'])) {
        http_response_code(500);
        die(json_encode(["error" => "Invalid weather data received"]));
    }

    // Extract weather info
    $humidity = $data['main']['humidity'] ?? '';
    $temp_data = $data['main']['temp'] ?? '';
    $weatherDescription = $data['weather'][0]['description'] ?? '';
    $windspeed = $data['wind']['speed'] ?? '';
    $pressure = $data['main']['pressure'] ?? '';

    // Insert data directly
    $insertQuery = "INSERT INTO tbl_weather (city, Humidity, temp_data, weather, windspeed, pressure) 
                    VALUES ('" . mysqli_real_escape_string($conn, $city) . "', 
                            '" . mysqli_real_escape_string($conn, $humidity) . "', 
                            '" . mysqli_real_escape_string($conn, $temp_data) . "', 
                            '" . mysqli_real_escape_string($conn, $weatherDescription) . "', 
                            '" . mysqli_real_escape_string($conn, $windspeed) . "', 
                            '" . mysqli_real_escape_string($conn, $pressure) . "')";

    if (!mysqli_query($conn, $insertQuery)) {
        http_response_code(500);
        die(json_encode(["error" => "Data not inserted: " . mysqli_error($conn)]));
    }

    // Re-fetch the newly inserted data
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
}

// Return JSON
echo json_encode($row);
mysqli_close($conn);
?>
