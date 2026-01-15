<?php
require_once __DIR__ . "/inc/init.php";
require_once __DIR__ . "/inc/user_repo.php";

$token = trim($_GET["token"] ?? "");
$error = "";
$msg = "";

if ($token === "") {
    $error = "Erişim anahtarı (token) eksik.";
} else {
    $u = find_user_by_reset_token($token);
    if (!$u) {
        $error = "Geçersiz veya süresi dolmuş sıfırlama anahtarı.";
    }
}

if (!$error && $_SERVER["REQUEST_METHOD"] === "POST") {
    $pass = $_POST["password"] ?? "";
    $pass2 = $_POST["password2"] ?? "";

    if ($pass === "" || $pass2 === "") {
        $error = "Lütfen her iki şifre alanını da doldurun.";
    } elseif ($pass !== $pass2) {
        $error = "Şifreler birbiriyle eşleşmiyor.";
    } elseif (strlen($pass) < 6) {
        $error = "Şifre en az 6 karakterden oluşmalıdır.";
    } else {
        update_password_by_user_id((int)$u["id"], $pass);
        $msg = "Şifreniz başarıyla güncellendi! Artık giriş yapabilirsiniz.";
    }
}
?>
<!doctype html>
<html lang="tr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Şifre Sıfırla - AI Tutor</title>
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
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: var(--white);
            width: 100%;
            max-width: 400px;
            padding: 2.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h2 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -1px;
            text-transform: uppercase;
        }

        .header p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
            transition: all 0.2s;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        button {
            width: 100%;
            padding: 0.75rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        button:hover {
            background: var(--primary-hover);
        }

        .alert {
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            text-align: center;
            border: 1px solid;
        }

        .alert-error {
            background: #fef2f2;
            color: #ef4444;
            border-color: #fee2e2;
        }

        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border-color: #dcfce7;
        }

        .btn-outline {
            display: inline-block;
            margin-top: 1rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .btn-outline:hover {
            text-decoration: underline;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="card">
        <div class="header">
            <h2>YENİ ŞİFRE</h2>
            <p>Güvenliğin için güçlü bir şifre belirle.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <?php if ($msg): ?>
            <div class="alert alert-success"><?php echo h($msg); ?></div>
            <div class="text-center">
                <a href="login.php" class="btn-outline">Giriş Ekranına Git</a>
            </div>
        <?php endif; ?>

        <?php if (!$error && !$msg): ?>
            <form method="post">
                <div class="form-group">
                    <label>Yeni Şifre</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label>Şifreyi Onayla</label>
                    <input type="password" name="password2" placeholder="••••••••" required>
                </div>

                <button type="submit">Şifreyi Güncelle</button>
            </form>
        <?php elseif ($error && !$u): ?>
            <div class="text-center">
                <a href="forgot_password.php" class="btn-outline">Yeni Link İste</a>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>
