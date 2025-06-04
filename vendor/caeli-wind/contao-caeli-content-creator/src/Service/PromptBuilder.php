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
        private readonly LoggerInterface $logger,
        private readonly string $projectDir
    ) {
    }

    /**
     * Baut den Prompt für die erste Phase (Rohtext-Generierung)
     */
    public function buildPrompt(CaeliContentCreatorModel $model, bool $isRawTextPhase = true): string
    {
        if ($isRawTextPhase) {
            return $this->buildRawTextPrompt($model);
        }
        
        // Für Phase 2 wird ein separater Methodenaufruf mit dem Rohtext benötigt
        throw new \InvalidArgumentException('Für Formatierungsphase bitte buildFormatPrompt() mit Rohtext verwenden.');
    }

    /**
     * Baut den Prompt für Phase 1: Rohtext-Generierung
     */
    private function buildRawTextPrompt(CaeliContentCreatorModel $model): string
    {
        $this->logger->debug('Building raw text prompt', ['topic' => $model->topic]);

        $templateData = [
            'topic' => $model->topic,
            'min_words' => (int)$model->min_words,
            'targetWords' => $this->calculateTargetWords((int)$model->min_words),
            'year' => trim((string)$model->year),
            'targetAudience' => trim((string)$model->targetAudience),
            'emphasis' => trim((string)$model->emphasis),
            'additionalInstructions' => trim((string)$model->additionalInstructions),
            'include_sources' => (bool)$model->include_sources,
        ];

        $prompt = $this->renderTemplate('step1_generate_raw.txt.twig', $templateData);
        
        $this->logger->info('Raw text prompt built', [
            'length' => strlen($prompt),
            'target_words' => $templateData['targetWords']
        ]);

        return $prompt;
    }

    /**
     * Baut den Prompt für Phase 2: HTML-Formatierung
     */
    public function buildFormatPrompt(CaeliContentCreatorModel $model, string $rawContent): string
    {
        $this->logger->debug('Building format prompt', ['raw_content_length' => strlen($rawContent)]);

        $templateData = [
            'topic' => $model->topic,
            'raw_content' => $rawContent,
            'year' => trim((string)$model->year),
            'targetAudience' => trim((string)$model->targetAudience),
            'emphasis' => trim((string)$model->emphasis),
            'additionalInstructions' => trim((string)$model->additionalInstructions),
            'include_sources' => (bool)$model->include_sources,
            'add_target_blank' => (bool)$model->add_target_blank,
        ];

        $prompt = $this->renderTemplate('step2_format_enhance.txt.twig', $templateData);
        
        $this->logger->info('Format prompt built', [
            'length' => strlen($prompt)
        ]);

        return $prompt;
    }

    /**
     * Rendert ein Template mit den gegebenen Daten
     */
    private function renderTemplate(string $templateName, array $data): string
    {
        // Template-Pfad relativ zum Bundle
        $templatePath = dirname(__DIR__, 2) . '/templates/prompt/' . $templateName;
        
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template '{$templateName}' nicht gefunden in: {$templatePath}");
        }
        
        // Template-Inhalt laden
        $templateContent = file_get_contents($templatePath);
        
        // Einfache Template-Variable-Ersetzung
        foreach ($data as $key => $value) {
            // Konvertiere alle Werte zu Strings
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_int($value) || is_float($value)) {
                $value = (string)$value;
            } elseif ($value === null) {
                $value = '';
            } elseif (is_array($value)) {
                $value = implode(', ', $value);
            } else {
                $value = (string)$value;
            }
            
            // Replace variables
            $templateContent = str_replace('{{ ' . $key . ' }}', $value, $templateContent);
        }
        
        // Handle conditional blocks
        $templateContent = $this->processConditionals($templateContent, $data);
        
        return $templateContent;
    }
    
    /**
     * Verarbeitet bedingte Blöcke in Templates
     */
    private function processConditionals(string $content, array $data): string
    {
        // Handle {% if condition %}...{% endif %} blocks
        $content = preg_replace_callback(
            '/\{\%\s*if\s+(\w+)\s*\%\}(.*?)\{\%\s*endif\s*\%\}/s',
            function ($matches) use ($data) {
                $condition = $matches[1];
                $block = $matches[2];
                
                // Check if condition is truthy
                if (isset($data[$condition]) && !empty($data[$condition])) {
                    return $block;
                }
                return '';
            },
            $content
        );
        
        return $content;
    }

    /**
     * Berechnet Zielwortzahl basierend auf Mindestworten
     */
    private function calculateTargetWords(int $minWords): int
    {
        if ($minWords <= 0) {
            return 800; // Fallback
        }
        
        // 20% Puffer über Mindestwortzahl
        return (int)($minWords * 1.2);
    }

    /**
     * Parst die API-Antwort der zweistufigen Generierung
     */
    public function parseGeneratedContent(string $apiResponse): array
    {
        $this->logger->debug('Parsing generated content', [
            'response_length' => strlen($apiResponse)
        ]);

        // Bereinige zuerst Code-Fences aus der gesamten Response
        $cleanedResponse = $this->cleanCodeFences($apiResponse);

        // Parse das Format: Title|||+++|||Teaser|||+++|||Content|||+++|||Tags
        $parts = explode('|||+++|||', $cleanedResponse);
        
        if (count($parts) < 4) {
            $this->logger->warning('Invalid response format, fallback parsing', [
                'parts_count' => count($parts)
            ]);
            
            // Fallback: Versuche JSON-Format zu parsen
            return $this->fallbackParseJson($cleanedResponse);
        }

        $result = [
            'title' => $this->cleanTitle(trim($parts[0])),
            'teaser' => trim($parts[1]),
            'content' => trim($parts[2]),
            'tags' => trim($parts[3])
        ];

        $this->logger->info('Content successfully parsed', [
            'title_length' => strlen($result['title']),
            'content_length' => strlen($result['content'])
        ]);

        return $result;
    }

    /**
     * Bereinigt Code-Fences und Markdown-Zeichen aus der Response
     */
    private function cleanCodeFences(string $content): string
    {
        // Entferne Code-Fences
        $content = preg_replace('/```[a-z]*\s*/', '', $content);
        $content = preg_replace('/\s*```/', '', $content);
        
        return trim($content);
    }

    /**
     * Bereinigt Titel von Markdown-Zeichen und Code-Fences
     */
    private function cleanTitle(string $title): string
    {
        // Entferne Code-Fences
        $title = preg_replace('/```[a-z]*\s*/', '', $title);
        $title = preg_replace('/\s*```/', '', $title);
        
        // Entferne Markdown-Formatierung
        $title = preg_replace('/\*\*(.*?)\*\*/', '$1', $title); // **bold**
        $title = preg_replace('/\*(.*?)\*/', '$1', $title);     // *italic*
        $title = preg_replace('/_(.*?)_/', '$1', $title);       // _italic_
        
        return trim($title);
    }

    /**
     * Fallback-Parser für JSON-Format oder andere Formate
     */
    private function fallbackParseJson(string $response): array
    {
        // Versuche JSON zu extrahieren
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $data = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && isset($data['title'], $data['content'])) {
                return [
                    'title' => $data['title'] ?? '',
                    'teaser' => $data['teaser'] ?? '',
                    'content' => $data['content'] ?? '',
                    'tags' => $data['tags'] ?? ''
                ];
            }
        }

        $this->logger->error('Failed to parse API response');
        throw new \RuntimeException('Konnte API-Antwort nicht parsen. Unerwartetes Format.');
    }
}
