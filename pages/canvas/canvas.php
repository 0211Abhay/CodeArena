<?php
require_once '../../config/sql.config.php';

require '../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

try {
    if (isset($_GET['question_id'])) {
        $question_id = $_GET['question_id'];
    
        // Use prepared statement for safety
        $stmt = $conn->prepare('SELECT * FROM questions WHERE question_id = :question_id');
        $stmt->execute(['question_id' => $question_id]);
        $questions = $stmt->fetchAll();
        $question = $questions[0];
    
    } else {
        header("Location: ./question_list.php");
        exit;
    }
} catch (\PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
    die();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeArena | Competitive Programming Platform</title>
    
    <!-- External Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/inter/3.19.3/inter.css" rel="stylesheet">
    
    <!-- CodeMirror CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
    
    <!-- CodeMirror JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/closebrackets.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/matchbrackets.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/clike/clike.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    
    <link rel="stylesheet" href="./canvas.css">

</head>
<body>

     <?php include '../../includes/header.php';?>

    <div class="container">
        <main>
            <div class="problem-panel">
                <div class="problem-header">
                    <div class="problem-title"><?php echo htmlspecialchars($question['question_id']); ?> . <?php echo htmlspecialchars($question['question_title']); ?></div>


                    <?php
    $difficulty = htmlspecialchars($question['difficulty']);

    // Define class based on difficulty
    $difficultyClass = '';
    if ($difficulty === 'Easy') {
        $difficultyClass = 'easy';
    } elseif ($difficulty === 'Medium') {
        $difficultyClass = 'medium';
    } elseif ($difficulty === 'Hard') {
        $difficultyClass = 'hard';
    }
?>

<div class="problem-difficulty <?php echo $difficultyClass; ?>">
    <?php echo $difficulty; ?>
</div>

                </div>
                <div class="problem-meta">
                    <?php
                    $tags = explode(',', $question['tags']);

                    for ($i = 0; $i < count($tags); $i++) {
                        $tags[$i] = trim($tags[$i]);

                        echo '<div class="tag">' . $tags[$i] . '</div>';
                    }
                    ?>
                </div>
                <div class="problem-content">
                    <p><?php echo htmlspecialchars($question['description']); ?></p>
                    
                    <h3>Example 1:</h3>
                    <?php  echo '<div class="code-example"><b>Input:</b>' .  htmlspecialchars($question['example_testcase_1']) . '<br><b>Output:</b>' .  htmlspecialchars($question['example_outcome_1']) . '<br><b>Explanation:</b> ' .  htmlspecialchars($question['explanation_1']).'</div>';?>
                    <h3>Example 2:</h3>
                    <?php  echo '<div class="code-example"><b>Input:</b>' .  htmlspecialchars($question['example_testcase_2']) . '<br><b>Output:</b>' .  htmlspecialchars($question['example_outcome_2']) . '<br><b>Explanation:</b> ' .  htmlspecialchars($question['explanation_2']).'</div>';?>
                    <h3>Example 3:</h3>
                    <?php  echo '<div class="code-example"><b>Input:</b>' .  htmlspecialchars($question['example_testcase_3']) . '<br><b>Output:</b>' .  htmlspecialchars($question['example_outcome_3']) . '<br><b>Explanation:</b> ' .  htmlspecialchars($question['explanation_3']).'</div>';?>
                    
                    <h3>Constraints:</h3>
                    <ul>
                    <?php
                    $constraints = explode(',', $question['constraints']);

                    for ($i = 0; $i < count($constraints); $i++) {
                        $constraints[$i] = trim($constraints[$i]);

                        echo '<li>' . $constraints[$i] . '</li>';
                    }
                    ?>
                    </ul>
                </div>
            </div>

            <div class="editor-panel">
                <div class="editor-header">
                    <div class="language-select">
                        <span>Language:</span>
                        <select id="language-selector" class="language-selector" title="Select programming language">
                            <option value="c">C</option>
                            <option value="cpp">C++</option>
                            <option value="java">Java</option>
                            <option value="py">Python</option>
                            <option value="js">JavaScript</option>
                        </select>
                    </div>
                    <div class="editor-actions">
                        <button class="reset">Reset</button>
                        <button class="run" onclick="executeCode()">Run</button>
                        <button class="submit">Submit</button>
                    </div>
                </div>
                <div class="editor-container">
                    <textarea 
                        id="code-editor"
                        title="Code editor"
                        placeholder="Write your code here..."
                    ></textarea>    
                </div>
                <div class="editor-footer">
                    <div class="test-cases">
                        <h1>Output</h1>
                    </div>
                    <div class="output-panel" id="output-panel">
                        > Run your code to see output
                    </div>
                </div>
            </div>

        </main>
        <?php include '../../includes/footer.php';?>
    </div>

    <script src="./canvas.js"></script>

</body>
</html>
