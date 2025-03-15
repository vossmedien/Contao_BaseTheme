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

namespace Vsm\VsmHelperTools\Model;

/**
 * Repräsentiert eine Zahlungssitzung im System
 */
class PaymentSession
{
    // Status-Konstanten
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_REFUNDED = 'refunded';
    
    protected string $sessionId;
    protected string $status;
    protected array $metadata;
    
    /**
     * Erstellt eine neue PaymentSession
     */
    public function __construct(string $sessionId, string $status, array $metadata = [])
    {
        $this->sessionId = $sessionId;
        $this->status = $status;
        $this->metadata = $metadata;
    }
    
    /**
     * Gibt die Session-ID zurück
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }
    
    /**
     * Gibt den Status zurück
     */
    public function getStatus(): string
    {
        return $this->status;
    }
    
    /**
     * Gibt die Metadaten zurück
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
    
    /**
     * Setzt den Status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
    
    /**
     * Fügt Metadaten hinzu oder aktualisiert sie
     */
    public function updateMetadata(array $metadata): void
    {
        $this->metadata = array_merge($this->metadata, $metadata);
    }
    
    /**
     * Prüft, ob die Session abgeschlossen ist
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
    
    /**
     * Prüft, ob die Session fehlgeschlagen ist
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
    
    /**
     * Prüft, ob die Session abgebrochen wurde
     */
    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }
    
    /**
     * Prüft, ob die Session zurückerstattet wurde
     */
    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }
    
    /**
     * Prüft, ob die Session noch ausstehend ist
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
} 