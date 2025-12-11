<?php

// Laravel Artisan Runner (web-accessible)
// - Password-protected session gate
// - Whitelisted commands only
// - Supports passing options via query string (?cmd=...&args[--flag]=value)
// - Useful on shared hosting without shell access

declare(strict_types=1);

// --- CONFIGURE THIS ---
$password = '26U[h+/LqVvt9'; // CHANGE THIS IMMEDIATELY

session_start();

// --- BASIC LOGIN HANDLER ---
if (isset($_POST['password'])) {
    if (hash_equals($password, (string)$_POST['password'])) {
        $_SESSION['artisan_runner_auth'] = true;
    } else {
        $error = 'Invalid password';
    }
}

if (!isset($_SESSION['artisan_runner_auth'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Laravel Artisan Runner</title>
        <style>
            body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, 'Helvetica Neue', Arial, 'Noto Sans', 'Apple Color Emoji', 'Segoe UI Emoji'; background:#111; color:#eee; display:flex; align-items:center; justify-content:center; height:100vh; margin:0; }
            form { background:#1f2937; padding:2rem; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,.4); width:320px; }
            h3 { margin:0 0 1rem; font-weight:600; }
            input { padding:0.6rem 0.75rem; border:none; border-radius:6px; width:100%; margin-top:0.5rem; background:#111827; color:#e5e7eb; }
            button { margin-top:1rem; padding:0.6rem 1rem; border:none; border-radius:6px; background:#2563eb; color:#fff; cursor:pointer; width:100%; }
            .error { color:#f87171; margin:.5rem 0 0; }
        </style>
    </head>
    <body>
        <form method="post">
            <h3>Enter Password</h3>
            <?php if (!empty($error)) echo "<p class=\"error\">".htmlspecialchars($error)."</p>"; ?>
            <input type="password" name="password" placeholder="Password" autocomplete="current-password">
            <button type="submit">Login</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// --- LARAVEL BOOTSTRAP ---
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

/** @var \Illuminate\Contracts\Console\Kernel $kernel */
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// --- COMMAND EXECUTION ---
$cmd = isset($_GET['cmd']) ? (string)$_GET['cmd'] : 'list';

// Whitelist allowed commands to reduce risk
$allowed = [
    'list',
    'about',
    'package:discover',
    'optimize:clear',
    'config:cache',
    'route:clear',
    'cache:clear',
    'view:clear',
    'queue:restart',
    'vendor:publish',
    // DB ops (use with care)
    'migrate', 'migrate:fresh', 'migrate:refresh', 'migrate:rollback', 'db:seed', 'migrate:status',
    // Scribe docs
    'scribe:generate',
];

if (!in_array($cmd, $allowed, true)) {
    http_response_code(403);
    echo 'Forbidden command';
    exit;
}

// Accept flags/options via query string, e.g. ?cmd=vendor:publish&args[--provider]=Knuckles\\Scribe\\ScribeServiceProvider&args[--tag]=scribe-config
$args = [];
if (isset($_GET['args']) && is_array($_GET['args'])) {
    foreach ($_GET['args'] as $key => $value) {
        // Keys should look like --flag or --flag[]
        $k = (string)$key;
        if ($k !== '' && $k[0] === '-') {
            $args[$k] = $value;
        }
    }
}

// Force-safe flags for certain commands in non-interactive environments
if (in_array($cmd, ['migrate', 'migrate:fresh', 'migrate:refresh', 'db:seed', 'vendor:publish'], true)) {
    $args['--force'] = true;
}

// Helpful defaults
$args['--no-interaction'] = true;

// Reasonable execution limits
@set_time_limit(300);
@ini_set('memory_limit', '512M');

// Render header
echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Artisan Runner</title>
<style>body{background:#0b0b0b;color:#e5e7eb;font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,\'Helvetica Neue\',Arial,\'Noto Sans\'}
.wrap{max-width:960px;margin:32px auto;padding:0 16px}
.bar{display:flex;gap:8px;align-items:center;margin-bottom:12px}
input,button{padding:.5rem .75rem;border-radius:6px;border:none}
button{background:#2563eb;color:#fff;cursor:pointer}
pre{background:#000;color:#10b981;padding:16px;border-radius:8px;overflow:auto;white-space:pre-wrap;word-break:break-word}
a{color:#93c5fd;text-decoration:none}
</style></head><body><div class="wrap">';

echo '<div class="bar">';
echo '<strong>Command:</strong> <code>' . htmlspecialchars($cmd) . '</code>';
echo ' &nbsp; <a href="?cmd=list">List</a>';
echo ' &nbsp; <a href="?cmd=package:discover">Discover</a>';
echo ' &nbsp; <a href="?cmd=optimize:clear">Clear caches</a>';
echo ' &nbsp; <a href="?cmd=scribe:generate">Scribe generate</a>';
echo '</div>';

echo '<pre>';
try {
    $kernel->call($cmd, $args);
    echo htmlspecialchars($kernel->output());
} catch (Throwable $e) {
    $message = (string)$e->getMessage();
    echo 'Error: ' . htmlspecialchars($message);
    // Attempt a fallback run for Scribe if the command isn't registered
    if ($cmd === 'scribe:generate' && str_contains($message, 'does not exist')) {
        echo "\n\nAttempting direct Scribe command fallback...\n";
        try {
            // Build a Laravel Console Application with proper signature for Laravel 12
            $events = $app->make(Illuminate\Contracts\Events\Dispatcher::class);
            $console = new Illuminate\Console\Application($app, $events, $app->version());
            $console->setAutoExit(false);

            // Ensure the command is registered with the console application
            $console->add($app->make(Knuckles\Scribe\Commands\GenerateDocumentation::class));

            $input  = new Symfony\Component\Console\Input\StringInput('scribe:generate --no-interaction');
            $output = new Symfony\Component\Console\Output\BufferedOutput();

            $console->run($input, $output);
            echo htmlspecialchars($output->fetch());
        } catch (Throwable $e2) {
            echo "\nFallback failed: " . htmlspecialchars($e2->getMessage());
        }
    }
}
echo '</pre>';

echo '</div></body></html>';


