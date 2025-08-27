<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Models\komentar;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class UnifiedDocumentPage extends Page
{
    protected static string $resource = DocumentResource::class;
    protected static string $view = 'filament.resources.document-resource.pages.unified-document';

    public ?\App\Models\document $record = null;
    public $comments = [];
    public $newComment = '';
    public $isEditing = false;
    public $editForm = [];

    public function mount($record): void
    {
        $this->record = \App\Models\document::findOrFail($record);
        $this->loadComments();
        $this->initializeEditForm();
    }

    public function loadComments(): void
    {
        $this->comments = $this->record->comments()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function initializeEditForm(): void
    {
        $this->editForm = [
            'judul_dokumen' => $this->record->judul_dokumen,
            'file' => $this->record->file,
            'status' => $this->record->status,
            'id_mitra' => $this->record->id_mitra,
        ];
    }

    public function addComment(): void
    {
        $this->validate([
            'newComment' => 'required|string|max:1000',
        ]);

        $comment = new komentar([
            'dokumen_id' => $this->record->id,
            'user_id' => Auth::id(),
            'komentar' => $this->newComment,
        ]);
        $comment->save();

        $this->newComment = '';
        $this->loadComments();

        Notification::make()
            ->title('Comment added successfully')
            ->success()
            ->send();
    }

    public function saveDocument(): void
    {
        $this->validate([
            'editForm.judul_dokumen' => 'required|string|max:255',
            'editForm.file' => 'required|string',
            'editForm.status' => 'required|in:review,revisi,approved',
        ]);

        $this->record->update($this->editForm);
        
        Notification::make()
            ->title('Document updated successfully')
            ->success()
            ->send();
    }

    public function toggleEditMode(): void
    {
        $this->isEditing = !$this->isEditing;
        if (!$this->isEditing) {
            $this->initializeEditForm();
        }
    }

    public function getViewData(): array
    {
        return [
            'record' => $this->record,
            'comments' => $this->comments,
            'canEdit' => $this->canEdit(),
            'userRole' => $this->getUserRole(),
        ];
    }

    protected function canEdit(): bool
    {
        $user = Auth::user();
        
        // Check if user has required roles
        $userRoles = $user->roles->pluck('name')->toArray();
        
        // HSSE and S&D can edit when status is review
        if ($this->record->status === 'review') {
            return in_array('hsse', $userRoles) || in_array('snd', $userRoles);
        }
        
        // Admin can always edit
        return in_array('admin', $userRoles) || in_array('super_admin', $userRoles);
    }

    protected function getUserRole(): string
    {
        $user = Auth::user();
        $userRoles = $user->roles->pluck('name')->toArray();
        
        if (in_array('hsse', $userRoles)) return 'HSSE';
        if (in_array('snd', $userRoles)) return 'S&D';
        if (in_array('mitra', $userRoles)) return 'Mitra';
        
        return 'User';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggleEdit')
                ->label(fn() => $this->isEditing ? 'View Mode' : 'Edit Mode')
                ->icon(fn() => $this->isEditing ? 'heroicon-o-eye' : 'heroicon-o-pencil')
                ->action('toggleEditMode')
                ->visible($this->canEdit()),
                
            Actions\Action::make('save')
                ->label('Save Changes')
                ->icon('heroicon-o-check')
                ->action('saveDocument')
                ->visible($this->isEditing && $this->canEdit()),
        ];
    }
}
