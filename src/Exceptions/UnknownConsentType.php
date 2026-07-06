<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Exceptions;

use InvalidArgumentException;

final class UnknownConsentType extends InvalidArgumentException
{
    public static function slug(string $slug): self
    {
        return new self(sprintf(
            'consent type [%s] is neither configured (laranail.ai-compliance.consent_types) nor present in the database',
            $slug,
        ));
    }
}
