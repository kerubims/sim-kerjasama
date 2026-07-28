<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'category',
        'address',
        'phone',
        'email',
        'website',
        'description',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all documents associated with this partner through users and document_parties.
     */
    public function documents()
    {
        return Document::whereHas('parties', function ($q) {
            $q->whereIn('user_id', $this->users()->pluck('id'));
        });
    }
}
