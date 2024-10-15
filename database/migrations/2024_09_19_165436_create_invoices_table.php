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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string(column: 'number')->unique();
            $table->foreignId(column: 'customer_id')->references('id')->on('customers')
            ->onDelete('cascade')->onUpdate('cascade');
            $table->date('date');
            $table->date('due_date');
            $table->string(column: 'reference')->nullable();
            $table->text('terms_and_conditions');
            $table->double(column: 'sub_total');
            $table->double('discount')->default(0);
            $table->double('total');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};