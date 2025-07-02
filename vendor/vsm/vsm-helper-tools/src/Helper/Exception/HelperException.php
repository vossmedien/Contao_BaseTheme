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

namespace Vsm\VsmHelperTools\Helper\Exception;

/**
 * Helper Exception
 * 
 * Erweiterte Exception-Klasse für Helper mit zusätzlichem Kontext
 * für besseres Debugging und Error-Handling.
 */
class HelperException extends \RuntimeException
{
    private array $context = [];
    private string $helperClass = '';
    private string $operation = '';
    
    /**
     * Erstellt eine neue HelperException
     * 
     * @param string $message Die Fehlermeldung
     * @param array $context Zusätzlicher Kontext (z.B. Parameter, Pfade)
     * @param int $code Der Fehlercode
     * @param \Throwable|null $previous Die vorherige Exception
     */
    public function __construct(
        string $message, 
        array $context = [], 
        int $code = 0, 
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
        
        // Helper-Klasse aus dem Stack ermitteln
        $this->detectHelperClass();
    }
    
    /**
     * Factory-Methode für Datei-nicht-gefunden Fehler
     */
    public static function fileNotFound(string $path, string $operation = ''): self
    {
        $exception = new self(
            sprintf('Datei nicht gefunden: %s', $path),
            ['path' => $path, 'operation' => $operation]
        );
        $exception->operation = $operation;
        return $exception;
    }
    
    /**
     * Factory-Methode für ungültige Parameter
     */
    public static function invalidParameter(string $parameter, $value, string $expected): self
    {
        return new self(
            sprintf('Ungültiger Parameter "%s": %s erwartet, %s erhalten', 
                $parameter, 
                $expected, 
                is_object($value) ? get_class($value) : gettype($value)
            ),
            [
                'parameter' => $parameter,
                'value' => $value,
                'expected' => $expected
            ]
        );
    }
    
    /**
     * Factory-Methode für Verarbeitungsfehler
     */
    public static function processingFailed(string $operation, string $reason, array $context = []): self
    {
        $exception = new self(
            sprintf('Verarbeitung fehlgeschlagen (%s): %s', $operation, $reason),
            array_merge(['operation' => $operation, 'reason' => $reason], $context)
        );
        $exception->operation = $operation;
        return $exception;
    }
    
    /**
     * Factory-Methode für Konfigurations-Fehler
     */
    public static function configurationError(string $config, string $reason): self
    {
        return new self(
            sprintf('Konfigurations-Fehler bei "%s": %s', $config, $reason),
            ['config' => $config, 'reason' => $reason]
        );
    }
    
    /**
     * Factory-Methode für fehlende Abhängigkeiten
     */
    public static function missingDependency(string $dependency, string $requiredFor): self
    {
        return new self(
            sprintf('Fehlende Abhängigkeit "%s" benötigt für: %s', $dependency, $requiredFor),
            ['dependency' => $dependency, 'required_for' => $requiredFor]
        );
    }
    
    /**
     * Gibt den Kontext zurück
     */
    public function getContext(): array
    {
        return $this->context;
    }
    
    /**
     * Fügt zusätzlichen Kontext hinzu
     */
    public function addContext(string $key, $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }
    
    /**
     * Gibt die Helper-Klasse zurück
     */
    public function getHelperClass(): string
    {
        return $this->helperClass;
    }
    
    /**
     * Gibt die Operation zurück
     */
    public function getOperation(): string
    {
        return $this->operation;
    }
    
    /**
     * Gibt eine formatierte Fehlermeldung zurück
     */
    public function getDetailedMessage(): string
    {
        $message = $this->getMessage();
        
        if ($this->helperClass) {
            $message = sprintf('[%s] %s', basename(str_replace('\\', '/', $this->helperClass)), $message);
        }
        
        if ($this->operation) {
            $message .= sprintf(' (Operation: %s)', $this->operation);
        }
        
        if (!empty($this->context)) {
            $message .= "\nKontext: " . json_encode($this->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        
        return $message;
    }
    
    /**
     * Konvertiert die Exception zu einem Array für Logging
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'helper_class' => $this->helperClass,
            'operation' => $this->operation,
            'context' => $this->context,
            'trace' => $this->getTraceAsString()
        ];
    }
    
    /**
     * Ermittelt die Helper-Klasse aus dem Stack-Trace
     */
    private function detectHelperClass(): void
    {
        $trace = $this->getTrace();
        
        foreach ($trace as $frame) {
            if (isset($frame['class']) && strpos($frame['class'], '\\Helper\\') !== false) {
                $this->helperClass = $frame['class'];
                break;
            }
        }
    }
} 