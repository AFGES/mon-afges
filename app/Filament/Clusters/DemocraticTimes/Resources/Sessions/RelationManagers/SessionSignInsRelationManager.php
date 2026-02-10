<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Sessions\RelationManagers;

use App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Schemas\SessionSignInForm;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class SessionSignInsRelationManager extends RelationManager
{
    protected static string $relationship = 'signIns';

    public static function getTitle($ownerRecord, $livewire): string
    {
        return __('emargement.plural');
    }

    public function form(Schema $schema): Schema
    {
        return SessionSignInForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        $session = $this->getOwnerRecord();
        $status = $session->getQuorumStatus();
        $type = $session->democraticTimeType;

        // Build description showing quorum status
        $description = '';
        if ($type) {
            $description .= '<div class="text-sm">';
            $calculationType = $type->calculate_quorum_per_college ? __('emargement.quorum.per_college') : __('emargement.quorum.combined');
            $description .= '<div class="mb-2"><strong>'.__('emargement.quorum.calculation_type').':</strong> '.$calculationType.'</div>';
            $description .= '<div class="mb-2"><strong>'.__('emargement.quorum.required_percentage').':</strong> '.$type->quorum_percentage.'%</div>';
            $description .= '<div class="mb-2"><strong>'.__('emargement.quorum.includes_proxies').':</strong> '.($type->include_proxies_in_quorum ? __('common.yes') : __('common.no')).'</div>';

            if ($status['type'] === 'combined') {
                $color = $status['met'] ? 'success' : 'danger';
                $statusText = $status['met'] ? __('emargement.quorum.met') : __('emargement.quorum.not_met');
                $description .= '<div class="mt-3 p-3 rounded-lg bg-'.$color.'-50 dark:bg-'.$color.'-900/20">';
                $description .= '<div class="font-semibold text-'.$color.'-800 dark:text-'.$color.'-200">'.$statusText.'</div>';
                $description .= '<div class="mt-1 text-'.$color.'-700 dark:text-'.$color.'-300">';
                $description .= sprintf('%d/%d (%s%%)', $status['present_count'], $status['total_eligible'], $status['percentage']);
                $description .= '</div></div>';
            } else {
                $description .= '<div class="mt-3 space-y-2">';
                foreach ($status['details'] as $detail) {
                    $color = $detail['met'] ? 'success' : 'danger';
                    $icon = $detail['met'] ? '✓' : '✗';
                    $description .= '<div class="p-2 rounded bg-'.$color.'-50 dark:bg-'.$color.'-900/20">';
                    $description .= '<span class="font-semibold text-'.$color.'-800 dark:text-'.$color.'-200">'.$icon.' '.$detail['college_name'].':</span> ';
                    $description .= '<span class="text-'.$color.'-700 dark:text-'.$color.'-300">';
                    $description .= sprintf('%d/%d (%s%%)', $detail['present_count'], $detail['total_eligible'], $detail['percentage']);
                    $description .= '</span></div>';
                }

                $overallColor = $status['met'] ? 'success' : 'danger';
                $overallText = $status['met'] ? __('emargement.quorum.met') : __('emargement.quorum.not_met');
                $description .= '<div class="mt-2 p-3 rounded-lg bg-'.$overallColor.'-50 dark:bg-'.$overallColor.'-900/20">';
                $description .= '<div class="font-bold text-'.$overallColor.'-800 dark:text-'.$overallColor.'-200">';
                $description .= __('emargement.quorum.overall_status').': '.$overallText;
                $description .= '</div></div>';
                $description .= '</div>';
            }
            $description .= '</div>';
        }

        return $table
            ->description(fn () => new \Illuminate\Support\HtmlString($description))
            ->columns([
                TextColumn::make('seat.label')
                    ->label(__('emargement.fields.seat'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('delegate.email')
                    ->label(__('emargement.fields.delegate'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('signed_in_at')
                    ->label(__('emargement.fields.signed_in_at'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('signed_out_at')
                    ->label(__('emargement.fields.signed_out_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('emargement.status.open')),

                IconColumn::make('is_signed_in')
                    ->label(__('emargement.fields.is_signed_in'))
                    ->boolean()
                    ->trueIcon(Heroicon::CheckCircle)
                    ->falseIcon(Heroicon::XCircle)
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn ($record) => $record->isSignedIn()),

                IconColumn::make('is_proxy_sign_in')
                    ->label(__('emargement.fields.is_proxy_sign_in'))
                    ->boolean()
                    ->trueIcon(Heroicon::UserGroup)
                    ->falseIcon(Heroicon::User)
                    ->trueColor('info')
                    ->falseColor('gray')
                    ->getStateUsing(fn ($record) => $record->isProxySignIn()),
            ])
            ->filters([
                SelectFilter::make('seat_id')
                    ->label(__('emargement.fields.seat'))
                    ->relationship('seat', fn ($record) => $record->label())
                    ->searchable()
                    ->preload(),

                Filter::make('signed_in')
                    ->label(__('emargement.filters.currently_signed_in'))
                    ->query(fn (Builder $query) => $query->whereNull('signed_out_at')),

                Filter::make('signed_out')
                    ->label(__('emargement.filters.signed_out'))
                    ->query(fn (Builder $query) => $query->whereNotNull('signed_out_at')),
            ])
            ->defaultSort('signed_in_at', 'desc')
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
