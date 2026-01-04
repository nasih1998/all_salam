<?php
/**
 * Export Translations Script
 * This script exports translations from the database to a JSON file
 * Usage: Run this on your SERVER to export translations
 */

// Database configuration - UPDATE THESE FOR YOUR SERVER





$host = 'localhost';
$dbname = 'u421260613_salam';
$username = 'u421260613_salam';
$password = '7&Bu:Gm*';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get locale to export (default: Kurdish Sorani)
    $locale = isset($_GET['locale']) ? $_GET['locale'] : 'ckb';
    
    // Fetch translations
    $stmt = $pdo->prepare("SELECT `locale`, `key`, `value` FROM `translations` WHERE `locale` = :locale ORDER BY `key`");
    $stmt->execute(['locale' => $locale]);
    $translations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Export as JSON
    $filename = "translations_{$locale}_" . date('Y-m-d_H-i-s') . ".json";
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    exit;
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
