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
        Schema::create('tblfnsku', function (Blueprint $table) {
            $table->integer('FNSKUID')->primary();
            $table->string('FNSKU', 50)->nullable();
            $table->string('SKU', 50)->nullable();
            $table->string('grading', 50)->nullable();
            $table->string('MSKU', 50)->nullable();
            $table->integer('Units')->nullable();
            $table->integer('available')->nullable();
            $table->integer('productid')->nullable();
            $table->timestamp('insert_date')->useCurrent();
            $table->string('amazon_status')->default('Existed');
            $table->string('addedby')->nullable();
            $table->date('dateFreeUp')->nullable();
            $table->string('donotreplenish', 50)->default('none');
            $table->string('dnr_reason')->nullable();
            $table->string('LimitStatus', 5)->default('False');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblfnsku');
    }
};