<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

final class Log
{
    public static function insert(string $tagId, string $action, int $slot): void
    {
        Database::get()->prepare(
            'INSERT INTO logs (tag_id, action, slot) VALUES (?, ?, ?)'
        )->execute([$tagId, $action, $slot]);
    }

    public static function recent(int $limit = 20): array
    {
        $st = Database::get()->prepare(
            'SELECT * FROM logs ORDER BY date_heure DESC LIMIT ?'
        );
        $st->bindValue(1, $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function paginate(int $page, int $perPage = 30): array
    {
        $offset = ($page - 1) * $perPage;
        $st = Database::get()->prepare(
            'SELECT * FROM logs ORDER BY date_heure DESC LIMIT ? OFFSET ?'
        );
        $st->bindValue(1, $perPage, PDO::PARAM_INT);
        $st->bindValue(2, $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function count(): int
    {
        return (int) Database::get()->query('SELECT COUNT(*) FROM logs')->fetchColumn();
    }
}
