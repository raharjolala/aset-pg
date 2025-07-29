<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'includes/db.php';

if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query  = "SELECT * FROM users WHERE username='$username'";
    $result = mysqli_query($conn, $query);
    $data   = mysqli_fetch_assoc($result);

    if ($data && password_verify($password, $data['password'])) {
        $_SESSION['user'] = $data['username'];
        $_SESSION['role'] = $data['role'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Username atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Aset PG</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 60%, #43cea2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .login-container {
            background: rgba(255,255,255,0.98);
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(30,60,90,0.20);
            padding: 44px 34px 34px 34px;
            width: 100%;
            max-width: 350px;
            animation: fadeInUp 0.8s cubic-bezier(.4,2,.6,1);
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .login-container h2 {
            text-align: center;
            margin-bottom: 24px;
            color: #1e3c72;
            letter-spacing: 1px;
            font-weight: 700;
            text-shadow: 0 2px 8px #43cea233;
        }
        .login-container form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .login-container input {
            padding: 12px 14px;
            border: 1.5px solid #b0bec5;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
            transition: border 0.2s, box-shadow 0.2s;
            background: #f7fafc;
        }
        .login-container input:focus {
            border: 1.5px solid #2a5298;
            box-shadow: 0 0 0 2px #43cea244;
            background: #e3f6f3;
        }
        .login-container button {
            padding: 12px 0;
            background: linear-gradient(90deg, #1e3c72 0%, #43cea2 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: 1px;
            box-shadow: 0 2px 8px #2a529822;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
        }
        .login-container button:hover {
            background: linear-gradient(90deg, #43cea2 0%, #1e3c72 100%);
            color: #fff;
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 4px 16px #1e3c7240;
        }
        .login-container .error {
            color: #c0392b;
            background: #fbeee6;
            border: 1px solid #e57373;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 10px;
            text-align: center;
            font-size: 15px;
            animation: shake 0.3s;
        }
        @keyframes shake {
            10%, 90% { transform: translateX(-2px); }
            20%, 80% { transform: translateX(4px); }
            30%, 50%, 70% { transform: translateX(-8px); }
            40%, 60% { transform: translateX(8px); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login Aset PG</h2>
        <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
        <form method="POST" autocomplete="off">
            <input type="text" name="username" placeholder="Username" required autofocus>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>
    </div>
</body>
</html>
