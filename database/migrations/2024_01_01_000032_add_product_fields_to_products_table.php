<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('brand', 100)->nullable()->after('short_description');
            $table->string('material', 100)->nullable()->after('brand');
            $table->string('color', 100)->nullable()->after('material');
            $table->string('finish', 100)->nullable()->after('color');
            $table->string('size', 100)->nullable()->after('finish');
            $table->string('style', 100)->nullable()->after('size');
            $table->string('product_type', 100)->nullable()->after('style');
            $table->string('weight', 50)->nullable()->after('product_type');
            $table->string('dimensions', 100)->nullable()->after('weight');
            $table->text('warranty')->nullable()->after('dimensions');
            $table->text('care_instructions')->nullable()->after('warranty');
            $table->text('customization_info')->nullable()->after('care_instructions');
            $table->text('shipping_info')->nullable()->after('customization_info');
            $table->json('tags')->nullable()->after('shipping_info');
            $table->boolean('is_new_arrival')->default(false)->after('is_featured');
            $table->integer('view_count')->default(0)->after('is_new_arrival');
            $table->integer('purchase_count')->default(0)->after('view_count');

            $table->index('brand');
            $table->index('material');
            $table->index('color');
            $table->index('size');
            $table->index('style');
            $table->index(['is_new_arrival', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'brand', 'material', 'color', 'finish', 'size', 'style', 'product_type',
                'weight', 'dimensions', 'warranty', 'care_instructions', 'customization_info',
                'shipping_info', 'tags', 'is_new_arrival', 'view_count', 'purchase_count',
            ]);
        });
    }
};