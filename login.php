<?php
include 'db.php';
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action']; 
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    
    if ($action == "register") {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $check = $conn->query("SELECT id FROM users WHERE username='$username'");
        if ($check->num_rows > 0) {
            $error = "Username already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $password);
            if ($stmt->execute()) {
                $success = "Account created! Now you can Login.";
            } else {
                $error = "Registration failed.";
            }
        }
    } else {
        $password = $_POST['password'];
        $result = $conn->query("SELECT * FROM users WHERE username='$username'");
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: index.php");
                exit();
            } else { $error = "Invalid Password!"; }
        } else { $error = "User not found!"; }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KharchaPaani - Auth</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0;
        }
        .card {
            border: none; border-radius: 25px;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            width: 100%; max-width: 400px; padding: 20px;
        }
        .brand-logo { font-size: 3rem; color: #764ba2; margin-bottom: 5px; }
        .btn-custom {
            background: linear-gradient(to right, #667eea, #764ba2);
            color: white; border: none; border-radius: 12px; padding: 12px; font-weight: 600;
            transition: 0.3s;
        }
        .btn-custom:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(118,75,162,0.3); color: white; }
        .input-group-text { background: #f8f9fa; border-right: none; border-radius: 12px 0 0 12px; color: #764ba2; }
        .form-control { background: #f8f9fa; border-left: none; border-radius: 0 12px 12px 0; padding: 12px; }
        .toggle-link { color: #764ba2; cursor: pointer; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>

<div class="card mx-3">
    <div class="text-center mb-4">
        <div class="brand-logo"><i class="bi bi-wallet2"></i></div>
        <h2 class="fw-bold" id="form-title">Login</h2>
        <p class="text-muted small" id="form-subtitle">Manage your <b>KharchaPaani</b></p>
    </div>

    <?php if($error) echo "<div class='alert alert-danger py-2 small'>$error</div>"; ?>
    <?php if($success) echo "<div class='alert alert-success py-2 small'>$success</div>"; ?>

    <form method="POST" id="auth-form">
        <input type="hidden" name="action" id="action-type" value="login">
        
        <div class="mb-3">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>
        </div>

        <div class="mb-4">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
        </div>

        <button type="submit" class="btn btn-custom w-100 mb-3" id="submit-btn">Login</button>
    </form>

    <div class="text-center">
        <p class="text-muted small mb-0" id="toggle-text">
            Don't have an account? <span class="toggle-link" onclick="toggleForm()">Create One</span>
        </p>
    </div>
</div>

<script>
    function toggleForm() {
        const title = document.getElementById('form-title');
        const subtitle = document.getElementById('form-subtitle');
        const submitBtn = document.getElementById('submit-btn');
        const actionType = document.getElementById('action-type');
        const toggleText = document.getElementById('toggle-text');

        if (actionType.value === 'login') {
            title.innerText = "Sign Up";
            subtitle.innerText = "Start your journey with KharchaPaani";
            submitBtn.innerText = "Register";
            actionType.value = "register";
            toggleText.innerHTML = 'Already have an account? <span class="toggle-link" onclick="toggleForm()">Login</span>';
        } else {
            title.innerText = "Login";
            subtitle.innerText = "Welcome back to KharchaPaani";
            submitBtn.innerText = "Login";
            actionType.value = "login";
            toggleText.innerHTML = 'Don\'t have an account? <span class="toggle-link" onclick="toggleForm()">Create One</span>';
        }
    }
</script>
</body>
</html>