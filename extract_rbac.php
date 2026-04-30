<?php

$srcBase = __DIR__ . '/laravel-rbac-master/laravel-rbac-master/src';
$destBase = __DIR__ . '/app/Rbac';

function copyRbac($src, $dest) {
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }
    
    $dir = opendir($src);
    while (($file = readdir($dir)) !== false) {
        if ($file == '.' || $file == '..') continue;
        
        $srcPath = $src . '/' . $file;
        $destPath = $dest . '/' . $file;
        
        if (is_dir($srcPath)) {
            copyRbac($srcPath, $destPath);
        } else {
            $content = file_get_contents($srcPath);
            // Replace namespaces
            $content = str_replace('namespace Itstructure\LaRbac', 'namespace App\Rbac', $content);
            $content = str_replace('use Itstructure\LaRbac', 'use App\Rbac', $content);
            $content = str_replace('Itstructure\LaRbac', 'App\Rbac', $content);
            file_put_contents($destPath, $content);
        }
    }
    closedir($dir);
}

copyRbac($srcBase, $destBase);

// Copy views
$srcViews = __DIR__ . '/laravel-rbac-master/laravel-rbac-master/resources/views';
$destViews = __DIR__ . '/resources/views/rbac';
function copyViews($src, $dest) {
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }
    $dir = opendir($src);
    while (($file = readdir($dir)) !== false) {
        if ($file == '.' || $file == '..') continue;
        $srcPath = $src . '/' . $file;
        $destPath = $dest . '/' . $file;
        if (is_dir($srcPath)) {
            copyViews($srcPath, $destPath);
        } else {
            $content = file_get_contents($srcPath);
            $content = str_replace('Itstructure\LaRbac', 'App\Rbac', $content);
            file_put_contents($destPath, $content);
        }
    }
    closedir($dir);
}
copyViews($srcViews, $destViews);

// Copy migrations
$srcMigrations = __DIR__ . '/laravel-rbac-master/laravel-rbac-master/database/migrations';
$destMigrations = __DIR__ . '/database/migrations';
function copyMigrations($src, $dest) {
    if (!is_dir($dest)) return;
    $dir = opendir($src);
    while (($file = readdir($dir)) !== false) {
        if ($file == '.' || $file == '..') continue;
        $srcPath = $src . '/' . $file;
        $destPath = $dest . '/' . $file;
        if (is_file($srcPath)) {
            copy($srcPath, $destPath);
        }
    }
    closedir($dir);
}
copyMigrations($srcMigrations, $destMigrations);

echo "Extraction complete!\n";
