<?php

namespace App\Services;

use App\Models\AppWorkflow;
use Illuminate\Support\Arr;

class WorkflowEngine
{
    public function initialState(AppWorkflow $workflow): ?string
    {
        $definition = $workflow->definition ?? [];
        $initial = Arr::get($definition, 'initial');

        if ($initial) {
            return $initial;
        }

        $states = Arr::get($definition, 'states', []);
        return $states[0] ?? null;
    }

    public function canTransition(AppWorkflow $workflow, string $from, string $to): bool
    {
        $transitions = Arr::get($workflow->definition ?? [], 'transitions', []);

        foreach ($transitions as $transition) {
            if (($transition['from'] ?? null) === $from && ($transition['to'] ?? null) === $to) {
                return true;
            }
        }

        return false;
    }
}
