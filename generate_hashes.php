<?php
$passwords = ['admin123', 'sales123', 'manager123'];
foreach ($passwords as $pwd) {
    $hash = password_hash($pwd, PASSWORD_DEFAULT);
    echo "$pwd: $hash\n\n";
}
?>
