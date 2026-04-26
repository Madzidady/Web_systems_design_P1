<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'priority')) {
                $table->string('priority')->default('medium')->after('status');
            }

            if (Schema::hasColumn('tasks', 'album_number')) {
                $table->dropColumn('album_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'album_number')) {
                $table->string('album_number')->default('unknown');
            }

            if (Schema::hasColumn('tasks', 'priority')) {
                $table->dropColumn('priority');
            }
        });
    }
};
