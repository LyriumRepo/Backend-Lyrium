<?php

/**
 * Script completo para arreglar estructura de archivos Laravel
 */
echo '<h1>Fixing Laravel Structure</h1>';

$baseDir = __DIR__;
echo "<p>Base: $baseDir</p>";

// Función recursiva para arreglar TODO
function fixAll($dir, $level = 0)
{
    if (! is_dir($dir)) {
        return;
    }

    $files = scandir($dir);
    $indent = str_repeat('  ', $level);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $fullPath = $dir.'/'.$file;

        // Si es archivo con backslash
        if (strpos($file, '\\') !== false && is_file($fullPath)) {
            $newName = str_replace('\\', '/', $file);
            $newPath = $dir.'/'.$newName;
            $targetDir = dirname($newPath);

            if (! is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            if (rename($fullPath, $newPath)) {
                echo "<p style='color:green'>{$indent}✓ Fixed: $file</p>";
            } else {
                echo "<p style='color:red'>{$indent}✗ Failed: $file</p>";
            }
        }

        // Si es directorio con backslash
        if (strpos($file, '\\') !== false && is_dir($fullPath)) {
            $newName = str_replace('\\', '/', $file);
            $newPath = $dir.'/'.$newName;

            if (rename($fullPath, $newPath)) {
                echo "<p style='color:blue'>{$indent}📁 Fixed dir: $file</p>";
                fixAll($newPath, $level + 1);
            }
        }

        // Si es directorio, procesar recursivamente
        if (is_dir($fullPath) && $file !== '.' && $file !== '..') {
            fixAll($fullPath, $level + 1);
        }
    }
}

fixAll($baseDir);

echo '<h2>Verification</h2>';

$checks = [
    'app/Http/Controllers/Api/AuthController.php' => 'Auth Controller',
    'app/Http/Middleware/ForceJson.php' => 'ForceJson Middleware',
    'bootstrap/app.php' => 'Bootstrap app',
    'config/app.php' => 'Config app',
    'routes/api.php' => 'Routes API',
    'artisan' => 'Artisan',
    'vendor/autoload.php' => 'Vendor autoload',
    'database/migrations' => 'Database migrations',
];

$allOk = true;
foreach ($checks as $path => $name) {
    if (file_exists($baseDir.'/'.$path)) {
        echo "<p style='color:green'>✓ $name</p>";
    } else {
        echo "<p style='color:red'>✗ $name NOT FOUND</p>";
        $allOk = false;
    }
}

echo '<h2>Test Artisan</h2>';
if ($allOk) {
    chdir($baseDir);
    echo '<pre>';
    $output = [];
    exec('php artisan --version 2>&1', $output);
    echo implode("\n", $output);
    echo '</pre>';

    echo '<h2>Generate APP_KEY</h2>';
    echo '<pre>';
    exec('php artisan key:generate --force 2>&1', $output);
    echo implode("\n", $output);
    echo '</pre>';

    echo '<h2>Run Migrations</h2>';
    echo '<pre>';
    exec('php artisan migrate --force 2>&1', $output);
    echo implode("\n", $output);
    echo '</pre>';
} else {
    echo "<p style='color:red'>Cannot run artisan - some files are missing</p>";
}

echo '<h2>Directory Structure</h2>';
echo '<pre>';
exec('ls -la '.escapeshellarg($baseDir).'/app/Http/ 2>&1', $out);
echo implode("\n", $out);
echo '</pre>';
