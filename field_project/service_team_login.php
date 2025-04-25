<?php
session_start(); // Start the session
include 'db.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $team_id = $_POST['employeeId'];
    $password = $_POST['password'];
    
    $sql = "SELECT password FROM team_sigin WHERE team_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $team_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashedPassword);
        $stmt->fetch();
        if (password_verify($password, $hashedPassword)) {
            $_SESSION['authenticated'] = true;
            $_SESSION['otp'] = rand(1000, 9999); // Corrected OTP storage
            $_SESSION['otpExpiry'] = time() + 300;
            echo json_encode(["status" => "otp_sent", "message" => "OTP has been sent."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid credentials!"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Employee ID not found!"]);
    }
    
    $stmt->close();
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verifyOtp'])) {
    $enteredOtp = $_POST['otp'];

    if (!isset($_SESSION['otp'])) {
        echo json_encode(["status" => "error", "message" => "OTP expired or not generated."]);
        exit();
    }

    if ($enteredOtp == $_SESSION['otp']) {
        unset($_SESSION['otp']); // Clear OTP after successful verification
        echo json_encode(["status" => "success", "message" => "Your service has completed successfully"]);
    } else {
        
        echo json_encode(["status" => "error", "message" => "Invalid OTP"]);
    }
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Team Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); width: 300px; text-align: center; }
        h1 { color: #007bff; }
        label { display: block; margin: 10px 0 5px; text-align: left; }
        input { width: 90%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 5px; }
        button { width: 100%; padding: 10px; background-color: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #218838; }
        .hidden { display: none; }
        .error { color: red; font-size: 0.9em; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container" id="login-container">
        <h1>Service Team Login</h1>
        <form id="login-form" method="POST" action="">
            <label for="employeeId">Employee ID:</label>
            <input type="text" id="employeeId" name="employeeId" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Login</button>
        </form>
        <p id="login-error" class="error hidden"></p>
        <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
        <a href="index.html">Back to Home</a>
    </div>

    <div class="container hidden" id="otp-container">
        <h1>Enter OTP</h1>
        <form id="otp-form">
            <label for="otp">OTP (4 digits):</label>
            <input type="text" id="otp" name="otp" maxlength="4" required>
            <button type="submit">Verify OTP</button>
        </form>
        <p id="otp-error" class="error hidden"></p>
    </div>

    <div class="container hidden" id="success-container">
        <h1>Login Successful</h1>
        <a href="index.html">Back to Home</a>
        <div class="signup-link">
            <p>Don't have an account? <a href="signup.html">Sign up here</a></p>
        </div>
    </div>

    <script>
        document.getElementById("login-form").addEventListener("submit", function(event) {
            event.preventDefault();
            let formData = new FormData(this);
            formData.append("login", "1");
            
            fetch("", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "otp_sent") {
                    document.getElementById("login-container").classList.add("hidden");
                    document.getElementById("otp-container").classList.remove("hidden");
                } else {
                    document.getElementById("login-error").textContent = data.message;
                    document.getElementById("login-error").classList.remove("hidden");
                }
            });
        });

        document.getElementById("otp-form").addEventListener("submit", function(event) {
            event.preventDefault();
            
            let formData = new FormData(this);
            formData.append("verifyOtp", "1");

            fetch("service_team_login.php", {  // Ensure this points to the correct file
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    document.getElementById("otp-container").classList.add("hidden");
                    document.getElementById("success-container").classList.remove("hidden");
                    document.getElementById("success-container").innerHTML = `<h1>${data.message}</h1>`;
                } else {
                    document.getElementById("otp-error").textContent = data.message;
                    document.getElementById("otp-error").classList.remove("hidden");
                }
            })
            .catch(error => console.error("Error:", error));
        });
    </script>
</body>
</html>
