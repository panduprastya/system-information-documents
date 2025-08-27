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
		$user = auth()->user();
		
		// Jika user adalah Mitra, pastikan mereka hanya dapat melihat dokumen mereka sendiri
		if ($user && $user->hasRole('Mitra') && $record->id_mitra !== $user->id) {
			abort(403, 'Anda tidak dapat melihat dokumen yang bukan milik Anda.');
		}
		
		return $record;
	}

	protected function getHeaderActions(): array
	{
		return [
			Actions\Action::make('back')
				->label('Kembali')
				->icon('heroicon-o-arrow-left')
				->url(fn () => url()->previous())
				->color('gray'),
				
			Actions\EditAction::make()
				->visible(function () {
					$user = auth()->user();
					// Admin cannot edit documents, only view them
					if ($user && $user->hasRole('Admin')) {
						return false;
					}
					
					// Mitra can edit documents if either status is revisi, or if both are not approved
					if ($user && $user->hasRole('Mitra')) {
						$hasRevisiStatus = $this->record->hsse_status === 'revisi' || $this->record->snd_status === 'revisi';
						$bothApproved = $this->record->hsse_status === 'approved' && $this->record->snd_status === 'approved';
						$bothPendingOrRevisi = ($this->record->hsse_status === 'pending' || $this->record->hsse_status === 'revisi') && 
										   ($this->record->snd_status === 'pending' || $this->record->snd_status === 'revisi');
						
						// Hide edit button when both statuses are pending or revisi (show only review button)
						if ($bothPendingOrRevisi) {
							return false;
						}
						
						return $hasRevisiStatus || !$bothApproved;
					}
					
					// // HSSE and S&D users can edit
					// return $user && ($user->hasRole('HSSE') || $user->hasRole('S&D'));
				}),
				
			//Actions\Action::make('revisi_dokumen')
			  //  ->label('Revisi Dokumen')
			   // ->icon('heroicon-o-pencil-square')
				//->url(fn () => DocumentResource::getUrl('edit', ['record' => $this->record->getKey()]))
				//->color('warning')
				//->visible(function () {
					//$user = auth()->user();
					// Show revisi button for Mitra when either status is revisi
					//return $user && $user->hasRole('Mitra') && 
						//($this->record->hsse_status === 'revisi' || $this->record->snd_status === 'revisi');
				//}),
			
			Actions\Action::make('download')
				->label('Download PDF')
				->icon('heroicon-o-arrow-down-tray')
				->url(fn () => route('documents.download', ['document' => $this->record->getKey()]))
				->openUrlInNewTab()
				->visible(function () {
					$user = auth()->user();
					return $user && $user->hasRole('Mitra') &&
						$this->record->hsse_status === 'approved' && $this->record->snd_status === 'approved';
				}),
			
			Actions\Action::make('approve')
				->label('Approved')
				->color('success')
				->icon('heroicon-o-check-circle')
				->requiresConfirmation()
				->modalHeading('Approve Document')
				->modalDescription('Are you sure you want to approve this document?')
				->action(function () {
					$user = auth()->user();
					
					if ($user->hasRole('HSSE')) {
						$this->record->update([
							'hsse_status' => 'approved',
							'id_hsse' => $user->id
						]);
					} elseif ($user->hasRole('S&D')) {
						$this->record->update([
							'snd_status' => 'approved',
							'id_snd' => $user->id
						]);
					}
					
					\Filament\Notifications\Notification::make()
						->title('Document approved successfully!')
						->success()
						->send();
				})
				->visible(function () {
					$user = auth()->user();
					if ($user->hasRole('HSSE')) {
						return $this->record->hsse_status !== 'approved';
					} elseif ($user->hasRole('S&D')) {
						return $this->record->snd_status !== 'approved';
					}
					return false;
				}),
				
			Actions\Action::make('revisi')
				->label('Revisi')
				->color('warning')
				->icon('heroicon-o-arrow-path')
				->requiresConfirmation()
				->modalHeading('Revisi Document')
				->action(function () {
					$user = auth()->user();
					
					if ($user->hasRole('HSSE')) {
						$this->record->update([
							'hsse_status' => 'revisi',
							'id_hsse' => $user->id
						]);
					} elseif ($user->hasRole('S&D')) {
						$this->record->update([
							'snd_status' => 'revisi',
							'id_snd' => $user->id
						]);
					}
					
					$this->redirect(DocumentResource::getUrl('edit', ['record' => $this->record->getKey()]));
					\Filament\Notifications\Notification::make()
						->title('Document revised successfully!')
						->success()
						->send();
				})
				->visible(function () {
					$bothApproved = $this->record->hsse_status === 'approved' && $this->record->snd_status === 'approved';
					if ($bothApproved) {
						return false;
					}
					return auth()->user()->hasRole('HSSE') || auth()->user()->hasRole('S&D');
				}),
			
			Actions\Action::make('reject')
				->label('Rejected')
				->color('danger')
				->icon('heroicon-o-x-circle')
				->requiresConfirmation()
				->modalHeading('Reject Document')
				->modalDescription('Are you sure you want to reject this document?')
				->action(function () {
					$user = auth()->user();
					
					if ($user->hasRole('HSSE')) {
						$this->record->update([
							'hsse_status' => 'rejected',
							'id_hsse' => $user->id
						]);
					} elseif ($user->hasRole('S&D')) {
						$this->record->update([
							'snd_status' => 'rejected',
							'id_snd' => $user->id
						]);
					}
					
					\Filament\Notifications\Notification::make()
						->title('Document rejected!')
						->danger()
						->send();
				})
				->visible(function () {
					$bothApproved = $this->record->hsse_status === 'approved' && $this->record->snd_status === 'approved';
					if ($bothApproved) {
						return false;
					}
					$user = auth()->user();
					if ($user->hasRole('HSSE')) {
						return $this->record->hsse_status !== 'rejected';
					} elseif ($user->hasRole('S&D')) {
						return $this->record->snd_status !== 'rejected';
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
						// TextEntry::make('status')
						//     ->badge()
						//     ->color(fn (string $state): string => match ($state) {
						//         'reviewing' => 'warning',
						//         'approved' => 'success',
						//         'pending' => 'gray',
						//         'rejected' => 'danger',
						//     }),
						TextEntry::make('hsse_status')
							->label('HSSE Status')
							->badge()
							->color(fn (string $state): string => match ($state) {
								'reviewing' => 'warning',
								'approved' => 'success',
								'pending' => 'gray',
								'rejected' => 'danger',
								'revisi' => 'info',
							}),
						TextEntry::make('snd_status')
							->label('S&D Status')
							->badge()
							->color(fn (string $state): string => match ($state) {
								'reviewing' => 'warning',
								'approved' => 'success',
								'pending' => 'gray',
								'rejected' => 'danger',
								'revisi' => 'info',
							}),
						TextEntry::make('tanggal_upload')
							->label('Upload Date')
							->dateTime(),
						TextEntry::make('tanggal_acc')
							->label('Approval Date')
							->dateTime(),
						TextEntry::make('hsse_review_started_at')
							->label('HSSE Review Started')
							->dateTime()
							->hidden(fn ($record) => empty($record->hsse_review_started_at)),
						TextEntry::make('snd_review_started_at')
							->label('SND Review Started')
							->dateTime()
							->hidden(fn ($record) => empty($record->snd_review_started_at)),
						TextEntry::make('keterangan')
							->label('Keterangan Dokumen')
							->columnSpanFull()
							->markdown()
							->hidden(fn ($record) => empty($record->keterangan))
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
						\Filament\Infolists\Components\View::make('filament.resources.document-resource.pages.hsse-comments')
							->viewData(['comments' => $this->record->hsseComments]),
					]),
				
				Section::make('S&D Comments')
					->schema([
						\Filament\Infolists\Components\View::make('filament.resources.document-resource.pages.snd-comments')
							->viewData(['comments' => $this->record->setAppends(['sndComments'])->sndComments]),
					]),
					

			]);
	}

}