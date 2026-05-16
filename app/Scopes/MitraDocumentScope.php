<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class MitraDocumentScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $user = Auth::user();

        // Jika user adalah Mitra, hanya tampilkan dokumen yang mereka buat
        if ($user && $user->hasRole('Mitra')) {
            $builder->where('id_user', $user->id_user);
        }

        // Admin, HSSE, dan CRM dapat melihat semua dokumen
        // Tidak ada filter tambahan untuk role tersebut
    }
}

