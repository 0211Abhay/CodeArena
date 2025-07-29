<?php

session_start();
require_once '../config/sql.config.php';

// Allow CORS for development
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// === Setup logging ===
$logFile = __DIR__ . '/log.txt';
function writeLog($message) {
    global $logFile;
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] " . $message . "\n", FILE_APPEND);
}

// === Get POST data ===
$language = $_POST['language'] ?? 'cpp';
$code = $_POST['code'] ?? '';
$question_id = $_POST['question_id'] ?? null;

if (!$question_id) {
    echo "Error: Question ID not provided.";
    exit;
}

// Judge0 API endpoint
$apiUrl = "http://10.80.19.77:2358/submissions/batch?base64_encoded=true&wait=true";

// Language mapping
$languageIds = [
    "c" => 50,
    "cpp" => 52,
    "java" => 62,
    "py" => 71,
    "js" => 63
];
$languageId = $languageIds[$language] ?? 71;

// === Fetch testcases ===
try {
    $stmt = $conn->prepare("SELECT stdin, expected_output FROM question_testcases WHERE question_id = :question_id");
    $stmt->execute(['question_id' => $question_id]);
    $testcases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Received question_id = $question_id\n";
    echo "Fetched " . count($testcases) . " testcases.\n";

    if (!$testcases || count($testcases) === 0) {
        echo "❌ No testcases found for question_id = $question_id\n\n";
        exit;
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    exit;
}

// === Prepare batch payload ===
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

// === Log request payload ===
$payload = json_encode(['submissions' => $batch], JSON_PRETTY_PRINT);
writeLog("=== API CALL START ===\nURL: $apiUrl\nPayload:\n$payload");

// === Submit to Judge0 ===
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$start = microtime(true);
$response = curl_exec($ch);
$end = microtime(true);
$duration = round($end - $start, 3);

// === Handle cURL errors ===
if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);
    writeLog("❌ CURL ERROR: $error\n⏱️ Duration: {$duration}s\n=== API CALL END ===\n");
    echo "❌ cURL Error: $error\n";
    exit;
}
curl_close($ch);

// === Log API response ===
writeLog("✅ RESPONSE:\n$response\n⏱️ Duration: {$duration}s\n=== API CALL END ===\n");

// === Decode and process ===
$results = json_decode($response, true);
if (!isset($results['submissions']) || !is_array($results['submissions'])) {
    echo "Error: Unexpected response from Judge0 API.\n";
    echo "Raw response:\n";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    exit;
}

// === Initialize tracking ===
$total = count($results['submissions']);
$passed = 0;
$failed = 0;

$all_passed = true;

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
        $failed++;
    } else {
        $passed++;
    }

    echo "Testcase #" . ($index + 1) . ":\n";
    echo "Input:\n$stdin\n";
    echo "Expected:\n$expected\n";
    echo "Actual:\n$actual\n";
    echo "Status: $statusText\n";
    echo "Verdict: $verdict\n\n";
}

// === Final Verdict ===
$successRate = $total > 0 ? round(($passed / $total) * 100, 2) : 0;
echo "Final Verdict: " . ($all_passed ? "✅ All Testcases Passed" : "❌ Some Testcases Failed") . "\n";
echo "✅ Passed: $passed\n❌ Failed: $failed\n📊 Success Rate: $successRate%\n";

// === Log verdict stats ===
writeLog("📊 SUMMARY: Total = $total | ✅ Passed = $passed | ❌ Failed = $failed | Success Rate = $successRate%\n");

?>
