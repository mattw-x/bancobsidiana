<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) { $table->softDeletes(); });
        Schema::table('accounts', function (Blueprint $table) { $table->softDeletes(); });
        Schema::table('cards', function (Blueprint $table) { $table->softDeletes(); });
        Schema::table('merchants', function (Blueprint $table) { $table->softDeletes(); });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) { $table->dropSoftDeletes(); });
        Schema::table('accounts', function (Blueprint $table) { $table->dropSoftDeletes(); });
        Schema::table('cards', function (Blueprint $table) { $table->dropSoftDeletes(); });
        Schema::table('merchants', function (Blueprint $table) { $table->dropSoftDeletes(); });
    }
};
