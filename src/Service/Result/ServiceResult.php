<?php
declare(strict_types=1);

namespace App\Service\Result;

use BackedEnum;

readonly class ServiceResult
{
    private function __construct(
        private bool $success,
        private mixed $data = null,
        private ?BackedEnum $errorType = null,
    )
    {
    }

    public static function success(mixed $data = null): self
    {
        return new self(true, $data, null);
    }

    public static function failure(BackedEnum $errorType): self
    {
        return new self(false, null, $errorType);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getErrorType(): BackedEnum
    {
        return $this->errorType;
    }
}