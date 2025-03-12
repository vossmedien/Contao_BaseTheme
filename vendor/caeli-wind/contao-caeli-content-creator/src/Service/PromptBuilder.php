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
        $prompt .= "- BUTTONS: Füge nur dann Call-To-Action Buttons hinzu, wenn sie THEMATISCH ZUM INHALT PASSEN! Maximal 3 Buttons, UNGLEICHMÄSSIG im Text VERTEILEN. Verwende NUR Buttons, die zum Kontext des Artikels passen:\n";
        $prompt .= "  <a href=\"/kontakt\" class=\"btn btn-primary\">Kontakt aufnehmen</a> - nur wenn im Artikel Beratung oder persönlicher Austausch sinnvoll ist\n";
        $prompt .= "  <a href=\"/grundeigentuemer#pachtrechner\" class=\"btn btn-success\">Jetzt Pachteinnahmen berechnen</a> - nur bei Themen zu Grundstücken, Pachtverträgen oder Einnahmen durch Windenergie\n";
        $prompt .= "  <a href=\"/grundstueck\" class=\"btn btn-info\">Flächencheck starten</a> - nur bei Themen zur Flächeneignung oder Standortsuche\n";
        $prompt .= "  WICHTIG: Setze Buttons nur ein, wenn sie INHALTLICH PASSEN! Es müssen NICHT alle oder überhaupt Buttons verwendet werden.\n";
        $prompt .= "- VERBOTEN: Verwende KEINE Bootstrap-Abstandsklassen (mt-, mb-, my-, mx-, py-, px-, etc.)!\n";
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
        
        // Anweisungen für mehr Variation und inhaltliche Tiefe
        $prompt .= "\n=============================================\n";
        $prompt .= "ANWEISUNGEN FÜR INHALTLICHE TIEFE UND VARIATION:\n";
        $prompt .= "=============================================\n";
        $prompt .= "- ABSATZLÄNGE: Schreibe SUBSTANTIELLE Absätze mit 5-8 Sätzen. Vermeide zu kurze, oberflächliche Absätze. \n";
        $prompt .= "- ARGUMENTATION: Entwickle Gedanken vollständig mit Begründungen, Beispielen und Daten.\n";
        $prompt .= "- INHALTSDICHTE: Jeder Abschnitt sollte konkrete Fakten, Zahlen oder Beispiele enthalten.\n";
        $prompt .= "- VARIIERE DIE STRUKTUR: Der Aufbau soll NICHT standardisiert sein. Vermeide das Muster 'Einleitung-Hauptteil-Schluss'.\n";
        $prompt .= "=============================================\n\n";
        
        // Anweisungen für Bootstrap-Elemente mit mehr Variation
        $prompt .= "\n=============================================\n";
        $prompt .= "BOOTSTRAP-ELEMENTE MIT VARIATION (WÄHLE MINDESTENS ZWEI):\n";
        $prompt .= "=============================================\n";
        $prompt .= "Wähle aus den folgenden Optionen, aber VERWENDE NICHT ALLE, sondern NUR DIE, DIE INHALTLICH SINNVOLL sind:\n\n";
        
        // Cards mit Variationen
        $prompt .= "OPTION 1 - CARDS (VARIATIONEN):\n";
        $prompt .= "- Einfache Info-Card: <div class=\"card\"><div class=\"card-body\"><h5 class=\"card-title\">Titel</h5><p class=\"card-text\">Inhalt...</p></div></div>\n";
        $prompt .= "- Feature-Card mit Icon: <div class=\"card\"><div class=\"card-body\"><i class=\"fas fa-wind\"></i><h5 class=\"card-title\">Titel</h5><p class=\"card-text\">Inhalt...</p></div></div>\n";
        $prompt .= "- Statistik-Card: <div class=\"card\"><div class=\"card-body\"><h5 class=\"card-title\">Titel</h5><p class=\"display-4\">Zahl</p><p class=\"card-text\">Erklärung...</p></div></div>\n\n";
        
        // Alerts mit Variationen
        $prompt .= "OPTION 2 - ALERTS (VARIATIONEN):\n";
        $prompt .= "- Info-Alert: <div class=\"alert alert-info\">Information...</div>\n";
        $prompt .= "- Success-Alert: <div class=\"alert alert-success\">Erfolg/Tipp...</div>\n";
        $prompt .= "- Warning-Alert: <div class=\"alert alert-warning\">Wichtiger Hinweis...</div>\n\n";
        
        // Tabellen mit Variationen
        $prompt .= "OPTION 3 - TABELLEN (VARIATIONEN):\n";
        $prompt .= "- Vergleichstabelle: <table class=\"table\"><thead><tr><th>Option A</th><th>Option B</th></tr></thead><tbody>...</tbody></table>\n";
        $prompt .= "- Datentabelle: <table class=\"table table-striped\"><thead><tr><th>Kategorie</th><th>Wert</th><th>Veränderung</th></tr></thead><tbody>...</tbody></table>\n";
        $prompt .= "- Timeline-Tabelle: <table class=\"table\"><thead><tr><th>Jahr</th><th>Entwicklung</th></tr></thead><tbody>...</tbody></table>\n\n";
        
        // Weitere Optionen
        $prompt .= "OPTION 4 - WEITERE ELEMENTE (WENN PASSEND):\n";
        $prompt .= "- Zitatblock: <blockquote class=\"blockquote\"><p>Zitat...</p><footer class=\"blockquote-footer\">Quelle</footer></blockquote>\n";
        $prompt .= "- Fortschrittsbalken: <div class=\"progress\"><div class=\"progress-bar\" style=\"width: 75%\">75%</div></div>\n";
        $prompt .= "- Akkordeon (für FAQs): <div class=\"accordion\">...</div>\n";
        $prompt .= "=============================================\n\n";
        
        // Strukturelle Variationen
        $prompt .= "STRUKTURELLE VARIATION (NICHT ALLES VERWENDEN):\n";
        $prompt .= "- Der Artikel kann mit einer Frage, einem Zitat, einer überraschenden Statistik ODER einer konkreten Situation beginnen\n";
        $prompt .= "- Verwende UNTERSCHIEDLICHE strukturelle Elemente: Argumentationsketten, Fallbeispiele, Pro/Contra-Analysen, Checklisten\n";
        $prompt .= "- Schreibe Zwischenüberschriften, die spezifisch und interessant sind (keine generischen Bezeichnungen)\n";
        $prompt .= "- Verwende ungewöhnliche, aber sinnvolle Strukturen wie: historische Entwicklung, geographische Perspektiven, oder Stakeholder-Analysen\n";
        $prompt .= "- Der Artikel kann mit einem Aufruf zum Handeln, einer Zukunftsperspektive ODER einer Reflexionsfrage enden\n\n";
        
        $prompt .= "\nFür das JSON-Format verwende folgendes Schema:\n";
        $prompt .= "\nBitte generiere im folgenden JSON-Format:
        {
            \"title\": \"Titel des Artikels\",
            \"teaser\": \"Kurze Zusammenfassung des Inhalts (2-3 Sätze)\",
            \"content\": \"Der vollständige Artikel mit HTML-Formatierung\",
            \"tags\": \"Kommagetrennte Liste von Tags für den Artikel\"
        }";
        
        return $prompt;
    }
} 