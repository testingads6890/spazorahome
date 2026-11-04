<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Location masters (country > state > district > city > pincode) and the new product form fields. */
    public function up(): void
    {
        if (! Schema::hasTable('countries')) Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->char('iso2', 2)->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (! Schema::hasTable('states')) Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['country_id', 'name']);
        });

        if (! Schema::hasTable('districts')) Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['state_id', 'name']);
        });

        if (! Schema::hasTable('cities')) Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['district_id', 'name']);
        });

        if (! Schema::hasTable('pincodes')) Schema::create('pincodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('code', 10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['city_id', 'code']);
        });

        Schema::table('products', function (Blueprint $table) {
            // Each column is added only if an earlier SQL patch has not already added it.
            if (! Schema::hasColumn('products', 'max_order_qty')) {
                $table->unsignedInteger('max_order_qty')->nullable()->after('min_order_qty');
            }
            if (! Schema::hasColumn('products', 'secondary_material_grade')) {
                $table->string('secondary_material_grade', 150)->nullable()->after('secondary_material');
            }
            if (! Schema::hasColumn('products', 'service_type')) {
                $table->string('service_type', 20)->nullable()->after('assembly_required');
            }
            foreach (['country_id' => 'countries', 'state_id' => 'states', 'district_id' => 'districts', 'city_id' => 'cities', 'pincode_id' => 'pincodes'] as $column => $parent) {
                if (! Schema::hasColumn('products', $column)) {
                    $table->foreignId($column)->nullable()->constrained($parent)->nullOnDelete();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (['country_id', 'state_id', 'district_id', 'city_id', 'pincode_id'] as $fk) {
                $table->dropConstrainedForeignId($fk);
            }
            $table->dropColumn(['max_order_qty', 'secondary_material_grade', 'service_type']);
        });
        Schema::dropIfExists('pincodes');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('states');
        Schema::dropIfExists('countries');
    }
};
