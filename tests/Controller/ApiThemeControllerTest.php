<?php
declare(strict_types=1);

namespace Controller;

use App\Controller\api\ApiThemeController;
use App\Helper\BearerTokenAuthHelper;
use App\Service\Result\Error\ThemeUploadError;
use App\Service\Result\ServiceResult;
use App\Service\Theme\ThemeUploadService;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiThemeControllerTest extends TestCase
{
    private string $validToken = 'valid-bearer-token';
    private string $invalidToken = 'invalid-bearer-token';

    public function testUploadWithValidTokenAndValidFile(): void
    {
        $mockBearerTokenAuthHelper = $this->createMock(BearerTokenAuthHelper::class);
        $mockBearerTokenAuthHelper->expects($this->once())
            ->method('getBearerTokenFromRequest')
            ->willReturn($this->validToken);

        $mockThemeUploadService = $this->createMock(ThemeUploadService::class);
        $mockThemeUploadService->expects($this->once())
            ->method('uploadTheme')
            ->willReturn(ServiceResult::success());

        $mockUploadedFile = $this->createStub(UploadedFile::class);
        $mockUploadedFile->method('getRealPath')->willReturn('/tmp/test-file.zip');

        $controller = new ApiThemeController(
            $this->validToken,
            $mockBearerTokenAuthHelper,
            $mockThemeUploadService
        );
        $request = new Request();
        $request->files->set('theme', $mockUploadedFile);

        $response = $controller->upload($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testUploadWithInvalidToken(): void
    {
        $mockBearerTokenAuthHelper = $this->createMock(BearerTokenAuthHelper::class);
        $mockBearerTokenAuthHelper->expects($this->once())
            ->method('getBearerTokenFromRequest')
            ->willReturn($this->invalidToken);

        $mockThemeUploadService = $this->createStub(ThemeUploadService::class);

        $controller = new ApiThemeController(
            $this->validToken,
            $mockBearerTokenAuthHelper,
            $mockThemeUploadService
        );
        $request = new Request();

        $response = $controller->upload($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testUploadWithNoToken(): void
    {
        $mockBearerTokenAuthHelper = $this->createMock(BearerTokenAuthHelper::class);
        $mockBearerTokenAuthHelper->expects($this->once())
            ->method('getBearerTokenFromRequest')
            ->willReturn(null);

        $mockThemeUploadService = $this->createStub(ThemeUploadService::class);

        $controller = new ApiThemeController(
            $this->validToken,
            $mockBearerTokenAuthHelper,
            $mockThemeUploadService
        );
        $request = new Request();

        $response = $controller->upload($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testUploadWithNoFiles(): void
    {
        $mockBearerTokenAuthHelper = $this->createMock(BearerTokenAuthHelper::class);
        $mockBearerTokenAuthHelper->expects($this->once())
            ->method('getBearerTokenFromRequest')
            ->willReturn($this->validToken);

        $mockThemeUploadService = $this->createStub(ThemeUploadService::class);

        $controller = new ApiThemeController(
            $this->validToken,
            $mockBearerTokenAuthHelper,
            $mockThemeUploadService
        );
        $request = new Request();

        $response = $controller->upload($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testUploadWithMultipleFiles(): void
    {
        $mockBearerTokenAuthHelper = $this->createMock(BearerTokenAuthHelper::class);
        $mockBearerTokenAuthHelper->expects($this->once())
            ->method('getBearerTokenFromRequest')
            ->willReturn($this->validToken);

        $mockThemeUploadService = $this->createStub(ThemeUploadService::class);

        $controller = new ApiThemeController(
            $this->validToken,
            $mockBearerTokenAuthHelper,
            $mockThemeUploadService
        );

        $uploadedFile1 = $this->createMock(UploadedFile::class);
        $uploadedFile2 = $this->createMock(UploadedFile::class);

        $request = new Request();
        $request->files->set('theme1', $uploadedFile1);
        $request->files->set('theme2', $uploadedFile2);

        $response = $controller->upload($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testUploadWithThemeUploadServiceFailure(): void
    {
        $mockBearerTokenAuthHelper = $this->createMock(BearerTokenAuthHelper::class);
        $mockBearerTokenAuthHelper->expects($this->once())
            ->method('getBearerTokenFromRequest')
            ->willReturn($this->validToken);

        $mockThemeUploadService = $this->createMock(ThemeUploadService::class);
        $mockThemeUploadService->expects($this->once())
            ->method('uploadTheme')
            ->willReturn(ServiceResult::failure(ThemeUploadError::INVALID_ZIP));

        $controller = new ApiThemeController(
            $this->validToken,
            $mockBearerTokenAuthHelper,
            $mockThemeUploadService
        );

        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->expects($this->once())
            ->method('getRealPath')
            ->willReturn('/tmp/test-file.zip');

        $request = new Request();
        $request->files->set('theme', $uploadedFile);

        $response = $controller->upload($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testUploadWithThemeUploadServiceException(): void
    {
        $mockBearerTokenAuthHelper = $this->createMock(BearerTokenAuthHelper::class);
        $mockBearerTokenAuthHelper->expects($this->once())
            ->method('getBearerTokenFromRequest')
            ->willReturn($this->validToken);

        $mockThemeUploadService = $this->createMock(ThemeUploadService::class);
        $mockThemeUploadService->expects($this->once())
            ->method('uploadTheme')
            ->willThrowException(new Exception());

        $controller = new ApiThemeController(
            $this->validToken,
            $mockBearerTokenAuthHelper,
            $mockThemeUploadService
        );

        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->expects($this->once())
            ->method('getRealPath')
            ->willReturn('/tmp/test-file.zip');

        $request = new Request();
        $request->files->set('theme', $uploadedFile);

        $response = $controller->upload($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }
}
