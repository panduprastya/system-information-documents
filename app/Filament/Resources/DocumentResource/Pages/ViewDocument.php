<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\ImageEntry;
use Illuminate\Support\Facades\URL;

class ViewDocument extends ViewRecord
{
	protected static string $resource = DocumentResource::class;

	protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
	{
		$record = parent::resolveRecord($key);

		// Eager load relationships to prevent N+1 queries
		$record->load(['mitra', 'hsse', 'snd', 'hsseComments.user', 'sndComments.user']);

		$user = auth()->user();

		// Jika user adalah Mitra, pastikan mereka hanya dapat melihat dokumen mereka sendiri
		if ($user && $user->hasRole('Mitra') && $record->id_mitra !== $user->id) {
			abort(403, 'Anda tidak dapat melihat dokumen yang bukan milik Anda.');
		}

		return $record;
	}

	protected function getHeaderActions(): array
	{
		$user = auth()->user();
		$isMitra = $user && $user->hasRole('Mitra');

		return [
			Actions\Action::make('back')
				->label('Kembali')
				->icon('heroicon-o-arrow-left')
				->url(fn() => url()->previous())
				->color('gray'),

			Actions\EditAction::make()
				->visible(function () use ($isMitra) {
					// Hide for Mitra
					if ($isMitra) {
						return false;
					}

					$user = auth()->user();
					// Hide for HSSE on view page
					if ($user && $user->hasRole('HSSE')) {
						return false;
					}
					// Admin cannot edit here
					if ($user && $user->hasRole('Admin')) {
						return false;
					}
					// Mitra follows same rule as list page
					if ($user && $user->hasRole('Mitra')) {
						$hsse = $this->record->hsse_status;
						$snd = $this->record->snd_status;
						$allowed = fn(string $s) => in_array($s, ['pending', 'revisi'], true);
						$blocked = fn(string $s) => in_array($s, ['reviewing', 'rejected'], true);
						if ($blocked($hsse) || $blocked($snd))
							return false;
						if ($hsse === 'approved' && $snd === 'approved')
							return false;
						return $allowed($hsse) || $allowed($snd);
					}
					return false;
				}),

			Actions\Action::make('download')
				->label('Download PDF')
				->icon('heroicon-o-arrow-down-tray')
				->url(fn() => route('documents.download', ['document' => $this->record->getKey()]))
				->openUrlInNewTab()
				->visible(function () use ($isMitra) {
					$user = auth()->user();
					return $user && $user->hasRole('Mitra') &&
						$this->record->hsse_status === 'approved' && $this->record->snd_status === 'approved';
				}),

			Actions\Action::make('revisi')
				->label('Revisi')
				->color('warning')
				->icon('heroicon-o-arrow-path')
				->url(fn() => DocumentResource::getUrl('edit', ['record' => $this->record->getKey()]))
				->visible(function () use ($isMitra) {
					// Hide for Mitra
					if ($isMitra) {
						return false;
					}

					$user = auth()->user();
					if ($user->hasRole('HSSE')) {
						return request()->boolean('review') && in_array($this->record->hsse_status, ['pending', 'reviewing']) && (empty($this->record->id_hsse) || (int) $this->record->id_hsse === (int) $user->id);
					} elseif ($user->hasAnyRole(['S&D', 'SND'])) {
						return request()->boolean('review') && in_array($this->record->snd_status, ['pending', 'reviewing']) && (empty($this->record->id_snd) || (int) $this->record->id_snd === (int) $user->id);
					}
					return false;
				}),

			Actions\Action::make('approve')
				->label('Approve')
				->color('success')
				->icon('heroicon-o-check-circle')
				->url(fn() => route('documents.approve', $this->record))
				->openUrlInNewTab(false)
				->requiresConfirmation()
				->modalHeading('Approve Document')
				->modalDescription('Are you sure you want to approve this document?')
				->visible(function () use ($isMitra) {
					// Hide for Mitra
					if ($isMitra) {
						return false;
					}

					$user = auth()->user();
					if ($user->hasRole('HSSE')) {
						return request()->boolean('review')
							&& in_array($this->record->hsse_status, ['pending', 'reviewing'])
							&& (empty($this->record->id_hsse) || (int) $this->record->id_hsse === (int) $user->id);
					} elseif ($user->hasAnyRole(['S&D', 'SND'])) {
						return request()->boolean('review')
							&& in_array($this->record->snd_status, ['pending', 'reviewing'])
							&& (empty($this->record->id_snd) || (int) $this->record->id_snd === (int) $user->id);
					}
					return false;
				}),

			Actions\Action::make('reject_document')
				->label('Rejected')
				->color('danger')
				->icon('heroicon-o-x-circle')
				->modalHeading('Reject Document')
				->modalDescription('Silakan berikan alasan penolakan dokumen ini.')
				->modalSubmitActionLabel('Reject')
				->modalCancelActionLabel('Cancel')
				->modalWidth('md')
				->form([
					\Filament\Forms\Components\Textarea::make('rejection_reason')
						->label('Alasan Penolakan')
						->placeholder('Masukkan alasan penolakan dokumen...')
						->required()
						->rows(5)
						->maxLength(1000)
						->helperText('Alasan ini akan disimpan sebagai komentar pada dokumen.'),
				])
				->action(function (array $data): void {
					$record = $this->record;
					$user = auth()->user();
					$reason = $data['rejection_reason'] ?? '';

					if ($user->hasRole('HSSE')) {
						if (empty($record->id_hsse)) {
							$record->id_hsse = $user->id;
						}

						$record->hsse_status = 'rejected';
						$record->save();

						$record->hsseComments()->create([
							'user_id' => $user->id,
							'komentar' => $reason,
							'status_after' => 'rejected',
						]);

						\Filament\Notifications\Notification::make()
							->title('Document HSSE Status Rejected')
							->body('Dokumen berhasil ditolak.')
							->success()
							->send();

					} elseif ($user->hasAnyRole(['S&D', 'SND'])) {
						if (empty($record->id_snd)) {
							$record->id_snd = $user->id;
						}

						$record->snd_status = 'rejected';
						$record->save();

						$record->sndComments()->create([
							'user_id' => $user->id,
							'komentar' => $reason,
							'status_after' => 'rejected',
						]);

						\Filament\Notifications\Notification::make()
							->title('Document S&D Status Rejected')
							->body('Dokumen berhasil ditolak.')
							->success()
							->send();
					}

					$this->redirect(DocumentResource::getUrl('index'));
				})
				->visible(function () use ($isMitra) {
					// Hide for Mitra
					if ($isMitra) {
						return false;
					}

					$user = auth()->user();
					if ($user->hasRole('HSSE')) {
						return request()->boolean('review') && in_array($this->record->hsse_status, ['pending', 'reviewing']) && (empty($this->record->id_hsse) || (int) $this->record->id_hsse === (int) $user->id);
					} elseif ($user->hasAnyRole(['S&D', 'SND'])) {
						return request()->boolean('review') && in_array($this->record->snd_status, ['pending', 'reviewing']) && (empty($this->record->id_snd) || (int) $this->record->id_snd === (int) $user->id);
					}
					return false;
				}),

		];
	}

	public function infolist(Infolist $infolist): Infolist
	{
		return $infolist
			->schema([
				Section::make('Document Details')
					->schema([
						TextEntry::make('judul_dokumen')
							->label('Document Title'),
						TextEntry::make('mitra.name')
							->label('Partner Name'),
						TextEntry::make('hsse_status')
							->label('HSSE Status')
							->badge()
							->color(fn(string $state): string => match ($state) {
								'reviewing' => 'warning',
								'approved' => 'success',
								'pending' => 'gray',
								'rejected' => 'danger',
								'revisi' => 'info',
							}),
						TextEntry::make('snd_status')
							->label('S&D Status')
							->badge()
							->color(fn(string $state): string => match ($state) {
								'reviewing' => 'warning',
								'approved' => 'success',
								'pending' => 'gray',
								'rejected' => 'danger',
								'revisi' => 'info',
							}),
						TextEntry::make('tanggal_upload')
							->label('Upload Date')
							->dateTime(),
						TextEntry::make('hsse_review_started_at')
							->label('HSSE Review Started')
							->dateTime()
							->hidden(fn($record) => empty($record->hsse_review_started_at)),
						TextEntry::make('snd_review_started_at')
							->label('SND Review Started')
							->dateTime()
							->hidden(fn($record) => empty($record->snd_review_started_at)),
						TextEntry::make('keterangan')
							->label('Keterangan Dokumen')
							->columnSpanFull()
							->markdown()
							->hidden(fn($record) => empty($record->keterangan))
							->visible(auth()->user()->hasRole('Mitra')),
					])
					->columns(2),

				Section::make('PDF Preview')
					->schema([
						\Filament\Infolists\Components\View::make('filament.resources.document-resource.pages.pdf-viewer')
							->viewData(['pdfUrl' => $this->record->file]),
					])
					->collapsible()
					->columnSpanFull(),

				Section::make('HSSE Comments')
					->schema([
						\Filament\Infolists\Components\RepeatableEntry::make('hsseComments')
							->label('')
							->schema([
								\Filament\Infolists\Components\TextEntry::make('user.name')
									->label('Reviewer')
									->icon('heroicon-m-user'),
								\Filament\Infolists\Components\TextEntry::make('created_at')
									->label('Waktu')
									->dateTime(),
								\Filament\Infolists\Components\TextEntry::make('komentar')
									->label('Komentar')
									->markdown()
									->columnSpanFull(),
							])
							->columns(2),
					]),

				Section::make('S&D Comments')
					->schema([
						\Filament\Infolists\Components\RepeatableEntry::make('sndComments')
							->label('')
							->schema([
								\Filament\Infolists\Components\TextEntry::make('user.name')
									->label('Reviewer')
									->icon('heroicon-m-user'),
								\Filament\Infolists\Components\TextEntry::make('created_at')
									->label('Waktu')
									->dateTime(),
								\Filament\Infolists\Components\TextEntry::make('komentar')
									->label('Komentar')
									->markdown()
									->columnSpanFull(),
							])
							->columns(2),
					]),


			]);
	}

}