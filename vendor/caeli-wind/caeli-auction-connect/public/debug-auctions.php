<?php
/**
 * Debug-Skript für Auktionsdaten
 * Bitte Datei nach Verwendung löschen!
 */

// Definiere den Projektpfad für Autoloading
$projectDir = realpath(__DIR__.'/../../../..');

// Autoloading (mit Composer)
require $projectDir.'/vendor/autoload.php';

// Contao Framework initialisieren
$framework = System::getContainer()->get('contao.framework');
$framework->initialize();

// AuctionService abrufen
$auctionService = System::getContainer()->get(CaeliWind\CaeliAuctionConnect\Service\AuctionService::class);

// Ausgabe formatieren
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Auctions Debug</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .debug-info { background: #eee; padding: 10px; margin-bottom: 20px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Auctions Debug</h1>
    
    <div class="debug-info">
        <p><strong>WICHTIG:</strong> Diese Debug-Seite aus Sicherheitsgründen nach Verwendung löschen!</p>
        <p>Diese Seite zeigt alle verfügbaren Auktionen mit ihren IDs an.</p>
    </div>
    
    <h2>Verfügbare Auktionen</h2>
    
    <?php
    // Auktionen abrufen
    $auctions = $auctionService->getAuctions([], true);
    
    if (!empty($auctions)) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Titel</th><th>Status</th><th>Details</th></tr>';
        
        foreach ($auctions as $auction) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($auction['id'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($auction['title'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($auction['status'] ?? 'N/A') . '</td>';
            echo '<td><a href="/auktionen/detail/' . htmlspecialchars($auction['id'] ?? '') . '" target="_blank">Details anzeigen</a></td>';
            echo '</tr>';
        }
        
        echo '</table>';
    } else {
        echo '<p>Keine Auktionen gefunden!</p>';
    }
    ?>
    
    <h2>Verfügbare Felder einer Auktion</h2>
    
    <?php
    if (!empty($auctions)) {
        $firstAuction = reset($auctions);
        echo '<pre>';
        echo 'Verfügbare Felder der ersten Auktion: ' . implode(', ', array_keys($firstAuction));
        echo '</pre>';
        
        echo '<pre>';
        echo 'Vollständige Daten der ersten Auktion: ';
        print_r($firstAuction);
        echo '</pre>';
    }
    ?>
</body>
</html> 