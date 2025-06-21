// Starter code templates
const starterCode = {
  js: `console.log('Hello, World!')`,
  py: `print('Hello, World!')`,
  java: `public class Main {
    public static void main(String[] args) {
        // Write your solution here
        System.out.println("Hello, World!");
    }
}`,
  cpp: `#include <bits/stdc++.h>
using namespace std;

int main() {
    // Write your solution here
    cout << "Hello, World!";
    return 0;
}`,

  c: `#include <stdio.h>

int main(){
    // Write your solution here
    printf("Hello, World!");
    return 0;
}`,
};

// CodeMirror mode mapping
const codeMirrorModes = {
  js: "javascript",
  py: "python",
  java: "text/x-java",
  cpp: "text/x-c++src",
  c: "text/x-csrc",
};

// Initialize CodeMirror
const editor = CodeMirror.fromTextArea(document.getElementById("code-editor"), {
  lineNumbers: true,
  theme: "monokai",
  mode: "text/x-csrc",
  indentUnit: 4,
  indentWithTabs: true,
  smartIndent: true,
  lineWrapping: true,
  autoCloseBrackets: true,
  matchBrackets: true,
  autoCloseTags: true,
  extraKeys: {
    Tab: function (cm) {
      if (cm.somethingSelected()) {
        cm.indentSelection("add");
      } else {
        cm.replaceSelection("    ", "end");
      }
    },
  },
});

// Set initial code
editor.setValue(starterCode.c);

// Language selector
const languageSelector = document.getElementById("language-selector");
languageSelector.addEventListener("change", function () {
  const language = this.value;
  editor.setOption("mode", codeMirrorModes[language]);
  editor.setValue(starterCode[language]);
});

// Tab switching
const tabs = document.querySelectorAll(".test-case-tab");
tabs.forEach((tab) => {
  tab.addEventListener("click", function () {
    tabs.forEach((t) => t.classList.remove("active"));
    this.classList.add("active");

    const isCustom = this.textContent === "Custom";
    const testCaseContent = document.querySelector(".test-case-content");

    if (isCustom) {
      testCaseContent.innerHTML = `<textarea class="custom-input" placeholder="Enter your test case here...">[1,2,3,1]</textarea>`;
    } else {
      const testCases = {
        "Case 1": "[1,2,3,1]",
        "Case 2": "[1,2,3,4]",
        "Case 3": "[1,1,1,3,3,4,3,2,4,2]",
      };
      testCaseContent.innerHTML = `<textarea class="custom-input" readonly>${
        testCases[this.textContent]
      }</textarea>`;
    }
  });
});

// Run button
const runButton = document.querySelector(".run");
// runButton.addEventListener('click', function() {
//     const outputPanel = document.querySelector('.output-panel');
//     outputPanel.textContent = '> Running...';

//     // Simulate execution delay
//     setTimeout(() => {
//         // Get the current test case
//         const activeTab = document.querySelector('.test-case-tab.active');
//         const input = document.querySelector('.custom-input').value;

//         // This is where you'd actually run the code
//         // For demo purposes, we'll just show sample output
//         if (activeTab.textContent === 'Case 1' || input.includes('1,2,3,1')) {
//             outputPanel.textContent = '> Output: true\n> Execution time: 52 ms\n> Memory usage: 46.2 MB';
//         } else if (activeTab.textContent === 'Case 2' || input.includes('1,2,3,4')) {
//             outputPanel.textContent = '> Output: false\n> Execution time: 48 ms\n> Memory usage: 45.8 MB';
//         } else {
//             outputPanel.textContent = '> Output: true\n> Execution time: 64 ms\n> Memory usage: 47.5 MB';
//         }
//     }, 800);
// });

// Submit button
const submitButton = document.querySelector(".submit");
submitButton.addEventListener("click", function () {
  const outputPanel = document.querySelector(".output-panel");
  outputPanel.textContent = "> Submitting solution...";

  // Simulate submission delay
  setTimeout(() => {
    outputPanel.innerHTML =
      '> <span style="color: #4cc9f0;">Submission successful!</span>\n> All test cases passed.\n> Runtime: 56 ms (faster than 85.32% of submissions)\n> Memory: 46.8 MB (less than 67.21% of submissions)';

    // Show a notification
    alert("Solution submitted successfully! All test cases passed.");
  }, 1500);
});

// Reset button
const resetButton = document.querySelector(".reset");
resetButton.addEventListener("click", function () {
  const language = languageSelector.value;
  editor.setValue(starterCode[language]);
  const outputPanel = document.querySelector(".output-panel");
  outputPanel.textContent = "> Code reset. Run your code to see output";
});

// function executeCode() {
//     $.ajax({
//         url: "http://10.80.2.206/HCD_Project/services/compiler.php",
//         method: "POST",
//         data: {
//             language: $("#language-selector").val(),
//             code: editor.getValue()
//         },
//         success: function(response) {
//             $("#output-panel").html(response.replace(/\n/g, "<br>"));
//         }
//     });
// }
function executeCode() {
  const questionId = new URLSearchParams(window.location.search).get("question_id");
  const language = $("#language-selector").val();
  const code = editor.getValue();
  const outputPanel = $("#output-panel");

  outputPanel.html("> Submitting 100 batch executions...<br>");

  let successCount = 0;
  let failureCount = 0;
  const totalRequests = 100;

  for (let i = 1; i <= totalRequests; i++) {
    $.ajax({
      url: "http://10.80.1.28/CodeArena/services/batch_compile.php",
      method: "POST",
      data: {
        language: language,
        code: code,
        question_id: questionId,
      },
      success: function (response) {
        successCount++;
        appendOutput(i, true, response);
        checkDone();
      },
      error: function (xhr) {
        failureCount++;
        const errorMsg = xhr.responseText || "Unknown error";
        appendOutput(i, false, errorMsg);
        checkDone();
      },
    });
  }

  function appendOutput(runNum, isSuccess, content) {
    const prefix = isSuccess ? "[✔]" : "[✖]";
    const color = isSuccess ? "#4caf50" : "#f44336";
    outputPanel.append(
      `<div style="color: ${color}; margin-bottom: 12px;">
        ${prefix} Run ${runNum}:\n${content.replace(/\n/g, "<br>")}
      </div><hr style="border: none; border-top: 1px solid #444;">`
    );
  }

  function checkDone() {
    if (successCount + failureCount === totalRequests) {
      outputPanel.append(`<div style="font-weight: bold;">✅ All done: ${successCount} Success, ❌ ${failureCount} Failed</div>`);
    }
  }
}

// Clear output button
const clearButton = document.querySelector(".clear-output");
clearButton.addEventListener("click", function () {
  document.getElementById("output-panel").innerHTML = "> Output cleared.";
});
