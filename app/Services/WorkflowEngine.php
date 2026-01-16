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

    public function canTransition(AppWorkflow $workflow, string $from, string $to, array $context = []): bool
    {
        $transition = $this->findTransition($workflow, $from, $to);

        if (! $transition) {
            return false;
        }

        if (! $this->roleAllowed($workflow, $transition, $context['role'] ?? null)) {
            return false;
        }

        $record = $context['record'] ?? null;
        if ($record && ! $this->conditionMatches($transition['condition'] ?? null, $record->data ?? [])) {
            return false;
        }

        return true;
    }

    public function findTransition(AppWorkflow $workflow, string $from, string $to): ?array
    {
        $transitions = Arr::get($workflow->definition ?? [], 'transitions', []);

        foreach ($transitions as $transition) {
            if (($transition['from'] ?? null) === $from && ($transition['to'] ?? null) === $to) {
                return $transition;
            }
        }

        return null;
    }

    public function applyPostAction(?array $transition, array $recordData): array
    {
        $postAction = $transition['post_action'] ?? null;
        if (! is_array($postAction)) {
            return $recordData;
        }

        $type = $postAction['type'] ?? null;
        return match ($type) {
            'set_field' => $this->setField($recordData, $postAction),
            'append_note' => $this->appendNote($recordData, $postAction),
            default => $recordData,
        };
    }

    protected function roleAllowed(AppWorkflow $workflow, array $transition, ?string $role): bool
    {
        $requiredRole = $transition['requires_role'] ?? null;
        if ($requiredRole && $role !== $requiredRole) {
            return false;
        }

        $approvals = Arr::get($workflow->definition ?? [], 'approvals', []);
        foreach ($approvals as $approval) {
            if (($approval['state'] ?? null) === ($transition['to'] ?? null)) {
                $approvalRole = $approval['role'] ?? null;
                if ($approvalRole && $role !== $approvalRole) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function conditionMatches(?array $condition, array $data): bool
    {
        if (! $condition) {
            return true;
        }

        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? 'eq';
        $value = $condition['value'] ?? null;

        if (! $field) {
            return true;
        }

        $recordValue = Arr::get($data, $field);

        return match ($operator) {
            'contains' => is_string($recordValue) && str_contains($recordValue, (string) $value),
            'gt' => $recordValue > $value,
            'lt' => $recordValue < $value,
            'neq' => $recordValue != $value,
            default => $recordValue == $value,
        };
    }

    protected function setField(array $data, array $postAction): array
    {
        $field = $postAction['field'] ?? null;
        if (! $field) {
            return $data;
        }

        $data[$field] = $postAction['value'] ?? null;

        return $data;
    }

    protected function appendNote(array $data, array $postAction): array
    {
        $field = $postAction['field'] ?? 'notes';
        $note = $postAction['value'] ?? '';
        if ($note === '') {
            return $data;
        }

        $existing = $data[$field] ?? '';
        $data[$field] = trim($existing."\n".$note);

        return $data;
    }
}
