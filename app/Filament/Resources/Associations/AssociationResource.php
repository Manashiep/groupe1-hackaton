<?php

namespace App\Filament\Resources\Associations;

use App\Enums\AssociationStatus;
use App\Filament\Resources\Associations\Pages\ManageAssociations;
use App\Models\Association;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Associations de nettoyage : la mairie accepte ou refuse les demandes de création.
 */
class AssociationResource extends Resource
{
    protected static ?string $model = Association::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'associations';

    protected static ?string $navigationLabel = 'Associations';

    protected static ?string $modelLabel = 'association';

    protected static ?string $pluralModelLabel = 'associations';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $count = Association::where('status', AssociationStatus::EnAttente)->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')->label('Nom'),
                TextEntry::make('status')->label('Statut')->badge(),
                TextEntry::make('leader.name')->label('Chef du groupe'),
                TextEntry::make('leader.phone')->label('Téléphone')->placeholder('—'),
                TextEntry::make('village.name')->label('Village')->placeholder('Plusieurs villages'),
                ColorEntry::make('color')->label('Couleur'),
                TextEntry::make('description')->label('Présentation')->columnSpanFull(),
                TextEntry::make('motivation')->label('Message à la mairie')->placeholder('—')->columnSpanFull(),
                TextEntry::make('admin_note')->label('Réponse de la mairie')->placeholder('—')->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['leader', 'village'])->withCount(['members', 'posts']))
            ->columns([
                ColorColumn::make('color')->label(''),
                TextColumn::make('name')->label('Association')->description(fn (Association $record): string => 'Chef : '.$record->leader->name)->searchable(),
                TextColumn::make('village.name')->label('Village')->placeholder('Plusieurs'),
                TextColumn::make('members_count')->label('Membres'),
                TextColumn::make('posts_count')->label('Publications'),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('created_at')->label('Demandée')->since(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(AssociationStatus::class),
            ])
            ->recordActions([
                ViewAction::make()->label('Voir'),
                Action::make('approve')
                    ->label('Accepter')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->visible(fn (Association $record): bool => $record->status === AssociationStatus::EnAttente)
                    ->requiresConfirmation()
                    ->modalDescription('Le demandeur deviendra chef du groupe et pourra publier et accepter des membres.')
                    ->action(fn (Association $record) => $record->update(['status' => AssociationStatus::Active, 'admin_note' => null]))
                    ->successNotificationTitle('Association acceptée'),
                Action::make('refuse')
                    ->label('Refuser')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('gray')
                    ->visible(fn (Association $record): bool => $record->status === AssociationStatus::EnAttente)
                    ->schema([
                        Textarea::make('admin_note')->label('Motif (visible par le demandeur)')->required()->maxLength(500),
                    ])
                    ->action(fn (Association $record, array $data) => $record->update([...$data, 'status' => AssociationStatus::Refusee]))
                    ->successNotificationTitle('Demande refusée'),
                Action::make('suspend')
                    ->label('Suspendre')
                    ->icon(Heroicon::OutlinedPauseCircle)
                    ->color('gray')
                    ->visible(fn (Association $record): bool => $record->status === AssociationStatus::Active)
                    ->schema([
                        Textarea::make('admin_note')->label('Motif')->required()->maxLength(500),
                    ])
                    ->action(fn (Association $record, array $data) => $record->update([...$data, 'status' => AssociationStatus::Refusee])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAssociations::route('/'),
        ];
    }
}
