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
use Psr\Log\LoggerInterface;
use Twig\Environment as TwigEnvironment;

class PromptBuilder
{
    public function __construct(
        private readonly TwigEnvironment $twig,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Baut den Prompt basierend auf den Modelldaten.
     */
    public function buildPrompt(CaeliContentCreatorModel $model): string
    {
        $this->logger->debug('Building prompt for topic', ['topic' => $model->topic]);

        $minWords = (int) $model->min_words;
        $topic = (string) $model->topic;
        $emphasis = (string) $model->emphasis;
        $year = trim((string) $model->year);
        $additionalInstructions = trim((string) $model->additionalInstructions);
        $targetAudience = trim((string) $model->targetAudience);
        $includeSources = (bool) $model->include_sources;
        $addTargetBlank = (bool) $model->add_target_blank;

        if (!empty($year)) {
            $topic .= sprintf(' (Bezug: %s)', $year);
            if (!empty($emphasis)) {
                $emphasis .= sprintf(' (im Kontext von %s)', $year);
            } else {
                $emphasis = sprintf('Fokus auf das Jahr %s', $year);
            }
        }

        $promptLines = [
            "Erstelle einen umfassenden, gut strukturierten und ansprechenden Blogbeitrag.",
            "Thema: {$topic}",
        ];

        if (!empty($targetAudience)) {
            $promptLines[] = "Zielgruppe: {$targetAudience}";
        }
        if (!empty($emphasis)) {
            $promptLines[] = "Schwerpunkt/Betonung: {$emphasis}";
        }
        if ($minWords > 0) {
            // Fordere eine höhere Wortzahl an, um die Mindestgrenze sicherzustellen
            $targetWords = (int)($minWords * 1.5);
            $promptLines[] = "Der Beitrag sollte mindestens {$minWords} Wörter umfassen, idealerweise etwa {$targetWords} Wörter.";
        }

        // Füge die detaillierten Anweisungen hinzu
        if (!empty($additionalInstructions)) {
            $promptLines[] = "\n!!! WICHTIGE ZUSÄTZLICHE ANWEISUNGEN (BITTE GENAU BEFOLGEN):\n{$additionalInstructions}";
        }

        $promptLines[] = "\nStruktur und Inhalt:";
        $promptLines[] = "- **WICHTIG:** Beginne die Ausgabe mit einem kurzen, prägnanten Teaser (1-2 Sätze), eingeschlossen in `<div class=\"generated-teaser\">...</div>`.";
        $promptLines[] = "- Direkt nach dem Teaser-Div, beginne den Hauptinhalt mit einer thematischen H2-Überschrift. **KEINE H1 verwenden!** Die Hierarchie sollte H2 -> H3 usw. sein.";
        $promptLines[] = "- Schreibe kurze, leserfreundliche Absätze (3-4 Sätze).";
        $promptLines[] = "- Schließe den Hauptinhalt mit einem positiven Ausblick.";
        $promptLines[] = "- Integriere relevante Keywords natürlich.";
        $promptLines[] = "- Verwende Bootstrap-Elemente wie 'alert alert-info', 'card' mit 'card-body', 'card-title', 'display-4', 'card-text' und verschiedene Buttons wie 'btn btn-success, primary, secondary etc.' sinnvoll und mit relevantem Inhalt.";

        if ($includeSources) {
            $targetAttribute = $addTargetBlank ? ' target="_blank"' : '';
            $promptLines[] = "- **OBLIGATORISCH:** Wenn externe Studien, Statistiken oder spezifische Quellen (z.B. IEA, Fraunhofer) erwähnt werden, **MUSS** ein Link zur Quelle hinzugefügt werden. Verwende EXAKT dieses Format: `<a href='URL'{$targetAttribute}>[Quellenbezeichnung]</a>`. Stelle sicher, dass die Links **aktuell (z.B. 2024/2025)** und **gültig** sind. Veraltete oder fehlerhafte Links sind unerwünscht. Nutze dein aktuelles Wissen.";
        } else {
            $promptLines[] = "- Es ist nicht notwendig, explizite Links zu Quellen einzufügen.";
        }

        $promptLines[] = "\nSEO-Daten:";
        $promptLines[] = "- Generiere einen prägnanten, SEO-optimierten Seitentitel (pageTitle, max. 60 Zeichen).";
        $promptLines[] = "- Generiere eine informative Meta-Beschreibung (description, ca. 155 Zeichen), die zum Klicken anregt.";

        $promptLines[] = "\nFormat der GESAMTEN Antwort:";
        $promptLines[] = "- Gib die Antwort NUR im folgenden Format zurück, ohne zusätzlichen Text davor oder danach:";
        $promptLines[] = "[CONTENT_START]";
        $promptLines[] = "<div class=\"generated-teaser\">... Teaser hier ...</div>";
        $promptLines[] = "<h2>... Hauptinhalt HTML hier ...</h2>";
        $promptLines[] = "[CONTENT_END]";
        $promptLines[] = "[PAGETITLE_START]";
        $promptLines[] = "... Generierter Seitentitel hier ...";
        $promptLines[] = "[PAGETITLE_END]";
        $promptLines[] = "[DESCRIPTION_START]";
        $promptLines[] = "... Generierte Meta-Beschreibung hier ...";
        $promptLines[] = "[DESCRIPTION_END]";
        $promptLines[] = "[TAGS_START]";
        $promptLines[] = "... Komma-separierte Tags hier ...";
        $promptLines[] = "[TAGS_END]";
        $promptLines[] = "- Füge KEIN separates <div class=\"preview-tags\"> mehr in den [CONTENT_START]...[CONTENT_END] Block ein.";

        $prompt = implode("\n", $promptLines);

        $this->logger->info('Prompt successfully built');
        $this->logger->debug('Final generated prompt', ['prompt_length' => strlen($prompt)]); // Log length instead of full prompt for brevity

        return $prompt;
    }
}
