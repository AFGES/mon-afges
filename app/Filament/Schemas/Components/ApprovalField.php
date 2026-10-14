<?php

declare(strict_types=1);

namespace App\Filament\Schemas\Components;

use Closure;
use Filament\Forms\Components\Field;

final class ApprovalField extends Field
{
    protected string $view = 'filament.schemas.components.approval-field';

    protected string|Closure|null $approverRelationship = 'approver';

    protected string|Closure|null $statusAttribute = 'approval_status';

    protected string|Closure|null $approvedAtAttribute = 'approved_at';

    protected string|Closure|null $rejectionReasonAttribute = 'rejection_reason';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);
    }

    public function approverRelationship(string|Closure|null $relationship): static
    {
        $this->approverRelationship = $relationship;

        return $this;
    }

    public function getApproverRelationship(): ?string
    {
        $value = $this->evaluate($this->approverRelationship);

        return is_string($value) ? $value : null;
    }

    public function statusAttribute(string|Closure|null $attribute): static
    {
        $this->statusAttribute = $attribute;

        return $this;
    }

    public function getStatusAttribute(): ?string
    {
        $value = $this->evaluate($this->statusAttribute);

        return is_string($value) ? $value : null;
    }

    public function approvedAtAttribute(string|Closure|null $attribute): static
    {
        $this->approvedAtAttribute = $attribute;

        return $this;
    }

    public function getApprovedAtAttribute(): ?string
    {
        $value = $this->evaluate($this->approvedAtAttribute);

        return is_string($value) ? $value : null;
    }

    public function rejectionReasonAttribute(string|Closure|null $attribute): static
    {
        $this->rejectionReasonAttribute = $attribute;

        return $this;
    }

    public function getRejectionReasonAttribute(): ?string
    {
        $value = $this->evaluate($this->rejectionReasonAttribute);

        return is_string($value) ? $value : null;
    }
}
