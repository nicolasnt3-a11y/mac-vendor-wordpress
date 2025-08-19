<?php
/**
 * Script de débogage pour analyser le problème de recherche MAC
 */

// Chemin vers le fichier oui.csv
$csv_file = 'oui.csv';

if (!file_exists($csv_file)) {
    die("Fichier oui.csv introuvable\n");
}

// Adresse MAC à tester
$test_mac = '78:02:8B:BC:D7:16';

echo "=== Débogage de la recherche MAC ===\n\n";

// Fonction pour extraire l'OUI
function extract_oui($mac) {
    $clean_mac = preg_replace('/[^0-9A-Fa-f]/', '', strtoupper($mac));
    return substr($clean_mac, 0, 6);
}

// Fonction pour valider MAC
function is_valid_mac($mac) {
    $clean_mac = preg_replace('/[^0-9A-Fa-f]/', '', $mac);
    return strlen($clean_mac) === 12;
}

// Fonction pour rechercher un constructeur
function find_vendor($mac, $csv_file) {
    $oui = extract_oui($mac);
    
    echo "Adresse MAC: $mac\n";
    echo "OUI extrait: $oui\n\n";
    
    $handle = fopen($csv_file, 'r');
    if (!$handle) {
        echo "❌ Impossible d'ouvrir le fichier CSV\n";
        return array();
    }
    
    // Lire l'en-tête
    $header = fgetcsv($handle);
    echo "En-têtes CSV: " . implode(', ', $header) . "\n\n";
    
    $found = false;
    $line_count = 0;
    $apple_ouis = array();
    
    while (($data = fgetcsv($handle)) !== false) {
        $line_count++;
        
        if (count($data) >= 1) {
            $csv_oui = preg_replace('/[^0-9A-Fa-f]/', '', strtoupper(trim($data[0])));
            
            // Chercher les OUI d'Apple
            if (isset($data[1]) && stripos($data[1], 'apple') !== false) {
                $apple_ouis[] = array(
                    'oui' => $csv_oui,
                    'vendor' => $data[1],
                    'line' => $line_count
                );
            }
            
            // Vérifier si c'est notre OUI
            if ($csv_oui === $oui) {
                echo "✅ OUI trouvé à la ligne $line_count!\n";
                echo "  OUI: $csv_oui\n";
                echo "  Constructeur: " . (isset($data[1]) ? $data[1] : 'N/A') . "\n";
                echo "  Organisation: " . (isset($data[2]) ? $data[2] : 'N/A') . "\n";
                echo "  Adresse: " . (isset($data[3]) ? $data[3] : 'N/A') . "\n";
                $found = true;
                break;
            }
        }
        
        // Limiter l'affichage pour les gros fichiers
        if ($line_count > 1000 && !$found) {
            echo "⚠️  Recherche limitée aux 1000 premières lignes\n";
            break;
        }
    }
    
    fclose($handle);
    
    if (!$found) {
        echo "❌ OUI $oui non trouvé dans le fichier\n\n";
        
        if (!empty($apple_ouis)) {
            echo "🍎 OUI d'Apple trouvés dans le fichier:\n";
            foreach ($apple_ouis as $apple) {
                echo "  - OUI: {$apple['oui']} | {$apple['vendor']} (ligne {$apple['line']})\n";
            }
        } else {
            echo "🍎 Aucun OUI d'Apple trouvé dans le fichier\n";
        }
    }
    
    return $found;
}

// Test de validation
echo "Test de validation:\n";
echo "MAC valide: " . (is_valid_mac($test_mac) ? "✅ Oui" : "❌ Non") . "\n";
echo "OUI extrait: " . extract_oui($test_mac) . "\n\n";

// Recherche dans le fichier
echo "Recherche dans le fichier oui.csv...\n";
find_vendor($test_mac, $csv_file);

// Test avec d'autres OUI connus d'Apple
echo "\n=== Test avec d'autres OUI d'Apple ===\n";
$apple_test_ouis = array('000C29', '001C42', '00236C', '0050C2', '000A27', '000A95');

foreach ($apple_test_ouis as $oui) {
    echo "\nTest OUI: $oui\n";
    $test_mac_with_oui = $oui . 'BCDEF0';
    find_vendor($test_mac_with_oui, $csv_file);
}

echo "\n=== Fin du débogage ===\n";
?>
