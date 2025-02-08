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
        Schema::create('tblasin', function (Blueprint $table) {
            $table->integer('asinid')->primary();
            $table->string('ASIN', 50)->nullable();
            $table->string('productname', 255)->nullable();
            $table->mediumText('description')->nullable();
            $table->string('UPC')->nullable();
            $table->double('AsinRawMaterialCost')->nullable();
            $table->double('lbs')->default(0);
            $table->double('oz')->nullable();
            $table->double('kg')->nullable();
            $table->double('ASINCOST')->nullable();
            $table->string('EAN')->nullable();
            $table->integer('asin_limit')->nullable();
            $table->string('instructioncard')->nullable();
            $table->string('ParentAsin')->nullable();
            $table->string('instructioncard2')->nullable();
            $table->string('vectorimage')->nullable();
            $table->string('instructionlink')->nullable();
            $table->string('metakeyword', 50)->nullable();
            $table->dateTime('dateupload')->nullable();
            $table->date('dateedit')->nullable();
            $table->string('editby')->nullable();
            $table->string('UpgradeAsin', 30)->nullable();
            $table->decimal('dimension_length', 11, 2)->nullable();
            $table->decimal('dimension_width', 11, 2)->nullable();
            $table->decimal('dimension_height', 11, 2)->nullable();
            $table->string('dimension_unit', 30)->nullable();
            $table->decimal('weight_value', 11, 2)->nullable();
            $table->string('weight_unit', 30)->nullable();
            $table->string('amazon_status')->default('Existed');
            $table->string('update_status', 30)->nullable();
            $table->string('length_status', 30)->nullable();
            $table->string('width_status', 30)->nullable();
            $table->string('height_status', 30)->nullable();
            $table->string('lbs_status', 30)->nullable();
            $table->string('oz_status', 30)->nullable();
            $table->string('kg_status', 30)->nullable();
            $table->string('TRANSPARENCY_QR_STATUS', 150)->nullable();
            $table->float('white_length')->nullable();
            $table->float('white_width')->nullable();
            $table->float('white_height')->nullable();
            $table->float('white_lbs')->default(0);
            $table->float('white_oz')->nullable();
            $table->string('asinStatus', 50)->nullable();
            $table->string('GrandASIN', 50)->nullable();
            $table->integer('card_id')->nullable();
            $table->string('CousinASIN', 50)->nullable();
            $table->string('Unit', 50)->default('1');
            $table->string('usermanuallink', 50)->nullable();
            $table->integer('fbaTotalQuantity')->default(0);
            $table->integer('fbaUnitsold')->default(0);
            $table->string('BuyboxStatus', 50)->nullable();
            $table->integer('fbm_posted_qty')->nullable();
            $table->boolean('checkedStatus')->default(0);
            $table->string('asinNotes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblasin');
    }
};