<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy\Versioning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Auth\Authenticatable;
use Simtabi\Laranail\AiCompliance\Models\PolicyVersion;
use Simtabi\Laranail\AiCompliance\Events\PolicyPublished;
use Simtabi\Laranail\AiCompliance\Enums\PolicyVersionStatus;
use Simtabi\Laranail\AiCompliance\Exceptions\CannotPublishVersion;

/**
 * Publishes a draft version. The invariant this class owns: at most one
 * published version per document, ever — superseding the current published
 * version and promoting the draft happen in one transaction.
 */
final readonly class PolicyPublisher
{
    public function __construct(
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    /**
     * The authorship morph is only recordable for eloquent-backed users;
     * other authenticatables publish anonymously.
     */
    public function publish(PolicyVersion $version, Model|Authenticatable|null $publisher = null): PolicyVersion
    {
        $publisher = $publisher instanceof Model ? $publisher : null;

        return $this->db->connection()->transaction(function () use ($version, $publisher): PolicyVersion {
            $version->refresh();

            if (! $version->isDraft()) {
                throw CannotPublishVersion::notADraft($version);
            }

            /** @var PolicyVersion|null $current */
            $current = PolicyVersion::query()
                ->where('policy_document_id', $version->policy_document_id)
                ->where('status', PolicyVersionStatus::Published->value)
                ->lockForUpdate()
                ->first();

            $current?->update([
                'status'        => PolicyVersionStatus::Superseded,
                'superseded_at' => now(),
            ]);

            $version->update(array_filter([
                'status'          => PolicyVersionStatus::Published,
                'published_at'    => now(),
                'effective_at'    => $version->effective_at ?? now(),
                'authorable_type' => $publisher?->getMorphClass(),
                'authorable_id'   => $publisher?->getKey(),
            ], static fn (mixed $value): bool => $value !== null));

            $this->events->dispatch(new PolicyPublished($version, $current));

            return $version;
        });
    }
}
