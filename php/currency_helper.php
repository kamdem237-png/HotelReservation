<?php
/**
 * HELPER DE CONVERSION DE DEVISE
 * Convertit les prix EUR en FCFA
 */

// Taux de change fixe : 1 EUR = 655.957 FCFA (arrondi à 656)
define('EUR_TO_FCFA', 656);

/**
 * Convertir un montant EUR en FCFA
 */
function convertToFCFA($amountEUR) {
    return $amountEUR * EUR_TO_FCFA;
}

/**
 * Formater un prix en FCFA
 */
function formatPriceFCFA($amount, $convertFromEUR = false) {
    if ($convertFromEUR) {
        $amount = convertToFCFA($amount);
    }
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

/**
 * Formater un prix pour affichage (sans conversion)
 * Utile si les prix sont déjà en FCFA dans la DB
 */
function displayPrice($amount) {
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

/**
 * Convertir un prix d'affichage pour la base de données
 */
function priceForDB($displayPrice) {
    // Retirer les espaces et FCFA, convertir en nombre
    $clean = str_replace([' ', 'FCFA', ','], '', $displayPrice);
    return (float)$clean;
}
?>
