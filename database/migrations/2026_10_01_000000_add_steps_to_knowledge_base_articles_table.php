<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_base_articles', function (Blueprint $table): void {
            $table->json('steps')->nullable()->after('problem_description');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_base_articles', function (Blueprint $table): void {
            $table->dropColumn('steps');
        });
    }
};
