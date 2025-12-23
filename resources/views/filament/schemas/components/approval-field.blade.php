<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @php
        $record = $getRecord();
        $statusAttribute = $getStatusAttribute();
        $approvedAtAttribute = $getApprovedAtAttribute();
        $approvedByAttribute = $getApprovedByAttribute();
        $rejectionReasonAttribute = $getRejectionReasonAttribute();
        $approverRelationship = $getApproverRelationship();
        
        $status = data_get($record, $statusAttribute);
        $approvedAt = data_get($record, $approvedAtAttribute);
        $approvedBy = data_get($record, $approvedByAttribute);
        $rejectionReason = data_get($record, $rejectionReasonAttribute);
        $approver = $record?->{$approverRelationship};
        
        $isPending = $status === \App\Enums\ApprovalStatus::Pending;
        $isApproved = $status === \App\Enums\ApprovalStatus::Approved;
        $isRejected = $status === \App\Enums\ApprovalStatus::Rejected;
    @endphp

    <div class="space-y-4">
        @if ($isPending)
            {{-- Pending state: Show approve and reject buttons --}}
            <div class="flex gap-3">
                <x-filament::button
                    color="success"
                    wire:click="mountAction('approve')"
                    size="sm"
                >
                    {{ __('approval.actions.approve') }}
                </x-filament::button>

                <x-filament::button
                    color="danger"
                    wire:click="mountAction('reject')"
                    size="sm"
                >
                    {{ __('approval.actions.reject') }}
                </x-filament::button>
            </div>
        @else
            {{-- Approved or Rejected state: Show information --}}
            <div class="rounded-lg border p-4 dark:border-gray-700">
                <div class="space-y-3">
                    {{-- Status badge --}}
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('approval.fields.status') }}:
                        </span>
                        <x-filament::badge :color="$status->getColor()">
                            {{ $status->getLabel() }}
                        </x-filament::badge>
                    </div>

                    {{-- Approved by --}}
                    @if ($approver)
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $isApproved ? __('approval.fields.approved_by') : __('approval.fields.rejected_by') }}:
                            </span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $approver->name }}
                            </span>
                        </div>
                    @endif

                    {{-- Approved at --}}
                    @if ($approvedAt)
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $isApproved ? __('approval.fields.approved_at') : __('approval.fields.rejected_at') }}:
                            </span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $approvedAt->format('d/m/Y H:i') }}
                            </span>
                        </div>
                    @endif

                    {{-- Rejection reason --}}
                    @if ($isRejected && $rejectionReason)
                        <div class="space-y-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('approval.fields.rejection_reason') }}:
                            </span>
                            <p class="text-sm text-gray-600 dark:text-gray-400 rounded bg-gray-50 dark:bg-gray-800 p-2">
                                {{ $rejectionReason }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-dynamic-component>
