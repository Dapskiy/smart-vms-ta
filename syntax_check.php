<?php
// Simple syntax checker
$files = [
    'app/Filament/Resources/RoleResource.php',
    'app/Filament/Resources/RoleResource/Pages/ListRoles.php',
    'app/Filament/Resources/RoleResource/Pages/CreateRole.php',
    'app/Filament/Resources/RoleResource/Pages/EditRole.php',
    'app/Filament/Resources/Permissions/PermissionResource.php',
];

foreach ($files as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        $output = [];
        $return = 0;
        exec("php -l " . escapeshellarg($fullPath), $output, $return);
        if ($return === 0) {
            echo "✓ $file\n";
        } else {
            echo "✗ $file\n";
            echo implode("\n", $output) . "\n";
        }
    } else {
        echo "? $file (not found)\n";
    }
}
