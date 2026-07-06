<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Console\Commands;

use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Models\PolicyVersion;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicyPublisher;
use Simtabi\Laranail\Package\Tools\Commands\Command;

/**
 * Publishes a document's open draft, superseding the currently published
 * version in the same transaction.
 */
final class PolicyPublishCommand extends Command
{
    protected $signature = 'laranail::ai-compliance.policy.publish
                            {slug : the document slug, e.g. consent.ai_training}';

    protected $description = 'Publish a policy document\'s open draft';

    public function handle(PolicyPublisher $publisher): int
    {
        $slug = $this->argument('slug');

        if (! is_string($slug) || $slug === '') {
            $this->error('a document slug is required');

            return self::FAILURE;
        }

        $document = PolicyDocument::query()
            ->forDefaultTenant()
            ->where('slug', $slug)
            ->first();

        if (! $document instanceof PolicyDocument) {
            $this->error(sprintf('policy document [%s] not found', $slug));

            return self::FAILURE;
        }

        $draft = $document->draftVersion()->first();

        if (! $draft instanceof PolicyVersion) {
            $this->error(sprintf('policy document [%s] has no open draft to publish', $slug));

            return self::FAILURE;
        }

        $published = $publisher->publish($draft);

        $this->components->info(sprintf(
            'published %s %s; subjects consented under superseded versions of this document now need re-consent where it is a consent text',
            $slug,
            $published->version,
        ));

        return self::SUCCESS;
    }
}
