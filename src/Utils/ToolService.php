<?php
declare(strict_types=1);

class ToolService
{
    /**
     * Returns the full URL of a tool image, caching the value in session.
     * If the tool has no image or an error occurs, returns an empty string.
     */
    public static function getToolImageUrl(PDO $pdo, string $table, int $toolId): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if ($toolId <= 0 || !preg_match('/^[a-z0-9_]+$/i', $table)) {
            return '';
        }

        if (!isset($_SESSION['tool_images'])) {
            $_SESSION['tool_images'] = [];
        }

        if (isset($_SESSION['tool_images'][$table][$toolId])) {
            return (string)$_SESSION['tool_images'][$table][$toolId];
        }

        $sql = "SELECT image FROM {$table} WHERE tool_id = ? LIMIT 1";
        $stmt = $pdo->prepare($sql);
        if ($stmt === false) {
            return '';
        }

        try {
            $stmt->execute([$toolId]);
            $img = $stmt->fetchColumn();
        } catch (\PDOException $e) {
            return '';
        }

        if (!$img) {
            $_SESSION['tool_images'][$table][$toolId] = '';
            return '';
        }

        $path = asset((string)$img);
        $_SESSION['tool_images'][$table][$toolId] = $path;
        return $path;
    }
}
