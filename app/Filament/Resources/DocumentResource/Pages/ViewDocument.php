<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Services\ApprovalCertificateService;
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
		$record->load(['mitra', 'hsse', 'crm', 'hsseComments.user', 'crmComments.user']);

		$user = auth()->user();

		// Jika user adalah Mitra, pastikan mereka hanya dapat melihat dokumen mereka sendiri
		if ($user && $user->hasRole('Mitra') && $record->id_mitra !== $user->id) {
			abort(403, 'Anda tidak dapat melihat dokumen yang bukan milik Anda.');
		}

		// Jika user adalah HSSE, pastikan dokumen adalah tipe HSSE
		if ($user && $user->hasRole('HSSE') && $record->document_type !== 'hsse') {
			abort(403, 'Anda tidak memiliki akses ke dokumen CRM.');
		}

		// Jika user adalah CRM, pastikan dokumen adalah tipe CRM
		if ($user && $user->hasRole('CRM') && $record->document_type !== 'crm') {
			abort(403, 'Anda tidak memiliki akses ke dokumen HSSE.');
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
				->url(fn() => DocumentResource::getUrl('index'))
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
					// Mitra can edit based on document type
					if ($user && $user->hasRole('Mitra')) {
						// Untuk dokumen HSSE, hanya cek hsse_status
						if ($this->record->document_type === 'hsse') {
							$status = $this->record->hsse_status;
							return in_array($status, ['pending', 'revisi', 'rejected'], true);
						}

						// Untuk dokumen CRM, hanya cek crm_status
						if ($this->record->document_type === 'crm') {
							$status = $this->record->crm_status;
							return in_array($status, ['pending', 'revisi', 'rejected'], true);
						}
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

					if (!$user || !$user->hasRole('Mitra')) {
						return false;
					}

					// Untuk dokumen HSSE, hanya cek hsse_status
					if ($this->record->document_type === 'hsse') {
						return $this->record->hsse_status === 'approved';
					}

					// Untuk dokumen CRM, hanya cek crm_status
					if ($this->record->document_type === 'crm') {
						return $this->record->crm_status === 'approved';
					}

					return false;
				}),

			Actions\Action::make('download_certificate')
				->label('Download Lembar Pengesahan')
				->icon('heroicon-o-document-check')
				->color('success')
				->action(function (ApprovalCertificateService $certificateService) {
					try {
						$pdf = $certificateService->generateCertificate($this->record);
						$filename = 'Lembar_Pengesahan_' . str_replace(' ', '_', $this->record->judul_dokumen) . '.pdf';

						return response()->streamDownload(function () use ($pdf) {
							echo $pdf->output();
						}, $filename);
					} catch (\Exception $e) {
						\Filament\Notifications\Notification::make()
							->title('Error')
							->body($e->getMessage())
							->danger()
							->send();
					}
				})
				->visible(function () {
					// Check if document is fully approved
					if ($this->record->document_type === 'hsse') {
						return $this->record->hsse_status === 'approved';
					}

					if ($this->record->document_type === 'crm') {
						return $this->record->crm_status === 'approved';
					}

					return false;
				}),

			Actions\Action::make('revisi')
				->label('Tambah Komentar')
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
						return in_array($this->record->hsse_status, ['pending', 'reviewing']) && (empty($this->record->id_hsse) || (int) $this->record->id_hsse === (int) $user->id);
					} elseif ($user->hasRole('CRM')) {
						return in_array($this->record->crm_status, ['pending', 'reviewing']) && (empty($this->record->id_crm) || (int) $this->record->id_crm === (int) $user->id);
					}
					return false;
				}),

			Actions\Action::make('approve_document')
				->label('Approve')
				->color('success')
				->icon('heroicon-o-check-circle')
				->requiresConfirmation()
				->modalHeading('Konfirmasi Penyetujuan')
				->modalDescription('Apakah Anda yakin ingin menyetujui dokumen ini?')
				->modalSubmitActionLabel('Ya, Setujui')
				->modalCancelActionLabel('Batal')
				->action(function () {
					$record = $this->record;
					$user = auth()->user();

					if ($user->hasRole('HSSE')) {
						if (empty($record->id_hsse)) {
							$record->id_hsse = $user->id;
						}
						$record->hsse_status = 'approved';
						$record->id_hsse = $user->id;
						$record->save();

						\Filament\Notifications\Notification::make()
							->title('Document HSSE Status Approved')
							->success()
							->send();
					} elseif ($user->hasRole('CRM')) {
						if (empty($record->id_crm)) {
							$record->id_crm = $user->id;
						}
						$record->crm_status = 'approved';
						$record->id_crm = $user->id;
						$record->save();

						\Filament\Notifications\Notification::make()
							->title('Document CRM Status Approved')
							->success()
							->send();
					}

					return redirect()->to('/admin/documents');
				})
				->visible(function () use ($isMitra) {
					// Hide for Mitra
					if ($isMitra) {
						return false;
					}

					$user = auth()->user();
					if (!$user)
						return false;

					if ($user->hasRole('HSSE')) {
						return in_array($this->record->hsse_status, ['pending', 'reviewing'])
							&& (empty($this->record->id_hsse) || (int) $this->record->id_hsse === (int) $user->id);
					} elseif ($user->hasRole('CRM')) {
						return in_array($this->record->crm_status, ['pending', 'reviewing'])
							&& (empty($this->record->id_crm) || (int) $this->record->id_crm === (int) $user->id);
					}
					return false;
				}),

			Actions\Action::make('reject_document')
				->label('Reject')
				->color('danger')
				->icon('heroicon-o-x-circle')
				->url(fn() => route('documents.reject.form', $this->record))
				->visible(function () use ($isMitra) {
					// Hide for Mitra
					if ($isMitra) {
						return false;
					}

					$user = auth()->user();
					if ($user->hasRole('HSSE')) {
						return in_array($this->record->hsse_status, ['pending', 'reviewing']) && (empty($this->record->id_hsse) || (int) $this->record->id_hsse === (int) $user->id);
					} elseif ($user->hasRole('CRM')) {
						return in_array($this->record->crm_status, ['pending', 'reviewing']) && (empty($this->record->id_crm) || (int) $this->record->id_crm === (int) $user->id);
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
						TextEntry::make('document_type')
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
							}),
						TextEntry::make('hsse_status')
							->label('HSSE Status')
							->badge()
							->color(fn(string $state): string => match ($state) {
								'reviewing' => 'warning',
								'approved' => 'success',
								'pending' => 'gray',
								'rejected' => 'danger',
								'revisi' => 'info',
							})
							->visible(fn($record) => $record->document_type === 'hsse'),
						TextEntry::make('crm_status')
							->label('CRM Status')
							->badge()
							->color(fn(string $state): string => match ($state) {
								'reviewing' => 'warning',
								'approved' => 'success',
								'pending' => 'gray',
								'rejected' => 'danger',
								'revisi' => 'info',
							})
							->visible(fn($record) => $record->document_type === 'crm'),
						TextEntry::make('tanggal_upload')
							->label('Upload Date')
							->dateTime(),
						TextEntry::make('hsse_review_started_at')
							->label('HSSE Review Started')
							->dateTime()
							->visible(fn($record) => $record->document_type === 'hsse' && !empty($record->hsse_review_started_at)),
						TextEntry::make('crm_review_started_at')
							->label('CRM Review Started')
							->dateTime()
							->visible(fn($record) => $record->document_type === 'crm' && !empty($record->crm_review_started_at)),
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
					])
					->visible(fn($record) => $record->document_type === 'hsse'),

				Section::make('CRM Comments')
					->schema([
						\Filament\Infolists\Components\RepeatableEntry::make('crmComments')
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
					])
					->visible(fn($record) => $record->document_type === 'crm'),


			]);
	}

}