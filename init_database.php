<?php
require_once __DIR__ . '/config/config.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed!");
}

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Initialization - EarningsLLC</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 flex items-center justify-center p-4'>
    <div class='max-w-4xl w-full bg-slate-800/50 backdrop-blur-sm rounded-2xl p-8 border border-slate-700'>
        <h1 class='text-3xl font-bold text-white mb-6'>Database Initialization</h1>
        <div class='space-y-4'>";

try {
    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');

    // Split by statements
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        function($stmt) {
            return !empty($stmt) &&
                   !preg_match('/^--/', $stmt) &&
                   !preg_match('/^CREATE DATABASE/', $stmt) &&
                   !preg_match('/^USE /', $stmt);
        }
    );

    $successCount = 0;
    $errorCount = 0;

    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;

        try {
            $db->exec($statement);

            // Extract table name for display
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?\s*\(/i', $statement, $matches)) {
                $tableName = $matches[1];
                echo "<div class='bg-green-500/10 border border-green-500/50 rounded-lg p-3 text-green-300'>";
                echo "<p>✓ Created/verified table: <strong>$tableName</strong></p>";
                echo "</div>";
                $successCount++;
            } else if (preg_match('/INSERT INTO.*?`?(\w+)`?/i', $statement, $matches)) {
                $tableName = $matches[1];
                echo "<div class='bg-blue-500/10 border border-blue-500/50 rounded-lg p-3 text-blue-300'>";
                echo "<p>✓ Inserted data into: <strong>$tableName</strong></p>";
                echo "</div>";
                $successCount++;
            }
        } catch (PDOException $e) {
            // Ignore duplicate entry errors
            if (strpos($e->getMessage(), 'Duplicate entry') === false &&
                strpos($e->getMessage(), 'already exists') === false) {
                echo "<div class='bg-red-500/10 border border-red-500/50 rounded-lg p-3 text-red-300'>";
                echo "<p>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
                $errorCount++;
            }
        }
    }

    echo "<div class='mt-6 bg-green-500/20 border border-green-500 rounded-lg p-6 text-green-300'>";
    echo "<p class='text-xl font-bold mb-2'>✓ Database initialization complete!</p>";
    echo "<p>Successfully executed: <strong>$successCount</strong> statements</p>";
    if ($errorCount > 0) {
        echo "<p>Errors encountered: <strong>$errorCount</strong></p>";
    }
    echo "</div>";

    echo "<div class='mt-6 flex gap-4'>";
    echo "<a href='/setup_admin' class='flex-1 bg-emerald-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-emerald-700 transition-all text-center'>Setup Admin User</a>";
    echo "<a href='/admin' class='flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-all text-center'>Go to Admin Panel</a>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='bg-red-500/10 border border-red-500/50 rounded-lg p-4 text-red-300'>";
    echo "<p class='font-bold'>Fatal Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "    </div>
    </div>
</body>
</html>";
?>
