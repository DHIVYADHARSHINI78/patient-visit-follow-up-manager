<?php
session_start();
require_once 'config/db.php'; 

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password']; 
    
    try {
        $stmt = $pdo->prepare("SELECT user_id, password, role, patient_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Vijay Sethu profile ulla poga 'suganya123' use pannunga
        if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) { 
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['patient_id'] = $user['patient_id']; 
            
            // Success redirect
            if ($user['role'] === 'admin') {
                header('Location: index.php');
            } else {
                header('Location: patient_view.php');
            }
            exit(); // Redirection-ku apram kandippa exit() pannanum
        } else {
            $error = 'Invalid Login Credentials.';
        }
    } catch (PDOException $e) {
        $error = "DB Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Clinic Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f6; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-card { width: 100%; max-width: 380px; padding: 2rem; background: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="login-card">
        <h3 class="text-center fw-bold mb-4">Clinic Pro</h3>
        <?php if ($error): ?> <div class="alert alert-danger small text-center"><?= $error ?></div> <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label small">Username</label>
                <input type="text" name="username" class="form-control"  required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label small">Password</label>
                <input type="password" name="password" class="form-control"  required>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold">Login to Portal</button>
        </form>
    </div>
</body>
</html>