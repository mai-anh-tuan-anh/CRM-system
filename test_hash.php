<?php
$hash = password_hash('admin123', PASSWORD_DEFAULT);
echo "admin123 hash: " . $hash . "\n";
echo "Verify test: " . (password_verify('admin123', $hash) ? 'PASS' : 'FAIL') . "\n";

// Test với hash hiện tại trong SQL
$sqlHash = '$2y$10$8MDgnBSrBdPHrjUBPczgX.TjHE4NmeXuLtsSvWjQCgkQyLzEd3laK';
echo "\nSQL hash test: " . (password_verify('admin123', $sqlHash) ? 'PASS' : 'FAIL') . "\n";
?>
