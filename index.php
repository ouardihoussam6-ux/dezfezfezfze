<?php
declare(strict_types=1);

require_once __DIR__ . '/models/Place.php';
require_once __DIR__ . '/models/Log.php';
require_once __DIR__ . '/includes/layout.php';

try {
    $places = Place::all();
    $stats  = Place::stats();
    $logs   = Log::recent(15);
    $error  = null;
} catch (Throwable $e) {
    $places = [];
    $stats  = ['libre' => 0, 'occupee' => 0, 'panne' => 0];
    $logs   = [];
    $error  = 'Impossible de se connecter à la base de données. Avez-vous exécuté setup.php ?';
}

$etatLabel = ['libre' => 'Libre', 'occupee' => 'Occupée', 'panne' => 'En panne'];

$actionLabel = [
    'lecture'          => 'Lecture',
    'proposition_slot' => 'Proposition',
    'slot_valide'      => 'Validée',
    'slot_libere'      => 'Libérée',
    'slot_defaut'      => 'Défaut',
];

render_header('Tableau de bord', 'index.php');
?>

<div class="page-header">
    <h1>Tableau de bord</h1>
    <p>État du parking en temps réel — actualisé toutes les 5 secondes.</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-err"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Stats pills -->
<div class="stats-row" id="stats-row">
    <div class="stat-pill" data-stat="libre">
        <span class="dot" style="background:var(--free-dot)"></span>
        <span class="count"><?= $stats['libre'] ?></span> libre<?= $stats['libre'] !== 1 ? 's' : '' ?>
    </div>
    <div class="stat-pill" data-stat="occupee">
        <span class="dot" style="background:var(--occupied-dot)"></span>
        <span class="count"><?= $stats['occupee'] ?></span> occupée<?= $stats['occupee'] !== 1 ? 's' : '' ?>
    </div>
    <div class="stat-pill" data-stat="panne">
        <span class="dot" style="background:var(--broken-dot)"></span>
        <span class="count"><?= $stats['panne'] ?></span> en panne
    </div>
</div>

<!-- Parking places -->
<div class="places-grid" id="places-grid">
    <?php foreach ($places as $p): ?>
        <?php $etat = $p['etat'] ?? 'libre'; ?>
        <div class="place-card <?= htmlspecialchars($etat) ?>" data-place="<?= (int)$p['id_place'] ?>">

            <svg class="place-icon" width="40" height="40" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="9" width="20" height="10" rx="2"/>
                <path d="M6 9V7a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2"/>
                <circle cx="7" cy="19" r="1.5"/>
                <circle cx="17" cy="19" r="1.5"/>
            </svg>

            <div class="place-num">Place <?= (int)$p['id_place'] ?></div>

            <div class="place-status">
                <span class="status-badge <?= htmlspecialchars($etat) ?>">
                    <span class="dot"></span>
                    <span class="label"><?= htmlspecialchars($etatLabel[$etat] ?? $etat) ?></span>
                </span>
                <?php if (!empty($p['uid_actuel'])): ?>
                    <span class="place-uid"><?= htmlspecialchars($p['uid_actuel']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($places)): ?>
        <div class="empty" style="grid-column:1/-1">Aucune place configurée. Exécutez setup.php.</div>
    <?php endif; ?>
</div>

<!-- Recent logs -->
<div class="section-title">Activité récente</div>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Badge</th>
                <th>Action</th>
                <th>Place</th>
            </tr>
        </thead>
        <tbody id="logs-body">
            <?php if (empty($logs)): ?>
                <tr><td colspan="4" class="empty">Aucune activité enregistrée.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <?php
                        $dt = new DateTimeImmutable($log['date_heure']);
                        $action = $log['action'];
                    ?>
                    <tr>
                        <td data-label="Date"><?= $dt->format('d/m H:i') ?></td>
                        <td data-label="Badge"><span class="mono"><?= htmlspecialchars($log['tag_id']) ?></span></td>
                        <td data-label="Action">
                            <span class="action-pill <?= htmlspecialchars($action) ?>">
                                <?= htmlspecialchars($actionLabel[$action] ?? $action) ?>
                            </span>
                        </td>
                        <td data-label="Place">
                            <span class="slot-badge <?= $log['slot'] == 0 ? 's0' : '' ?>">
                                <?= $log['slot'] > 0 ? (int)$log['slot'] : '—' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Reset distant -->
<div class="reset-section">
    <div class="desc">
        <h3>Reset distant</h3>
        <p>Envoie un ordre de redémarrage à l'ESP32. Pris en compte dans les 3 secondes.</p>
    </div>
    <button id="btn-reset" class="btn btn-reset">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
            <path d="M3 3v5h5"/>
        </svg>
        Redémarrer l'ESP32
    </button>
</div>

<?php render_footer(); ?>
