<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkspaceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'active_activity',
        'access_passkey',
        'multi_labeler_mode',
    ];

    protected $casts = [
        'multi_labeler_mode' => 'boolean',
    ];

    public const ACTIVE_PREPROCESS = 'preprocess';
    public const ACTIVE_LABELING = 'labeling';
    public const ACTIVE_AUDIT = 'audit';

    public const ACTIVE_ACTIVITIES = [
        self::ACTIVE_PREPROCESS,
        self::ACTIVE_LABELING,
        self::ACTIVE_AUDIT,
    ];
}
