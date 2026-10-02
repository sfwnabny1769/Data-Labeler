<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Butir 9 rencana redesign: support multi-labeler per gambar.
 *
 * Tiap baris = satu suara (vote) dari satu labeler untuk satu gambar.
 * Quando multi_labeler_mode aktif, Image::label TIDAK langsung diisi;
 * label_final baru ditentukan setelah semua labeler memberi suara.
 *
 * Kolom images.label tetap dipakai sebagai hasil akhir consensus supaya
 * seluruh query existing (leaderboard, CSV export, approve/reject) tetap jalan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('image_id')
                ->constrained('images')
                ->cascadeOnDelete();
            $table->string('labeled_by');            // nickname labeler
            $table->string('prodi')->nullable();
            $table->integer('label');                // 0, 1, 2 (dinamis dari config)
            $table->timestamps();

            // Satu labeler hanya boleh satu suara per gambar.
            $table->unique(['image_id', 'labeled_by'], 'image_labels_image_labeler_unique');
            $table->index(['image_id', 'label']);
            $table->index('labeled_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_labels');
    }
};