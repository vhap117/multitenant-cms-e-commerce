<?php

namespace App\Filament\Resources\VHAP\Core\Models\Tenants\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;

class TenantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Tenant Name')
                            ->weight('bold'),

                        TextEntry::make('email')
                            ->label('Primary Contact / Billing Email')
                            ->copyable(),

                        TextEntry::make('domain')
                            ->label('Domain Name')
                            ->icon('heroicon-m-globe-alt')
                            ->copyable(),

                        TextEntry::make('database')
                            ->label('Database Connection')
                            ->fontFamily('mono')
                            ->copyable(),
                    ]),

                Section::make('Subscription & Status')
                    ->columns(3)
                    ->schema([
                        IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean()
                            ->trueColor('success')
                            ->falseColor('danger'),

                        TextEntry::make('plan')
                            ->label('Subscription Plan')
                            ->badge()
                            ->color(fn ($state) => match ($state?->value) {
                                'free' => 'gray',
                                'pro' => 'success',
                                'enterprise' => 'warning',
                                default => 'gray',
                            }),

                        TextEntry::make('stripe_id')
                            ->label('Stripe Customer ID')
                            ->fontFamily('mono')
                            ->placeholder('No Stripe ID generated yet')
                            ->copyable(),
                    ]),

                Section::make('Metadata')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Created Date')
                            ->dateTime(),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ]),
            ]);
    }
}