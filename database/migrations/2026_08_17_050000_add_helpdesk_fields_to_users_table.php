<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('employee_id')->unique()->after('name');
            $table->string('division')->after('employee_id');
            $table->string('role')->default('employee')->index()->after('division');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['employee_id']);
            $table->dropIndex(['role']);
            $table->dropColumn(['employee_id', 'division', 'role']);
        });
    }
};
