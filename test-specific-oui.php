<?php
/**
 * Script de test spécifique pour l'OUI 78028B
 */

// Chemin vers le fichier oui.csv
$csv_file = 'oui.csv';

if (!file_exists($csv_file)) {
    die("Fichier oui.csv introuvable\n");
}

echo "=== Test spécifique pour OUI 78028B ===\n\n";

// Adresse MAC à tester
$test_mac = '78:02:8B:BC:D7:16';
$target_oui = '78028B';

echo "Adresse MAC: $test_mac\n";
echo "OUI recherché: $target_oui\n\n";

// Fonction pour extraire l'OUI
function extract_oui($mac) {
    $clean_mac = preg_replace('/[^0-9A-Fa-f]/', '', strtoupper($mac));
    return substr($clean_mac, 0, 6);
}

// Test de l'extraction
$extracted_oui = extract_oui($test_mac);
echo "OUI extrait: $extracted_oui\n";
echo "Correspondance: " . ($extracted_oui === $target_oui ? "✅ Oui" : "❌ Non") . "\n\n";

// Ouvrir le fichier CSV
$handle = fopen($csv_file, 'r');
if (!$handle) {
    die("❌ Impossible d'ouvrir le fichier CSV\n");
}

// Lire l'en-tête
$header = fgetcsv($handle);
echo "En-têtes CSV: " . implode(', ', $header) . "\n\n";

$line_count = 0;
$found_78028b = false;
$apple_entries = array();
$similar_ouis = array();

while (($data = fgetcsv($handle)) !== false) {
    $line_count++;
    
    if (count($data) >= 1) {
        $csv_oui_raw = trim($data[0]);
        $csv_oui_clean = preg_replace('/[^0-9A-Fa-f]/', '', strtoupper($csv_oui_raw));
        
        // Chercher exactement 78028B
        if ($csv_oui_clean === $target_oui) {
            echo "✅ OUI $target_oui trouvé à la ligne $line_count!\n";
            echo "  Raw: '$csv_oui_raw'\n";
            echo "  Clean: '$csv_oui_clean'\n";
            echo "  Constructeur: " . (isset($data[1]) ? $data[1] : 'N/A') . "\n";
            echo "  Organisation: " . (isset($data[2]) ? $data[2] : 'N/A') . "\n";
            echo "  Adresse: " . (isset($data[3]) ? $data[3] : 'N/A') . "\n\n";
            $found_78028b = true;
        }
        
        // Collecter les entrées Apple
        if (isset($data[1]) && stripos($data[1], 'apple') !== false) {
            $apple_entries[] = array(
                'line' => $line_count,
                'raw' => $csv_oui_raw,
                'clean' => $csv_oui_clean,
                'vendor' => $data[1]
            );
        }
        
        // Chercher des OUI similaires (commençant par 78)
        if (strpos($csv_oui_clean, '78') === 0) {
            $similar_ouis[] = array(
                'line' => $line_count,
                'raw' => $csv_oui_raw,
                'clean' => $csv_oui_clean,
                'vendor' => isset($data[1]) ? $data[1] : 'N/A'
            );
        }
    }
    
    // Limiter la recherche pour éviter les boucles infinies
    if ($line_count > 50000) {
        echo "⚠️  Recherche limitée aux 50000 premières lignes\n";
        break;
    }
}

fclose($handle);

if (!$found_78028b) {
    echo "❌ OUI $target_oui non trouvé dans le fichier\n\n";
    
    // Afficher les OUI similaires
    if (!empty($similar_ouis)) {
        echo "🔍 OUI similaires (commençant par 78):\n";
        foreach (array_slice($similar_ouis, 0, 10) as $similar) {
            echo "  - Ligne {$similar['line']}: {$similar['raw']} -> {$similar['clean']} | {$similar['vendor']}\n";
        }
        if (count($similar_ouis) > 10) {
            echo "  ... et " . (count($similar_ouis) - 10) . " autres\n";
        }
        echo "\n";
    }
    
    // Afficher les entrées Apple
    if (!empty($apple_entries)) {
        echo "🍎 Entrées Apple trouvées:\n";
        foreach (array_slice($apple_entries, 0, 10) as $apple) {
            echo "  - Ligne {$apple['line']}: {$apple['raw']} -> {$apple['clean']} | {$apple['vendor']}\n";
        }
        if (count($apple_entries) > 10) {
            echo "  ... et " . (count($apple_entries) - 10) . " autres\n";
        }
        echo "\n";
    }
}

echo "=== Test terminé ===\n";
echo "Lignes parcourues: $line_count\n";
?>
