<?php

namespace App\Filament\Resources\VHAP\Core\Models\Tenants\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Toggle;
use VHAP\Core\Enums\TenantPlan;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('domain', str($state)->slug() . '.localhost')),
                        
                        TextInput::make('email')
                            ->label('Primary Contact Email')
                            ->email()
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('admin_user.email', $state)),

                        Select::make('plan')
                            ->label('Subscription Plan')
                            ->options(TenantPlan::class)
                            ->default(TenantPlan::FREE->value)
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Active Status')
                            ->default(true)
                            ->required(),

                        TextInput::make('domain')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('The subdomain or custom domain for the tenant (e.g. tenant.localhost)'),

                        TextInput::make('database')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('The dedicated database name for this tenant'),
                    ]),

                Section::make('Tenant Admin User')
                    ->statePath('admin_user')
                    ->schema([
                        TextInput::make('name')
                            ->label('Admin Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Admin Email')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label('Admin Password')
                            ->password()
                            ->required()
                            ->maxLength(255),
                    ]),
            ]);
    }
}
