<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('status_description', 'is_active')) {
            Schema::table('status_description', function (Blueprint $table) {
                $table->tinyInteger('is_active')->default(1)->after('description');
            });
        }
        
        if (!Schema::hasColumn('status_description', 'status_id')) {
            Schema::table('status_description', function (Blueprint $table) {
                $table->integer('status_id')->nullable()->after('id');
                $table->string('status_name')->nullable()->after('status_id');
            });
            
            // Update existing records
            DB::statement('UPDATE status_description SET status_id = id, status_name = description WHERE status_id IS NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('status_description', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'status_id', 'status_name']);
        });
    }
};
