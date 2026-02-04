<?php
namespace App\Filament\Resources\DocumentResource\Pages;

use Filament\Actions;
use Filament\Forms\Form;
use App\Models\HsseComment;
use App\Models\SndComment;
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
        $record->load(['mitra', 'hsse', 'snd', 'hsseComments.user', 'sndComments.user']);

        $user = auth()->user();

        // Jika user adalah Mitra, pastikan mereka hanya dapat mengedit dokumen mereka sendiri
        if ($user && $user->hasRole('Mitra') && $record->id_mitra !== $user->id) {
            abort(403, 'Anda tidak dapat mengedit dokumen yang bukan milik Anda.');
        }

        // Jika user adalah Mitra, izinkan edit jika:
        // - Kedua status pending/revisi, atau
        // - Salah satu approved dan yang lain pending/revisi
        // Tidak diizinkan jika kedua approved atau salah satu reviewing/rejected
        if ($user && $user->hasRole('Mitra')) {
            $hsse = $record->hsse_status;
            $snd = $record->snd_status;
            $isPendingOrRevisi = in_array($hsse, ['pending', 'revisi'], true) || in_array($snd, ['pending', 'revisi'], true);
            $anyBlocked = in_array($hsse, ['reviewing', 'rejected'], true) || in_array($snd, ['reviewing', 'rejected'], true);
            $bothApproved = ($hsse === 'approved') && ($snd === 'approved');
            if (!$isPendingOrRevisi || $anyBlocked || $bothApproved) {
                abort(403, 'Dokumen ini tidak dapat diedit. Mitra dapat mengedit jika ada status pending/revisi dan tidak ada status reviewing/rejected, serta tidak keduanya approved.');
            }
        }

        return $record;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        // Jika user adalah Mitra, pastikan id_mitra tidak berubah
        if ($user && $user->hasRole('Mitra')) {
            $data['id_mitra'] = $user->id;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        // Redirect ke halaman list dokumen setelah save changes
        return $this->getResource()::getUrl('index');
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
                                    ->disabled(fn() => auth()->user()->hasRole('HSSE') || auth()->user()->hasRole('S&D'))
                                    ->dehydrated(fn() => !(auth()->user()->hasRole('HSSE') || auth()->user()->hasRole('S&D'))),
                                FileUpload::make('file')
                                    ->label('PDF File')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->required()
                                    ->maxSize(10240)
                                    ->helperText('Upload a PDF file (max 10MB)')
                                    ->disabled(fn() => auth()->user()->hasRole('HSSE') || auth()->user()->hasRole('S&D'))
                                    ->dehydrated(fn() => !(auth()->user()->hasRole('HSSE') || auth()->user()->hasRole('S&D'))),
                                Textarea::make('keterangan')
                                    ->label('Keterangan Dokumen')
                                    ->placeholder('Masukkan keterangan tambahan untuk dokumen ini...')
                                    ->rows(4)
                                    ->maxLength(5000)
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

                                        $html = '<div class="grid grid-cols-2 gap-4">';

                                        // HSSE Status
                                        $hsseColor = match ($record->hsse_status) {
                                            'approved' => 'bg-green-100 text-green-800 border-green-200',
                                            'rejected' => 'bg-red-100 text-red-800 border-red-200',
                                            'revisi' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                            'reviewing' => 'bg-blue-100 text-blue-800 border-blue-200',
                                            default => 'bg-gray-100 text-gray-800 border-gray-200'
                                        };

                                        $html .= '<div class="p-3 border rounded-lg ' . $hsseColor . '">';
                                        $html .= '<div class="font-semibold">HSSE Status</div>';
                                        $html .= '<div class="text-sm">' . ucfirst($record->hsse_status ?? 'pending') . '</div>';
                                        $html .= '</div>';

                                        // SND Status
                                        $sndColor = match ($record->snd_status) {
                                            'approved' => 'bg-green-100 text-green-800 border-green-200',
                                            'rejected' => 'bg-red-100 text-red-800 border-red-200',
                                            'revisi' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                            'reviewing' => 'bg-blue-100 text-blue-800 border-blue-200',
                                            default => 'bg-gray-100 text-gray-800 border-gray-200'
                                        };

                                        $html .= '<div class="p-3 border rounded-lg ' . $sndColor . '">';
                                        $html .= '<div class="font-semibold">S&D Status</div>';
                                        $html .= '<div class="text-sm">' . ucfirst($record->snd_status ?? 'pending') . '</div>';
                                        $html .= '</div>';

                                        $html .= '</div>';

                                        return new \Illuminate\Support\HtmlString($html);
                                    })
                                    ->columnSpanFull()
                                    ->visible(auth()->user()->hasRole('Mitra')),

                                // Placeholder::make('revision_info')
                                //     ->label('Alasan Revisi')
                                //     ->content(function ($record) {
                                //         if (!$record || !$record->needsRevision()) {
                                //             return 'Dokumen ini tidak memerlukan revisi.';
                                //         }

                                //         $reasons = $record->getRevisionReasons();
                                //         if (empty($reasons)) {
                                //             return 'Tidak ada alasan revisi yang tersedia.';
                                //         }

                                //         $html = '<div class="space-y-2">';
                                //         foreach ($reasons as $reason) {
                                //             $html .= '<div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">';
                                //             $html .= '<div class="font-semibold text-yellow-800">' . $reason['type'] . ' Review</div>';
                                //             $html .= '<div class="text-sm text-yellow-700 mt-1">' . nl2br(e($reason['comment'])) . '</div>';
                                //             $html .= '<div class="text-xs text-yellow-600 mt-2">Reviewer: ' . e($reason['reviewer']) . ' | ' . $reason['date']->format('d/m/Y H:i') . '</div>';
                                //             $html .= '</div>';
                                //         }
                                //         $html .= '</div>';

                                //         return new \Illuminate\Support\HtmlString($html);
                                //     })
                                //     ->columnSpanFull()
                                //     ->visible(auth()->user()->hasRole('Mitra')),
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
                            ->columnSpan(['lg' => 2]),

                        Section::make('S&D Comments')
                            ->schema([
                                Textarea::make('new_snd_comment')
                                    ->label('Add New S&D Comment')
                                    ->placeholder('Enter your comment or feedback about this document...')
                                    ->rows(4)
                                    ->maxLength(2000)
                                    ->columnSpanFull()
                                    ->visible(auth()->user()->hasRole('S&D')),

                                Repeater::make('existing_snd_comments')
                                    ->label('Previous S&D Comments')
                                    ->relationship('sndComments')
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
                            ->columnSpan(['lg' => 2]),
                    ]),
            ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Extract new comments before parent update
        $newHsseComment = $data['new_hsse_comment'] ?? null;
        $newSndComment = $data['new_snd_comment'] ?? null;
        unset($data['new_hsse_comment']);
        unset($data['new_snd_comment']);

        // Update the document first
        $record = parent::handleRecordUpdate($record, $data);

        // Jika user adalah Mitra, ubah status HSSE dan S&D menjadi pending
        $currentUser = Auth::user();
        if ($currentUser->hasRole('Mitra')) {
            $statusChanged = false;

            // Ubah status HSSE menjadi pending jika saat ini adalah 'revisi'
            if ($record->hsse_status === 'revisi') {
                $record->hsse_status = 'pending';
                $statusChanged = true;
            }

            // Ubah status SND menjadi pending jika saat ini adalah 'revisi'
            if ($record->snd_status === 'revisi') {
                $record->snd_status = 'pending';
                $statusChanged = true;
            }

            // Reset review timestamps jika status berubah (tetap pertahankan id_hsse dan id_snd)
            if ($statusChanged) {
                $record->hsse_review_started_at = null;
                $record->snd_review_started_at = null;
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
                'document_id' => $record->getKey(),
                'user_id' => $currentUserId,
                'komentar' => $newHsseComment,
            ]);

            // Update HSSE status to revisi and set HSSE user when comment is saved
            $record->update([
                'hsse_status' => 'revisi',
                'id_hsse' => $currentUserId
            ]);

            // Tampilkan notifikasi atau pesan bahwa HSSE telah memberikan revisi
            Notification::make()
                ->success()
                ->title('Revisi Berhasil')
                ->body('Revisi berhasil dikirim oleh ' . $currentUser->name)
                ->send();
        }

        // Save new S&D comment if provided and user has S&D role
        if (!empty($newSndComment) && $currentUser->hasRole('S&D')) {
            sndComment::create([
                'document_id' => $record->getKey(),
                'user_id' => $currentUserId,
                'komentar' => $newSndComment,
            ]);

            // Update S&D status to revisi and set S&D user when comment is saved
            $record->update([
                'snd_status' => 'revisi',
                'id_snd' => $currentUserId
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
