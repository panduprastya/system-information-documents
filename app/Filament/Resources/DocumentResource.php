<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Document;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\DocumentResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\DocumentResource\RelationManagers;
use Filament\Notifications\Notification;

use function Laravel\Prompts\textarea;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $isMitra = $user && $user->hasRole('Mitra');
        
        return $form
            ->schema([
                Forms\Components\TextInput::make('judul_dokumen')
                    ->required()
                    ->maxLength(255),
                FileUpload::make('file')
                    ->required(),
                Forms\Components\Textarea::make('keterangan')
                    ->label('Keterangan Dokumen')
                    ->placeholder('Masukkan keterangan tambahan untuk dokumen ini...')
                    ->rows(4)
                    ->maxLength(5000)
                    ->columnSpanFull()
                    ->visible($isMitra),
                // Field id_mitra hanya untuk Admin, HSSE, S&D
                Forms\Components\Select::make('id_mitra')
                    ->label('Pilih Mitra')
                    ->relationship('mitra', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(!$isMitra)
                    ->required(!$isMitra)
                    ->disabled($isMitra), // Disable field untuk Mitra
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('judul_dokumen')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mitra.name')
                    ->label('Nama Mitra')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('file')
                    ->searchable(),
                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan Dokumen')
                    ->limit(60)
                    ->wrap()
                    ->toggleable()
                    ->visible(auth()->user()->hasRole('Mitra')),
                // Tables\Columns\TextColumn::make('status')
                //     ->badge()
                //     ->color(fn (string $state): string => match ($state) {
                //         'pending' => 'gray',
                //         'reviewing' => 'warning',
                //         'approved' => 'success',
                //         'rejected' => 'danger',
                //         default => 'gray',
                //     }),
                Tables\Columns\TextColumn::make('hsse_status')
                    ->label('HSSE Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'reviewing' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'revisi' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'reviewing' => 'heroicon-o-eye',
                        'approved' => 'heroicon-o-check-circle',
                        'rejected' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('snd_status')
                    ->label('S&D Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'review' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'revisi' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'review' => 'heroicon-o-eye',
                        'approved' => 'heroicon-o-check-circle',
                        'rejected' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable(),
                // Tables\Columns\TextColumn::make('tanggal_upload')
                //     ->dateTime()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('tanggal_acc')
                //     ->dateTime()
                //     ->sortable(),
                Tables\Columns\TextColumn::make('HSSE.name')
                    ->label('Nama HSSE')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hsseComments')
                    ->label('HSSE Comments')
                    ->formatStateUsing(function ($state, $record) {
                        $comments = $record->hsseComments()->with('user')->get();
                        if ($comments->isEmpty()) {
                            return 'No comments';
                        }
                        
                        $latestComment = $comments->first();
                        $count = $comments->count();
                        
                        $text = $latestComment->komentar ?? '';
                        if (strlen($text) > 50) {
                            $text = substr($text, 0, 50) . '...';
                        }
                        
                        return $count > 1 
                            ? $text . " ({$count} comments)" 
                            : $text;
                    })
                    ->tooltip(function ($record) {
                        $comments = $record->hsseComments()->with('user')->get();
                        if ($comments->isEmpty()) {
                            return 'No comments';
                        }
                        
                        return $comments->map(function ($comment) {
                            $userName = $comment->user ? $comment->user->name : 'Unknown';
                            return $userName . ': ' . $comment->komentar;
                        })->implode("\n");
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('hsseComments', function ($query) use ($search) {
                            $query->where('komentar', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('snd.name')
                    ->label('Nama S&D')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sndComments')
                    ->label('S&D Comments')
                    ->formatStateUsing(function ($state, $record) {
                        $comments = $record->sndComments()->with('user')->get();
                        if ($comments->isEmpty()) {
                            return 'No comments';
                        }
                        
                        $latestComment = $comments->first();
                        $count = $comments->count();
                        
                        $text = $latestComment->komentar ?? '';
                        if (strlen($text) > 50) {
                            $text = substr($text, 0, 50) . '...';
                        }
                        
                        return $count > 1 
                            ? $text . " ({$count} comments)" 
                            : $text;
                    })
                    ->tooltip(function ($record) {
                        $comments = $record->sndComments()->with('user')->get();
                        if ($comments->isEmpty()) {
                            return 'No comments';
                        }
                        
                        return $comments->map(function ($comment) {
                            $userName = $comment->user ? $comment->user->name : 'Unknown';
                            return $userName . ': ' . $comment->komentar;
                        })->implode("\n");
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('sndComments', function ($query) use ($search) {
                            $query->where('komentar', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('hsse_status')
                    ->label('HSSE Status')
                    ->options([
                        'pending' => 'Pending',
                        'reviewing' => 'Reviewing',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'revisi' => 'Revisi',
                    ]),
                Tables\Filters\SelectFilter::make('snd_status')
                    ->label('S&D Status')
                    ->options([
                        'pending' => 'Pending',
                        'reviewing' => 'Reviewing',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'revisi' => 'Revisi',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Document $record) => route('documents.download', ['document' => $record->getKey()]))
                    ->openUrlInNewTab()
                    ->visible(function (Document $record) {
                        $user = auth()->user();
                        return $user && $user->hasRole('Mitra') &&
                            $record->hsse_status === 'approved' && $record->snd_status === 'approved';
                    }),
                    
                Tables\Actions\Action::make('revision_info')
                    ->label('Info Revisi')
                    ->icon('heroicon-o-information-circle')
                    ->color('warning')
                    ->visible(function (Document $record) {
                        $user = auth()->user();
                        return $user && $user->hasRole('Mitra') && $record->needsRevision();
                    })
                    ->action(function (Document $record) {
                        $reasons = $record->getRevisionReasons();
                        $message = "Dokumen ini memerlukan revisi:\n\n";
                        
                        foreach ($reasons as $reason) {
                            $message .= "{$reason['type']} Review:\n";
                            $message .= "Alasan: {$reason['comment']}\n";
                            $message .= "Reviewer: {$reason['reviewer']}\n";
                            $message .= "Tanggal: {$reason['date']->format('d/m/Y H:i')}\n\n";
                        }
                        
                        $message .= "Silakan edit dokumen untuk melakukan revisi sesuai feedback di atas.";
                        
                        Notification::make()
                            ->title('Informasi Revisi')
                            ->body($message)
                            ->persistent()
                            ->send();
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(function (Document $record) {
                        $user = auth()->user();
                        
                        // Admin cannot edit documents
                        if ($user->hasRole('Admin')) {
                            return false;
                        }
                        
                        // Mitra can edit documents ONLY when either HSSE or SND status is 'revisi'
                        if ($user->hasRole('Mitra')) {
                            $hasRevisiStatus = $record->hsse_status === 'revisi' || $record->snd_status === 'revisi';
                            return $hasRevisiStatus;
                        }
                        
                        // HSSE and S&D users cannot edit documents (they can only review)
                        return false;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()->hasRole('Admin')),// tombol delete hanya ditampilkan untuk admin
                Tables\Actions\Action::make('Review')
                    ->label('Review')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(function (Document $record) {
                        $user = auth()->user();
                        
                        // Don't show review button if both statuses are approved
                        if ($record->hsse_status === 'approved' && $record->snd_status === 'approved') {
                            return false;
                        }
                        
                        // HSSE can only review if their status is pending or revisi
                        if ($user->hasRole('HSSE')) {
                            return $record->hsse_status === 'pending' || $record->hsse_status === 'revisi';
                        }
                        
                        // S&D can only review if their status is pending or revisi
                        elseif ($user->hasRole('S&D')) {
                            return $record->snd_status === 'pending' || $record->snd_status === 'revisi';
                        }
                        
                        // Other roles cannot review
                        return false;
                    })
                    ->requiresConfirmation()
                    ->action(function (Document $record) {
                        $user = auth()->user();
                        $updateData = [];
                        
                        if ($user->hasRole('HSSE')) {
                            $updateData['hsse_status'] = 'reviewing';
                            $updateData['hsse_review_started_at'] = now();
                            $updateData['id_hsse'] = $user->id;
                        } elseif ($user->hasRole('S&D')) {
                            $updateData['snd_status'] = 'reviewing';
                            $updateData['snd_review_started_at'] = now();
                            $updateData['id_snd'] = $user->id;
                        }
                        
                        $record->update($updateData);
                        return redirect(static::getUrl('view',['record'=>$record])); // masuk kedalam halaman view dokumen
                    })
                ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ])
                ->visible(fn() => auth()->user()->hasRole('Admin')), //supaya bulk action dapat diakses oleh admin saja
            ]);
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
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'view' => Pages\ViewDocument::route('/{record}'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['mitra', 'hsseComments.user','sndComments.user']);
        
        $user = auth()->user();
        
        // Jika user adalah Mitra, hanya tampilkan dokumen yang mereka buat
        if ($user && $user->hasRole('Mitra')) {
            $query->where('id_mitra', $user->id);
        }
        
        return $query;
    }
}
