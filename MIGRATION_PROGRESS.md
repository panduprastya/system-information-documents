# Progress Update: S&D → CRM Migration

## ✅ Completed Files:

### Backend Core:

1. ✅ Database Migration (`2026_02_08_124500_rename_snd_to_crm.php`)
2. ✅ Models:
    - `Crm.php` (created)
    - `CrmComment.php` (created)
    - `document.php` (updated)
3. ✅ Controllers:
    - `CrmCommentController.php` (created)
    - `DocumentApprovalController.php` (updated)
    - `DocumentDownloadController.php` (updated)
4. ✅ Routes (`web.php`)
5. ✅ Scopes (`MitraDocumentScope.php`)

### Filament Resources:

6. ✅ `app/Filament/Resources/DocumentResource.php`
7. ✅ `app/Filament/Widgets/DocumentStatsOverview.php`

## ⏳ Remaining Files to Update:

### Filament Widgets:

- `app/Filament/Widgets/DocumentStatusChart.php`
- `app/Filament/Widgets/DocumentStatusPieChart.php`

### Filament Pages:

- `app/Filament/Resources/DocumentResource/Pages/EditDocument.php`
- `app/Filament/Resources/DocumentResource/Pages/ViewDocument.php`

### Views:

- `resources/views/pdf/approval-cover.blade.php`
- `resources/views/filament/resources/document-resource/pages/snd-comments.blade.php` (rename to crm-comments.blade.php)

### Files to Delete:

- `app/Models/snd.php` (old model)
- `app/Models/SndComment.php` (old model)
- `app/Http/Controllers/SndCommentController.php` (old controller)

## Next Steps:

1. Update remaining Filament widgets
2. Update Filament pages
3. Update views
4. Delete old files
5. Test the application
