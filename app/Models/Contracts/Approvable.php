<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface Approvable
{
    public function isPending(): bool;

    public function isApproved(): bool;

    public function isRejected(): bool;

    public function approve(int|string|null $approvedBy = null): bool;

    public function reject(?string $reason = null, int|string|null $rejectedBy = null): bool;
}
