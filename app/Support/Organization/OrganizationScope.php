<?php

namespace App\Support\Organization;

/**
 * The resolved set of ids a user's queries are constrained to. Per array,
 * `null` means unrestricted (no filter applied — e.g. super admin) and `[]`
 * means deny-all (e.g. no active employee record for this user). An
 * authorization boundary, so this distinction must never be collapsed.
 */
final class OrganizationScope
{
    /**
     * @param  array<int>|null  $entityIds
     * @param  array<int>|null  $branchIds
     * @param  array<int>|null  $departmentIds
     */
    public function __construct(
        public readonly ?array $entityIds,
        public readonly ?array $branchIds,
        public readonly ?array $departmentIds,
    ) {}

    public static function unrestricted(): self
    {
        return new self(null, null, null);
    }

    public static function denyAll(): self
    {
        return new self([], [], []);
    }

    /**
     * @return array{entityIds: array<int>|null, branchIds: array<int>|null, departmentIds: array<int>|null}
     */
    public function toArray(): array
    {
        return [
            'entityIds' => $this->entityIds,
            'branchIds' => $this->branchIds,
            'departmentIds' => $this->departmentIds,
        ];
    }

    /**
     * @param  array{entityIds: array<int>|null, branchIds: array<int>|null, departmentIds: array<int>|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['entityIds'], $data['branchIds'], $data['departmentIds']);
    }
}
