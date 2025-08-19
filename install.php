<?php
/**
 * Script d'installation pour le plugin MAC Vendor Lookup
 * À exécuter une seule fois après avoir téléchargé le plugin
 */

// Vérifier que nous sommes dans un environnement WordPress
if (!defined('ABSPATH')) {
    // Si nous ne sommes pas dans WordPress, définir un chemin par défaut
    define('ABSPATH', dirname(__FILE__) . '/');
}

echo "=== Installation du plugin MAC Vendor Lookup ===\n\n";

// Vérifier les prérequis
echo "1. Vérification des prérequis...\n";

// Vérifier PHP
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die("ERREUR: PHP 7.4 ou supérieur requis. Version actuelle: " . PHP_VERSION . "\n");
}
echo "✓ PHP " . PHP_VERSION . " OK\n";

// Vérifier le fichier oui.csv
if (!file_exists('oui.csv')) {
    die("ERREUR: Fichier oui.csv introuvable. Veuillez le placer dans le dossier du plugin.\n");
}
echo "✓ Fichier oui.csv trouvé\n";

// Vérifier la taille du fichier
$file_size = filesize('oui.csv');
if ($file_size < 1000) {
    echo "⚠ ATTENTION: Le fichier oui.csv semble très petit (" . $file_size . " octets)\n";
} else {
    echo "✓ Taille du fichier oui.csv: " . number_format($file_size) . " octets\n";
}

// Vérifier les permissions
if (!is_readable('oui.csv')) {
    die("ERREUR: Le fichier oui.csv n'est pas lisible. Vérifiez les permissions.\n");
}
echo "✓ Permissions de lecture OK\n";

// Vérifier la structure du fichier
echo "\n2. Analyse de la structure du fichier oui.csv...\n";
$handle = fopen('oui.csv', 'r');
if (!$handle) {
    die("ERREUR: Impossible d'ouvrir le fichier oui.csv\n");
}

// Lire la première ligne pour vérifier l'en-tête
$header = fgetcsv($handle);
if ($header) {
    echo "✓ En-têtes détectés: " . count($header) . " colonnes\n";
    foreach ($header as $index => $value) {
        echo "  Colonne " . ($index + 1) . ": " . substr($value, 0, 30) . "\n";
    }
}

// Compter les lignes de données
$data_lines = 0;
$valid_oui_count = 0;
while (($data = fgetcsv($handle)) !== false) {
    $data_lines++;
    if (count($data) >= 1) {
        $oui = preg_replace('/[^0-9A-Fa-f]/', '', strtoupper(trim($data[0])));
        if (strlen($oui) === 6) {
            $valid_oui_count++;
        }
    }
    
    // Afficher un exemple après 100 lignes
    if ($data_lines === 100) {
        echo "✓ Exemple de données (ligne 100):\n";
        foreach ($data as $index => $value) {
            echo "  Colonne " . ($index + 1) . ": " . substr($value, 0, 50) . "\n";
        }
    }
}

fclose($handle);

echo "✓ Total des lignes de données: " . number_format($data_lines) . "\n";
echo "✓ OUI valides trouvés: " . number_format($valid_oui_count) . "\n";

// Test de performance
echo "\n3. Test de performance...\n";
$start_time = microtime(true);

$handle = fopen('oui.csv', 'r');
$test_count = 0;
$found_count = 0;

// Ignorer l'en-tête
fgetcsv($handle);

// Test avec quelques OUI connus
$test_ouis = ['00000C', '00000E', '000001', '000002', '000003'];

foreach ($test_ouis as $test_oui) {
    rewind($handle);
    fgetcsv($handle); // Ignorer l'en-tête
    
    while (($data = fgetcsv($handle)) !== false) {
        $test_count++;
        if (count($data) >= 1) {
            $csv_oui = preg_replace('/[^0-9A-Fa-f]/', '', strtoupper(trim($data[0])));
            if ($csv_oui === $test_oui) {
                $found_count++;
                break;
            }
        }
    }
}

fclose($handle);

$end_time = microtime(true);
$search_time = $end_time - $start_time;

echo "✓ Temps de recherche moyen: " . round($search_time * 1000, 2) . " ms\n";
echo "✓ OUI de test trouvés: " . $found_count . "/" . count($test_ouis) . "\n";

// Recommandations
echo "\n4. Recommandations...\n";

if ($data_lines > 10000) {
    echo "⚠ Le fichier est volumineux (" . number_format($data_lines) . " lignes)\n";
    echo "  Considérez l'utilisation d'une base de données pour de meilleures performances\n";
}

if ($search_time > 1.0) {
    echo "⚠ Les recherches sont lentes (" . round($search_time, 2) . " secondes)\n";
    echo "  Considérez l'indexation ou l'utilisation d'une base de données\n";
}

// Créer un fichier de configuration
echo "\n5. Création du fichier de configuration...\n";

$config_content = "<?php
/**
 * Configuration du plugin MAC Vendor Lookup
 * Généré automatiquement lors de l'installation
 */

// Informations sur le fichier oui.csv
define('MAC_VENDOR_CSV_FILE', '" . realpath('oui.csv') . "');
define('MAC_VENDOR_TOTAL_LINES', " . $data_lines . ");
define('MAC_VENDOR_VALID_OUI', " . $valid_oui_count . ");

// Paramètres de performance
define('MAC_VENDOR_SEARCH_TIMEOUT', 30); // secondes
define('MAC_VENDOR_MAX_RESULTS', 1000);

// Informations sur l'installation
define('MAC_VENDOR_INSTALL_DATE', '" . date('Y-m-d H:i:s') . "');
define('MAC_VENDOR_VERSION', '1.0');
?>";

if (file_put_contents('config.php', $config_content)) {
    echo "✓ Fichier config.php créé\n";
} else {
    echo "⚠ Impossible de créer le fichier config.php\n";
}

// Instructions finales
echo "\n=== Installation terminée ===\n\n";
echo "Pour utiliser le plugin :\n";
echo "1. Activez le plugin dans l'administration WordPress\n";
echo "2. Créez une page et utilisez le shortcode [mac_vendor_lookup]\n";
echo "3. Ou utilisez le template fourni dans le README.md\n\n";

echo "Fichiers créés :\n";
echo "- mac-vendor-lookup.php (plugin principal)\n";
echo "- js/mac-vendor-lookup.js (interface JavaScript)\n";
echo "- css/mac-vendor-lookup.css (styles)\n";
echo "- config.php (configuration)\n";
echo "- README.md (documentation)\n\n";

echo "Support :\n";
echo "- Consultez le README.md pour la documentation complète\n";
echo "- Vérifiez les logs WordPress en cas de problème\n";
echo "- Le plugin est compatible avec WordPress 5.0+\n";

echo "\nInstallation réussie ! 🎉\n";
?>
