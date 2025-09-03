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
						$allowed = fn(string $s) => in_array($s, ['pending','revisi'], true);
						$blocked = fn(string $s) => in_array($s, ['reviewing','rejected'], true);
						if ($blocked($hsse) || $blocked($snd)) return false;
						if ($hsse === 'approved' && $snd === 'approved') return false;
						return $allowed($hsse) || $allowed($snd);
					}
					return redirect(\App\Filament\Resources\DocumentResource::getUrl('index'));
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
			
			Actions\Action::make('revisi')
				->label('Revisi')
				->color('warning')
				->icon('heroicon-o-arrow-path')
				->url(fn () => DocumentResource::getUrl('edit', ['record' => $this->record->getKey()]))
				->visible(function () {
					$user = auth()->user();
					if ($user->hasRole('HSSE')) {
						return request()->boolean('review') && $this->record->hsse_status === 'pending' && (empty($this->record->id_hsse) || (int)$this->record->id_hsse === (int)$user->id);
					} elseif ($user->hasAnyRole(['S&D','SND'])) {
						return request()->boolean('review') && $this->record->snd_status === 'pending' && (empty($this->record->id_snd) || (int)$this->record->id_snd === (int)$user->id);
					}
					return false;
				}),

			Actions\Action::make('approve')
				->label('Approve')
				->color('success')
				->icon('heroicon-o-check-circle')
				->requiresConfirmation()
				->modalHeading('Approve Document')
				->modalDescription('Are you sure you want to approve this document?')
				->action(function () {
					$user = auth()->user();

					\Filament\Notifications\Notification::make()
						->title('Processing approval...')
						->success()
						->send();

					\DB::transaction(function () use ($user) {
						if ($user->hasRole('HSSE')) {
							if (empty($this->record->id_hsse)) {
								$this->record->id_hsse = $user->id;
							}
							if ((int)$this->record->id_hsse !== (int)$user->id) {
								\Filament\Notifications\Notification::make()
									->title('You are not the assigned HSSE reviewer.')
									->danger()
									->send();
								return;
							}
							$this->record->hsse_status = 'approved';
							$this->record->id_hsse = $user->id;
						} elseif ($user->hasAnyRole(['S&D','SND'])) {
							if (empty($this->record->id_snd)) {
								$this->record->id_snd = $user->id;
							}
							if ((int)$this->record->id_snd !== (int)$user->id) {
								\Filament\Notifications\Notification::make()
									->title('You are not the assigned S&D reviewer.')
									->danger()
									->send();
								return;
							}
							$this->record->snd_status = 'approved';
							$this->record->id_snd = $user->id;
						}

						$this->record->save();
					});

					\Filament\Notifications\Notification::make()
						->title('Document approved!')
						->success()
						->send();

					$this->record->refresh();
					return redirect(\App\Filament\Resources\DocumentResource::getUrl('index'));
				})
				->visible(function () {
					$user = auth()->user();
					if ($user->hasRole('HSSE')) {
						return $this->record->hsse_status === 'pending'
							&& (empty($this->record->id_hsse) || (int)$this->record->id_hsse === (int)$user->id);
					} elseif ($user->hasAnyRole(['S&D','SND'])) {
						return $this->record->snd_status === 'pending'
							&& (empty($this->record->id_snd) || (int)$this->record->id_snd === (int)$user->id);
					}
					return false;
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
						if (empty($this->record->id_hsse)) {
							$this->record->id_hsse = $user->id;
						}
						if ((int)$this->record->id_hsse !== (int)$user->id) {
							\Filament\Notifications\Notification::make()
								->title('You are not the assigned HSSE reviewer.')
								->danger()
								->send();
							return;
						}
						$this->record->update([
							'hsse_status' => 'rejected',
							'id_hsse' => $user->id
						]);
					} elseif ($user->hasAnyRole(['S&D','SND'])) {
						if (empty($this->record->id_snd)) {
							$this->record->id_snd = $user->id;
						}
						if ((int)$this->record->id_snd !== (int)$user->id) {
							\Filament\Notifications\Notification::make()
								->title('You are not the assigned S&D reviewer.')
								->danger()
								->send();
							return;
						}
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
					$user = auth()->user();
					if ($user->hasRole('HSSE')) {
						return request()->boolean('review') && $this->record->hsse_status === 'pending' && $this->record->id_hsse == $user->id;
					} elseif ($user->hasAnyRole(['S&D','SND'])) {
						return request()->boolean('review') && $this->record->snd_status === 'pending' && $this->record->id_snd == $user->id;
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