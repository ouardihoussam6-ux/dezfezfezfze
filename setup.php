<?php
declare(strict_types=1);

/**
 * Smart Park — Setup complet
 * 1. Se connecte en root MariaDB (sans mot de passe, défaut sur Pi)
 * 2. Crée la base + l'utilisateur admin + les tables + les données
 */

$ROOT_PASS = '';       // Mot de passe root MariaDB (vide par défaut sur Pi)
$DB_HOST   = 'localhost';
$DB_NAME   = 'smart_park';
$DB_USER   = 'admin';
$DB_PASS   = 'admin';

$steps = [];
$ok    = true;

function step(array &$steps, bool $success, string $msg): void
{
    $steps[] = [$success ? 'ok' : 'err', $msg];
}

try {
    /* ── 1. Connexion root ───────────────────────────────── */
    $root = new PDO(
        "mysql:host=$DB_HOST;charset=utf8mb4",
        'root',
        $ROOT_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    step($steps, true, 'Connexion MariaDB root établie.');

    /* ── 2. Base de données ──────────────────────────────── */
    $root->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME`
                 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    step($steps, true, "Base de données <b>$DB_NAME</b> prête.");

    /* ── 3. Utilisateur admin ────────────────────────────── */
    // Crée l'utilisateur s'il n'existe pas, puis met à jour le mot de passe
    $root->exec("CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS'");
    $root->exec("ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS'");
    $root->exec("GRANT ALL PRIVILEGES ON `$DB_NAME`.* TO '$DB_USER'@'localhost'");
    $root->exec("FLUSH PRIVILEGES");
    step($steps, true, "Utilisateur <b>$DB_USER</b> créé et autorisé.");

    /* ── 4. Tables ───────────────────────────────────────── */
    $root->exec("USE `$DB_NAME`");

    $root->exec("CREATE TABLE IF NOT EXISTS badges (
        id         INT          NOT NULL AUTO_INCREMENT,
        tag_uid    VARCHAR(50)  NOT NULL,
        nom        VARCHAR(100) NOT NULL DEFAULT 'Inconnu',
        autorise   TINYINT(1)   NOT NULL DEFAULT 1,
        created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_uid (tag_uid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    step($steps, true, 'Table <b>badges</b> prête.');

    $root->exec("CREATE TABLE IF NOT EXISTS places (
        id_place   INT         NOT NULL,
        etat       ENUM('libre','occupee','panne') NOT NULL DEFAULT 'libre',
        uid_actuel VARCHAR(50) DEFAULT NULL,
        updated_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id_place)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    step($steps, true, 'Table <b>places</b> prête.');

    $root->exec("CREATE TABLE IF NOT EXISTS logs (
        id         INT         NOT NULL AUTO_INCREMENT,
        date_heure TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
        tag_id     VARCHAR(50) NOT NULL,
        action     VARCHAR(50) NOT NULL,
        slot       INT         NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY idx_tag  (tag_id),
        KEY idx_date (date_heure)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    step($steps, true, 'Table <b>logs</b> prête.');

    /* ── 5. Données initiales ────────────────────────────── */
    $root->exec("INSERT IGNORE INTO places (id_place, etat)
                 VALUES (1,'libre'),(2,'libre'),(3,'libre')");
    step($steps, true, '3 places initialisées.');

    /* ── 6. reset_ordre.txt ──────────────────────────────── */
    $file = __DIR__ . '/reset_ordre.txt';
    if (!file_exists($file)) {
        file_put_contents($file, '0');
        step($steps, true, 'Fichier <b>reset_ordre.txt</b> créé.');
    } else {
        step($steps, true, 'Fichier <b>reset_ordre.txt</b> déjà présent.');
    }

    /* ── 7. Vérification finale avec le compte admin ─────── */
    $test = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $count = $test->query('SELECT COUNT(*) FROM places')->fetchColumn();
    step($steps, true, "Connexion admin vérifiée — $count places en base.");

} catch (PDOException $e) {
    $ok      = false;
    $msg     = $e->getMessage();

    /* Aide contextuelle selon l'erreur */
    if (str_contains($msg, 'Access denied') && str_contains($msg, 'root')) {
        $hint = 'Le mot de passe root MariaDB est incorrect. '
              . 'Modifiez <code>$ROOT_PASS</code> en haut de ce fichier, '
              . 'ou connectez-vous via <code>sudo mysql</code> pour le réinitialiser.';
    } elseif (str_contains($msg, "Can't connect") || str_contains($msg, 'Connection refused')) {
        $hint = 'MariaDB n\'est pas démarré. Lancez : <code>sudo systemctl start mariadb</code>';
    } else {
        $hint = null;
    }

    $steps[] = ['err', htmlspecialchars($msg)];
    if ($hint) $steps[] = ['info', $hint];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup — Smart Park</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<nav>
    <div class="nav-inner">
        <span class="brand">Smart Park</span>
    </div>
</nav>
<main style="max-width:580px">
    <div class="page-header">
        <h1>Installation</h1>
        <p>Initialisation de la base de données.</p>
    </div>

    <div class="card">
        <?php foreach ($steps as [$type, $msg]): ?>
            <div class="setup-line setup-<?= $type ?>">
                <?= $type === 'ok' ? '✓' : ($type === 'err' ? '✗' : '→') ?>
                <?= $msg ?>
            </div>
        <?php endforeach; ?>

        <?php if ($ok): ?>
            <div class="setup-success">
                <p>Installation terminée. <strong>Supprimez ce fichier</strong> avant la mise en production.</p>
                <a href="/index.php" class="btn btn-primary" style="margin-top:12px">Accéder au tableau de bord</a>
            </div>
        <?php else: ?>
            <div class="setup-line setup-err" style="margin-top:12px">
                Corrigez l'erreur ci-dessus puis rechargez cette page.
            </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
