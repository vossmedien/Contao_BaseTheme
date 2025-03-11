<?php

declare(strict_types=1);

/*
 * This file is part of Caeli KI Content-Creator.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/contao-caeli-content-creator
 */

namespace CaeliWind\ContaoCaeliContentCreator\Service;

use CaeliWind\ContaoCaeliContentCreator\Model\CaeliContentCreatorModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;

class PromptBuilder
{
    private ContaoFramework $framework;
    
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }
    
    /**
     * Baut den Prompt basierend auf den Modelldaten auf
     */
    public function buildPrompt(CaeliContentCreatorModel $model, bool $isDebug = false): string
    {
        // Prompt vorbereiten
        $prompt = "Erstelle einen Blog-Artikel zum Thema: " . $model->topic . ".\n\n";
        
        // Debug-Ausgabe für Entwicklung
        if ($isDebug) {
            $logDir = $this->framework->getAdapter(System::class)->getContainer()->getParameter('kernel.logs_dir');
            file_put_contents($logDir . '/prompt-builder.log', "Erstelle Prompt für Thema: {$model->topic}\n", FILE_APPEND);
        }
        
        // Grundlegende Formatierungsanweisungen hinzufügen
        $prompt .= "=============================================\n";
        $prompt .= "TECHNISCHE FORMATIERUNGSREGELN (STRENG EINZUHALTEN):\n";
        $prompt .= "=============================================\n";
        $prompt .= "- VERWENDE NIEMALS H1-Überschriften! Beginne mit H2 und verwende H3 für Unterabschnitte.\n";
        $prompt .= "- NIEMALS Überschriften wie 'Einleitung', 'Fazit', etc. verwenden! Stattdessen thematische Überschriften.\n";
        $prompt .= "- BUTTONS: Füge MINDESTENS DREI Call-To-Action Buttons hinzu, die im Text VERTEILT sind (genau in dieser Form):\n";
        $prompt .= "  <a href=\"/kontakt\" class=\"btn btn-primary\">Kontakt aufnehmen</a>\n";
        $prompt .= "  <a href=\"/grundeigentuemer#pachtrechner\" class=\"btn btn-success\">Jetzt Pachteinnahmen berechnen</a>\n";
        $prompt .= "  <a href=\"/grundstueck\" class=\"btn btn-info\">Flächencheck starten</a>\n";
        $prompt .= "- VERBOTEN: Verwende KEINE Bootstrap-Abstandsklassen (mt-, mb-, my-, mx-, py-, px-, etc.)!\n";
        $prompt .= "- BOOTSTRAP-ELEMENTE: Füge mindestens je ein Card, Alert und Table-Element hinzu.\n";
        $prompt .= "- QUELLEN: Verlinke komplette URLs, nicht nur das Wort 'Link'.\n";
        $prompt .= "=============================================\n\n";
        
        // Zielgruppe und Betonung
        if (!empty($model->targetAudience)) {
            $prompt .= "Zielgruppe: " . $model->targetAudience . "\n";
        }
        
        if (!empty($model->emphasis)) {
            $prompt .= "Besondere Betonung auf: " . $model->emphasis . "\n";
        }
        
        // Mindestwortzahl explizit hervorheben
        if (!empty($model->min_words)) {
            // Setze Zielwortzahl 50% über der Mindestwortzahl
            $targetWords = (int)$model->min_words * 1.5;
            $prompt .= "=============================================\n";
            $prompt .= "WICHTIG ZUR LÄNGE - STRENG EINZUHALTEN:\n";
            $prompt .= "=============================================\n";
            $prompt .= "- Der Artikel MUSS MINDESTENS " . $model->min_words . " Wörter umfassen. Dies ist eine VERPFLICHTENDE Anforderung.\n";
            $prompt .= "- Ziele auf " . $targetWords . " Wörter oder mehr.\n";
            $prompt .= "=============================================\n\n";
        } else {
            // Standardmäßig mindestens 1000 Wörter
            $prompt .= "=============================================\n";
            $prompt .= "WICHTIG ZUR LÄNGE - STRENG EINZUHALTEN:\n";
            $prompt .= "=============================================\n";
            $prompt .= "- Der Artikel MUSS MINDESTENS 1000 Wörter umfassen. Dies ist eine VERPFLICHTENDE Anforderung.\n";
            $prompt .= "- Ziele auf 1500 Wörter oder mehr.\n";
            $prompt .= "=============================================\n\n";
        }
        
        // Quellenangaben und Links
        if ($model->include_sources) {
            $prompt .= "Füge am Ende des Artikels relevante Quellenangaben hinzu.\n";
        }
        
        if ($model->add_target_blank) {
            $prompt .= "Alle externen Links sollen mit target=\"_blank\" versehen werden, damit sie in einem neuen Tab geöffnet werden.\n";
        }
        
        // WICHTIG: Zusätzliche Anweisungen aus Backend einfügen
        if (!empty($model->additionalInstructions)) {
            $prompt .= "\n=============================================\n";
            $prompt .= "ZUSÄTZLICHE INHALTLICHE ANWEISUNGEN:\n";
            $prompt .= "=============================================\n";
            $prompt .= $model->additionalInstructions . "\n";
            $prompt .= "=============================================\n\n";
        }
        
        // Detaillierte technische Formatierungsanweisungen hinzufügen
        $prompt .= "\nWICHTIGE DETAILLIERTE FORMATIERUNGSANWEISUNGEN:\n";
        
        // HTML-Formatierung
        $prompt .= "- Verwende h2 und h3 für Überschriften und Zwischenüberschriften (KEINE h1!).\n";
        $prompt .= "- Nutze p-Tags für Absätze, strong für Hervorhebungen, ul und li für Listen.\n";
        $prompt .= "- Strukturbezeichnungen wie 'Einleitung:', 'Fazit:' etc. NICHT in Überschriften verwenden.\n";
        
        // Bootstrap-Elemente im Detail
        $prompt .= "\nBOOTSTRAP-ELEMENTE (MINDESTENS JE EINS VON JEDEM):\n";
        
        // Cards richtig formatieren
        $prompt .= "- Card für wichtige Informationen (ohne mb-Klassen):\n";
        $prompt .= "  <div class=\"card\"><div class=\"card-body\"><h5 class=\"card-title\">Wichtige Information</h5><p class=\"card-text\">Inhalt...</p></div></div>\n";
        
        // Alerts richtig formatieren
        $prompt .= "- Alert für Hinweise (ohne mb-Klassen):\n";
        $prompt .= "  <div class=\"alert alert-info\">Wichtiger Hinweis...</div>\n";
        
        // Tabellen richtig formatieren
        $prompt .= "- Tabelle für Datenvergleiche (ohne mb-Klassen):\n";
        $prompt .= "  <table class=\"table table-striped\"><thead><tr><th>Kategorie</th><th>Wert</th></tr></thead><tbody><tr><td>Beispiel</td><td>Daten</td></tr></tbody></table>\n";
        
        $prompt .= "\nFür das JSON-Format verwende folgendes Schema:\n";
        $prompt .= "\nBitte generiere im folgenden JSON-Format:
        {
            \"title\": \"Titel des Artikels\",
            \"teaser\": \"Kurze Zusammenfassung des Inhalts (2-3 Sätze)\",
            \"content\": \"Der vollständige Artikel mit HTML-Formatierung\",
            \"tags\": \"Kommagetrennte Liste von Tags für den Artikel\"
        }";
        
        // Hinweise zu zusätzlichen Formatierungsmöglichkeiten
        $prompt .= "\nSTRUKTURELLE VORGABEN FÜR MEHR UMFANG:\n";
        $prompt .= "- Unterteile den Hauptteil in mindestens 5-7 verschiedene Abschnitte mit eigenen H2-Überschriften\n";
        $prompt .= "- Füge unter jeder H2-Überschrift mindestens 2-3 Unterabschnitte mit H3-Überschriften ein\n";
        $prompt .= "- Jeder Unterabschnitt sollte 3-5 Absätze umfassen\n";
        $prompt .= "- Integriere mindestens 3 Listen (mit jeweils mindestens 5 Punkten)\n";
        $prompt .= "- Füge nach jedem Hauptabschnitt eine zusammenfassende Schlussfolgerung ein\n";
        $prompt .= "- Ergänze einen ausführlichen FAQ-Bereich am Ende mit mindestens 5 Fragen und Antworten\n";
        $prompt .= "- Schließe mit einer umfassenden Zusammenfassung und einem Ausblick ab\n\n";
        
        $prompt .= "Fülle jede dieser strukturellen Vorgaben mit relevanten, gehaltvollem Inhalt. Die Struktur dient dazu, die erforderliche Mindestwortzahl zu erreichen und einen umfassenden Artikel zu erstellen.\n\n";
        
        return $prompt;
    }
} 