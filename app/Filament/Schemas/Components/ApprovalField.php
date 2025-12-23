<?php

declare(strict_types=1);

namespace App\Filament\Schemas\Components;

use Closure;
use Filament\Forms\Components\Field;

class ApprovalField extends Field
{
    protected string $view = 'filament.schemas.components.approval-field';

    protected string|Closure|null $approverRelationship = 'approver';

    protected string|Closure|null $statusAttribute = 'status';

    protected string|Closure|null $approvedAtAttribute = 'approved_at';

    protected string|Closure|null $approvedByAttribute = 'approved_by';

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
        return $this->evaluate($this->approverRelationship);
    }

    public function statusAttribute(string|Closure|null $attribute): static
    {
        $this->statusAttribute = $attribute;

        return $this;
    }

    public function getStatusAttribute(): ?string
    {
        return $this->evaluate($this->statusAttribute);
    }

    public function approvedAtAttribute(string|Closure|null $attribute): static
    {
        $this->approvedAtAttribute = $attribute;

        return $this;
    }

    public function getApprovedAtAttribute(): ?string
    {
        return $this->evaluate($this->approvedAtAttribute);
    }

    public function approvedByAttribute(string|Closure|null $attribute): static
    {
        $this->approvedByAttribute = $attribute;

        return $this;
    }

    public function getApprovedByAttribute(): ?string
    {
        return $this->evaluate($this->approvedByAttribute);
    }

    public function rejectionReasonAttribute(string|Closure|null $attribute): static
    {
        $this->rejectionReasonAttribute = $attribute;

        return $this;
    }

    public function getRejectionReasonAttribute(): ?string
    {
        return $this->evaluate($this->rejectionReasonAttribute);
    }
}
