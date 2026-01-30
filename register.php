<?php
require_once 'config/db.php'; 
include 'includes/header.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $check->execute([$username]);
        
        if ($check->rowCount() > 0) {
            $error = "Username already taken!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            
            if ($stmt->execute([$username, $hashed_password])) {
                $success = "New user registered successfully, please wait!...";
            } else {
                $error = "Something went wrong!";
            }
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <h3 class="fw-bold mb-4">Register New Staff</h3>
                    
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger" id="error-alert"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($success)): ?>
                        <div class="alert alert-success" id="success-alert">
                            <i class="bi bi-check-circle-fill me-2"></i> <?= $success ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2">Create Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
 
    const successAlert = document.getElementById('success-alert');
    
    if (successAlert) {
    
        setTimeout(() => {
            successAlert.style.display = 'none';
        }, 5000);

       
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 10000);
    }
</script>

<?php include 'includes/footer.php'; ?>