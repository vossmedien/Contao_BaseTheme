<?php

declare(strict_types=1);

/*
 * This file is part of VSM Helper und Integrations.
 *
 * (c) Vossmedien - Christian Voss 2025 <christian@vossmedien.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/vsm/vsm-helper-tools
 */

namespace Vsm\VsmHelperTools\Helper\Traits;

/**
 * Debuggable Trait
 * 
 * Fügt Debug-Funktionalität zu Helpern hinzu für einfacheres
 * Debugging und Performance-Analyse.
 */
trait DebuggableTrait
{
    // Debug-Modus Status
    private static bool $debug = false;
    
    // Debug-Log Speicher
    private static array $debugLog = [];
    
    // Performance Metriken
    private static array $timings = [];
    
    /**
     * Aktiviert den Debug-Modus
     */
    public static function enableDebug(): void
    {
        self::$debug = true;
        self::debug('Debug-Modus aktiviert');
    }
    
    /**
     * Deaktiviert den Debug-Modus
     */
    public static function disableDebug(): void
    {
        self::debug('Debug-Modus deaktiviert');
        self::$debug = false;
    }
    
    /**
     * Prüft ob Debug-Modus aktiv ist
     */
    public static function isDebugEnabled(): bool
    {
        return self::$debug;
    }
    
    /**
     * Fügt eine Debug-Nachricht hinzu
     */
    protected static function debug(string $message, array $data = []): void
    {
        if (!self::$debug) {
            return;
        }
        
        $helperName = basename(str_replace('\\', '/', static::class));
        
        $entry = [
            'time' => microtime(true),
            'helper' => $helperName,
            'message' => $message,
            'data' => $data,
            'memory' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ];
        
        self::$debugLog[] = $entry;
        
        // Optional: Sofort ausgeben im Development
        if (defined('CONTAO_DEV_MODE') && CONTAO_DEV_MODE) {
            error_log(sprintf(
                "[DEBUG %s] %s | Memory: %s", 
                $helperName,
                $message,
                self::formatBytes($entry['memory'])
            ));
        }
    }
    
    /**
     * Misst die Ausführungszeit einer Operation
     */
    protected static function profile(string $operation, callable $callback)
    {
        $start = microtime(true);
        $startMemory = memory_get_usage(true);
        
        try {
            $result = $callback();
            
            $duration = microtime(true) - $start;
            $memoryUsed = memory_get_usage(true) - $startMemory;
            
            self::$timings[$operation] = [
                'count' => ($self::$timings[$operation]['count'] ?? 0) + 1,
                'total_time' => ($self::$timings[$operation]['total_time'] ?? 0) + $duration,
                'last_time' => $duration,
                'avg_time' => 0, // Wird unten berechnet
                'max_time' => max($self::$timings[$operation]['max_time'] ?? 0, $duration),
                'min_time' => min($self::$timings[$operation]['min_time'] ?? PHP_FLOAT_MAX, $duration),
                'memory_used' => $memoryUsed
            ];
            
            // Durchschnitt berechnen
            self::$timings[$operation]['avg_time'] = 
                self::$timings[$operation]['total_time'] / self::$timings[$operation]['count'];
            
            self::debug("Operation '{$operation}' ausgeführt", [
                'duration_ms' => round($duration * 1000, 2),
                'memory_used' => self::formatBytes($memoryUsed)
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            self::debug("Operation '{$operation}' fehlgeschlagen", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Gibt das Debug-Log zurück
     */
    public static function getDebugLog(): array
    {
        return self::$debugLog;
    }
    
    /**
     * Gibt die Performance-Metriken zurück
     */
    public static function getProfilingData(): array
    {
        return self::$timings;
    }
    
    /**
     * Gibt einen formatierten Debug-Report aus
     */
    public static function getDebugReport(): string
    {
        $report = "=== DEBUG REPORT ===\n\n";
        
        // Performance-Metriken
        if (!empty(self::$timings)) {
            $report .= "PERFORMANCE METRICS:\n";
            foreach (self::$timings as $operation => $metrics) {
                $report .= sprintf(
                    "- %s: %dx calls, avg: %.2fms, total: %.2fms\n",
                    $operation,
                    $metrics['count'],
                    $metrics['avg_time'] * 1000,
                    $metrics['total_time'] * 1000
                );
            }
            $report .= "\n";
        }
        
        // Debug-Log (letzte 20 Einträge)
        if (!empty(self::$debugLog)) {
            $report .= "DEBUG LOG (last 20 entries):\n";
            $lastEntries = array_slice(self::$debugLog, -20);
            foreach ($lastEntries as $entry) {
                $report .= sprintf(
                    "[%.3f] %s: %s\n",
                    $entry['time'],
                    $entry['helper'],
                    $entry['message']
                );
                if (!empty($entry['data'])) {
                    $report .= "  Data: " . json_encode($entry['data'], JSON_PRETTY_PRINT) . "\n";
                }
            }
        }
        
        return $report;
    }
    
    /**
     * Löscht alle Debug-Daten
     */
    public static function clearDebugData(): void
    {
        self::$debugLog = [];
        self::$timings = [];
        self::debug('Debug-Daten gelöscht');
    }
    
    /**
     * Formatiert Bytes in lesbare Größe
     */
    private static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
} 