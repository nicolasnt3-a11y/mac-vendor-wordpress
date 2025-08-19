<?php
/**
 * Plugin Name: MAC Vendor Lookup
 * Description: Plugin pour identifier le constructeur d'adresses MAC à partir du fichier oui.csv
 * Version: 1.0
 * Author: Assistant
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class MacVendorLookup {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_mac_vendor_lookup', array($this, 'ajax_mac_vendor_lookup'));
        add_action('wp_ajax_nopriv_mac_vendor_lookup', array($this, 'ajax_mac_vendor_lookup'));
        add_action('wp_ajax_mac_vendor_debug', array($this, 'ajax_mac_vendor_debug'));
        add_shortcode('mac_vendor_lookup', array($this, 'shortcode'));
    }
    
    public function init() {
        // Initialisation du plugin
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('mac-vendor-lookup', plugin_dir_url(__FILE__) . 'js/mac-vendor-lookup.js', array('jquery'), '1.0', true);
        wp_localize_script('mac-vendor-lookup', 'mac_vendor_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mac_vendor_nonce')
        ));
        wp_enqueue_style('mac-vendor-lookup', plugin_dir_url(__FILE__) . 'css/mac-vendor-lookup.css', array(), '1.0');
    }
    
    public function shortcode() {
        ob_start();
        ?>
        <div class="mac-vendor-container">
            <div class="mac-vendor-form">
                <h3>Recherche de constructeur MAC</h3>
                <div class="form-group">
                    <label for="mac_addresses">Adresses MAC (une par ligne ou séparées par des virgules) :</label>
                    <textarea id="mac_addresses" class="form-control" rows="5" placeholder="Exemple:&#10;00:11:22:33:44:55&#10;AA:BB:CC:DD:EE:FF&#10;12:34:56:78:9A:BC"></textarea>
                </div>
                <button type="button" id="lookup_mac" class="btn btn-primary">Rechercher</button>
                <button type="button" id="export_csv" class="btn btn-success" style="display:none;">Exporter CSV</button>
                <button type="button" id="clear_results" class="btn btn-secondary" style="display:none;">Effacer</button>
                <?php if (defined('WP_DEBUG') && WP_DEBUG) : ?>
                <button type="button" id="debug_mac" class="btn btn-warning">Debug</button>
                <?php endif; ?>
            </div>
            
            <div id="loading" class="loading" style="display:none;">
                <div class="spinner"></div>
                <p>Recherche en cours...</p>
            </div>
            
            <div id="results" class="mac-vendor-results" style="display:none;">
                <h4>Résultats</h4>
                <div class="table-responsive">
                    <table id="results_table" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Adresse MAC</th>
                                <th>Constructeur</th>
                                <th>Organisation</th>
                                <th>Adresse</th>
                            </tr>
                        </thead>
                        <tbody id="results_body">
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div id="error" class="alert alert-danger" style="display:none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function ajax_mac_vendor_lookup() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mac_vendor_nonce')) {
            wp_die('Sécurité');
        }
        
        $mac_addresses = sanitize_textarea_field($_POST['mac_addresses']);
        $addresses = $this->parse_mac_addresses($mac_addresses);
        
        if (empty($addresses)) {
            wp_send_json_error('Aucune adresse MAC valide fournie');
        }
        
        $results = array();
        $csv_file = plugin_dir_path(__FILE__) . 'oui.csv';
        
        if (!file_exists($csv_file)) {
            wp_send_json_error('Fichier oui.csv introuvable');
        }
        
        foreach ($addresses as $mac) {
            $vendor = $this->find_vendor($mac, $csv_file);
            
            // Si pas trouvé, essayer la recherche robuste
            if (empty($vendor)) {
                $vendor = $this->find_vendor_robust($mac, $csv_file);
            }
            
            $results[] = array(
                'mac' => $mac,
                'vendor' => $vendor['vendor'] ?? 'Non trouvé',
                'organization' => $vendor['organization'] ?? '',
                'address' => $vendor['address'] ?? ''
            );
        }
        
        wp_send_json_success($results);
    }
    
    public function ajax_mac_vendor_debug() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mac_vendor_nonce')) {
            wp_die('Sécurité');
        }
        
        $mac_address = sanitize_text_field($_POST['mac_address']);
        $csv_file = plugin_dir_path(__FILE__) . 'oui.csv';
        
        if (!file_exists($csv_file)) {
            wp_send_json_error('Fichier oui.csv introuvable');
        }
        
        $oui = substr(preg_replace('/[^0-9A-Fa-f]/', '', strtoupper($mac_address)), 0, 6);
        
        $debug_info = array(
            'mac_address' => $mac_address,
            'oui_extracted' => $oui,
            'csv_file_exists' => file_exists($csv_file),
            'csv_file_size' => filesize($csv_file),
            'first_lines' => array()
        );
        
        // Lire les premières lignes du fichier CSV
        $handle = fopen($csv_file, 'r');
        if ($handle) {
            for ($i = 0; $i < 5; $i++) {
                $line = fgetcsv($handle);
                if ($line) {
                    $debug_info['first_lines'][] = $line;
                }
            }
            fclose($handle);
        }
        
        // Rechercher l'OUI spécifique
        $vendor = $this->find_vendor($mac_address, $csv_file);
        $debug_info['vendor_found'] = !empty($vendor);
        $debug_info['vendor_data'] = $vendor;
        
        wp_send_json_success($debug_info);
    }
    
    // Fonction alternative de recherche plus robuste
    private function find_vendor_robust($mac, $csv_file) {
        $oui = substr(preg_replace('/[^0-9A-Fa-f]/', '', strtoupper($mac)), 0, 6);
        
        // Essayer plusieurs formats d'OUI
        $oui_variants = array(
            $oui,
            strtolower($oui),
            strtoupper($oui)
        );
        
        $handle = fopen($csv_file, 'r');
        if (!$handle) {
            return array();
        }
        
        // Ignorer l'en-tête
        $header = fgetcsv($handle);
        
        // Détecter le format du fichier CSV
        $csv_format = 'standard';
        if ($header && count($header) >= 4) {
            $first_col = trim($header[0]);
            if (strpos(strtolower($first_col), 'registry') !== false) {
                $csv_format = 'ieee';
            }
        }
        
        $line_count = 0;
        while (($data = fgetcsv($handle)) !== false) {
            $line_count++;
            
            if (count($data) >= 2) {
                $csv_oui_raw = '';
                $vendor = '';
                $organization = '';
                $address = '';
                
                if ($csv_format === 'ieee') {
                    // Format IEEE: Registry | Assignment | Organization Name | Organization Address
                    $csv_oui_raw = trim($data[1]); // Assignment (colonne 2)
                    $vendor = isset($data[2]) ? trim($data[2]) : '';
                    $organization = isset($data[2]) ? trim($data[2]) : '';
                    $address = isset($data[3]) ? trim($data[3]) : '';
                } else {
                    // Format standard: OUI | Organization Name | Organization Address
                    $csv_oui_raw = trim($data[0]);
                    $vendor = isset($data[1]) ? trim($data[1]) : '';
                    $organization = isset($data[2]) ? trim($data[2]) : '';
                    $address = isset($data[3]) ? trim($data[3]) : '';
                }
                
                // Essayer plusieurs formats de nettoyage
                $csv_oui_variants = array(
                    preg_replace('/[^0-9A-Fa-f]/', '', strtoupper($csv_oui_raw)),
                    preg_replace('/[^0-9A-Fa-f]/', '', strtolower($csv_oui_raw)),
                    strtoupper($csv_oui_raw),
                    strtolower($csv_oui_raw)
                );
                
                // Vérifier toutes les variantes
                foreach ($oui_variants as $oui_variant) {
                    foreach ($csv_oui_variants as $csv_oui_variant) {
                        if ($csv_oui_variant === $oui_variant) {
                            fclose($handle);
                            return array(
                                'vendor' => $vendor,
                                'organization' => $organization,
                                'address' => $address
                            );
                        }
                    }
                }
            }
            
            if ($line_count > 100000) {
                break;
            }
        }
        
        fclose($handle);
        return array();
    }
    
    private function parse_mac_addresses($input) {
        $addresses = array();
        
        // Diviser par lignes ou virgules
        $lines = preg_split('/[\r\n,]+/', $input);
        
        foreach ($lines as $line) {
            $mac = trim($line);
            if ($this->is_valid_mac($mac)) {
                $addresses[] = strtoupper($mac);
            }
        }
        
        return $addresses;
    }
    
    private function is_valid_mac($mac) {
        // Supprimer les caractères non hexadécimaux
        $clean_mac = preg_replace('/[^0-9A-Fa-f]/', '', $mac);
        
        // Vérifier la longueur (6 octets = 12 caractères hex)
        if (strlen($clean_mac) !== 12) {
            return false;
        }
        
        return true;
    }
    
    private function find_vendor($mac, $csv_file) {
        // Extraire les 3 premiers octets (OUI)
        $oui = substr(preg_replace('/[^0-9A-Fa-f]/', '', strtoupper($mac)), 0, 6);
        
        // Debug: Log l'OUI recherché
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MAC Vendor Lookup: Recherche OUI: $oui pour MAC: $mac");
        }
        
        $handle = fopen($csv_file, 'r');
        if (!$handle) {
            return array();
        }
        
        // Ignorer l'en-tête si présent
        $header = fgetcsv($handle);
        
        // Détecter le format du fichier CSV
        $csv_format = 'standard'; // format par défaut
        if ($header && count($header) >= 4) {
            $first_col = trim($header[0]);
            if (strpos(strtolower($first_col), 'registry') !== false) {
                $csv_format = 'ieee'; // format IEEE avec Registry, Assignment, etc.
            }
        }
        
        // Debug: Log le format détecté
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MAC Vendor Lookup: Format CSV détecté: $csv_format");
        }
        
        $line_count = 0;
        $found_ouis = array(); // Pour le debug
        $similar_ouis = array(); // OUI similaires pour debug
        
        while (($data = fgetcsv($handle)) !== false) {
            $line_count++;
            
            if (count($data) >= 2) {
                $csv_oui_raw = '';
                $vendor = '';
                $organization = '';
                $address = '';
                
                if ($csv_format === 'ieee') {
                    // Format IEEE: Registry | Assignment | Organization Name | Organization Address
                    $csv_oui_raw = trim($data[1]); // Assignment (colonne 2)
                    $vendor = isset($data[2]) ? trim($data[2]) : ''; // Organization Name (colonne 3)
                    $organization = isset($data[2]) ? trim($data[2]) : ''; // Organization Name (colonne 3)
                    $address = isset($data[3]) ? trim($data[3]) : ''; // Organization Address (colonne 4)
                } else {
                    // Format standard: OUI | Organization Name | Organization Address
                    $csv_oui_raw = trim($data[0]); // OUI (colonne 1)
                    $vendor = isset($data[1]) ? trim($data[1]) : ''; // Organization Name (colonne 2)
                    $organization = isset($data[2]) ? trim($data[2]) : ''; // Organization Name (colonne 3)
                    $address = isset($data[3]) ? trim($data[3]) : ''; // Organization Address (colonne 4)
                }
                
                $csv_oui = preg_replace('/[^0-9A-Fa-f]/', '', strtoupper($csv_oui_raw));
                
                // Debug: Collecter quelques OUI pour vérification
                if ($line_count <= 10) {
                    $found_ouis[] = array(
                        'raw' => $csv_oui_raw,
                        'clean' => $csv_oui,
                        'vendor' => $vendor,
                        'format' => $csv_format
                    );
                }
                
                // Collecter les OUI similaires (même préfixe)
                if (strpos($csv_oui, substr($oui, 0, 2)) === 0 && $line_count <= 100) {
                    $similar_ouis[] = array(
                        'raw' => $csv_oui_raw,
                        'clean' => $csv_oui,
                        'vendor' => $vendor,
                        'line' => $line_count,
                        'format' => $csv_format
                    );
                }
                
                // Vérifier si l'OUI correspond
                if ($csv_oui === $oui) {
                    fclose($handle);
                    
                    // Debug: Log le succès
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("MAC Vendor Lookup: OUI trouvé à la ligne $line_count: $csv_oui (format: $csv_format)");
                    }
                    
                    return array(
                        'vendor' => $vendor,
                        'organization' => $organization,
                        'address' => $address
                    );
                }
            }
            
            // Limiter la recherche pour éviter les boucles infinies
            if ($line_count > 100000) {
                break;
            }
        }
        
        fclose($handle);
        
        // Debug: Log les informations détaillées
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MAC Vendor Lookup: OUI $oui non trouvé après $line_count lignes (format: $csv_format)");
            error_log("MAC Vendor Lookup: Premiers OUI dans le fichier:");
            foreach ($found_ouis as $found) {
                error_log("  Raw: '{$found['raw']}' | Clean: '{$found['clean']}' | Vendor: {$found['vendor']} | Format: {$found['format']}");
            }
            
            if (!empty($similar_ouis)) {
                error_log("MAC Vendor Lookup: OUI similaires trouvés:");
                foreach (array_slice($similar_ouis, 0, 5) as $similar) {
                    error_log("  Ligne {$similar['line']}: '{$similar['raw']}' -> '{$similar['clean']}' | {$similar['vendor']} | Format: {$similar['format']}");
                }
            }
        }
        
        return array();
    }
}

// Initialiser le plugin
new MacVendorLookup();
