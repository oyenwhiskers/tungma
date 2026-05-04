<?php

/**
 * Artisan Runner for cPanel
 * Usage: https://management.tungmaexpress.com.my/artisan-runner.php?cmd=migrate
 */

// Basic security check (Optional: remove the 'return' if you want to lock it down later)
// if (!isset($_GET['secret']) || $_GET['secret'] !== 'your-secure-password') {
//     die('Unauthorized');
// }

// Turn on ALL error reporting so we never get a blank 500 error screen!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('LARAVEL_START', microtime(true));

// Verify vendor exists before throwing fatal error
if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    die("<h1>Fatal Error</h1><p>The <b>vendor/autoload.php</b> file is missing! Did you upload the 'vendor' folder?</p>");
}

// Load Composer Autoloader
require __DIR__.'/../vendor/autoload.php';

// Boot the Laravel Application
$app = require_once __DIR__.'/../bootstrap/app.php';

// Resolve the Console Kernel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Get the command from the URL query parameter (default: help)
$commandString = $_GET['cmd'] ?? 'help';

// Parse command arguments simply
$args = explode(' ', urldecode($commandString));
$command = array_shift($args);

$params = [];
foreach ($args as $arg) {
    if (str_starts_with($arg, '--')) {
        $parts = explode('=', $arg);
        $params[$parts[0]] = $parts[1] ?? true;
    } else {
        $params[] = $arg;
    }
}

// Force migrations just in case
if ($command === 'migrate') {
    $params['--force'] = true;
}

$output = new \Symfony\Component\Console\Output\BufferedOutput();

try {
    // Execute the command
    $status = $kernel->call($command, $params, $output);
    
    echo "<h1>Artisan Runner</h1>";
    echo "<h3>Command: <code>php artisan $commandString</code></h3>";
    echo "<hr>";
    echo "<pre style='background:#1e1e1e; color:#00ff00; padding:20px; border-radius:5px;'>";
    echo htmlspecialchars($output->fetch());
    echo "</pre>";
    
} catch (\Exception $e) {
    echo "<h1>Artisan Error</h1>";
    echo "<pre style='background:#ffeeee; color:#aa0000; padding:20px;'>";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
}
