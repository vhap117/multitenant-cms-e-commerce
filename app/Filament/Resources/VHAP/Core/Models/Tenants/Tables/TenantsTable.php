<?php

namespace App\Filament\Resources\VHAP\Core\Models\Tenants\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tenant Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('email')
                    ->label('Contact Email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('domain')
                    ->label('Subdomain / URL')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-globe-alt')
                    ->iconColor('gray'),

                TextColumn::make('database')
                    ->label('Database')
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('plan')
                    ->label('Plan')
                    ->badge()
                    ->color(fn ($state) => match ($state?->value) {
                        'free' => 'gray',
                        'pro' => 'success',
                        'enterprise' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Active Status')
                    ->disabled()
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->visible(fn (\VHAP\Core\Models\Tenant $record) => $record->is_active)
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Suspension Reason')
                            ->required()
                    ])
                    ->action(function (\VHAP\Core\Models\Tenant $record, array $data) {
                        app(\VHAP\Core\Actions\SuspendTenantAction::class)->execute($record, $data['reason']);
                    })
                    ->successNotificationTitle('Tenant suspended'),
                \Filament\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (\VHAP\Core\Models\Tenant $record) => $record->provisioning_status !== 'active' && !$record->is_active)
                    ->action(function (\VHAP\Core\Models\Tenant $record) {
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Initial activation not yet implemented')
                            ->body('This requires dispatching the ProvisionNewTenantAction with a valid DTO.')
                            ->send();
                    }),
                \Filament\Actions\Action::make('reactivate')
                    ->label('Reactivate')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->visible(fn (\VHAP\Core\Models\Tenant $record) => $record->provisioning_status === 'active' && !$record->is_active)
                    ->action(function (\VHAP\Core\Models\Tenant $record) {
                        app(\VHAP\Core\Actions\ReactivateTenantAction::class)->execute($record);
                    })
                    ->successNotificationTitle('Tenant reactivated'),
                \Filament\Actions\Action::make('destroy')
                    ->label('Destroy Environment')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Destroy Tenant Environment')
                    ->modalDescription('Are you absolutely sure? This will delete the database, storage, and all records permanently. This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, destroy it')
                    ->action(function (\VHAP\Core\Models\Tenant $record) {
                        app(\VHAP\Core\Actions\DestroyTenantEnvironmentAction::class)->execute($record);
                    })
                    ->successNotificationTitle('Tenant environment destroyed'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}