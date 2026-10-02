<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->string('reserved_by')->nullable()->after('prodi');
            $table->timestamp('reserved_until')->nullable()->after('reserved_by');

            $table->index(['label_status', 'reserved_until'], 'idx_images_lease_status');
            $table->index('reserved_by', 'idx_images_reserved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropIndex('idx_images_lease_status');
            $table->dropIndex('idx_images_reserved_by');
            $table->dropColumn(['reserved_by', 'reserved_until']);
        });
    }
};
