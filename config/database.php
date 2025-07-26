<?php
// Database configuration - Using SQLite for easier setup
define('DB_PATH', __DIR__ . '/../hospital_crm.db');

try {
    $pdo = new PDO("sqlite:" . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Enable foreign key constraints in SQLite
    $pdo->exec("PRAGMA foreign_keys = ON");
    
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Global database connection
$GLOBALS['pdo'] = $pdo;
?>
