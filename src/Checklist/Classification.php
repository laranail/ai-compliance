<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Checklist;

use Simtabi\Laranail\AiCompliance\Enums\CheckStatus;
use Simtabi\Laranail\AiCompliance\Models\ChecklistItem;
use Simtabi\Laranail\AiCompliance\Models\ClassificationAnswer;

/**
 * The project intake (spec section 2): answers are stored as evidence and
 * switch checklist sections on or off. A flat applies_when map requires
 * every rule: the item is switched off (na) when any rule has an answer
 * that mismatches. An ['any_of' => [rule-set, ...]] map keeps the item
 * applicable while at least one alternative holds and switches it off only
 * when every alternative is answered and defeated. Unanswered questions
 * leave the item applicable — the conservative default.
 */
final class Classification
{
    /**
     * @param  array<string, string>  $answers
     */
    public function record(array $answers, string $answeredBy): void
    {
        foreach ($answers as $questionKey => $answer) {
            $existing = ClassificationAnswer::query()
                ->forDefaultTenant()
                ->where('question_key', $questionKey)
                ->first();

            $attributes = [
                'answer' => $answer,
                'answered_by' => $answeredBy,
                'answered_at' => now(),
            ];

            if ($existing instanceof ClassificationAnswer) {
                $existing->update($attributes);
            } else {
                ClassificationAnswer::query()->create([
                    'tenant_id' => '',
                    'question_key' => $questionKey,
                    ...$attributes,
                ]);
            }
        }

        $this->recompute();
    }

    /**
     * @return array<string, string>
     */
    public function answers(): array
    {
        $answers = [];

        foreach (ClassificationAnswer::query()->forDefaultTenant()->get() as $answer) {
            $answers[$answer->question_key] = $answer->answer;
        }

        return $answers;
    }

    /**
     * Re-derive na states from the current answers. Only the na <-> review
     * transition is automated; ok/fail/manual evidence is never touched by
     * classification.
     */
    public function recompute(): void
    {
        $answers = $this->answers();

        $items = ChecklistItem::query()
            ->forDefaultTenant()
            ->whereNotNull('applies_when')
            ->get();

        foreach ($items as $item) {
            $reason = $this->switchedOffReason($item->applies_when ?? [], $answers);

            if ($reason !== null && $item->status !== CheckStatus::NotApplicable) {
                $item->update([
                    'status' => CheckStatus::NotApplicable,
                    'evidence_ref' => 'switched off by classification: ' . $reason,
                ]);
            }

            if ($reason === null && $item->status === CheckStatus::NotApplicable) {
                $item->update([
                    'status' => CheckStatus::Review,
                    'evidence_ref' => null,
                ]);
            }
        }
    }

    /**
     * @param  array<string, string>  $rules
     * @param  array<string, string>  $answers
     */
    private function switchedOffReason(array $rules, array $answers): ?string
    {
        if (isset($rules['any_of']) && is_array($rules['any_of'])) {
            return $this->allAlternativesDefeated($rules['any_of'], $answers);
        }

        foreach ($rules as $questionKey => $expected) {
            $answer = $answers[$questionKey] ?? null;

            if ($answer !== null && $answer !== $expected) {
                return sprintf('%s=%s', $questionKey, $answer);
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, string>>  $alternatives
     * @param  array<string, string>  $answers
     */
    private function allAlternativesDefeated(array $alternatives, array $answers): ?string
    {
        $reasons = [];

        foreach ($alternatives as $alternative) {
            $reason = null;

            foreach ($alternative as $questionKey => $expected) {
                $answer = $answers[$questionKey] ?? null;

                if ($answer !== $expected) {
                    // an unanswered question cannot defeat an alternative
                    if ($answer === null) {
                        return null;
                    }

                    $reason = sprintf('%s=%s', $questionKey, $answer);
                    break;
                }
            }

            if ($reason === null) {
                return null; // this alternative holds; the item applies
            }

            $reasons[] = $reason;
        }

        return implode(', ', $reasons);
    }
}
