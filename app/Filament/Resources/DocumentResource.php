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
                    ->label('Judul Dokumen')
                    ->placeholder('Masukkan judul dokumen yang jelas dan deskriptif')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Contoh: Laporan Keselamatan Kerja Bulan Januari 2026')
                    ->columnSpanFull(),
                Forms\Components\Select::make('document_type')
                    ->label('Tipe Dokumen')
                    ->options([
                        'hsse' => 'HSSE (Health, Safety, Security & Environment)',
                        'crm' => 'CRM (Channel Reliability Management)',
                    ])
                    ->required()
                    ->default('hsse')
                    ->helperText(new \Illuminate\Support\HtmlString('<span style="color: #dc2626; font-size: 14px; font-weight: 600;">⚠️ PENTING: Pilih tipe dokumen yang sesuai. Dokumen HSSE akan direview oleh tim HSSE, sedangkan dokumen CRM akan direview oleh tim CRM.</span>'))
                    ->visible($isMitra)
                    ->columnSpanFull(),
                FileUpload::make('file')
                    ->label('File Dokumen (PDF)')
                    ->required()
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(10240) // Maksimal 10 MB (dalam kilobytes)
                    ->helperText('📄 Upload file dalam format PDF dengan ukuran maksimal 10 MB')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('keterangan')
                    ->label('Keterangan Dokumen')
                    ->placeholder('Masukkan keterangan atau catatan tambahan untuk dokumen ini...')
                    ->rows(4)
                    ->maxLength(5000)
                    ->required()
                    ->helperText('Anda dapat menambahkan informasi tambahan seperti periode dokumen, tujuan, atau catatan khusus lainnya')
                    ->columnSpanFull()
                    ->visible($isMitra)
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
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Tipe Dokumen')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'hsse' => 'info',
                        'crm' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'hsse' => 'HSSE',
                        'crm' => 'CRM',
                        default => strtoupper($state),
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan Dokumen')
                    ->limit(60)
                    ->wrap()
                    ->toggleable()
                    ->visible(auth()->user()->hasRole('Mitra') || auth()->user()->hasRole('Admin') || auth()->user()->hasRole('HSSE') || auth()->user()->hasRole('CRM')),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(function (Document $record): string {
                        // Return status sesuai document_type
                        return $record->document_type === 'hsse'
                            ? $record->hsse_status
                            : $record->crm_status;
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'reviewing' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'revisi' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'reviewing' => 'heroicon-o-eye',
                        'approved' => 'heroicon-o-check-circle',
                        'rejected' => 'heroicon-o-x-circle',
                        'revisi' => 'heroicon-o-arrow-path',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable(query: function ($query, string $direction): void {
                        // Sort berdasarkan document_type
                        $query->orderByRaw("
                            CASE 
                                WHEN document_type = 'hsse' THEN hsse_status 
                                ELSE crm_status 
                            END {$direction}
                        ");
                    }),
                Tables\Columns\TextColumn::make('HSSE.name')
                    ->label('Nama HSSE')
                    ->searchable()
                    ->sortable()
                    ->visible(fn($record) => $record && $record->document_type === 'hsse'),
                Tables\Columns\TextColumn::make('crm.name')
                    ->label('Nama CRM')
                    ->searchable()
                    ->sortable()
                    ->visible(fn($record) => $record && $record->document_type === 'crm'),
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
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn(Document $record) => route('documents.download', ['document' => $record->getKey()]))
                    ->openUrlInNewTab()
                    ->visible(function (Document $record) {
                        $user = auth()->user();

                        if (!$user || !$user->hasRole('Mitra')) {
                            return false;
                        }

                        // Untuk dokumen HSSE, hanya cek hsse_status
                        if ($record->document_type === 'hsse') {
                            return $record->hsse_status === 'approved';
                        }

                        // Untuk dokumen CRM, hanya cek crm_status
                        if ($record->document_type === 'crm') {
                            return $record->crm_status === 'approved';
                        }

                        return false;
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(function (Document $record) {
                        $user = auth()->user();

                        // Admin cannot edit document
                        if ($user->hasRole('Admin')) {
                            return false;
                        }

                        // Mitra can edit based on document type
                        if ($user->hasRole('Mitra')) {
                            // Untuk dokumen HSSE, hanya cek hsse_status
                            if ($record->document_type === 'hsse') {
                                $status = $record->hsse_status;
                                // Hanya bisa edit jika status revisi
                                return $status === 'revisi';
                            }

                            // Untuk dokumen CRM, hanya cek crm_status
                            if ($record->document_type === 'crm') {
                                $status = $record->crm_status;
                                return $status === 'revisi';
                            }
                        }

                        // HSSE and CRM users cannot edit documents (they can only review)
                        return false;
                    }),
                Tables\Actions\Action::make('Review')
                    ->label('Review')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(function (Document $record) {
                        $user = auth()->user();

                        // HSSE can only review HSSE documents
                        if ($user->hasRole('HSSE')) {
                            // Cek apakah dokumen adalah tipe HSSE
                            if ($record->document_type !== 'hsse') {
                                return false;
                            }

                            // Cek status
                            if (!in_array($record->hsse_status, ['pending', 'reviewing'])) {
                                return false;
                            }

                            // If a previous HSSE reviewer exists, only allow that same user
                            if (!empty($record->id_hsse)) {
                                return (int) $record->id_hsse === (int) $user->id;
                            }
                            // If no previous reviewer, allow first HSSE reviewer to claim
                            return true;
                        }

                        // CRM can only review CRM documents
                        elseif ($user->hasRole('CRM')) {
                            // Cek apakah dokumen adalah tipe CRM
                            if ($record->document_type !== 'crm') {
                                return false;
                            }

                            // Cek status
                            if (!in_array($record->crm_status, ['pending', 'reviewing'])) {
                                return false;
                            }

                            if (!empty($record->id_crm)) {
                                return (int) $record->id_crm === (int) $user->id;
                            }
                            return true;
                        }

                        // Other roles cannot review
                        return false;
                    })
                    ->action(function (Document $record) {
                        $user = auth()->user();
                        $updateData = [];

                        if ($user->hasRole('HSSE')) {
                            // If there is no assigned HSSE yet, assign this user; otherwise do not change
                            if (empty($record->id_hsse)) {
                                $updateData['id_hsse'] = $user->id;
                                $updateData['hsse_review_started_at'] = now();
                                $updateData['hsse_status'] = 'reviewing'; // Ubah status menjadi reviewing
                            } elseif ((int) $record->id_hsse !== (int) $user->id) {
                                Notification::make()
                                    ->title('Akses Ditolak')
                                    ->body('Dokumen ini hanya dapat direview oleh HSSE yang sebelumnya ditugaskan.')
                                    ->danger()
                                    ->send();
                                return;
                            } else {
                                // Jika sudah assigned, tetap update status menjadi reviewing
                                $updateData['hsse_status'] = 'reviewing';
                            }
                        } elseif ($user->hasRole('CRM')) {
                            if (empty($record->id_crm)) {
                                $updateData['id_crm'] = $user->id;
                                $updateData['crm_review_started_at'] = now();
                                $updateData['crm_status'] = 'reviewing'; // Ubah status menjadi reviewing
                            } elseif ((int) $record->id_crm !== (int) $user->id) {
                                Notification::make()
                                    ->title('Akses Ditolak')
                                    ->body('Dokumen ini hanya dapat direview oleh CRM yang sebelumnya ditugaskan.')
                                    ->danger()
                                    ->send();
                                return;
                            } else {
                                // Jika sudah assigned, tetap update status menjadi reviewing
                                $updateData['crm_status'] = 'reviewing';
                            }
                        }

                        if (!empty($updateData)) {
                            $record->update($updateData);

                            Notification::make()
                                ->title('Review Dimulai')
                                ->success()
                                ->send();
                        }

                        // For HSSE and CRM, open view page in review mode
                        $url = static::getUrl('view', ['record' => $record]);

                        if ($user->hasRole('HSSE') || $user->hasRole('CRM')) {
                            return redirect()->to($url . '?review=1');
                        }

                        return redirect()->to($url);
                    })
            ])
            ->bulkActions([]);
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
            // Hanya ambil kolom yang dibutuhkan tabel list — jangan fetch 'file' (path PDF)
            ->select([
                'documents.id',
                'documents.judul_dokumen',
                'documents.document_type',
                'documents.keterangan',
                'documents.hsse_status',
                'documents.crm_status',
                'documents.id_mitra',
                'documents.id_hsse',
                'documents.id_crm',
                'documents.created_at',
                'documents.updated_at',
                'documents.deleted_at',
                'documents.file',
            ])
            ->with(['mitra:id,name', 'hsse:id,name', 'crm:id,name']);

        $user = auth()->user();

        // Jika user adalah Mitra, hanya tampilkan dokumen yang mereka buat
        if ($user && $user->hasRole('Mitra')) {
            $query->where('id_mitra', $user->id);
        }

        // Jika user adalah HSSE, hanya tampilkan dokumen tipe HSSE
        if ($user && $user->hasRole('HSSE')) {
            $query->where('document_type', 'hsse');
        }

        // Jika user adalah CRM, hanya tampilkan dokumen tipe CRM
        if ($user && $user->hasRole('CRM')) {
            $query->where('document_type', 'crm');
        }

        return $query;
    }
}
