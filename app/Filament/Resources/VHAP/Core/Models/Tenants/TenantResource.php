<?php

namespace App\Filament\Resources\VHAP\Core\Models\Tenants;

use App\Filament\Resources\VHAP\Core\Models\Tenants\Pages\CreateTenant;
use App\Filament\Resources\VHAP\Core\Models\Tenants\Pages\EditTenant;
use App\Filament\Resources\VHAP\Core\Models\Tenants\Pages\ListTenants;
use App\Filament\Resources\VHAP\Core\Models\Tenants\Pages\ViewTenant;
use App\Filament\Resources\VHAP\Core\Models\Tenants\Schemas\TenantForm;
use App\Filament\Resources\VHAP\Core\Models\Tenants\Schemas\TenantInfolist;
use App\Filament\Resources\VHAP\Core\Models\Tenants\Tables\TenantsTable;
use App\Models\VHAP\Core\Models\Tenant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TenantForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TenantInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'view' => ViewTenant::route('/{record}'),
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
