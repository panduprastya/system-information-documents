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
                Forms\Components\Select::make('tipe_dokumen')
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
                Tables\Columns\TextColumn::make('tipe_dokumen')
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
                        // Return status sesuai tipe_dokumen
                        return $record->tipe_dokumen === 'hsse'
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
                        // Sort berdasarkan tipe_dokumen
                        $query->orderByRaw("
                            CASE 
                                WHEN tipe_dokumen = 'hsse' THEN hsse_status 
                                ELSE crm_status 
                            END {$direction}
                        ");
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
                        if ($record->tipe_dokumen === 'hsse') {
                            return $record->hsse_status === 'approved';
                        }

                        // Untuk dokumen CRM, hanya cek crm_status
                        if ($record->tipe_dokumen === 'crm') {
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
                            if ($record->tipe_dokumen === 'hsse') {
                                $status = $record->hsse_status;
                                // Hanya bisa edit jika status revisi
                                return $status === 'revisi';
                            }

                            // Untuk dokumen CRM, hanya cek crm_status
                            if ($record->tipe_dokumen === 'crm') {
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
                            if ($record->tipe_dokumen !== 'hsse') {
                                return false;
                            }

                            // Cek status
                            return in_array($record->hsse_status, ['pending', 'reviewing']);
                        }

                        // CRM can only review CRM documents
                        if ($user->hasRole('CRM')) {
                            // Cek apakah dokumen adalah tipe CRM
                            if ($record->tipe_dokumen !== 'crm') {
                                return false;
                            }

                            // Cek status
                            return in_array($record->crm_status, ['pending', 'reviewing']);
                        }

                        // Other roles cannot review
                        return false;
                    })
                    ->action(function (Document $record) {
                        $user = auth()->user();
                        $updateData = [];

                        if ($user->hasRole('HSSE')) {
                            $updateData['hsse_status'] = 'reviewing';
                        } elseif ($user->hasRole('CRM')) {
                            $updateData['crm_status'] = 'reviewing';
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
            // Hanya ambil kolom yang dibutuhkan tabel list — jangan fetch field tidak ada
            ->select([
                'document.id_document',
                'document.id_user',
                'document.judul_dokumen',
                'document.tipe_dokumen',
                'document.keterangan',
                'document.hsse_status',
                'document.crm_status',
                'document.tanggal_upload',
                'document.file',
            ])
            ->with(['mitra:id_user,name']);

        $user = auth()->user();

        // Jika user adalah Mitra, hanya tampilkan dokumen yang mereka buat.
        // Catatan: Document model sudah punya Global Scope MitraDocumentScope,
        // jadi filter ini hanya diperlukan jika scope tidak aktif.
        if ($user && $user->hasRole('Mitra')) {
            $query->where('id_user', $user->id_user);
        }

        // Jika user adalah HSSE, hanya tampilkan dokumen tipe HSSE
        if ($user && $user->hasRole('HSSE')) {
            $query->where('tipe_dokumen', 'hsse');
        }

        // Jika user adalah CRM, hanya tampilkan dokumen tipe CRM
        if ($user && $user->hasRole('CRM')) {
            $query->where('tipe_dokumen', 'crm');
        }

        return $query;
    }
}

