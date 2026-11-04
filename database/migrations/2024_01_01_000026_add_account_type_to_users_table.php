<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_type', ['customer', 'designer', 'architect'])->default('customer')->after('id');
            $table->string('company_name')->nullable()->after('name');
            $table->string('designation')->nullable()->after('company_name');
            $table->text('professional_description')->nullable()->after('designation');
            $table->string('license_number')->nullable()->after('professional_description');
            $table->string('website')->nullable()->after('license_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['account_type', 'company_name', 'designation', 'professional_description', 'license_number', 'website']);
        });
    }
};
