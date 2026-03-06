<?php
declare(strict_types=1);

require_once __DIR__ . '/models/Badge.php';
require_once __DIR__ . '/includes/layout.php';

$msg   = null;
$type  = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = strtoupper(trim($_POST['uid'] ?? ''));
    $nom = trim($_POST['nom'] ?? '');

    if ($uid === '') {
        $msg  = 'L\'UID du badge est requis.';
        $type = 'err';
    } elseif (!preg_match('/^[A-F0-9]{2}([:\-]?[A-F0-9]{2}){3,6}$/', $uid)) {
        $msg  = 'Format d\'UID invalide. Exemple : A1B2C3D4';
        $type = 'err';
    } else {
        try {
            $existing = Badge::findByUid($uid);
            if ($existing) {
                $msg  = 'Ce badge est déjà enregistré dans le système.';
                $type = 'info';
            } else {
                Badge::create($uid, $nom ?: 'Inconnu');
                $msg  = 'Badge enregistré et autorisé avec succès.';
                $type = 'ok';
            }
        } catch (Throwable) {
            $msg  = 'Erreur serveur. Veuillez réessayer.';
            $type = 'err';
        }
    }
}

render_header('Inscrire un badge', 'inscription.php');
?>

<div class="registration-wrap">

    <div class="page-header" style="text-align:center">
        <h1>Inscrire un badge</h1>
        <p>Enregistrez votre badge RFID pour accéder au parking.</p>
    </div>

    <div class="card">
        <?php if ($msg): ?>
            <div class="alert alert-<?= $type ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="form-group">
                <label for="uid">UID du badge</label>
                <input
                    type="text"
                    id="uid"
                    name="uid"
                    placeholder="ex. A1B2C3D4"
                    value="<?= htmlspecialchars($_POST['uid'] ?? '') ?>"
                    autocomplete="off"
                    maxlength="32"
                    required
                >
                <span class="hint">L'UID est affiché sur votre badge ou lisible sur l'écran du parking.</span>
            </div>

            <div class="form-group">
                <label for="nom">Nom <span style="color:var(--muted);font-weight:400">(optionnel)</span></label>
                <input
                    type="text"
                    id="nom"
                    name="nom"
                    placeholder="ex. Jean Dupont"
                    value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                    maxlength="100"
                >
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                Inscrire le badge
            </button>
        </form>
    </div>

</div>

<?php render_footer(); ?>
