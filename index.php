<?php
declare(strict_types=1);

require_once __DIR__ . '/models/Place.php';
require_once __DIR__ . '/models/Log.php';
require_once __DIR__ . '/includes/layout.php';

$etatLabel  = ['libre' => 'Libre', 'occupee' => 'Occupée', 'panne' => 'En panne'];
$actionLabel = [
    'lecture'          => 'Lecture',
    'proposition_slot' => 'Proposition',
    'slot_valide'      => 'Validée',
    'slot_libere'      => 'Libérée',
    'slot_defaut'      => 'Défaut',
];

try {
    $places = Place::all();
    $stats  = Place::stats();
    $logs   = Log::recent(15);
    $error  = null;
} catch (Throwable) {
    $places = [];
    $stats  = ['libre' => 0, 'occupee' => 0, 'panne' => 0];
    $logs   = [];
    $error  = 'Impossible de se connecter à la base de données. Lancez setup.php.';
}

render_header('Tableau de bord', 'index.php');
?>

<p class="page-title">Tableau de bord</p>
<p class="page-sub">État du parking — actualisation automatique toutes les 5 secondes.</p>

<?php if ($error): ?>
    <div class="alert alert-err"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="stats-bar" id="stats-row">
    <div class="stat-item" data-stat="libre">
        <span class="dot dot-green"></span>
        <span><strong class="count"><?= $stats['libre'] ?></strong> libre<?= $stats['libre'] !== 1 ? 's' : '' ?></span>
    </div>
    <div class="stat-item" data-stat="occupee">
        <span class="dot dot-red"></span>
        <span><strong class="count"><?= $stats['occupee'] ?></strong> occupée<?= $stats['occupee'] !== 1 ? 's' : '' ?></span>
    </div>
    <div class="stat-item" data-stat="panne">
        <span class="dot dot-amber"></span>
        <span><strong class="count"><?= $stats['panne'] ?></strong> en panne</span>
    </div>
</div>

<div class="places-grid" id="places-grid">
    <?php foreach ($places as $p): ?>
        <?php $etat = $p['etat'] ?? 'libre'; ?>
        <div class="place-card <?= htmlspecialchars($etat) ?>" data-place="<?= (int) $p['id_place'] ?>">
            <div class="place-label">Place <?= (int) $p['id_place'] ?></div>
            <div class="place-status">
                <span class="status-chip <?= htmlspecialchars($etat) ?>">
                    <span class="dot dot-<?= $etat === 'libre' ? 'green' : ($etat === 'occupee' ? 'red' : 'amber') ?>"></span>
                    <span class="label"><?= htmlspecialchars($etatLabel[$etat] ?? $etat) ?></span>
                </span>
                <?php if (!empty($p['uid_actuel'])): ?>
                    <span class="place-uid"><?= htmlspecialchars($p['uid_actuel']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($places)): ?>
        <div class="empty" style="grid-column:1/-1">Aucune place. Lancez setup.php.</div>
    <?php endif; ?>
</div>

<p class="section-label">Activité récente</p>
<div class="table-wrap">
    <table>
        <thead>
            <tr><th>Date</th><th>Badge</th><th>Action</th><th>Place</th></tr>
        </thead>
        <tbody id="logs-body">
            <?php if (empty($logs)): ?>
                <tr><td colspan="4" class="empty">Aucune activité.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <?php $dt = new DateTimeImmutable($log['date_heure']); ?>
                    <tr>
                        <td data-label="Date"><?= $dt->format('d/m H:i') ?></td>
                        <td data-label="Badge"><span class="mono"><?= htmlspecialchars($log['tag_id']) ?></span></td>
                        <td data-label="Action">
                            <span class="action-badge <?= htmlspecialchars($log['action']) ?>">
                                <?= htmlspecialchars($actionLabel[$log['action']] ?? $log['action']) ?>
                            </span>
                        </td>
                        <td data-label="Place">
                            <span class="slot-circle <?= $log['slot'] == 0 ? 'empty' : '' ?>">
                                <?= $log['slot'] > 0 ? (int) $log['slot'] : '—' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="reset-bar">
    <div>
        <h3>Reset distant ESP32</h3>
        <p>Envoie un ordre de redémarrage pris en compte dans les 3 secondes.</p>
    </div>
    <button id="btn-reset" class="btn btn-amber">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
        Redémarrer l'ESP32
    </button>
</div>

<?php render_footer(); ?>
