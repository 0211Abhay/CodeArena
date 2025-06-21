<?php

session_start(); 
require_once '../config/db.config.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Input from frontend
$language = strtolower($_POST['language']);
$code = $_POST['code'];
$question_id = $_POST['question_id'] ?? null;
$enrollment_no = $_SESSION['user'] ?? null;

if (!$question_id || !$enrollment_no) {
    echo "Error: Missing question_id or user not logged in.";
    exit;
}

// Fetch question details
try {
    $stmt = $conn->prepare("SELECT testcase, expected_output FROM questions WHERE question_id = :question_id");
    $stmt->execute(['question_id' => $question_id]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$question) {
        echo "Error: Question not found.";
        exit;
    }

    $stdin = $question['testcase'];
    $expected_output = $question['expected_output'];
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    exit;
}

// Judge0 Setup
$apiUrl = "http://10.80.2.206:2358/submissions?base64_encoded=true&wait=false";
$languageIds = [
    "c" => 50,
    "cpp" => 52,
    "java" => 62,
    "py" => 71,
    "js" => 63
];
$languageId = $languageIds[$language] ?? 71;

$data = [
    "source_code" => base64_encode($code),
    "language_id" => $languageId,
    "stdin" => base64_encode($stdin),
    "cpu_time_limit" => 1.0,
    "expected_output" => base64_encode($expected_output),
    "max_output_size" => 10240,
    "memory_limit" => 128000,
    "stack_limit" => 64000
];

// Submit code
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

if ($response === false) {
    echo "Error: Unable to connect to Judge0 API.";
    exit;
}

$result = json_decode($response, true);
$token = $result['token'] ?? null;

if (!$token) {
    echo "Error: Failed to retrieve submission token.";
    exit;
}

// Save submission token to DB
try {
    $stmt = $conn->prepare("INSERT INTO submissions (enrollment_no, question_id, submission_id) VALUES (:enrollment_no, :question_id, :token)");
    $stmt->execute([
        'enrollment_no' => $enrollment_no,
        'question_id' => $question_id,
        'token' => $token
    ]);
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    exit;
}

// Fetch submission result (polling)
$statusUrl = "http://10.80.2.206:2358/submissions/$token?base64_encoded=true&wait=true";

while (true) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $statusUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);

    if ($result === false) {
        echo "Error: Failed to retrieve submission result.";
        exit;
    }

    $result = json_decode($result, true);
    $statusId = $result['status']['id'];
    $statusDescription = $result['status']['description'];
    

    $actual_output = base64_decode($result['stdout'] ?? '');
        $expected = $expected_output;
    
        // Normalize line endings (Windows vs Linux)
        $actual_output = str_replace("\r\n", "\n", trim($actual_output));
        $expected = str_replace("\r\n", "\n", trim($expected));


    if ($statusId == 3) {
        
    
        echo "Token: $token\n";
        echo "Input:\n$stdin\n";
        echo "Expected Output:\n$expected\n";
        echo "Actual Output:\n$actual_output\n";
    
        if ($actual_output === $expected) {
            echo "Verdict: ✅ Correct Answer ($statusDescription)";
        } else {
            echo "Verdict: ❌ Wrong Answer ($statusDescription)";
        }
        break;
    }

    if ($statusId == 4) {
       
    
        echo "Token: $token\n";
        echo "Input:\n$stdin\n";
        echo "Expected Output:\n$expected\n";
        echo "Actual Output:\n$actual_output\n";
    
        if ($actual_output === $expected) {
            echo "Verdict: ✅ Correct Answer ($statusDescription)";
        } else {
            echo "Verdict: ❌ Wrong Answer ($statusDescription)";
        }
        break;
    }
    if (in_array($statusId, [6, 7, 8, 9, 10, 11, 12])) {
        $errorMessage = base64_decode($result['stderr'] ?? $result['compile_output'] ?? "Unknown error.");
        echo "Error ($statusDescription): $errorMessage";
        break;
    }

    if ($statusId == 5) {
        echo "Error (Time Limit Exceeded): Execution took too long.";
        break;
    }

    if ($statusId == 13) {
        echo "Internal Error: An unexpected error occurred.";
        break;
    }

    usleep(100000); // 100 ms
}


?>