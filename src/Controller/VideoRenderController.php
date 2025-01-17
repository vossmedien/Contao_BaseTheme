<?php
declare(strict_types=1);

namespace App\Controller;  // Änderung hier: src\Controller -> App\Controller

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use VSM_HelperFunctions\VideoHelper;

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