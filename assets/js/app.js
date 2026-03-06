/* ── Smart Park — frontend ────────────────────────────────── */

const REFRESH_INTERVAL = 5000;

/* Live dashboard refresh */
function initDashboard() {
    const grid = document.getElementById('places-grid');
    const statsRow = document.getElementById('stats-row');
    const logsBody = document.getElementById('logs-body');

    if (!grid) return;

    async function refresh() {
        try {
            const res = await fetch('/api/status.php');
            if (!res.ok) return;
            const data = await res.json();

            updatePlaces(grid, data.places);
            if (statsRow) updateStats(statsRow, data.stats);
            if (logsBody && data.recent) updateLogs(logsBody, data.recent);
        } catch (_) { /* silently skip on network error */ }
    }

    setInterval(refresh, REFRESH_INTERVAL);
}

function updatePlaces(grid, places) {
    places.forEach(p => {
        const card = grid.querySelector(`[data-place="${p.id_place}"]`);
        if (!card) return;

        const etat = p.etat || 'libre';

        card.className = 'place-card ' + etat;

        const badge = card.querySelector('.status-badge');
        if (badge) {
            badge.className = 'status-badge ' + etat;
            badge.querySelector('.label').textContent = etatLabel(etat);
        }

        const uid = card.querySelector('.place-uid');
        if (uid) {
            uid.textContent = p.uid_actuel ? p.uid_actuel : '';
        }
    });
}

function updateStats(row, stats) {
    const pills = {
        libre:   row.querySelector('[data-stat="libre"] .count'),
        occupee: row.querySelector('[data-stat="occupee"] .count'),
        panne:   row.querySelector('[data-stat="panne"] .count'),
    };
    if (pills.libre)   pills.libre.textContent   = stats.libre   ?? 0;
    if (pills.occupee) pills.occupee.textContent = stats.occupee ?? 0;
    if (pills.panne)   pills.panne.textContent   = stats.panne   ?? 0;
}

function updateLogs(tbody, logs) {
    tbody.innerHTML = logs.map(log => `
        <tr>
            <td data-label="Date">${fmtDate(log.date_heure)}</td>
            <td data-label="Badge"><span class="mono">${esc(log.tag_id)}</span></td>
            <td data-label="Action"><span class="action-pill ${esc(log.action)}">${actionLabel(log.action)}</span></td>
            <td data-label="Place"><span class="slot-badge ${log.slot == 0 ? 's0' : ''}">${log.slot > 0 ? log.slot : '—'}</span></td>
        </tr>
    `).join('');
}

/* ── Reset order ────────────────────────────────────────── */
function initResetButton() {
    const btn = document.getElementById('btn-reset');
    if (!btn) return;

    btn.addEventListener('click', async () => {
        if (!confirm('Envoyer l\'ordre de redémarrage à l\'ESP32 ?')) return;
        btn.disabled = true;
        btn.textContent = 'Envoi…';
        try {
            const res = await fetch('/api/set_reset.php', { method: 'POST' });
            if (res.ok) {
                btn.textContent = 'Ordre envoyé';
                btn.classList.add('btn-ghost');
            } else {
                btn.textContent = 'Erreur';
            }
        } catch (_) {
            btn.textContent = 'Erreur réseau';
        }
    });
}

/* ── Helpers ────────────────────────────────────────────── */
function etatLabel(etat) {
    return { libre: 'Libre', occupee: 'Occupée', panne: 'En panne' }[etat] ?? etat;
}

function actionLabel(a) {
    return {
        lecture:          'Lecture',
        proposition_slot: 'Proposition',
        slot_valide:      'Validée',
        slot_libere:      'Libérée',
        slot_defaut:      'Défaut',
    }[a] ?? a;
}

function fmtDate(dt) {
    const d = new Date(dt.replace(' ', 'T'));
    const pad = n => String(n).padStart(2, '0');
    return `${pad(d.getDate())}/${pad(d.getMonth()+1)} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function esc(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/* ── Init ───────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    initDashboard();
    initResetButton();
});
