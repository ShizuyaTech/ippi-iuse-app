<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_issues', function (Blueprint $table) {
            $table->enum('issue_type', ['internal', 'to_vendor', 'to_customer'])
                  ->default('internal')
                  ->after('issue_date');
            $table->string('destination_name', 255)->nullable()->after('issue_type');
        });

        Schema::table('goods_issue_items', function (Blueprint $table) {
            $table->string('note', 500)->nullable()->after('quantity_issued');
        });
    }

    public function down(): void
    {
        Schema::table('goods_issues', function (Blueprint $table) {
            $table->dropColumn(['issue_type', 'destination_name']);
        });

        Schema::table('goods_issue_items', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
