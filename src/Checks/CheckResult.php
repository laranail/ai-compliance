<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Checks;

use Simtabi\Laranail\AiCompliance\Enums\CheckStatus;

final readonly class CheckResult
{
    public function __construct(
        public CheckStatus $status,
        public string $message,
    ) {}

    public static function ok(string $message): self
    {
        return new self(CheckStatus::Ok, $message);
    }

    public static function review(string $message): self
    {
        return new self(CheckStatus::Review, $message);
    }

    public static function fail(string $message): self
    {
        return new self(CheckStatus::Fail, $message);
    }
}
