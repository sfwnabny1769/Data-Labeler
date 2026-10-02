<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class AuditCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'given_label',
        'predicted_label',
        'label_quality_score',
        'sub_cluster_id',
        'neighbor_conflict_rate',
        'dominant_neighbor_class',
        'hdbscan_outlier_score',
        'priority_score',
        'round1_decision',
        'round1_note',
        'round1_by',
        'round1_at',
        'round2_decision',
        'round2_by',
        'round2_at',
    ];

    protected $casts = [
        'round1_at' => 'datetime',
        'round2_at' => 'datetime',
        'label_quality_score' => 'float',
        'neighbor_conflict_rate' => 'float',
        'hdbscan_outlier_score' => 'float',
        'priority_score' => 'float',
    ];

    public const ROUND1_RUBRIC = [
        'A' => 'Salah label -- gambar berisi objek sampah asli, tapi kategorinya salah.',
        'B' => 'Kontaminasi -- gambar bukan foto sampah sama sekali (chart, foto tidak relevan, dll).',
        'C' => 'Ambigu -- objek sampah asli, wajar dibingungkan; label saat ini tetap dipertahankan.',
        'D' => 'Model error -- label saat ini sudah benar, model/cleanlab saja yang salah tebak.',
    ];

    public const CLASS_OPTIONS = [
        '0_Aman' => 'Aman -- Paket/dus dalam kondisi utuh, mulus, dan layak kirim',
        '1_Rusak' => 'Rusak -- Paket/dus mengalami kerusakan fisik (penyok, sobek, basah, atau hancur)',
        '2_Lainnya' => 'Lainnya -- Kemasan non-standar / out-of-distribution (seperti food packaging, kantong plastik belanjaan, atau paper bag)',
    ];

    public const CONTAMINATION_DECISION = 'CONTAMINATION';

    /**
     * URL gambar -- dicari di public/dataset/train/{given_label}/{filename} dulu (folder
     * kelas yang sama seperti struktur data/train/ di project Python), fallback ke route
     * streaming kalau tidak diserve langsung oleh web server.
     */
    public function getUrlAttribute()
    {
        $path = public_path('dataset/train/' . $this->given_label . '/' . $this->filename);
        if (File::exists($path)) {
            return asset('dataset/train/' . $this->given_label . '/' . $this->filename);
        }
        return route('audit.image', ['label' => $this->given_label, 'filename' => $this->filename]);
    }
}
