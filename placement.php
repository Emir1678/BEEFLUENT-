<?php
require_once __DIR__ . "/inc/init.php";
require_login();
$user = current_user();
?>
<!doctype html>
<html lang="tr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Seviye Belirleme - AI Tutor</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #6366f1;
      --primary-hover: #4f46e5;
      --bg-main: #f8fafc;
      --white: #ffffff;
      --text-dark: #1e293b;
      --text-muted: #64748b;
      --border: #e2e8f0;
      --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
      --radius: 12px;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-main);
      color: var(--text-dark);
      line-height: 1.6;
    }

    header {
      background: var(--white);
      padding: 1rem 2rem;
      box-shadow: var(--shadow);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .logo {
      font-size: 1.25rem;
      font-weight: 800;
      color: var(--primary);
      text-decoration: none;
    }

    .nav-links a {
      text-decoration: none;
      color: var(--text-muted);
      font-weight: 500;
      margin-left: 1.5rem;
      font-size: 0.9rem;
      transition: 0.2s;
    }

    .nav-links a:hover {
      color: var(--primary);
    }

    .container {
      max-width: 800px;
      margin: 3rem auto;
      padding: 0 1.5rem;
    }

    .test-card {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 2.5rem;
      border: 1px solid var(--border);
    }

    .test-header {
      text-align: center;
      margin-bottom: 2rem;
    }

    .test-header h2 {
      font-size: 1.75rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .test-header p {
      color: var(--text-muted);
    }

    .btn-gen {
      background: var(--primary);
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: 0.2s;
      display: block;
      margin: 0 auto;
    }

    .btn-gen:hover {
      background: var(--primary-hover);
      transform: translateY(-1px);
    }

    #genStatus {
      text-align: center;
      margin-top: 1rem;
      font-size: 0.9rem;
      font-weight: 500;
    }

    /* Soru Kutuları */
    .question-box {
      background: #fcfcfd;
      border: 1px solid var(--border);
      padding: 1.5rem;
      border-radius: 10px;
      margin: 1.5rem 0;
    }

    .question-title {
      font-weight: 600;
      margin-bottom: 1rem;
      display: block;
    }

    .diff-badge {
      font-size: 0.7rem;
      background: #e2e8f0;
      padding: 2px 8px;
      border-radius: 10px;
      color: var(--text-muted);
      margin-left: 8px;
    }

    .option-label {
      display: block;
      background: var(--white);
      border: 1px solid var(--border);
      padding: 10px 15px;
      margin-top: 8px;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.2s;
      font-size: 0.95rem;
    }

    .option-label:hover {
      border-color: var(--primary);
      background: #f5f7ff;
    }

    .option-label input {
      margin-right: 10px;
    }

    .btn-finish {
      width: 100%;
      background: #10b981;
      color: white;
      border: none;
      padding: 14px;
      border-radius: 8px;
      font-weight: 700;
      cursor: pointer;
      margin-top: 2rem;
      transition: 0.2s;
    }

    .btn-finish:hover {
      background: #059669;
    }

    #result {
      margin-top: 2rem;
      padding: 1.5rem;
      background: #eff6ff;
      border-radius: 10px;
      border: 1px solid #dbeafe;
      color: var(--primary);
      text-align: center;
      font-size: 1.1rem;
    }
  </style>
</head>

<body>

  <header>
    <a href="dashboard.php" class="logo">AI TUTOR</a>
    <nav class="nav-links">
      <a href="dashboard.php">Panel</a>
      <a href="chat.php">Eğitmen</a>
      <a href="logout.php" style="color: #ef4444;">Çıkış</a>
    </nav>
  </header>

  <div class="container">
    <div class="test-card">
      <div class="test-header">
        <h2>Seviye Belirleme Testi</h2>
        <p>Bu test sonucunda yapay zeka eğitmeniniz size uygun seviyede yanıtlar verecektir.</p>
      </div>

      <button id="genBtn" class="btn-gen">Sınavı Oluştur</button>
      <p id="genStatus"></p>

      <form id="testForm" method="post" action="api/placement_submit.php" style="display:none;">
        <div id="questions"></div>
        <button type="submit" class="btn-finish">Testi Tamamla ve Sonucu Gör</button>
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

    genBtn.addEventListener("click", async () => {
      genBtn.style.display = "none";
      genStatus.innerHTML = '<span style="color: var(--primary)">Sınav hazırlanıyor, lütfen bekleyin...</span>';
      resultEl.style.display = "none";
      form.style.display = "none";
      questionsDiv.innerHTML = "";

      try {
        const res = await fetch("api/placement_generate.php");
        const data = await res.json();
        if (!data.ok) {
          genStatus.textContent = "Hata: " + (data.error || "Bilinmiyor");
          genBtn.style.display = "block";
          return;
        }

        const qRes = await fetch("placement_questions_json.php");
        const qData = await qRes.json();
        if (!qData.ok) {
          genStatus.textContent = "Sorular yüklenemedi.";
          genBtn.style.display = "block";
          return;
        }

        genStatus.innerHTML = "✅ Test hazır! Lütfen tüm soruları cevaplayın.";
        renderQuestions(qData.questions);
        form.style.display = "block";
      } catch (e) {
        genStatus.textContent = "Test oluşturulamadı.";
        genBtn.style.display = "block";
      }
    });

    function renderQuestions(questions) {
      questionsDiv.innerHTML = "";
      questions.forEach((q, i) => {
        const box = document.createElement("div");
        box.className = "question-box";

        const title = document.createElement("div");
        title.className = "question-title";
        title.innerHTML = `Soru ${i+1}: <span style="font-weight:400">${escapeHtml(q.prompt)}</span> <span class="diff-badge">${q.diff}</span>`;
        box.appendChild(title);

        q.options.forEach((opt, idx) => {
          const line = document.createElement("label");
          line.className = "option-label";
          line.innerHTML = `
                <input type="radio" name="ans[${i}]" value="${idx}" required>
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
      const fd = new FormData(form);
      const res = await fetch("api/placement_submit.php", {
        method: "POST",
        body: fd
      });
      const data = await res.json();

      if (!data.ok) {
        alert("Hata: " + (data.error || "Bilinmiyor"));
        return;
      }

      resultEl.style.display = "block";
      resultEl.innerHTML = `<strong>Sonuç:</strong> %${data.percentage} Başarı — <strong>Yeni Seviyen:</strong> ${data.level}`;
      form.style.display = "none";
      genBtn.textContent = "Testi Yeniden Başlat";
      genBtn.style.display = "block";
    });
  </script>

</body>

</html>