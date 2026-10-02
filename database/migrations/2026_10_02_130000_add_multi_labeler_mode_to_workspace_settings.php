<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_settings', function (Blueprint $table) {
            $table->boolean('multi_labeler_mode')->default(false)->after('access_passkey');
        });
    }

    public function down(): void
    {
        Schema::table('workspace_settings', function (Blueprint $table) {
            $table->dropColumn('multi_labeler_mode');
        });
    }
};
