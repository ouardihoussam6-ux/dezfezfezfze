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
            <span class="brand-dot"></span>
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
