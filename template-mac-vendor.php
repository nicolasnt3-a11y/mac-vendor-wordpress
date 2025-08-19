<?php
/**
 * Template Name: Mac Vendor
 * 
 * Template personnalisé pour la recherche de constructeurs MAC
 * Compatible avec les thèmes WordPress modernes
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Charger l'en-tête WordPress
get_header(); 

// Vérifier si l'utilisateur est connecté
$user_logged_in = is_user_logged_in();
?>

<div class="container mt-4">
    <div class="row" id="pdf">
        <div class="col-md-12">
            <!-- En-tête de la page -->
            <header class="page-header">
                <h1 class="page-title"><?php the_title(); ?></h1>
                <?php if (has_excerpt()) : ?>
                    <div class="page-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                <?php endif; ?>
            </header>

            <!-- Contenu de la page -->
            <div class="page-content">
                <?php if (has_content()) : ?>
                    <div class="content-description">
                        <?php the_content(); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section principale -->
            <main class="main-content">
                <?php if ($user_logged_in) : ?>
                    <!-- Utilisateur connecté - Afficher le plugin -->
                    <div class="mac-vendor-section">
                        <?php echo do_shortcode('[mac_vendor_lookup]'); ?>
                    </div>
                <?php else : ?>
                    <!-- Utilisateur non connecté - Message de connexion -->
                    <div class="login-required">
                        <div class="alert alert-info">
                            <h4>🔒 Accès restreint</h4>
                            <p>Vous devez être connecté pour utiliser l'outil de recherche de constructeurs MAC.</p>
                            <div class="login-actions">
                                <a href="<?php echo wp_login_url(get_permalink()); ?>" class="btn btn-primary">
                                    Se connecter
                                </a>
                                <?php if (get_option('users_can_register')) : ?>
                                    <a href="<?php echo wp_registration_url(); ?>" class="btn btn-secondary">
                                        Créer un compte
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>

            <!-- Informations supplémentaires -->
            <aside class="page-info">
                <div class="info-box">
                    <h5>ℹ️ Informations</h5>
                    <ul>
                        <li>Cet outil utilise la base de données officielle IEEE OUI</li>
                        <li>Les adresses MAC sont traitées de manière sécurisée</li>
                        <li>Les résultats peuvent être exportés au format CSV</li>
                        <li>Support des formats MAC : XX:XX:XX:XX:XX:XX, XX-XX-XX-XX-XX-XX, XXXXXXXXXXXX</li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</div>

<!-- Styles personnalisés pour ce template -->
<style>
.page-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e9ecef;
}

.page-title {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.page-excerpt {
    color: #6c757d;
    font-size: 1.1rem;
    font-style: italic;
}

.content-description {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    border-left: 4px solid #3498db;
}

.mac-vendor-section {
    margin: 2rem 0;
}

.login-required {
    margin: 3rem 0;
}

.login-required .alert {
    border: none;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
}

.login-required h4 {
    color: #2c3e50;
    margin-bottom: 1rem;
}

.login-actions {
    margin-top: 1.5rem;
}

.login-actions .btn {
    margin: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.page-info {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid #e9ecef;
}

.info-box {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border-left: 4px solid #27ae60;
}

.info-box h5 {
    color: #2c3e50;
    margin-bottom: 1rem;
    font-weight: 600;
}

.info-box ul {
    margin: 0;
    padding-left: 1.5rem;
}

.info-box li {
    margin-bottom: 0.5rem;
    color: #495057;
}

/* Responsive design */
@media (max-width: 768px) {
    .container {
        padding: 0 15px;
    }
    
    .page-title {
        font-size: 1.75rem;
    }
    
    .content-description {
        padding: 1rem;
    }
    
    .login-actions .btn {
        display: block;
        width: 100%;
        margin: 0.5rem 0;
    }
    
    .info-box {
        padding: 1rem;
    }
}

/* Animations */
.page-header,
.content-description,
.mac-vendor-section,
.login-required,
.page-info {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Amélioration de l'accessibilité */
.page-title:focus,
.btn:focus {
    outline: 2px solid #3498db;
    outline-offset: 2px;
}

/* Support pour les thèmes sombres */
@media (prefers-color-scheme: dark) {
    .page-title {
        color: #ecf0f1;
    }
    
    .content-description,
    .info-box {
        background: #2c3e50;
        color: #ecf0f1;
    }
    
    .info-box li {
        color: #bdc3c7;
    }
}
</style>

<?php
// Charger le pied de page WordPress
wp_footer(); 
get_footer(); 
?>
