<?php
// Simple migration executor using mysqli
$host = "localhost";
$db_name = "u800179901_70";
$username = "u800179901_70";
$password = "Investocc@2312";

echo "=== Training & Referral System Migration ===\n\n";

// Connect using mysqli
$mysqli = new mysqli($host, $username, $password, $db_name);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}

echo "✓ Connected to database successfully\n\n";

// Set charset
$mysqli->set_charset("utf8mb4");

// Read SQL file
$sqlFile = __DIR__ . '/migrations/training_and_referral_system.sql';
if (!file_exists($sqlFile)) {
    die("ERROR: Migration file not found at: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);

// Remove comments and split into statements
$lines = explode("\n", $sql);
$statement = "";
$statements = [];

foreach ($lines as $line) {
    $line = trim($line);

    // Skip empty lines and comments
    if (empty($line) || substr($line, 0, 2) === '--') {
        continue;
    }

    $statement .= " " . $line;

    // Check if statement is complete
    if (substr(trim($line), -1) === ';') {
        $statements[] = trim($statement);
        $statement = "";
    }
}

echo "Found " . count($statements) . " SQL statements\n";
echo str_repeat("-", 60) . "\n\n";

$successCount = 0;
$skipCount = 0;
$errorCount = 0;

foreach ($statements as $index => $stmt) {
    if (empty(trim($stmt))) continue;

    // Extract operation type for logging
    $operation = "Statement";
    if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $stmt, $matches)) {
        $operation = "CREATE TABLE: " . $matches[1];
    } elseif (preg_match('/INSERT INTO\s+`?(\w+)`?/i', $stmt, $matches)) {
        $operation = "INSERT INTO: " . $matches[1];
    } elseif (preg_match('/UPDATE\s+`?(\w+)`?/i', $stmt, $matches)) {
        $operation = "UPDATE: " . $matches[1];
    } elseif (preg_match('/ALTER TABLE\s+`?(\w+)`?/i', $stmt, $matches)) {
        $operation = "ALTER TABLE: " . $matches[1];
    }

    if ($mysqli->query($stmt)) {
        echo "✓ $operation - Success\n";
        $successCount++;
    } else {
        $error = $mysqli->error;

        // Check if it's a "already exists" error (can be ignored)
        if (strpos($error, 'already exists') !== false ||
            strpos($error, 'Duplicate column') !== false ||
            strpos($error, 'Duplicate key') !== false) {
            echo "ℹ $operation - Skipped (already exists)\n";
            $skipCount++;
        } else {
            echo "✗ $operation - ERROR: $error\n";
            $errorCount++;
        }
    }
}

echo "\n" . str_repeat("-", 60) . "\n";
echo "Migration Summary:\n";
echo "  Success: $successCount\n";
echo "  Skipped: $skipCount\n";
echo "  Errors:  $errorCount\n";
echo str_repeat("-", 60) . "\n";

if ($errorCount === 0) {
    echo "\n✅ Migration completed successfully!\n";
    echo "\nYou can now:\n";
    echo "  - Register with referral codes\n";
    echo "  - Complete 7 training tasks\n";
    echo "  - Earn $15 training bonus\n";
    echo "  - Withdraw training earnings\n\n";
} else {
    echo "\n⚠ Migration completed with $errorCount errors.\n";
    echo "Please review the errors above.\n\n";
}

$mysqli->close();
?>
