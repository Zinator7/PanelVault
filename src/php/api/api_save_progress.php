<?php
// ════════════════════════════════════════════════
//  API SAVE PROGRESS — Endpoint AJAX
//  PanelVault
//
//  QU'EST-CE QU'UN "ENDPOINT API" ?
//  C'est un fichier PHP dédié à recevoir des requêtes automatiques
//  (envoyées par JavaScript, pas par un humain qui clique un lien).
//  Il ne retourne pas du HTML pour une page, mais du JSON (données structurées).
//
//  FLUX COMPLET :
//  1. L'utilisateur passe à la page suivante dans le lecteur
//  2. js-reader.js envoie une requête POST avec fetch()
//  3. CE FICHIER reçoit la requête, valide les données, met à jour la BDD
//  4. Il répond avec du JSON : { "success": true } ou { "error": "..." }
//  5. js-reader.js reçoit la réponse (mais ne fait rien ici car tout est silencieux)
// ════════════════════════════════════════════════

// On déclare que tout ce fichier répond en JSON, pas en HTML.
// Content-Type dit au navigateur comment interpréter la réponse.
header('Content-Type: application/json');

// X-Content-Type-Options: nosniff = le navigateur ne doit pas deviner le type
// si on lui dit que c'est du JSON, c'est du JSON (sécurité basique des APIs)
header('X-Content-Type-Options: nosniff');

session_start();

// Vérifie que l'utilisateur est connecté.
// On ne veut pas qu'un anonyme puisse sauvegarder de la progression
// ou potentiellement modifier des données en base.
if (!isset($_SESSION['user'])) {
    // json_encode() convertit un tableau PHP en string JSON.
    // ['error' => 'Non authentifié'] → '{"error":"Non authentifié"}'
    echo json_encode(['error' => 'Non authentifié']);
    exit();
}

// On accepte uniquement les requêtes POST.
// GET sert à LIRE des données (comme afficher une page).
// POST sert à ENVOYER des données (comme sauvegarder).
// $_SERVER['REQUEST_METHOD'] contient la méthode HTTP utilisée ('GET', 'POST', etc.)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

// ─── Lecture du corps de la requête ───
//
// Avec fetch() et Content-Type: application/json, les données NE SONT PAS dans $_POST.
// Elles sont dans le "corps brut" (raw body) de la requête HTTP.
// php://input est un flux spécial qui permet de lire ce corps brut.
// file_get_contents() le lit et retourne une string JSON.
//
// Exemple du body reçu : '{"comic_id":42,"current_page":7,"total_pages":48}'
$body = file_get_contents('php://input');

// json_decode() convertit la string JSON en tableau PHP.
// Le 2ème argument "true" = retourner un tableau associatif (pas un objet stdClass)
// Sans "true" : $data->comic_id | Avec "true" : $data['comic_id']  ← on préfère ça
$data = json_decode($body, true);

// Si le JSON est invalide ou vide, json_decode() retourne null
if (!$data) {
    echo json_encode(['error' => 'Corps JSON invalide']);
    exit();
}

// ─── Validation et sécurisation des valeurs reçues ───
//
// On ne fait JAMAIS confiance aux données qui arrivent de l'extérieur (même du JS).
// Un utilisateur malveillant pourrait envoyer n'importe quoi.
//
// (int) = cast en entier. Si la valeur est "abc", ça donne 0.
// Si la valeur n'existe pas dans $data, isset() retourne false → on utilise 0.
//
// On utilise le ternaire : isset(...) ? (int)valeur : 0
// plutôt que $data['comic_id'] ?? 0 car ?? ne force pas le type.
$comic_id     = isset($data['comic_id'])     ? (int)$data['comic_id']     : 0;
$current_page = isset($data['current_page']) ? (int)$data['current_page'] : 0;
$total_pages  = isset($data['total_pages'])  ? (int)$data['total_pages']  : 0;

// Si une valeur est invalide (0 ou négative), on refuse la requête
if ($comic_id <= 0 || $current_page <= 0 || $total_pages <= 0) {
    echo json_encode(['error' => 'Paramètres invalides']);
    exit();
}

// "Borner" la page : s'assurer qu'elle est dans l'intervalle [1, total_pages].
// max(1, x) garantit que x >= 1
// min(total_pages, x) garantit que x <= total_pages
// Combined : max(1, min(48, page)) → toujours entre 1 et 48 (par exemple)
$current_page = max(1, min($total_pages, $current_page));

include '../db_connect.php';
include '../mvc/mvc_reading/crud_reading.php';

// On récupère l'ID utilisateur depuis la SESSION (pas depuis les données reçues !)
// Si on prenait user_id depuis $data, un utilisateur pourrait sauvegarder
// la progression d'un autre utilisateur — gros problème de sécurité.
$user_id = (int)$_SESSION['user']['id'];

// insert_progress() fait un INSERT ... ON DUPLICATE KEY UPDATE en SQL.
// C'est une requête "upsert" : elle crée la ligne si elle n'existe pas,
// ou la MET À JOUR si (user_id, comic_id) existe déjà.
// Voir crud_reading.php pour le détail de la requête SQL.
$result = insert_progress($conn, $user_id, $comic_id, $current_page, $total_pages);

if ($result) {
    // Calcul du % de progression pour la réponse
    $pct = round(($current_page / $total_pages) * 100);

    // On répond avec un JSON succès + les données calculées
    // La comparaison retourne true/false (PHP), json_encode le convertit en true/false (JSON)
    echo json_encode([
        'success'      => true,
        'current_page' => $current_page,
        'percent'      => $pct,
        'completed'    => ($current_page >= $total_pages), // true si c'est la dernière page
    ]);
} else {
    echo json_encode(['error' => 'Erreur lors de la sauvegarde en base']);
}
