<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - SalesFlow</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gradient-primary);
            padding: 2rem;
        }
        .login-box {
            background: white;
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 400px;
        }
        .login-box h1 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 1rem;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--accent-color);
        }
        .error-message {
            background: #fee;
            color: var(--danger-color);
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: none;
        }
        .error-message.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1>SalesFlow</h1>
            <div class="error-message" id="errorMessage"></div>
            <form id="loginForm">
                <div class="form-group">
                    <label for="username">ชื่อผู้ใช้ หรือ อีเมล</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">รหัสผ่าน</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%;">เข้าสู่ระบบ</button>
            </form>
            <p style="text-align: center; margin-top: 1.5rem; color: var(--text-secondary);">
                ยังไม่มีบัญชี? <a href="register.php" style="color: var(--accent-color);">สมัครสมาชิก</a>
            </p>
        </div>
    </div>
    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.classList.remove('show');
            
            const formData = {
                username: document.getElementById('username').value,
                password: document.getElementById('password').value
            };
            
            try {
                // ใช้ absolute path เพื่อหลีกเลี่ยงปัญหา routing
                const apiPath = window.location.pathname.includes('/SalesFlow/') 
                    ? '/SalesFlow/api/auth/login' 
                    : '/api/auth/login';
                const response = await fetch(apiPath, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                
                // อ่าน response เป็น text ก่อน แล้วค่อย parse JSON
                const responseText = await response.text();
                let data;
                
                try {
                    data = JSON.parse(responseText);
                } catch (jsonError) {
                    // ถ้า parse JSON ไม่ได้ แสดง error message จาก response text
                    errorDiv.textContent = 'เกิดข้อผิดพลาด: ' + (responseText || 'ไม่สามารถอ่านข้อมูลได้');
                    errorDiv.classList.add('show');
                    console.error('JSON parse error:', jsonError);
                    console.error('Response text:', responseText);
                    return;
                }
                
                if (response.ok) {
                    window.location.href = 'dashboard.php';
                } else {
                    errorDiv.textContent = data.error || 'เกิดข้อผิดพลาด';
                    errorDiv.classList.add('show');
                }
            } catch (error) {
                errorDiv.textContent = 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error.message;
                errorDiv.classList.add('show');
                console.error('Fetch error:', error);
            }
        });
    </script>
</body>
</html>

