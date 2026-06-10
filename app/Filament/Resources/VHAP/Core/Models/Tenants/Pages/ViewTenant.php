<?php

namespace App\Filament\Resources\VHAP\Core\Models\Tenants\Pages;

use App\Filament\Resources\VHAP\Core\Models\Tenants\TenantResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
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
        ];
    }
}
