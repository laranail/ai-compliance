<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Simtabi\Laranail\AiCompliance\Activity\ActivityRecorder;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Models\Provider;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The provider registry (spec 11.1): every ai vendor/model in use, its
 * contract position, and its due-diligence state. Deletes are soft so the
 * activity log keeps its references.
 */
final readonly class ProviderController
{
    public function __construct(
        private ActivityRecorder $activity,
    ) {}

    public function index(): JsonResponse
    {
        $providers = Provider::query()->orderBy('name')->get()
            ->map(fn (Provider $provider): array => $this->present($provider));

        return new JsonResponse(['data' => $providers]);
    }

    public function store(Request $request): JsonResponse
    {
        $provider = Provider::query()->create($this->validated($request));

        $this->activity->record(ActivityType::ProviderChange, context: [
            'action' => 'created',
            'provider' => $provider->name,
        ]);

        return new JsonResponse(['data' => $this->present($provider)], 201);
    }

    public function update(Request $request, int $provider): JsonResponse
    {
        $model = $this->providerOrFail($provider);

        $model->update($this->validated($request));

        $this->activity->record(ActivityType::ProviderChange, context: [
            'action' => 'updated',
            'provider' => $model->name,
        ]);

        return new JsonResponse(['data' => $this->present($model)]);
    }

    public function destroy(int $provider): JsonResponse
    {
        $model = $this->providerOrFail($provider);

        $model->delete();

        $this->activity->record(ActivityType::ProviderChange, context: [
            'action' => 'deactivated',
            'provider' => $model->name,
        ]);

        return new JsonResponse(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'vendor' => ['required', 'string', 'max:255'],
            'model_name' => ['required', 'string', 'max:255'],
            'model_version' => ['nullable', 'string', 'max:255'],
            'endpoint_region' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::in(['provider', 'deployer'])],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'dpa_signed_at' => ['nullable', 'date'],
            'trains_on_our_data' => ['required', 'string', Rule::in(['yes', 'no', 'configurable'])],
            'training_summary_url' => ['nullable', 'url', 'max:2000'],
            'sub_processors_url' => ['nullable', 'url', 'max:2000'],
            'marking_supported' => ['boolean'],
            'due_diligence_status' => ['sometimes', 'string', Rule::in(['pending', 'complete', 'lapsed'])],
            'owner' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Provider $provider): array
    {
        return [
            'id' => $provider->id,
            'name' => $provider->name,
            'vendor' => $provider->vendor,
            'model_name' => $provider->model_name,
            'model_version' => $provider->model_version,
            'endpoint_region' => $provider->endpoint_region,
            'role' => $provider->role,
            'purpose' => $provider->purpose,
            'dpa_signed_at' => $provider->dpa_signed_at?->toIso8601String(),
            'trains_on_our_data' => $provider->trains_on_our_data,
            'training_summary_url' => $provider->training_summary_url,
            'sub_processors_url' => $provider->sub_processors_url,
            'marking_supported' => $provider->marking_supported,
            'due_diligence_status' => $provider->due_diligence_status,
            'owner' => $provider->owner,
            'complete' => $provider->isComplete(),
        ];
    }

    private function providerOrFail(int $id): Provider
    {
        $provider = Provider::query()->find($id);

        if (! $provider instanceof Provider) {
            throw new NotFoundHttpException(sprintf('provider [%d] not found', $id));
        }

        return $provider;
    }
}
