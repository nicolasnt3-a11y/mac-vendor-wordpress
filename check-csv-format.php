<?php
/**
 * Script pour vérifier le format exact du fichier CSV
 */

$csv_file = 'oui.csv';

if (!file_exists($csv_file)) {
    die("Fichier oui.csv introuvable\n");
}

echo "=== Vérification du format CSV ===\n\n";

// Lire les premières lignes
$handle = fopen($csv_file, 'r');
if (!$handle) {
    die("Impossible d'ouvrir le fichier\n");
}

echo "Premières 10 lignes du fichier:\n";
echo "================================\n";

for ($i = 0; $i < 10; $i++) {
    $line = fgets($handle);
    if ($line === false) {
        break;
    }
    
    echo "Ligne " . ($i + 1) . ": " . trim($line) . "\n";
    
    // Analyser avec fgetcsv
    rewind($handle);
    for ($j = 0; $j <= $i; $j++) {
        $csv_data = fgetcsv($handle);
    }
    echo "  CSV: " . implode(' | ', $csv_data) . "\n";
    echo "  Colonnes: " . count($csv_data) . "\n";
    echo "  Première colonne (raw): '" . $csv_data[0] . "'\n";
    echo "  Première colonne (clean): '" . preg_replace('/[^0-9A-Fa-f]/', '', strtoupper($csv_data[0])) . "'\n";
    echo "\n";
}

fclose($handle);

// Chercher spécifiquement 78028B
echo "\n=== Recherche spécifique de 78028B ===\n";
$handle = fopen($csv_file, 'r');
$found = false;
$line_count = 0;

while (($line = fgets($handle)) !== false) {
    $line_count++;
    
    // Chercher dans la ligne brute
    if (strpos($line, '78028B') !== false) {
        echo "✅ 78028B trouvé à la ligne $line_count (recherche brute)\n";
        echo "Ligne complète: " . trim($line) . "\n";
        $found = true;
        break;
    }
    
    if ($line_count > 10000) {
        echo "⚠️  Recherche limitée aux 10000 premières lignes\n";
        break;
    }
}

if (!$found) {
    echo "❌ 78028B non trouvé dans les $line_count premières lignes\n";
}

fclose($handle);
?>
