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
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="page-placement">

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
      }[c]));
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
