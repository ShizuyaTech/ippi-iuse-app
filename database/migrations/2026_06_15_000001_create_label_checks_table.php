<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('label_checks', function (Blueprint $table) {
            $table->id();
            $table->string('part_label');
            $table->string('customer_label');
            $table->enum('result', ['ok', 'ng']);
            $table->string('reference_doc')->nullable()->comment('No GI / No PO / referensi dokumen terkait');
            $table->string('notes')->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_checks');
    }
};
