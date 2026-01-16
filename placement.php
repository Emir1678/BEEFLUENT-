<?php
require_once __DIR__ . "/inc/init.php";
require_login();
$user = current_user();
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Placement Test - BeeFluent</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    /* Fontu BeeFluent standartlarına (Inter) eşitleyen eklemeler */
    body,
    .test-card,
    .question-title,
    .option-label,
    .btn-gen,
    .btn-finish,
    .progress-info,
    #genStatus {
      font-family: 'Inter', sans-serif !important;
      -webkit-font-smoothing: antialiased;
    }

    /* Soruların daha modern ve okunaklı durması için */
    .question-title {
      font-weight: 700 !important;
      line-height: 1.5;
      color: #2D3436;
    }

    .option-label {
      font-weight: 500;
      cursor: pointer;
      display: block;
      padding: 10px;
      transition: background 0.2s;
    }

    /* İlerleme çubuğundaki metinler */
    .progress-info {
      font-weight: 700;
      font-size: 0.9rem;
      color: #636e72;
    }

    /* Buton metinlerini kalınlaştıralım */
    .btn-gen,
    .btn-finish {
      font-weight: 800 !important;
      letter-spacing: -0.01em;
    }
  </style>
</head>

<body class="page-placement">

  <header>
    <a href="dashboard.php" class="logo">BeeFluent</a>
    <nav class="nav-links">
      <a href="dashboard.php">Dashboard</a>
      <a href="chat.php">Tutor</a>
      <a href="logout.php" style="color: #ef4444;">Log out</a>
    </nav>
  </header>

  <div class="container">
    <div class="test-card">
      <div class="test-header">
        <h2>Placement Test</h2>
        <p>Your AI tutor will adapt its answers based on your level.</p>
      </div>

      <button id="genBtn" class="btn-gen" type="button">Generate Test</button>
      <p id="genStatus"></p>

      <form id="testForm" method="post" action="api/placement_submit.php" style="display:none;">

        <div class="test-progress-wrapper">
          <div class="progress-info">
            <span>Question <span id="current-question">0</span> / <span id="total-questions">0</span></span>
            <span id="progress-percentage">0% Completed</span>
          </div>
          <div class="bee-progress-container">
            <div class="bee-progress-fill" id="beeBar" style="width: 0%;">
              <div class="flying-bee">🐝</div>
            </div>
          </div>
        </div>
        <div id="questions"></div>
        <button type="submit" class="btn-finish">Finish Test & View Result</button>
      </form>

      <div id="result" style="display:none;"></div>
    </div>
  </div>

  <script>
    const genBtn = document.getElementById("genBtn");
    const genStatus = document.getElementById("genStatus");
    const form = document.getElementById("testForm");
    const questionsDiv = document.getElementById("questions");
    const resultEl = document.getElementById("result");
    const beeBar = document.getElementById("beeBar");
    const curQEl = document.getElementById("current-question");
    const totalQEl = document.getElementById("total-questions");
    const progressText = document.getElementById("progress-percentage");

    async function fetchJson(url, options = {}) {
      const res = await fetch(url, {
        credentials: "same-origin",
        cache: "no-store",
        ...options
      });
      const text = await res.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        throw new Error(`Could not parse JSON (${url})`);
      }
      if (!res.ok) throw new Error(data?.error || `HTTP ${res.status}`);
      return data;
    }

    function updateProgress() {
      const total = form.querySelectorAll(".question-box").length;
      const answered = form.querySelectorAll('input[type="radio"]:checked').length;
      const percentage = total > 0 ? Math.round((answered / total) * 100) : 0;

      beeBar.style.width = percentage + "%";
      progressText.textContent = `${percentage}% Completed`;
      curQEl.textContent = answered;
      totalQEl.textContent = total;
    }

    genBtn.addEventListener("click", async () => {
      genBtn.disabled = true;
      genBtn.style.display = "none";
      genStatus.innerHTML = '<span style="color: var(--primary)">Generating your test… please wait.</span>';
      resultEl.style.display = "none";
      form.style.display = "none";

      try {
        await fetchJson("api/placement_generate.php");
        const qData = await fetchJson("placement_questions_json.php");
        genStatus.innerHTML = `✅ Test ready! Please answer all questions.`;
        renderQuestions(qData.questions);
        form.style.display = "block";
        updateProgress();
      } catch (e) {
        genStatus.textContent = "Error: " + e.message;
        genBtn.style.display = "block";
      } finally {
        genBtn.disabled = false;
      }
    });

    function renderQuestions(questions) {
      questionsDiv.innerHTML = "";
      questions.forEach((q, i) => {
        const box = document.createElement("div");
        box.className = "question-box";
        const title = document.createElement("div");
        title.className = "question-title";
        title.innerHTML = `Question ${i + 1}: <span style="font-weight:400">${escapeHtml(q.prompt)}</span> <span class="diff-badge">${escapeHtml(q.diff)}</span>`;
        box.appendChild(title);

        q.options.forEach((opt, idx) => {
          const line = document.createElement("label");
          line.className = "option-label";
          line.innerHTML = `
            <input type="radio" name="ans[${i}]" value="${idx}" required onchange="updateProgress()">
            ${escapeHtml(opt)}
          `;
          box.appendChild(line);
        });
        questionsDiv.appendChild(box);
      });
    }

    function escapeHtml(s) {
      return (s + "").replace(/[&<>"']/g, c => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;"
      } [c]));
    }

    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      try {
        const fd = new FormData(form);
        const data = await fetchJson("api/placement_submit.php", {
          method: "POST",
          body: fd
        });
        resultEl.style.display = "block";
        resultEl.innerHTML = `<strong>Result:</strong> ${data.percentage}% — <strong>Level:</strong> ${escapeHtml(data.level)}`;
        form.style.display = "none";
        genBtn.textContent = "Restart Test";
        genBtn.style.display = "block";
      } catch (e) {
        alert("Submit error: " + e.message);
      }
    });
  </script>
</body>

</html>
