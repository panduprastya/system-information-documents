<?php
namespace App\Filament\Resources\DocumentResource\Pages;

use Filament\Actions;
use Filament\Forms\Form;
use App\Models\HsseComment;
use App\Models\CrmComment;
use App\Models\document;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\DocumentResource;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::resolveRecord($key);

        // Eager load relationships to prevent N+1 queries
        $record->load(['mitra', 'hsseComments.user', 'crmComments.user']);

        $user = auth()->user();

        // Jika user adalah Mitra, pastikan mereka hanya dapat mengedit dokumen mereka sendiri
        if ($user && $user->hasRole('Mitra') && $record->id_user !== $user->id_user) {
            abort(403, 'Anda tidak dapat mengedit dokumen yang bukan milik Anda.');
        }

        // Jika user adalah Mitra, izinkan edit jika:
        // - Kedua status pending/revisi/rejected, atau
        // - Salah satu approved dan yang lain pending/revisi/rejected
        // Tidak diizinkan jika kedua approved atau salah satu reviewing
        if ($user && $user->hasRole('Mitra')) {
            $hsse = $record->hsse_status;
            $crm = $record->crm_status;
            $canEdit = in_array($hsse, ['pending', 'revisi', 'rejected'], true) || in_array($crm, ['pending', 'revisi', 'rejected'], true);
            $anyReviewing = in_array($hsse, ['reviewing'], true) || in_array($crm, ['reviewing'], true);
            $bothApproved = ($hsse === 'approved') && ($crm === 'approved');
            if (!$canEdit || $anyReviewing || $bothApproved) {
                abort(403, 'Dokumen ini tidak dapat diedit. Mitra dapat mengedit jika ada status pending/revisi/rejected dan tidak ada status reviewing, serta tidak keduanya approved.');
            }
        }

        return $record;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        // Jika user adalah Mitra, pastikan id_user tidak berubah
        if ($user && $user->hasRole('Mitra')) {
            $data['id_user'] = $user->id_user;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(function () {
                    $user = auth()->user();
                    if ($user && ($user->hasRole('HSSE') || $user->hasRole('CRM'))) {
                        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
                    }
                    return $this->getResource()::getUrl('index');
                }),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        // Redirect ke halaman list dokumen setelah save changes
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        $user = auth()->user();

        // Jika user adalah Mitra, tambahkan konfirmasi pada Save Changes
        if ($user && $user->hasRole('Mitra')) {
            return [
                \Filament\Actions\Action::make('save')
                    ->label('Simpan Perubahan')
                    ->requiresConfirmation()
                    ->modalHeading('⚠️ Konfirmasi Perubahan Dokumen')
                    ->modalDescription('Pastikan semua perubahan yang Anda buat sudah benar sebelum menyimpan. Setelah disimpan, dokumen akan dikembalikan ke status "Pending" untuk direview ulang oleh reviewer. Apakah Anda yakin ingin menyimpan perubahan ini?')
                    ->modalSubmitActionLabel('Ya, Simpan Perubahan')
                    ->modalCancelActionLabel('Batal')
                    ->modalIcon('heroicon-o-arrow-path')
                    ->modalIconColor('warning')
                    ->color('warning')
                    ->action(function () {
                        // Validate and get form data
                        $data = $this->form->getState();

                        // Update the record
                        $this->record = $this->handleRecordUpdate($this->record, $data);

                        // Show success notification (already handled in handleRecordUpdate)
        
                        // Redirect to index
                        $this->redirect($this->getRedirectUrl());
                    }),
                \Filament\Actions\Action::make('cancel')
                    ->label('Batal')
                    ->url($this->getResource()::getUrl('index'))
                    ->color('gray'),
            ];
        }

        // Jika user adalah HSSE, tambahkan konfirmasi pada Save
        if ($user && $user->hasRole('HSSE')) {
            return [
                \Filament\Actions\Action::make('save')
                    ->label('Kirim Komentar')
                    ->color('primary')
                    ->action(function () {
                        $this->save();
                    }),
                \Filament\Actions\Action::make('cancel')
                    ->label('Batal')
                    ->url(function () {
                        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
                    })
                    ->color('gray'),
            ];
        }

        // Jika user adalah CRM, tambahkan konfirmasi pada Save
        if ($user && $user->hasRole('CRM')) {
            return [
                \Filament\Actions\Action::make('save')
                    ->label('Kirim Komentar')
                    ->color('primary')
                    ->action(function () {
                        $this->save();
                    }),
                \Filament\Actions\Action::make('cancel')
                    ->label('Batal')
                    ->url(function () {
                        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
                    })
                    ->color('gray'),
            ];
        }

        return parent::getFormActions();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->schema([
                        Section::make('Document Information')
                            ->schema([
                                TextInput::make('judul_dokumen')
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled(fn() => auth()->user()->hasRole('HSSE') || auth()->user()->hasRole('CRM'))
                                    ->dehydrated(fn() => !(auth()->user()->hasRole('HSSE') || auth()->user()->hasRole('CRM'))),
                                FileUpload::make('file')
                                    ->label('PDF File')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->required()
                                    ->maxSize(10240)
                                    ->helperText('Upload a PDF file (max 10MB)')
                                    ->disabled(fn() => auth()->user()->hasRole('HSSE') || auth()->user()->hasRole('CRM'))
                                    ->dehydrated(fn() => !(auth()->user()->hasRole('HSSE') || auth()->user()->hasRole('CRM'))),
                                Textarea::make('keterangan')
                                    ->label('Keterangan Dokumen')
                                    ->placeholder('Masukkan keterangan tambahan untuk dokumen ini...')
                                    ->rows(4)
                                    ->maxLength(5000)
                                    ->required()
                                    ->columnSpanFull()
                                    ->visible(auth()->user()->hasRole('Mitra')),
                            ])
                            ->columnSpan(['lg' => 2]),

                        Section::make('Revision Information')
                            ->schema([
                                Placeholder::make('document_status')
                                    ->label('Status Dokumen')
                                    ->content(function ($record) {
                                        if (!$record)
                                            return 'Status tidak tersedia';

                                        // Untuk dokumen HSSE, hanya tampilkan HSSE status
                                        if ($record->tipe_dokumen === 'hsse') {
                                            $hsseColor = match ($record->hsse_status) {
                                                'approved' => 'bg-green-100 text-green-800 border-green-200',
                                                'rejected' => 'bg-red-100 text-red-800 border-red-200',
                                                'revisi' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                                'reviewing' => 'bg-blue-100 text-blue-800 border-blue-200',
                                                default => 'bg-gray-100 text-gray-800 border-gray-200'
                                            };

                                            $html = '<div class="p-4 border rounded-lg ' . $hsseColor . '">';
                                            $html .= '<div class="font-semibold text-lg">HSSE Status</div>';
                                            $html .= '<div class="text-sm mt-1">' . ucfirst($record->hsse_status ?? 'pending') . '</div>';
                                            $html .= '</div>';

                                            return new \Illuminate\Support\HtmlString($html);
                                        }

                                        // Untuk dokumen CRM, hanya tampilkan CRM status
                                        if ($record->tipe_dokumen === 'crm') {
                                            $crmColor = match ($record->crm_status) {
                                                'approved' => 'bg-green-100 text-green-800 border-green-200',
                                                'rejected' => 'bg-red-100 text-red-800 border-red-200',
                                                'revisi' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                                'reviewing' => 'bg-blue-100 text-blue-800 border-blue-200',
                                                default => 'bg-gray-100 text-gray-800 border-gray-200'
                                            };

                                            $html = '<div class="p-4 border rounded-lg ' . $crmColor . '">';
                                            $html .= '<div class="font-semibold text-lg">CRM Status</div>';
                                            $html .= '<div class="text-sm mt-1">' . ucfirst($record->crm_status ?? 'pending') . '</div>';
                                            $html .= '</div>';

                                            return new \Illuminate\Support\HtmlString($html);
                                        }

                                        return 'Status tidak tersedia';
                                    })
                                    ->columnSpanFull()
                                    ->visible(auth()->user()->hasRole('Mitra')),


                            ])
                            ->columnSpan(['lg' => 2])
                            ->visible(auth()->user()->hasRole('Mitra')),

                        Section::make('PDF Preview')
                            ->schema([
                                Placeholder::make('pdf_preview')
                                    ->label('')
                                    ->content(function ($record) {
                                        if ($record && $record->file) {
                                            return view('filament.resources.document-resource.pages.pdf-preview', [
                                                'pdfUrl' => $record->file,
                                            ]);
                                        }
                                        return 'No PDF file uploaded yet.';
                                    }),
                            ])
                            ->columnSpanFull(),

                        Section::make('HSSE Comments')
                            ->schema([
                                Textarea::make('new_hsse_comment')
                                    ->label('Add New HSSE Comment')
                                    ->placeholder('Enter your comment or feedback about this document...')
                                    ->rows(4)
                                    ->maxLength(2000)
                                    ->columnSpanFull()
                                    ->visible(auth()->user()->hasRole('HSSE')),

                                Repeater::make('existing_hsse_comments')
                                    ->label('Previous HSSE Comments')
                                    ->relationship('hsseComments')
                                    ->schema([
                                        Textarea::make('komentar')
                                            ->label('Comment')
                                            ->rows(3)
                                            ->disabled()
                                            ->columnSpanFull(),
                                        Placeholder::make('reviewer_name')
                                            ->label('Commented By')
                                            ->content(fn($record) => $record->user->name ?? 'Unknown User'),
                                        Placeholder::make('commented_at')
                                            ->label('Commented At')
                                            ->content(fn($record) => $record->created_at?->format('d/m/Y H:i') ?? '-'),
                                    ])
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false)
                                    ->dehydrated(false)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(['lg' => 2])
                            ->visible(fn($record) => $record && $record->tipe_dokumen === 'hsse'),

                        Section::make('CRM Comments')
                            ->schema([
                                Textarea::make('new_snd_comment')
                                    ->label('Add New CRM Comment')
                                    ->placeholder('Enter your comment or feedback about this document...')
                                    ->rows(4)
                                    ->maxLength(2000)
                                    ->columnSpanFull()
                                    ->visible(auth()->user()->hasRole('CRM')),

                                Repeater::make('existing_snd_comments')
                                    ->label('Previous CRM Comments')
                                    ->relationship('crmComments')
                                    ->schema([
                                        Textarea::make('komentar')
                                            ->label('Comment')
                                            ->rows(3)
                                            ->disabled()
                                            ->columnSpanFull(),
                                        Placeholder::make('reviewer_name')
                                            ->label('Commented By')
                                            ->content(fn($record) => $record->user->name ?? 'Unknown User'),
                                        Placeholder::make('commented_at')
                                            ->label('Commented At')
                                            ->content(fn($record) => $record->created_at?->format('d/m/Y H:i') ?? '-'),
                                    ])
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false)
                                    ->dehydrated(false)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(['lg' => 2])
                            ->visible(fn($record) => $record && $record->tipe_dokumen === 'crm'),
                    ]),
            ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Extract new comments before parent update
        $newHsseComment = $data['new_hsse_comment'] ?? null;
        $newCrmComment = $data['new_snd_comment'] ?? null;
        unset($data['new_hsse_comment']);
        unset($data['new_snd_comment']);

        // Update the document first
        $record = parent::handleRecordUpdate($record, $data);

        // Jika user adalah Mitra, ubah status HSSE dan S&D menjadi pending
        $currentUser = Auth::user();
        if ($currentUser->hasRole('Mitra')) {
            $statusChanged = false;

            // Ubah status HSSE menjadi pending jika saat ini adalah 'revisi' atau 'rejected'
            if (in_array($record->hsse_status, ['revisi', 'rejected'])) {
                $record->hsse_status = 'pending';
                $statusChanged = true;
            }

            // Ubah status CRM menjadi pending jika saat ini adalah 'revisi' atau 'rejected'
            if (in_array($record->crm_status, ['revisi', 'rejected'])) {
                $record->crm_status = 'pending';
                $statusChanged = true;
            }

            if ($statusChanged) {
                // Tidak menyimpan timestamp review tambahan karena tabel dokumen tidak memiliki kolom tersebut
            }

            $record->save();

            if ($statusChanged) {
                Notification::make()
                    ->success()
                    ->title('Dokumen Diperbarui')
                    ->body('Dokumen berhasil diperbarui dan status diubah menjadi pending untuk review ulang')
                    ->send();
            } else {
                Notification::make()
                    ->success()
                    ->title('Dokumen Diperbarui')
                    ->body('Dokumen berhasil diperbarui')
                    ->send();
            }
        }

        $currentUser = Auth::user();
        $currentUserId = Auth::id();

        // Save new HSSE comment if provided and user has HSSE role
        if (!empty($newHsseComment) && $currentUser->hasRole('HSSE')) {
            HsseComment::create([
                'id_hsse_comment' => 'HC' . strtoupper(\Illuminate\Support\Str::random(8)),
                'id_document' => $record->getKey(),
                'id_user' => $currentUserId,
                'komentar' => $newHsseComment,
            ]);

            // Update HSSE status to revisi when comment is saved
            $record->update([
                'hsse_status' => 'revisi',
            ]);

            // Tampilkan notifikasi atau pesan bahwa HSSE telah memberikan revisi
            Notification::make()
                ->success()
                ->title('Revisi Berhasil')
                ->body('Revisi berhasil dikirim oleh ' . $currentUser->name)
                ->send();
        }

        // Save new CRM comment if provided and user has S&D role
        if (!empty($newCrmComment) && $currentUser->hasRole('CRM')) {
            CrmComment::create([
                'id_crm_comment' => 'CC' . strtoupper(\Illuminate\Support\Str::random(8)),
                'id_document' => $record->getKey(),
                'id_user' => $currentUserId,
                'komentar' => $newCrmComment,
            ]);

            // Update CRM status to revisi when comment is saved
            $record->update([
                'crm_status' => 'revisi',
            ]);

            // Tampilkan notifikasi atau pesan bahwa S&D telah memberikan revisi
            Notification::make()
                ->success()
                ->title('Revisi Berhasil')
                ->body('Revisi berhasil dikirim oleh ' . $currentUser->name)
                ->send();
        }

        return $record;
    }
}

