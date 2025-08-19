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
            $results[] = array(
                'mac' => $mac,
                'vendor' => $vendor['vendor'] ?? 'Non trouvé',
                'organization' => $vendor['organization'] ?? '',
                'address' => $vendor['address'] ?? ''
            );
        }
        
        wp_send_json_success($results);
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
        $oui = substr(preg_replace('/[^0-9A-Fa-f]/', '', $mac), 0, 6);
        
        $handle = fopen($csv_file, 'r');
        if (!$handle) {
            return array();
        }
        
        // Ignorer l'en-tête si présent
        $header = fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= 3) {
                $csv_oui = preg_replace('/[^0-9A-Fa-f]/', '', strtoupper($data[0]));
                
                if ($csv_oui === $oui) {
                    fclose($handle);
                    return array(
                        'vendor' => trim($data[1]),
                        'organization' => trim($data[2]),
                        'address' => isset($data[3]) ? trim($data[3]) : ''
                    );
                }
            }
        }
        
        fclose($handle);
        return array();
    }
}

// Initialiser le plugin
new MacVendorLookup();
