<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu suara label dari satu labeler untuk satu gambar (butir 9).
 *
 * Eksistsinya tabel ini yang membedakan mode single-labeler (default,
 * hanya Image yang dipakai) dari mode multi-labeler (mengukur
 * inter-annotator agreement).
 */
class ImageLabel extends Model
{
    use HasFactory;

    protected $table = 'image_labels';

    protected $fillable = [
        'image_id',
        'labeled_by',
        'prodi',
        'label',
    ];

    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }

    /**
     * unanimous = semua suara Fell onto label yang sama.
     */
    public function scopeUnanimous($query)
    {
        return $query->select('image_id')
            ->groupBy('image_id')
            ->havingRaw('COUNT(DISTINCT label) = 1');
    }
}