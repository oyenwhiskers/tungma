<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;

class DatabaseBackupService
{
    /**
     * Dump database to a .sql.gz file
     *
     * @param string $outputPath Absolute path to output .sql.gz
     * @param callable|null $progressCallback Callback receiving (string $message, int $current, int $total)
     * @return bool
     */
    public function dump(string $outputPath, ?callable $progressCallback = null): bool
    {
        $forcePhp = config('backup.database.force_php_dump', false);

        if (!$forcePhp && $this->canExecuteShell() && $this->hasMysqldump()) {
            try {
                if ($progressCallback) {
                    $progressCallback("Attempting mysqldump via CLI...", 1, 10);
                }
                if ($this->dumpWithMysqldump($outputPath, $progressCallback)) {
                    return true;
                }
            } catch (\Throwable $e) {
                Log::warning("mysqldump failed, falling back to pure-PHP dumper: " . $e->getMessage());
                if ($progressCallback) {
                    $progressCallback("CLI dump failed. Falling back to pure-PHP database dumper...", 1, 10);
                }
            }
        }

        // Pure-PHP Dumper Fallback
        return $this->dumpWithPhp($outputPath, $progressCallback);
    }

    /**
     * Restore database from a .sql.gz file
     *
     * @param string $sqlGzPath Absolute path to .sql.gz
     * @param callable|null $progressCallback Callback receiving (string $message, int $current, int $total)
     * @return bool
     */
    public function restore(string $sqlGzPath, ?callable $progressCallback = null): bool
    {
        if (!file_exists($sqlGzPath)) {
            throw new \Exception("Database dump file not found: {$sqlGzPath}");
        }

        $forcePhp = config('backup.database.force_php_dump', false);

        if (!$forcePhp && $this->canExecuteShell() && $this->hasMysqlCli()) {
            try {
                if ($progressCallback) {
                    $progressCallback("Attempting database restore via MySQL CLI...", 1, 10);
                }
                if ($this->restoreWithMysqlCli($sqlGzPath, $progressCallback)) {
                    return true;
                }
            } catch (\Throwable $e) {
                Log::warning("MySQL CLI restore failed, falling back to pure-PHP restorer: " . $e->getMessage());
                if ($progressCallback) {
                    $progressCallback("CLI restore failed. Falling back to pure-PHP restorer...", 1, 10);
                }
            }
        }

        // Pure-PHP Restorer Fallback
        return $this->restoreWithPhp($sqlGzPath, $progressCallback);
    }

    /**
     * Pure-PHP Streaming Database Dumper
     */
    protected function dumpWithPhp(string $outputPath, ?callable $progressCallback = null): bool
    {
        $connection = DB::connection();
        $pdo = $connection->getPdo();

        // Ensure directory exists
        $dir = dirname($outputPath);
        if (!file_exists($dir)) {
            @mkdir($dir, 0755, true);
        }

        $gz = gzopen($outputPath, 'wb9');
        if (!$gz) {
            throw new \Exception("Failed to open output file for writing: {$outputPath}");
        }

        $driver = DB::connection()->getDriverName();

        try {
            // Write standard dump header
            if ($driver === 'sqlite') {
                $header = "-- SQLite Database Dump\n"
                    . "-- Generation Time: " . date('Y-m-d H:i:s') . "\n"
                    . "PRAGMA foreign_keys = OFF;\n\n";
            } else {
                $header = "-- Pure-PHP MySQL Dump\n"
                    . "-- Host: " . config('database.connections.mysql.host', '127.0.0.1') . "\n"
                    . "-- Generation Time: " . date('Y-m-d H:i:s') . "\n"
                    . "-- Server version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n\n"
                    . "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n"
                    . "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n"
                    . "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n"
                    . "/*!40101 SET NAMES utf8mb4 */;\n"
                    . "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n"
                    . "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n"
                    . "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n"
                    . "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n";
            }

            gzwrite($gz, $header);

            // Get list of tables
            $tables = $this->getTableList($pdo, $driver);
            $totalTables = count($tables);

            if ($progressCallback) {
                $progressCallback("Found {$totalTables} database tables to export.", 0, $totalTables);
            }

            $chunkSize = config('backup.database.chunk_rows', 500);

            foreach ($tables as $index => $table) {
                $tableNum = $index + 1;

                if ($progressCallback) {
                    $progressCallback("Dumping table [{$tableNum}/{$totalTables}]: `{$table}`...", $tableNum, $totalTables);
                }

                // Table structure
                gzwrite($gz, "--\n-- Table structure for table `{$table}`\n--\n\n");
                gzwrite($gz, "DROP TABLE IF EXISTS `{$table}`;\n");

                $createSql = null;
                if ($driver === 'sqlite') {
                    $stmt = $pdo->prepare("SELECT sql FROM sqlite_master WHERE type='table' AND name = :name");
                    $stmt->execute([':name' => $table]);
                    $createSql = $stmt->fetchColumn();
                } else {
                    $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
                    $createSql = $createStmt['Create Table'] ?? ($createStmt['Create View'] ?? null);
                }

                if ($createSql) {
                    gzwrite($gz, $createSql . ";\n\n");
                }

                // Table data
                gzwrite($gz, "--\n-- Dumping data for table `{$table}`\n--\n\n");
                if ($driver !== 'sqlite') {
                    gzwrite($gz, "/*!40000 ALTER TABLE `{$table}` DISABLE KEYS */;\n");
                }

                // Count total rows
                $rowCount = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();

                if ($rowCount > 0) {
                    // Check if table has 'id' or primary key for chunking, otherwise use offset
                    $offset = 0;
                    while ($offset < $rowCount) {
                        $stmt = $pdo->prepare("SELECT * FROM `{$table}` LIMIT :limit OFFSET :offset");
                        $stmt->bindValue(':limit', $chunkSize, PDO::PARAM_INT);
                        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                        $stmt->execute();
                        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (empty($rows)) {
                            break;
                        }

                        // Build INSERT statement for this chunk
                        $columns = array_keys($rows[0]);
                        $escapedColumns = array_map(fn($col) => "`" . str_replace("`", "``", $col) . "`", $columns);
                        $colList = implode(', ', $escapedColumns);

                        $insertHeader = "INSERT INTO `{$table}` ({$colList}) VALUES\n";
                        $valuesList = [];

                        foreach ($rows as $row) {
                            $escapedValues = [];
                            foreach ($row as $val) {
                                if ($val === null) {
                                    $escapedValues[] = 'NULL';
                                } elseif (is_int($val) || is_float($val)) {
                                    $escapedValues[] = $val;
                                } else {
                                    $escapedValues[] = $pdo->quote((string) $val);
                                }
                            }
                            $valuesList[] = "(" . implode(', ', $escapedValues) . ")";
                        }

                        $insertSql = $insertHeader . implode(",\n", $valuesList) . ";\n";
                        gzwrite($gz, $insertSql);

                        $offset += count($rows);
                    }
                }

                gzwrite($gz, "/*!40000 ALTER TABLE `{$table}` ENABLE KEYS */;\n\n");
            }

            // Write footer
            $footer = "\n/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n"
                . "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n"
                . "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n"
                . "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n"
                . "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n"
                . "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n"
                . "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;\n"
                . "-- Dump completed on " . date('Y-m-d H:i:s') . "\n";

            gzwrite($gz, $footer);
            gzclose($gz);

            if ($progressCallback) {
                $fileSizeMb = round(filesize($outputPath) / 1048576, 2);
                $progressCallback("Pure-PHP database dump completed ({$fileSizeMb} MB).", $totalTables, $totalTables);
            }

            return true;

        } catch (\Throwable $e) {
            gzclose($gz);
            @unlink($outputPath);
            throw $e;
        }
    }

    /**
     * Pure-PHP SQL Restorer
     */
    protected function restoreWithPhp(string $sqlGzPath, ?callable $progressCallback = null): bool
    {
        $connection = DB::connection();
        $pdo = $connection->getPdo();

        $gz = gzopen($sqlGzPath, 'rb');
        if (!$gz) {
            throw new \Exception("Failed to open SQL dump file for reading: {$sqlGzPath}");
        }

        $driver = DB::connection()->getDriverName();

        if ($progressCallback) {
            $progressCallback("Starting pure-PHP SQL execution...", 0, 100);
        }

        // Disable foreign keys and set modes
        if ($driver === 'sqlite') {
            $pdo->exec("PRAGMA foreign_keys = OFF;");
        } else {
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
            $pdo->exec("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';");
            $pdo->exec("SET NAMES utf8mb4;");
        }

        $buffer = '';
        $queryCount = 0;

        try {
            while (!gzeof($gz)) {
                $line = gzgets($gz, 65536);
                if ($line === false) {
                    break;
                }

                $trimmed = trim($line);

                // Skip comments and empty lines when buffer is empty
                if (empty($buffer)) {
                    if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                        continue;
                    }
                }

                $buffer .= $line;

                // Check if statement ends with semicolon
                if (str_ends_with(rtrim($trimmed), ';')) {
                    try {
                        $pdo->exec($buffer);
                        $queryCount++;

                        if ($queryCount % 200 === 0 && $progressCallback) {
                            $progressCallback("Executed {$queryCount} SQL statements...", 0, 100);
                        }
                    } catch (\Throwable $stmtErr) {
                        // Ignore harmless comments / conditional statements that might fail
                        if (!str_starts_with(trim($buffer), '/*!')) {
                            Log::warning("SQL statement warning during restore: " . $stmtErr->getMessage());
                        }
                    }

                    $buffer = '';
                }
            }

            // Execute any remaining buffer
            if (!empty(trim($buffer))) {
                $pdo->exec($buffer);
                $queryCount++;
            }

            gzclose($gz);

            // Re-enable foreign key checks
            if ($driver === 'sqlite') {
                $pdo->exec("PRAGMA foreign_keys = ON;");
            } else {
                $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
            }

            if ($progressCallback) {
                $progressCallback("Pure-PHP database restore completed. Executed {$queryCount} statements.", 100, 100);
            }

            return true;

        } catch (\Throwable $e) {
            gzclose($gz);
            if ($driver === 'sqlite') {
                $pdo->exec("PRAGMA foreign_keys = ON;");
            } else {
                $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
            }
            throw new \Exception("Database restore failed: " . $e->getMessage());
        }
    }

    /**
     * Dump using CLI mysqldump
     */
    protected function dumpWithMysqldump(string $outputPath, ?callable $progressCallback = null): bool
    {
        $config = config('database.connections.mysql', []);
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';

        // Write a temporary options file for safe password handling
        $cnfFile = tempnam(sys_get_temp_dir(), 'mycnf_');
        $cnfContent = "[client]\n"
            . "host=\"{$host}\"\n"
            . "port=\"{$port}\"\n"
            . "user=\"{$username}\"\n"
            . "password=\"{$password}\"\n";
        file_put_contents($cnfFile, $cnfContent);

        $tempSql = tempnam(sys_get_temp_dir(), 'dump_') . '.sql';

        $cmd = sprintf(
            'mysqldump --defaults-extra-file=%s --single-transaction --quick --skip-lock-tables --default-character-set=utf8mb4 %s > %s',
            escapeshellarg($cnfFile),
            escapeshellarg($database),
            escapeshellarg($tempSql)
        );

        $returnVar = 0;
        $output = [];
        exec($cmd, $output, $returnVar);

        @unlink($cnfFile);

        if ($returnVar !== 0 || !file_exists($tempSql) || filesize($tempSql) === 0) {
            @unlink($tempSql);
            throw new \Exception("mysqldump command exited with code {$returnVar}: " . implode("\n", $output));
        }

        if ($progressCallback) {
            $progressCallback("Compressing mysqldump output to gzip...", 5, 10);
        }

        // Compress to .sql.gz
        $in = fopen($tempSql, 'rb');
        $out = gzopen($outputPath, 'wb9');
        while (!feof($in)) {
            gzwrite($out, fread($in, 65536));
        }
        fclose($in);
        gzclose($out);
        @unlink($tempSql);

        if ($progressCallback) {
            $progressCallback("CLI mysqldump completed successfully.", 10, 10);
        }

        return true;
    }

    /**
     * Restore using MySQL CLI
     */
    protected function restoreWithMysqlCli(string $sqlGzPath, ?callable $progressCallback = null): bool
    {
        $config = config('database.connections.mysql', []);
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';

        // Uncompress to temp sql
        $tempSql = tempnam(sys_get_temp_dir(), 'restore_') . '.sql';
        $in = gzopen($sqlGzPath, 'rb');
        $out = fopen($tempSql, 'wb');
        while (!gzeof($in)) {
            fwrite($out, gzread($in, 65536));
        }
        gzclose($in);
        fclose($out);

        $cnfFile = tempnam(sys_get_temp_dir(), 'mycnf_');
        $cnfContent = "[client]\n"
            . "host=\"{$host}\"\n"
            . "port=\"{$port}\"\n"
            . "user=\"{$username}\"\n"
            . "password=\"{$password}\"\n";
        file_put_contents($cnfFile, $cnfContent);

        $cmd = sprintf(
            'mysql --defaults-extra-file=%s --default-character-set=utf8mb4 %s < %s',
            escapeshellarg($cnfFile),
            escapeshellarg($database),
            escapeshellarg($tempSql)
        );

        $returnVar = 0;
        $output = [];
        exec($cmd, $output, $returnVar);

        @unlink($cnfFile);
        @unlink($tempSql);

        if ($returnVar !== 0) {
            throw new \Exception("mysql command exited with code {$returnVar}: " . implode("\n", $output));
        }

        return true;
    }

    /**
     * Get list of database tables
     */
    protected function getTableList(PDO $pdo, string $driver = 'mysql'): array
    {
        $tables = [];

        if ($driver === 'sqlite') {
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
        } else {
            $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
        }

        return $tables;
    }

    /**
     * Check if shell functions are available
     */
    protected function canExecuteShell(): bool
    {
        if (!function_exists('exec')) {
            return false;
        }

        $disabled = explode(',', (string) ini_get('disable_functions'));
        $disabled = array_map('trim', $disabled);

        return !in_array('exec', $disabled, true);
    }

    /**
     * Check if mysqldump is available
     */
    protected function hasMysqldump(): bool
    {
        $returnVar = 0;
        $output = [];
        @exec('mysqldump --version', $output, $returnVar);
        return $returnVar === 0;
    }

    /**
     * Check if mysql CLI is available
     */
    protected function hasMysqlCli(): bool
    {
        $returnVar = 0;
        $output = [];
        @exec('mysql --version', $output, $returnVar);
        return $returnVar === 0;
    }
}
