<?php

namespace App\Filament\Resources\Users;

use App\Enums\Role;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $slug = 'utilisateurs';

    protected static ?string $navigationLabel = 'Utilisateurs';

    protected static ?string $modelLabel = 'utilisateur';

    protected static ?string $pluralModelLabel = 'utilisateurs';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nom')->required()->maxLength(120),
                TextInput::make('email')->label('E-mail')->email()->required()->unique(ignoreRecord: true),
                TextInput::make('phone')->label('Téléphone')->tel()->maxLength(30),
                Select::make('role')->label('Rôle')->options(Role::class)->default(Role::Agent)->required()->live(),
                Select::make('truck_id')
                    ->label('Camion')
                    ->relationship('truck', 'name')
                    ->visible(fn (Get $get): bool => in_array($get('role'), [Role::Agent, Role::Agent->value], true)),
                TextInput::make('password')
                    ->label('Mot de passe')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Toggle::make('is_active')->label('Compte actif')->default(true),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nom')->description(fn (User $record): string => $record->email)->searchable(['name', 'email']),
                TextColumn::make('role')->label('Rôle')->badge(),
                TextColumn::make('truck.name')->label('Camion')->placeholder('—'),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('role')->label('Rôle')->options(Role::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (User $record): bool => $record->deletionBlocker(auth()->user()) !== null)
                    ->tooltip(fn (User $record): ?string => $record->deletionBlocker(auth()->user())),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
