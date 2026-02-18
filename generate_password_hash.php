<?php
/**
 * Script สำหรับสร้าง password hash
 * ใช้งาน: php generate_password_hash.php
 */

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: $password\n";
echo "Hash: $hash\n";
echo "\n";
echo "SQL UPDATE statement:\n";
echo "UPDATE users SET password_hash = '$hash' WHERE username = 'admin';\n";
?>







