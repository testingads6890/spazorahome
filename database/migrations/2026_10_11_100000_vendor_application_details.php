<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seller application: business details, GST and registration numbers, and the documents the team verifies.
 * Safe to run on a database where these were already added by hand: every step checks first.
 */
return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'contact_name' => fn (Blueprint $t) => $t->string('contact_name')->nullable()->after('business_name'),
            'business_type' => fn (Blueprint $t) => $t->string('business_type', 50)->nullable()->after('contact_name'),
            'website' => fn (Blueprint $t) => $t->string('website')->nullable()->after('business_type'),
            'description' => fn (Blueprint $t) => $t->text('description')->nullable()->after('website'),
            'product_categories' => fn (Blueprint $t) => $t->json('product_categories')->nullable()->after('description'),
            'registration_number' => fn (Blueprint $t) => $t->string('registration_number', 100)->nullable()->after('tax_number'),
            'gst_registered' => fn (Blueprint $t) => $t->boolean('gst_registered')->default(false)->after('registration_number'),
            'terms_accepted_at' => fn (Blueprint $t) => $t->timestamp('terms_accepted_at')->nullable()->after('approved_at'),
            'rejection_reason' => fn (Blueprint $t) => $t->text('rejection_reason')->nullable()->after('terms_accepted_at'),
        ];
        foreach ($columns as $name => $add) {
            if (! Schema::hasColumn('vendors', $name)) {
                Schema::table('vendors', $add);
            }
        }

        if (! Schema::hasTable('vendor_documents')) {
            Schema::create('vendor_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
                $table->string('type', 40);            // company_registration, gst_certificate, additional
                $table->string('label', 120)->nullable(); // what an additional document is (PAN card, address proof...)
                $table->string('path');
                $table->string('original_name')->nullable();
                $table->string('mime', 100)->nullable();
                $table->unsignedInteger('size')->default(0);
                $table->string('status', 20)->default('pending'); // pending, verified, rejected
                $table->text('note')->nullable();       // why a document was rejected
                $table->timestamp('verified_at')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['vendor_id', 'type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_documents');
        foreach (['contact_name', 'business_type', 'website', 'description', 'product_categories', 'registration_number', 'gst_registered', 'terms_accepted_at', 'rejection_reason'] as $name) {
            if (Schema::hasColumn('vendors', $name)) {
                Schema::table('vendors', fn (Blueprint $t) => $t->dropColumn($name));
            }
        }
    }
};
