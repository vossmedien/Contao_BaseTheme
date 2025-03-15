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
namespace Vsm\VsmHelperTools\Controller;  // Änderung hier: src\Controller -> App\Controller

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Vsm\VsmHelperTools\Helper\VideoHelper;

class VideoRenderController extends AbstractController
{
    #[Route('/video/render', name: 'video_render', methods: ['POST'])]
    public function renderVideo(Request $request): Response
    {
        if (!$request->isXmlHttpRequest()) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['uuid'])) {
            return new JsonResponse(['error' => 'Missing video UUID'], Response::HTTP_BAD_REQUEST);
        }

        $html = VideoHelper::renderVideo(
            $data['uuid'],
            $data['class'] ?? '',
            $data['name'] ?? null,
            $data['description'] ?? null,
            null,
            $data['poster'] ?? null,
            $data['attributes'] ?? '',
            true
        );

        return new Response($html);
    }
}