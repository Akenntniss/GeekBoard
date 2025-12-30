<?php
/**
 * Configuration globale du fuseau horaire GeekBoard
 * 
 * Ce fichier s'assure que le fuseau horaire de Paris est toujours utilisé
 * par défaut dans toute l'application (transitions été/hiver automatiques).
 * 
 * À inclure au début de chaque fichier PHP principal.
 */

// Forcer le fuseau horaire Paris pour toute l'application
date_default_timezone_set('Europe/Paris');

/**
 * Fonction utilitaire pour obtenir la date/heure actuelle en heure de Paris
 * 
 * @param string $format Format de date (par défaut: 'Y-m-d H:i:s')
 * @return string Date formatée en heure de Paris
 */
function date_paris($format = 'Y-m-d H:i:s') {
    // S'assurer que nous sommes en fuseau horaire de Paris
    $original_timezone = date_default_timezone_get();
    date_default_timezone_set('Europe/Paris');
    
    $result = date($format);
    
    // Restaurer le fuseau horaire original (au cas où)
    date_default_timezone_set($original_timezone);
    
    return $result;
}

/**
 * Fonction utilitaire pour créer un objet DateTime en fuseau horaire de Paris
 * 
 * @param string $time Chaîne de temps (optionnel)
 * @return DateTime Objet DateTime configuré pour Paris
 */
function datetime_paris($time = 'now') {
    return new DateTime($time, new DateTimeZone('Europe/Paris'));
}

/**
 * Fonction pour formater un timestamp en heure de Paris
 * 
 * @param int $timestamp Timestamp Unix
 * @param string $format Format de sortie
 * @return string Date formatée
 */
function format_timestamp_paris($timestamp, $format = 'Y-m-d H:i:s') {
    $dt = new DateTime();
    $dt->setTimestamp($timestamp);
    $dt->setTimezone(new DateTimeZone('Europe/Paris'));
    return $dt->format($format);
}

// Vérification que le fuseau horaire est bien configuré
if (date_default_timezone_get() !== 'Europe/Paris') {
    error_log("Attention: Le fuseau horaire n'est pas configuré sur Paris. Fuseau actuel: " . date_default_timezone_get());
}
