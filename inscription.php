<?php
declare(strict_types=1);

require_once __DIR__ . '/models/Badge.php';
require_once __DIR__ . '/includes/layout.php';

$msg  = null;
$type = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = strtoupper(trim($_POST['uid'] ?? ''));
    $nom = trim($_POST['nom'] ?? '');

    if ($uid === '') {
        $msg  = 'L\'UID du badge est requis.';
        $type = 'err';
    } else {
        try {
            if (Badge::findByUid($uid)) {
                $msg  = 'Ce badge est déjà enregistré.';
                $type = 'info';
            } else {
                Badge::create($uid, $nom ?: 'Inconnu');
                $msg  = 'Badge enregistré avec succès.';
                $type = 'ok';
            }
        } catch (Throwable) {
            $msg  = 'Erreur serveur. Réessayez.';
            $type = 'err';
        }
    }
}

render_header('Inscrire un badge', 'inscription.php');
?>

<div style="max-width:440px;margin:0 auto">

    <p class="page-title">Inscrire un badge</p>
    <p class="page-sub">Enregistrez votre badge RFID pour accéder au parking.</p>

    <?php if ($msg): ?>
        <div class="alert alert-<?= $type ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Zone de scan -->
    <div class="scan-zone" id="scan-zone" title="Cliquez pour activer le scan">
        <svg class="scan-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="5" width="20" height="14" rx="2"/>
            <line x1="2" y1="10" x2="22" y2="10"/>
        </svg>
        <p class="scan-text" id="scan-text">Cliquez pour scanner une carte</p>
        <p class="scan-hint" id="scan-hint">Approchez votre badge du lecteur après avoir cliqué</p>
    </div>

    <!-- Formulaire -->
    <div class="card">
        <form method="post" novalidate>
            <div class="field" style="margin-bottom:16px">
                <label for="uid">UID du badge</label>
                <input
                    type="text"
                    id="uid"
                    name="uid"
                    placeholder="Ex. A1B2C3D4"
                    value="<?= htmlspecialchars($_POST['uid'] ?? '') ?>"
                    autocomplete="off"
                    maxlength="32"
                    required
                >
                <span class="hint">Scannez avec le lecteur ou saisissez manuellement.</span>
            </div>

            <div class="field" style="margin-bottom:20px">
                <label for="nom">Nom <span style="color:var(--neutral-400);font-weight:400">(optionnel)</span></label>
                <input
                    type="text"
                    id="nom"
                    name="nom"
                    placeholder="Ex. Jean Dupont"
                    value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                    maxlength="100"
                >
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%">
                Inscrire le badge
            </button>
        </form>
    </div>

</div>

<?php render_footer(); ?>
