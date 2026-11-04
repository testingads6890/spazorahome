<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Bulk and trade enquiries sent from the public /bulk-enquiry page. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bulk_enquiries')) {
            return;
        }

        Schema::create('bulk_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 20);
            $table->string('email', 255);
            $table->string('whatsapp', 20)->nullable();
            $table->string('other_phone', 20)->nullable();
            $table->string('product_type', 120)->nullable();
            $table->json('category_ids')->nullable();
            $table->json('subcategory_ids')->nullable();
            $table->string('product_name', 255)->nullable();
            $table->unsignedInteger('quantity');
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new');
            $table->text('admin_note')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_enquiries');
    }
};
