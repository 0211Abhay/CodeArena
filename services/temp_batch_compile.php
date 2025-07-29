<?php

session_start();
require_once '../config/sql.config.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Get POST data
$language = $_POST['language'] ?? 'cpp';
$code = $_POST['code'] ?? '';
$question_id = $_POST['question_id'] ?? null;

if (!$question_id) {
    echo "Error: Question ID not provided.";
    exit;
}

// Judge0 API endpoint
$apiUrl = "http://10.80.2.82:2358/submissions/batch?base64_encoded=true&wait=true";

// Map language to Judge0 language_id
$languageIds = [
    "c" => 50,
    "cpp" => 52,
    "java" => 62,
    "py" => 71,
    "js" => 63
];
$languageId = $languageIds[$language] ?? 71;

// Fetch all testcases from DB
try {
    $stmt = $conn->prepare("SELECT stdin, expected_output FROM question_testcases WHERE question_id = :question_id");
    $stmt->execute(['question_id' => $question_id]);
    $testcases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Received question_id = $question_id\n";
    echo "Fetched " . count($testcases) . " testcases.\n";

    if (!$testcases || count($testcases) === 0) {
        echo "❌ No testcases found for question_id = $question_id\n\n";
        $stmtAll = $conn->query("SELECT question_id, stdin, expected_output FROM question_testcases");
        $all = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

        if (count($all) === 0) {
            echo "📭 Your question_testcases table is empty.\n";
        } else {
            echo "📄 Available question_ids in DB:\n";
            foreach ($all as $row) {
                echo " - question_id: {$row['question_id']}, stdin: {$row['stdin']}, expected_output: {$row['expected_output']}\n";
            }
        }

        exit;
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    exit;
}

// Prepare batch payload
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

if (empty($batch)) {
    echo "Error: No submissions to send to Judge0.";
    exit;
}

// ✅ FIX: Send wrapped payload with "submissions" key
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['submissions' => $batch])); // ✅ FIXED HERE
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

if ($response === false) {
    echo "Error: Batch submission failed.";
    exit;
}

// Decode Judge0 response
$results = json_decode($response, true);

// Check if structure is valid
if (!isset($results['submissions']) || !is_array($results['submissions'])) {
    echo "Error: Unexpected response from Judge0 API.<br>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    exit;
}

$all_passed = true;

// Display results for each testcase
foreach ($results['submissions'] as $index => $res) {
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
