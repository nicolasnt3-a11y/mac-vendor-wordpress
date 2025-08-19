<?php
/**
 * Script de test pour analyser le format du fichier oui.csv
 * À exécuter une seule fois pour comprendre la structure du fichier
 */

// Chemin vers le fichier oui.csv
$csv_file = 'oui.csv';

if (!file_exists($csv_file)) {
    die("Fichier oui.csv introuvable\n");
}

echo "=== Analyse du fichier oui.csv ===\n\n";

// Lire les premières lignes pour analyser la structure
$handle = fopen($csv_file, 'r');
if (!$handle) {
    die("Impossible d'ouvrir le fichier oui.csv\n");
}

// Lire les 5 premières lignes
echo "Premières 5 lignes du fichier :\n";
echo "--------------------------------\n";

for ($i = 0; $i < 5; $i++) {
    $line = fgets($handle);
    if ($line === false) {
        break;
    }
    
    echo "Ligne " . ($i + 1) . ": " . trim($line) . "\n";
    
    // Analyser avec fgetcsv pour voir la structure
    rewind($handle);
    for ($j = 0; $j <= $i; $j++) {
        $data = fgetcsv($handle);
    }
    
    if ($data) {
        echo "  Colonnes détectées : " . count($data) . "\n";
        foreach ($data as $index => $value) {
            echo "    Colonne " . ($index + 1) . ": " . substr($value, 0, 50) . "\n";
        }
    }
    echo "\n";
}

// Compter le nombre total de lignes
rewind($handle);
$total_lines = 0;
while (fgets($handle) !== false) {
    $total_lines++;
}

echo "Nombre total de lignes : " . $total_lines . "\n\n";

// Analyser quelques exemples d'OUI
echo "Exemples d'OUI trouvés :\n";
echo "------------------------\n";

rewind($handle);
$count = 0;
while (($data = fgetcsv($handle)) !== false && $count < 10) {
    if (count($data) >= 1) {
        $oui = trim($data[0]);
        if (preg_match('/^[0-9A-Fa-f]{6}$/', $oui)) {
            echo "OUI: " . $oui . " - ";
            if (isset($data[1])) {
                echo "Constructeur: " . substr(trim($data[1]), 0, 30);
            }
            echo "\n";
            $count++;
        }
    }
}

fclose($handle);

echo "\n=== Recommandations ===\n";
echo "1. Vérifiez que la première colonne contient bien les OUI (6 caractères hex)\n";
echo "2. Vérifiez que la deuxième colonne contient les noms des constructeurs\n";
echo "3. Ajustez le plugin si nécessaire selon la structure détectée\n";

// Test de recherche d'un OUI spécifique
echo "\n=== Test de recherche ===\n";
$test_oui = "00000C"; // Exemple d'OUI Cisco
echo "Recherche de l'OUI : " . $test_oui . "\n";

$handle = fopen($csv_file, 'r');
$found = false;

while (($data = fgetcsv($handle)) !== false) {
    if (count($data) >= 1) {
        $csv_oui = preg_replace('/[^0-9A-Fa-f]/', '', strtoupper(trim($data[0])));
        if ($csv_oui === $test_oui) {
            echo "Trouvé !\n";
            echo "  OUI: " . $data[0] . "\n";
            if (isset($data[1])) echo "  Constructeur: " . $data[1] . "\n";
            if (isset($data[2])) echo "  Organisation: " . $data[2] . "\n";
            if (isset($data[3])) echo "  Adresse: " . $data[3] . "\n";
            $found = true;
            break;
        }
    }
}

if (!$found) {
    echo "OUI " . $test_oui . " non trouvé\n";
}

fclose($handle);

echo "\n=== Fin de l'analyse ===\n";
?>
