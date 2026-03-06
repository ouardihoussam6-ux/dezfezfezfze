<?php
declare(strict_types=1);

function render_header(string $title, string $active = ''): void
{
    $nav = [
        'index.php'       => 'Tableau de bord',
        'badges.php'      => 'Badges',
        'logs.php'        => 'Journaux',
        'inscription.php' => 'Inscrire',
    ];
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> — Smart Park</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<nav>
    <div class="nav-inner">
        <a href="/index.php" class="brand">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="7" width="20" height="14" rx="2"/>
                <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                <line x1="12" y1="12" x2="12" y2="16"/>
                <line x1="10" y1="14" x2="14" y2="14"/>
            </svg>
            Smart Park
        </a>
        <ul class="nav-links">
            <?php foreach ($nav as $href => $label): ?>
                <li>
                    <a href="/<?= $href ?>"<?= $active === $href ? ' class="active"' : '' ?>>
                        <?= $label ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
<main>
    <?php
}

function render_footer(): void
{
    ?>
</main>
<script src="/assets/js/app.js"></script>
</body>
</html>
    <?php
}
