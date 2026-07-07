<?php
declare(strict_types=1);

namespace App\Controller\api;

use App\Helper\BearerTokenAuthHelper;
use App\Service\Theme\ThemeUploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class ApiThemeController extends AbstractController
{
    public function __construct(
        #[Autowire(param: 'API_TOKEN_BEARER_THEME')]
        private string $themeApiBearerToken,
        private BearerTokenAuthHelper $bearerTokenAuthHelper,
        private ThemeUploadService $themeUploadService,
    )
    {
    }

    #[Route('/api/v1/theme/upload', name: 'app_api_upload_theme', methods: ['POST'])]
    public function upload(Request $request): Response
    {
        $bearerToken = $this->bearerTokenAuthHelper->getBearerTokenFromRequest($request);
        if ($this->themeApiBearerToken === '' || $bearerToken !== $this->themeApiBearerToken) {
            return new JsonResponse([], Response::HTTP_FORBIDDEN);
        }

        // accepts file in every form property name
        $files = $request->files->all();
        $firstUploadedFile = reset($files);
        if (count($files) !== 1 || !$firstUploadedFile instanceof UploadedFile ) {
            return new JsonResponse(['error' => 'Bad file transmission. Check \'Content-*\' Headers'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->themeUploadService->uploadTheme($firstUploadedFile->getRealPath());
        } catch (Throwable $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!$result->isSuccess()) {
            $errorMessage = $result->getErrorType()->value;
            return new JsonResponse(['error' => $errorMessage], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([], Response::HTTP_OK);
    }
}
