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
                    ->visible(auth()->user()->hasRole('Mitra') || auth()->user()->hasRole('Admin')),
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

                        // Mitra can edit when:
                        // - both statuses are pending/revisi, OR
                        // - one status is approved and the other is pending/revisi
                        // Not allowed when both are approved, or when any is reviewing/rejected
                        if ($user->hasRole('Mitra')) {
                            $hsse = $record->hsse_status;
                            $snd = $record->snd_status;
                            $isPendingOrRevisi = fn(string $s) => in_array($s, ['pending', 'revisi'], true);
                            $isApproved = fn(string $s) => $s === 'approved';
                            $isBlocked = fn(string $s) => in_array($s, ['reviewing', 'rejected'], true);

                            if ($isBlocked($hsse) || $isBlocked($snd)) {
                                return false;
                            }

                            if ($isApproved($hsse) && $isApproved($snd)) {
                                return false;
                            }

                            return $isPendingOrRevisi($hsse) || $isPendingOrRevisi($snd);
                        }

                        // HSSE and S&D users cannot edit documents (they can only review)
                        return false;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(function (Document $record) {
                        $user = auth()->user();
                        // Admin can always delete (retain previous capability for admin)
                        if ($user->hasRole('Admin')) {
                            return true;
                        }
                        // Mitra can delete ONLY when both statuses are pending
                        if ($user->hasRole('Mitra')) {
                            return ($record->hsse_status === 'pending') && ($record->snd_status === 'pending');
                        }
                        return false;
                    }),// tombol delete hanya ditampilkan sesuai kondisi
                Tables\Actions\Action::make('Review')
                    ->label('Review')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(function (Document $record) {
                        $user = auth()->user();
                        
                        // Don't show review button if both statuses are approved
                        if ($record->hsse_status === 'approved' && $record->snd_status === 'approved') {
                            return false;
                        }
                        
                        // HSSE can only review if their status is pending AND they are the assigned reviewer (if any)
                        if ($user->hasRole('HSSE')) {
                            if ($record->hsse_status !== 'pending') {
                                return false;
                            }
                            // If a previous HSSE reviewer exists, only allow that same user
                            if (!empty($record->id_hsse)) {
                                return (int) $record->id_hsse === (int) $user->id;
                            }
                            // If no previous reviewer, allow first HSSE reviewer to claim
                            return true;
                        }

                        // S&D can only review if their status is pending AND they are the assigned reviewer (if any)
                        elseif ($user->hasAnyRole(['S&D','SND'])) {
                            if ($record->snd_status !== 'pending') {
                                return false;
                            }
                            if (!empty($record->id_snd)) {
                                return (int) $record->id_snd === (int) $user->id;
                            }
                            return true;
                        }
                        
                        // Other roles cannot review
                        return false;
                    })
                    ->requiresConfirmation()
                    ->action(function (Document $record) {
                        $user = auth()->user();
                        $updateData = [];

                        if ($user->hasRole('HSSE')) {
                            // If there is no assigned HSSE yet, assign this user; otherwise do not change
                            if (empty($record->id_hsse)) {
                                $updateData['id_hsse'] = $user->id;
                                $updateData['hsse_review_started_at'] = now();
                            } elseif ((int) $record->id_hsse !== (int) $user->id) {
                                abort(403, 'Dokumen ini hanya dapat direview oleh HSSE yang sebelumnya ditugaskan.');
                            }
                        } elseif ($user->hasAnyRole(['S&D','SND'])) {
                            if (empty($record->id_snd)) {
                                $updateData['id_snd'] = $user->id;
                                $updateData['snd_review_started_at'] = now();
                            } elseif ((int) $record->id_snd !== (int) $user->id) {
                                abort(403, 'Dokumen ini hanya dapat direview oleh S&D yang sebelumnya ditugaskan.');
                            }
                        }

                        if (!empty($updateData)) {
                            $record->update($updateData);
                        }
                        // For HSSE, open view page in review mode; S&D unchanged behavior
                        if ($user->hasRole('HSSE')) {
                            $url = static::getUrl('view', ['record' => $record]);
                            return redirect($url . '?review=1');
                        } elseif ($user->hasAnyRole(['S&D','SND'])) {
                            $url = static::getUrl('view', ['record' => $record]);
                            return redirect($url . '?review=1');
                        }
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
