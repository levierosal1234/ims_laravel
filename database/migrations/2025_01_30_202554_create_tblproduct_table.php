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
        Schema::create('tblproduct', function (Blueprint $table) {
            $table->integer('ProductID')->primary();
            $table->string('ProductTitle', 255)->nullable();
            $table->string('productname', 255)->nullable();
            $table->string('listedcondition', 50)->nullable();
            $table->double('price')->nullable();
            $table->double('priceshipping')->nullable();
            $table->double('tax')->nullable();
            $table->double('refund')->nullable();
            $table->date('orderdate')->nullable();
            $table->date('datetoday')->nullable();
            $table->date('paymentdate')->nullable();
            $table->string('paymentmethod', 55)->nullable();
            $table->date('shipdate')->nullable();
            $table->string('trackingnumber', 50)->nullable();
            $table->string('trackingnumber2', 50)->nullable();
            $table->string('trackingnumber3', 50)->nullable();
            $table->string('trackingnumber4', 50)->nullable();
            $table->string('trackingnumber5', 50)->nullable();
            $table->string('seller', 30)->nullable();
            $table->mediumText('description')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('orderqty')->nullable();
            $table->string('sourcetype', 20)->nullable();
            $table->string('serialnumber', 50)->nullable();
            $table->string('serialnumber2', 50)->nullable();
            $table->string('serialnumber3', 50)->nullable();
            $table->string('serialnumber4', 50)->nullable();
            $table->date('datedelivered')->nullable();
            $table->string('RPN', 20)->nullable();
            $table->string('PRD', 20)->nullable();
            $table->string('ASIN', 20)->nullable();
            $table->string('FNSKU', 20)->nullable();
            $table->string('MSKU', 20)->nullable();
            $table->string('grading', 20)->nullable();
            $table->string('materialtype', 50)->nullable();
            $table->mediumText('notes')->nullable();
            $table->mediumText('costumernote')->nullable();
            $table->string('deliveredYes')->nullable();
            $table->date('lpndate')->nullable();
            $table->string('modulelocation', 50)->nullable();
            $table->double('BoxWeight')->default(0);
            $table->date('DateCreated')->nullable();
            $table->string('carrier')->nullable();
            $table->string('Alias')->nullable();
            $table->integer('SID')->nullable();
            $table->double('Unit')->nullable();
            $table->double('unitprice')->nullable();
            $table->string('PCN')->nullable();
            $table->string('returnstatus')->default('Not Returned');
            $table->string('lpnID')->nullable();
            $table->string('stickernote')->nullable();
            $table->double('WeightOz')->nullable();
            $table->double('WeightKg')->nullable();
            $table->string('ReceivedStatus')->nullable();
            $table->string('MarketPlace')->nullable();
            $table->string('ChangedtoFNSKU')->nullable();
            $table->integer('SoldquantityCount')->nullable();
            $table->integer('rtcounter')->nullable();
            $table->string('orderid')->nullable();
            $table->string('basketnumber')->nullable();
            $table->string('shelvesnumber')->nullable();

            // Image columns
            for ($i = 1; $i <= 15; $i++) {
                $table->string("img{$i}")->nullable();
            }

            $table->string('UPC')->nullable();
            $table->double('AsinCost')->nullable();
            $table->double('SIDPRICEUSED')->nullable();
            $table->string('printby')->nullable();
            $table->string('itemnumber')->nullable();
            $table->string('warehouselocation')->nullable();
            $table->string('StoreName')->default('Renovar Tech');
            $table->string('EmployeeNote')->nullable();
            $table->string('TrackingLink')->nullable();
            $table->integer('Discount')->nullable();
            $table->integer('DiscountPrice')->nullable();
            $table->string('Parentasin', 50)->nullable();
            $table->string('BulkStatus')->nullable();
            $table->integer('BulkPrice')->nullable();
            $table->integer('splitfromRT')->nullable();
            $table->date('DateReceivedInWarehouse')->nullable();
            $table->string('costumer_name', 50)->nullable();
            $table->string('shipmentstatus', 50)->nullable();
            $table->string('shipmentnotes')->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('shipment_tracking_number')->nullable();
            $table->date('status_date')->nullable();
            $table->integer('FbmAvailable')->default(1);
            $table->integer('FbaAvailable')->default(0);
            $table->integer('Outbound')->default(0);
            $table->integer('Inbound')->default(0);
            $table->integer('Reserved')->default(0);
            $table->integer('Unfulfillable')->default(0);
            $table->string('Fulfilledby', 25)->default('FBM');
            $table->string('InboundStatus')->nullable();
            $table->string('AmazonOrderId', 50)->nullable();
            $table->string('ShipAddress')->nullable();
            $table->string('itemstatus')->nullable();
            $table->date('NotfoundDate')->nullable();
            $table->string('validation', 50);
            $table->string('metakeyword', 50)->nullable();
            $table->integer('mID')->nullable();
            $table->integer('migratedTO')->nullable();
            $table->string('fulfillment_status', 100)->nullable();
            $table->dateTime('stockroom_insert_date')->nullable();
            $table->date('mp_insert_date')->nullable();
            $table->string('fbm_list_status', 15)->nullable();
            $table->string('itemcondition')->nullable();
            $table->integer('printCount')->default(0);
            $table->string('fetchStatus', 50)->nullable();
            $table->string('UpgradeASIN', 50)->nullable();
            $table->string('GrandASIN', 50)->nullable();
            $table->string('CousinASIN', 50)->nullable();
            $table->string('boxChoice', 50)->default('Retailbox');
            $table->string('conditionStatusApplied', 50)->nullable();
            $table->integer('fbaTotalQuantity')->default(0);
            $table->integer('warranty')->nullable();
            $table->dateTime('lastDateUpdate')->nullable();
            $table->dateTime('validatedDate')->nullable();
            $table->string('editstatus', 50)->nullable();
            $table->string('testresult1')->nullable();
            $table->string('testresult2')->nullable();
            $table->string('testnotes')->nullable();
            $table->double('refundamount')->nullable();
            $table->string('teststatus')->nullable();
            $table->string('LST')->nullable();
            $table->string('reason')->nullable();
            $table->date('daterefund')->nullable();
            $table->date('datefiled')->nullable();
            $table->string('esresult')->nullable();
            $table->string('filedin')->nullable();
            $table->string('rtsresult')->nullable();
            $table->string('returntracking')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblproduct');
    }
};