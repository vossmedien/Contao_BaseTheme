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
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class PromptBuilder
{
    public function __construct(
        private readonly TwigEnvironment $twig,
        private readonly LoggerInterface $logger
    ) {
    } 

    /**
     * Baut den Prompt basierend auf den Modelldaten unter Verwendung eines Twig-Templates.
     */
    public function buildPrompt(CaeliContentCreatorModel $model, string $template = '@CaeliWindContaoCaeliContentCreator/prompt/default.txt.twig'): string
    {
        $this->logger->debug('Building prompt for topic', ['topic' => $model->topic]);

        $minWords = (int) $model->min_words;
        $targetWords = $minWords > 0 ? (int)($minWords * 2.0) : 0;

        $topic = (string) $model->topic;
        $emphasis = (string) $model->emphasis;
        $year = trim((string) $model->year);

        if (!empty($year)) {
            $topic .= sprintf(' (Bezug: %s)', $year);
            if (!empty($emphasis)) {
                $emphasis .= sprintf(' (im Kontext von %s)', $year);
            } else {
                $emphasis = sprintf('Fokus auf das Jahr %s', $year);
            }
        }

        $context = [
            'topic' => $topic,
            'targetAudience' => $model->targetAudience,
            'emphasis' => $emphasis,
            'min_words' => $minWords,
            'targetWords' => $targetWords,
            'include_sources' => (bool) $model->include_sources,
            'add_target_blank' => (bool) $model->add_target_blank,
            'additionalInstructions' => $model->additionalInstructions,
            'year' => $model->year,
            'temperature' => (float) $model->temperature,
            'topP' => (float) $model->topP,
        ];

        try {
            $prompt = $this->twig->render($template, $context);
            $this->logger->info('Prompt successfully built using template', ['template' => $template]);
            $this->logger->debug('Final generated prompt', ['prompt' => $prompt]);
            return $prompt;
        } catch (LoaderError | RuntimeError | SyntaxError $e) {
            $this->logger->error('Failed to build prompt from template', [
                'template' => $template,
                'exception' => $e->getMessage(),
                'context_keys' => array_keys($context)
            ]);
            throw new \RuntimeException("Failed to build prompt: " . $e->getMessage(), 0, $e);
        }
    }
}
