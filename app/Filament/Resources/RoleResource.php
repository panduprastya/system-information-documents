<?php

namespace App\Filament\Resources;

use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static ?string $navigationGroup = null;
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Role Name')
                    ->required()
                    ->maxLength(255)
                    // Mencegah role duplikat (case-insensitive), misal: HSSE vs hsse
                    ->default(fn () => null)
                    ->rules([
                        function (\Filament\Forms\Get $get) {
                            $raw = (string) ($get('name') ?? '');
                            $value = trim($raw);
                            $lower = mb_strtolower($value);

                            return function (string $attribute, $value, $fail) use ($lower) {
                                if ($lower === '') {
                                    return;
                                }

                                // Validasi case-insensitive pakai lower(name)
                                $exists = \App\Models\Role::query()
                                    ->whereRaw('LOWER(name) = ?', [$lower])
                                    ->exists();

                                if ($exists) {
                                    $fail('Role sudah ada.');
                                }
                            };
                        },
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Override ID column to show row numbers
                Tables\Columns\TextColumn::make('row_number')
                    ->label('ID')
                    ->state(
                        static function (Tables\Contracts\HasTable $livewire, $rowLoop): string {
                            return (string) (
                                $rowLoop->iteration +
                                ($livewire->getTableRecordsPerPage() * (
                                    $livewire->getTablePage() - 1
                                ))
                            );
                        }
                    )
                    ->sortable(false)
                    ->searchable(false),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Role')
                    ->modalDescription('Apakah Anda yakin ingin menghapus role ini?')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->modalCancelActionLabel('Batal')
                    ->successNotificationTitle('role berhasil dihapus'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\RoleResource\Pages\ListRoles::route('/'),
            'create' => \App\Filament\Resources\RoleResource\Pages\CreateRole::route('/create'),
            'edit' => \App\Filament\Resources\RoleResource\Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            // Menghilangkan tab Permissions dan Users
        ];
    }
}
