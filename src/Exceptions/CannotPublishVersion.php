<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Exceptions;

use LogicException;
use Simtabi\Laranail\AiCompliance\Models\PolicyVersion;

final class CannotPublishVersion extends LogicException
{
    public static function notADraft(PolicyVersion $version): self
    {
        return new self(sprintf(
            'policy version [%s] of document [%d] is %s; only drafts can be published',
            $version->version,
            $version->policy_document_id,
            $version->status->value,
        ));
    }
}
