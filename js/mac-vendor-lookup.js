jQuery(document).ready(function($) {
    
    // Variables globales
    let currentResults = [];
    
    // Gestionnaire pour le bouton de recherche
    $('#lookup_mac').on('click', function() {
        const macAddresses = $('#mac_addresses').val().trim();
        
        if (!macAddresses) {
            showError('Veuillez saisir au moins une adresse MAC');
            return;
        }
        
        // Masquer les erreurs précédentes
        hideError();
        
        // Afficher le loading
        showLoading();
        
        // Envoyer la requête AJAX
        $.ajax({
            url: mac_vendor_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mac_vendor_lookup',
                nonce: mac_vendor_ajax.nonce,
                mac_addresses: macAddresses
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    currentResults = response.data;
                    displayResults(currentResults);
                } else {
                    showError(response.data || 'Une erreur est survenue');
                }
            },
            error: function() {
                hideLoading();
                showError('Erreur de connexion. Veuillez réessayer.');
            }
        });
    });
    
    // Gestionnaire pour l'export CSV
    $('#export_csv').on('click', function() {
        if (currentResults.length === 0) {
            showError('Aucun résultat à exporter');
            return;
        }
        
        exportToCSV(currentResults);
    });
    
    // Gestionnaire pour effacer les résultats
    $('#clear_results').on('click', function() {
        clearResults();
    });
    
    // Fonction pour afficher les résultats
    function displayResults(results) {
        const tbody = $('#results_body');
        tbody.empty();
        
        results.forEach(function(result) {
            const row = $('<tr>');
            row.append($('<td>').text(formatMacAddress(result.mac)));
            row.append($('<td>').text(result.vendor));
            row.append($('<td>').text(result.organization));
            row.append($('<td>').text(result.address));
            tbody.append(row);
        });
        
        $('#results').show();
        $('#export_csv').show();
        $('#clear_results').show();
    }
    
    // Fonction pour formater l'adresse MAC
    function formatMacAddress(mac) {
        // Supprimer tous les caractères non hexadécimaux
        const clean = mac.replace(/[^0-9A-Fa-f]/g, '');
        
        // Formater en groupes de 2 caractères séparés par des deux-points
        return clean.match(/.{1,2}/g).join(':').toUpperCase();
    }
    
    // Fonction pour exporter en CSV
    function exportToCSV(results) {
        // En-têtes CSV
        let csvContent = "Adresse MAC,Constructeur,Organisation,Adresse\n";
        
        // Données
        results.forEach(function(result) {
            const row = [
                formatMacAddress(result.mac),
                escapeCsvField(result.vendor),
                escapeCsvField(result.organization),
                escapeCsvField(result.address)
            ];
            csvContent += row.join(',') + '\n';
        });
        
        // Créer et télécharger le fichier
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'mac_vendors_' + new Date().toISOString().slice(0, 10) + '.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
    
    // Fonction pour échapper les champs CSV
    function escapeCsvField(field) {
        if (field === null || field === undefined) {
            return '';
        }
        
        const stringField = String(field);
        
        // Si le champ contient une virgule, des guillemets ou une nouvelle ligne, l'entourer de guillemets
        if (stringField.includes(',') || stringField.includes('"') || stringField.includes('\n')) {
            return '"' + stringField.replace(/"/g, '""') + '"';
        }
        
        return stringField;
    }
    
    // Fonction pour effacer les résultats
    function clearResults() {
        $('#results').hide();
        $('#export_csv').hide();
        $('#clear_results').hide();
        $('#mac_addresses').val('');
        currentResults = [];
        hideError();
    }
    
    // Fonction pour afficher le loading
    function showLoading() {
        $('#loading').show();
        $('#results').hide();
        $('#export_csv').hide();
        $('#clear_results').hide();
    }
    
    // Fonction pour masquer le loading
    function hideLoading() {
        $('#loading').hide();
    }
    
    // Fonction pour afficher une erreur
    function showError(message) {
        $('#error').text(message).show();
    }
    
    // Fonction pour masquer les erreurs
    function hideError() {
        $('#error').hide();
    }
    
    // Validation en temps réel des adresses MAC
    $('#mac_addresses').on('input', function() {
        const value = $(this).val();
        const lines = value.split(/[\r\n,]+/);
        let validCount = 0;
        let totalCount = 0;
        
        lines.forEach(function(line) {
            const mac = line.trim();
            if (mac) {
                totalCount++;
                const clean = mac.replace(/[^0-9A-Fa-f]/g, '');
                if (clean.length === 12) {
                    validCount++;
                }
            }
        });
        
        // Mettre à jour l'indicateur de validation
        if (totalCount > 0) {
            const percentage = Math.round((validCount / totalCount) * 100);
            if (percentage === 100) {
                $(this).removeClass('is-invalid').addClass('is-valid');
            } else if (percentage > 0) {
                $(this).removeClass('is-valid is-invalid').addClass('is-warning');
            } else {
                $(this).removeClass('is-valid is-warning').addClass('is-invalid');
            }
        } else {
            $(this).removeClass('is-valid is-invalid is-warning');
        }
    });
    
    // Permettre la soumission avec Entrée
    $('#mac_addresses').on('keydown', function(e) {
        if (e.ctrlKey && e.keyCode === 13) { // Ctrl + Entrée
            $('#lookup_mac').click();
        }
    });
});
