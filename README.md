# Plugin MAC Vendor Lookup pour WordPress

Ce plugin WordPress permet d'identifier le constructeur d'adresses MAC à partir du fichier `oui.csv` de l'IEEE.

## Fonctionnalités

- **Recherche multiple** : Saisissez une ou plusieurs adresses MAC (séparées par des virgules ou des retours à la ligne)
- **Validation en temps réel** : Vérification automatique du format des adresses MAC
- **Interface moderne** : Design responsive et intuitif
- **Export CSV** : Téléchargement des résultats au format CSV
- **Sécurité** : Protection CSRF avec nonces WordPress

## Installation

1. **Téléchargez le plugin** dans le dossier `/wp-content/plugins/` de votre WordPress
2. **Copiez le fichier `oui.csv`** à la racine du dossier du plugin
3. **Activez le plugin** depuis l'administration WordPress
4. **Créez une page** et utilisez le shortcode `[mac_vendor_lookup]`

## Structure des fichiers

```
mac-vendor-lookup/
├── mac-vendor-lookup.php    # Fichier principal du plugin
├── js/
│   └── mac-vendor-lookup.js # JavaScript pour l'interface
├── css/
│   └── mac-vendor-lookup.css # Styles CSS
├── oui.csv                  # Base de données des constructeurs MAC
└── README.md               # Ce fichier
```

## Utilisation

### Shortcode
Utilisez le shortcode `[mac_vendor_lookup]` dans n'importe quelle page ou article.

### Template de page
```php
<?php
/*
 * Template Name: Mac Vendor
 */
get_header(); 
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h1><?php the_title(); ?></h1>
            <span style="font-size: 12px; padding: 15px;"><?php the_content(); ?></span>
            <br/>
            <?php 
            if ( is_user_logged_in() ) {
                echo do_shortcode( '[mac_vendor_lookup]' );
            } else {
                echo '<p>Vous devez être connecté pour utiliser cet outil.</p>';
            }
            ?>
            <br/>
        </div>
    </div>
</div>

<?php wp_footer(); get_footer(); ?>
```

## Format des adresses MAC

Le plugin accepte les formats suivants :
- `00:11:22:33:44:55`
- `00-11-22-33-44-55`
- `001122334455`
- `00 11 22 33 44 55`

## Fonctionnalités avancées

### Validation en temps réel
- Les adresses MAC sont validées automatiquement pendant la saisie
- Indicateurs visuels (vert = valide, orange = partiellement valide, rouge = invalide)

### Export CSV
- Cliquez sur "Exporter CSV" pour télécharger les résultats
- Le fichier inclut : Adresse MAC, Constructeur, Organisation, Adresse

### Raccourcis clavier
- `Ctrl + Entrée` : Lancer la recherche

## Configuration requise

- WordPress 5.0 ou supérieur
- PHP 7.4 ou supérieur
- Fichier `oui.csv` valide (base de données IEEE)

## Format du fichier oui.csv

Le fichier `oui.csv` doit contenir au minimum les colonnes suivantes :
- Colonne 1 : OUI (Organizational Unique Identifier) - 6 caractères hexadécimaux
- Colonne 2 : Nom du constructeur
- Colonne 3 : Organisation
- Colonne 4 : Adresse (optionnel)

Exemple :
```csv
00000C,Cisco Systems, Inc.,170 West Tasman Drive San Jose CA 95134-1706 USA
00000E,Fujitsu Limited,4-1-1 Kamikodanaka Nakahara-ku Kawasaki 211-8588 Japan
```

## Dépannage

### Le fichier oui.csv n'est pas trouvé
- Vérifiez que le fichier `oui.csv` est bien présent à la racine du dossier du plugin
- Vérifiez les permissions du fichier (lecture)

### Erreur AJAX
- Vérifiez que jQuery est chargé sur votre thème
- Vérifiez les logs d'erreur PHP

### Performance lente
- Le fichier `oui.csv` peut être volumineux (plusieurs MB)
- Considérez l'utilisation d'une base de données pour de meilleures performances

## Support

Pour toute question ou problème, consultez la documentation WordPress ou contactez votre développeur.

## Licence

Ce plugin est fourni "tel quel" sans garantie. Utilisez-le à vos propres risques.

## Changelog

### Version 1.0
- Version initiale
- Recherche de constructeurs MAC
- Interface utilisateur moderne
- Export CSV
- Validation en temps réel
