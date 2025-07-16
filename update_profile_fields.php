<?php
require_once 'config/database.php';

echo "Adding profile fields to users table...\n";

try {
    // Add missing fields to users table
    $alterQueries = [
        "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER email",
        "ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER avatar", 
        "ALTER TABLE users ADD COLUMN address TEXT NULL AFTER phone",
        "ALTER TABLE users ADD COLUMN date_of_birth DATE NULL AFTER address",
        "ALTER TABLE users ADD COLUMN emergency_contact VARCHAR(100) NULL AFTER date_of_birth",
        "ALTER TABLE users ADD COLUMN emergency_phone VARCHAR(20) NULL AFTER emergency_contact",
        "ALTER TABLE users ADD COLUMN bio TEXT NULL AFTER emergency_phone",
        "ALTER TABLE users ADD COLUMN qualifications TEXT NULL AFTER bio",
        "ALTER TABLE users ADD COLUMN experience TEXT NULL AFTER qualifications",
        "ALTER TABLE users ADD COLUMN specialization VARCHAR(100) NULL AFTER experience"
    ];
    
    foreach ($alterQueries as $query) {
        try {
            $pdo->exec($query);
            echo "✓ Added column: " . preg_replace('/.*ADD COLUMN (\w+).*/', '$1', $query) . "\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                echo "Error: " . $e->getMessage() . "\n";
            } else {
                echo "• Column already exists: " . preg_replace('/.*ADD COLUMN (\w+).*/', '$1', $query) . "\n";
            }
        }
    }
    
    echo "\n✅ Profile fields update completed!\n";
    echo "\n📋 New Profile Features:\n";
    echo "• Profile picture upload\n";
    echo "• Personal information (phone, address, DOB)\n";
    echo "• Emergency contact details\n";
    echo "• Bio/About section\n";
    echo "• Professional info (qualifications, experience, specialization)\n";
    echo "• Password change functionality\n";
    echo "• Recent activity timeline\n";
    echo "• Role-based statistics\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
