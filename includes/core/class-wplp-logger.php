<?php
if (!defined('ABSPATH')) exit;

class WPLP_Logger
{
    const LEVEL_INFO  = 'info';
    const LEVEL_ERROR = 'error';

    /**
     * Directorio de logs dentro del plugin
     */
    private static function log_dir(): string
    {
        return trailingslashit(WPLP_PATH) . 'logs';
    }

    /**
     * Archivo de log según nivel
     */
    private static function log_file(string $level): string
    {
        return self::log_dir() . '/' . $level . '.log';
    }

    /**
     * Crear directorio de logs si no existe
     */
    private static function ensure_log_dir(): void
    {
        $dir = self::log_dir();

        if (!file_exists($dir)) {

            wp_mkdir_p($dir);

            // seguridad básica para evitar acceso directo
            @file_put_contents(
                trailingslashit($dir) . 'index.php',
                "<?php\n// Silence is golden."
            );
        }
    }

    /**
     * Log informativo
     */
    public static function info(string $message, array $context = []): void
    {
        self::write(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Log de error
     */
    public static function error(string $message, array $context = []): void
    {
        self::write(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Escribir en el archivo de log
     */
    private static function write(string $level, string $message, array $context): void
    {
        // Solo loguear si WP_DEBUG está activo
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        self::ensure_log_dir();

        $timestamp = current_time('Y-m-d H:i:s');

        $entry = "[{$timestamp}] {$message}";

        if (!empty($context)) {
            $entry .= ' | ' . wp_json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        $entry .= PHP_EOL;

        $file = self::log_file($level);

        @file_put_contents(
            $file,
            $entry,
            FILE_APPEND | LOCK_EX
        );
    }
}