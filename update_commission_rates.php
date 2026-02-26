<?php
require_once __DIR__ . '/config/config.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "Updating VIP commission rates...\n\n";

    // New commission rates
    $newRates = [
        1 => 0.50,
        2 => 1.00,
        3 => 2.30,
        4 => 4.00,
        5 => 5.50
    ];

    $stmt = $db->prepare('UPDATE vip_tiers SET commission_rate = ? WHERE level = ?');

    foreach ($newRates as $level => $rate) {
        $stmt->execute([$rate, $level]);
        echo "✓ VIP $level commission rate updated to $rate%\n";
    }

    echo "\n✓ All commission rates updated successfully!\n\n";

    // Display updated rates
    echo "Current VIP Commission Rates:\n";
    echo "----------------------------\n";
    $stmt = $db->prepare('SELECT level, name, commission_rate FROM vip_tiers ORDER BY level ASC');
    $stmt->execute();
    $tiers = $stmt->fetchAll();

    foreach ($tiers as $tier) {
        echo "VIP {$tier['level']}: {$tier['commission_rate']}%\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
