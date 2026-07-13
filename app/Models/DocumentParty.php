<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentParty extends Model
{
    protected $fillable = [
        'document_id',
        'user_id',
        'role_type',
        'signature_path',
        'stamp_path',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
