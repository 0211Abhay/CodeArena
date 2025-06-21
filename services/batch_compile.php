<?php

session_start();
require_once '../config/db.config.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Setup
$apiUrl = "http://10.80.1.28:2358/submissions/batch?base64_encoded=true&wait=true";
$languageIds = [
    "c" => 50,
    "cpp" => 52,
    "java" => 62,
    "py" => 71,
    "js" => 63
];
$languageId = $languageIds[$language] ?? 71;

// Fetch all testcases
try {
    $stmt = $conn->prepare("SELECT stdin, expected_output FROM question_testcases WHERE question_id = :question_id");
    $stmt->execute(['question_id' => $question_id]);
    $testcases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$testcases) {
        echo "Error: No testcases found.";
        exit;
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    exit;
}

// Build batch submissions
$batch = [];
foreach ($testcases as $tc) {
    $batch[] = [
        "source_code" => base64_encode($code),
        "language_id" => $languageId,
        "stdin" => base64_encode($tc['stdin']),
        "expected_output" => base64_encode($tc['expected_output']),
        "cpu_time_limit" => 1.0,
        "max_output_size" => 10240,
        "memory_limit" => 128000,
        "stack_limit" => 64000
    ];
}

// Submit batch
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($batch));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

if ($response === false) {
    echo "Error: Batch submission failed.";
    exit;
}

// Parse results
$results = json_decode($response, true);
$all_passed = true;

foreach ($results as $index => $res) {
    $statusId = $res['status']['id'];
    $statusText = $res['status']['description'];
    $stdin = base64_decode($batch[$index]['stdin']);
    $expected = base64_decode($batch[$index]['expected_output']);
    $actual = base64_decode($res['stdout'] ?? '');
    $actual = str_replace("\r\n", "\n", trim($actual));
    $expected = str_replace("\r\n", "\n", trim($expected));
    $verdict = ($statusId === 3 && $actual === $expected) ? "✅ Passed" : "❌ Failed";

    if ($verdict === "❌ Failed") {
        $all_passed = false;
    }

    echo "Testcase #" . ($index + 1) . ":\n";
    echo "Input:\n$stdin\n";
    echo "Expected:\n$expected\n";
    echo "Actual:\n$actual\n";
    echo "Status: $statusText\n";
    echo "Verdict: $verdict\n\n";
}

// Final Verdict
echo "Final Verdict: " . ($all_passed ? "✅ All Testcases Passed" : "❌ Some Testcases Failed");

?>
