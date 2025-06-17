<?php
/**
 * ToolService.php
 * Utility helpers for tool assets.
 */

declare(strict_types=1);

class ToolService
{
    /**
     * Returns a publicly accessible URL for a tool image path.
     * Accepts relative paths stored in DB and converts them to
     * a path under the project root.
     *
     * @param string|null $path Relative path or absolute URL
     */
    public static function getToolImageUrl(?string $path): string
    {
        if (!$path) {
            return '';
        }
        // If already an absolute URL or begins with '/' return as is
        if (preg_match('#^(?:https?://|/)#i', $path)) {
            return ltrim($path, '/');
        }
        return 'wizard-stepper_git/' . ltrim($path, '/\\');
    }
}
