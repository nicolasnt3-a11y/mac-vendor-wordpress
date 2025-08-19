# Documentation Technique - Plugin MAC Vendor Lookup

## Architecture du Plugin

### Structure des fichiers
```
mac-vendor-lookup/
├── mac-vendor-lookup.php    # Classe principale du plugin
├── js/
│   └── mac-vendor-lookup.js # Interface utilisateur JavaScript
├── css/
│   └── mac-vendor-lookup.css # Styles CSS
├── oui.csv                  # Base de données IEEE OUI
├── config.php              # Configuration générée
├── install.php             # Script d'installation
├── test-csv-format.php     # Script de test
├── template-mac-vendor.php # Template WordPress
├── README.md               # Documentation utilisateur
└── DEVELOPER.md            # Cette documentation
```

### Classe principale : `MacVendorLookup`

#### Méthodes publiques
- `__construct()` : Initialise les hooks WordPress
- `init()` : Initialisation du plugin
- `enqueue_scripts()` : Charge les assets CSS/JS
- `shortcode()` : Génère l'interface utilisateur
- `ajax_mac_vendor_lookup()` : Traite les requêtes AJAX

#### Méthodes privées
- `parse_mac_addresses($input)` : Parse les adresses MAC
- `is_valid_mac($mac)` : Valide le format d'une adresse MAC
- `find_vendor($mac, $csv_file)` : Recherche un constructeur

## Hooks WordPress utilisés

### Actions
```php
add_action('init', array($this, 'init'));
add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
add_action('wp_ajax_mac_vendor_lookup', array($this, 'ajax_mac_vendor_lookup'));
add_action('wp_ajax_nopriv_mac_vendor_lookup', array($this, 'ajax_mac_vendor_lookup'));
```

### Shortcodes
```php
add_shortcode('mac_vendor_lookup', array($this, 'shortcode'));
```

## API JavaScript

### Variables globales
```javascript
mac_vendor_ajax.ajax_url  // URL pour les requêtes AJAX
mac_vendor_ajax.nonce     // Nonce de sécurité
```

### Fonctions principales
- `displayResults(results)` : Affiche les résultats dans le tableau
- `exportToCSV(results)` : Exporte les résultats en CSV
- `formatMacAddress(mac)` : Formate une adresse MAC
- `showLoading()` / `hideLoading()` : Gestion de l'état de chargement

## Format des données

### Entrée (adresses MAC)
Le plugin accepte plusieurs formats :
- `00:11:22:33:44:55` (format standard)
- `00-11-22-33-44-55` (format avec tirets)
- `001122334455` (format compact)
- `00 11 22 33 44 55` (format avec espaces)

### Sortie (résultats)
```php
array(
    'mac' => '001122334455',
    'vendor' => 'Nom du constructeur',
    'organization' => 'Nom de l\'organisation',
    'address' => 'Adresse physique'
)
```

## Format du fichier CSV

### Structure attendue
```csv
OUI,Constructeur,Organisation,Adresse
00000C,Cisco Systems Inc,Cisco Systems Inc,170 West Tasman Drive San Jose CA 95134-1706 USA
00000E,Fujitsu Limited,Fujitsu Limited,4-1-1 Kamikodanaka Nakahara-ku Kawasaki 211-8588 Japan
```

### Colonnes
1. **OUI** (6 caractères hexadécimaux) - Identifiant unique du constructeur
2. **Constructeur** - Nom du fabricant
3. **Organisation** - Nom de l'organisation (peut être identique au constructeur)
4. **Adresse** - Adresse physique (optionnel)

## Sécurité

### Protection CSRF
```php
// Vérification du nonce
if (!wp_verify_nonce($_POST['nonce'], 'mac_vendor_nonce')) {
    wp_die('Sécurité');
}
```

### Validation des données
```php
// Nettoyage des entrées
$mac_addresses = sanitize_textarea_field($_POST['mac_addresses']);

// Validation du format MAC
if (!$this->is_valid_mac($mac)) {
    // Adresse MAC invalide
}
```

### Prévention des injections
- Utilisation de `fgetcsv()` pour lire le fichier CSV
- Validation stricte des formats d'adresses MAC
- Échappement des données dans l'export CSV

## Performance

### Optimisations actuelles
- Lecture séquentielle du fichier CSV
- Validation précoce des adresses MAC
- Limitation du nombre de résultats

### Améliorations possibles
1. **Indexation** : Créer un index des OUI pour des recherches plus rapides
2. **Base de données** : Migrer vers MySQL/PostgreSQL pour de meilleures performances
3. **Cache** : Mettre en cache les résultats fréquents
4. **Pagination** : Limiter le nombre de résultats affichés

### Exemple d'indexation
```php
private function create_index() {
    $index = array();
    $handle = fopen($this->csv_file, 'r');
    
    while (($data = fgetcsv($handle)) !== false) {
        $oui = preg_replace('/[^0-9A-Fa-f]/', '', strtoupper($data[0]));
        $index[$oui] = array(
            'vendor' => $data[1],
            'organization' => $data[2],
            'address' => isset($data[3]) ? $data[3] : ''
        );
    }
    
    fclose($handle);
    return $index;
}
```

## Extensions possibles

### 1. Support de bases de données multiples
```php
class MacVendorLookup {
    private $databases = array(
        'ieee' => 'oui.csv',
        'custom' => 'custom_vendors.csv'
    );
    
    public function search_all_databases($mac) {
        $results = array();
        foreach ($this->databases as $name => $file) {
            $result = $this->find_vendor($mac, $file);
            if ($result) {
                $result['source'] = $name;
                $results[] = $result;
            }
        }
        return $results;
    }
}
```

### 2. API REST
```php
add_action('rest_api_init', function () {
    register_rest_route('mac-vendor/v1', '/lookup/(?P<mac>[a-fA-F0-9:]+)', array(
        'methods' => 'GET',
        'callback' => 'mac_vendor_api_lookup',
        'permission_callback' => 'mac_vendor_api_permission'
    ));
});
```

### 3. Cache Redis/Memcached
```php
private function get_cached_vendor($oui) {
    $cache_key = 'mac_vendor_' . $oui;
    $cached = wp_cache_get($cache_key, 'mac_vendor');
    
    if ($cached === false) {
        $vendor = $this->find_vendor_in_csv($oui);
        wp_cache_set($cache_key, $vendor, 'mac_vendor', 3600);
        return $vendor;
    }
    
    return $cached;
}
```

## Tests

### Tests unitaires
```php
class MacVendorLookupTest extends WP_UnitTestCase {
    public function test_mac_validation() {
        $plugin = new MacVendorLookup();
        
        $this->assertTrue($plugin->is_valid_mac('00:11:22:33:44:55'));
        $this->assertTrue($plugin->is_valid_mac('001122334455'));
        $this->assertFalse($plugin->is_valid_mac('invalid'));
    }
}
```

### Tests d'intégration
```php
public function test_ajax_lookup() {
    $_POST['nonce'] = wp_create_nonce('mac_vendor_nonce');
    $_POST['mac_addresses'] = '00:11:22:33:44:55';
    
    $this->_handleAjax('mac_vendor_lookup');
    
    $this->assertTrue(wp_doing_ajax());
}
```

## Débogage

### Logs
```php
// Activer les logs de débogage
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('MAC Vendor Lookup: ' . $message);
}
```

### Mode debug
```php
define('MAC_VENDOR_DEBUG', true);

if (MAC_VENDOR_DEBUG) {
    // Code de débogage
}
```

## Déploiement

### Checklist de déploiement
- [ ] Vérifier les permissions du fichier `oui.csv`
- [ ] Tester avec différentes adresses MAC
- [ ] Vérifier la compatibilité avec le thème
- [ ] Tester l'export CSV
- [ ] Vérifier les performances avec de gros volumes

### Variables d'environnement
```php
// Configuration de production
define('MAC_VENDOR_CACHE_ENABLED', true);
define('MAC_VENDOR_MAX_RESULTS', 1000);
define('MAC_VENDOR_TIMEOUT', 30);
```

## Support et maintenance

### Mise à jour du fichier oui.csv
1. Télécharger la dernière version depuis IEEE
2. Remplacer l'ancien fichier
3. Vider le cache si applicable
4. Tester avec quelques adresses MAC

### Monitoring
- Surveiller les temps de réponse
- Vérifier les erreurs dans les logs
- Monitorer l'utilisation de la mémoire

### Sauvegarde
- Sauvegarder le fichier `oui.csv`
- Exporter la configuration
- Documenter les modifications personnalisées
