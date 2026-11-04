<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The extra product details asked for in the vendor data sheet (Saarthi Designer vendor master): identity, material
 * and finish codes, lead time, assembly and installation, warranty and returns, packed size, dispatch, links, and the
 * per-category details (stored together in category_attributes).
 *
 * Every column is added only when it is missing, so this is safe on a server that already has some of them
 * (the live database already holds features, assembly_time, warranty_months, return_policy_days and a few more).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $add = function (string $column, callable $define) use ($table) {
                if (! Schema::hasColumn('products', $column)) {
                    $define($table)->nullable();
                }
            };

            // Identity, material and finish
            $add('vendor_sku', fn ($t) => $t->string('vendor_sku', 100));
            $add('manufacturer', fn ($t) => $t->string('manufacturer', 150));
            $add('model_no', fn ($t) => $t->string('model_no', 100));
            $add('collection_series', fn ($t) => $t->string('collection_series', 150));
            $add('material_grade', fn ($t) => $t->string('material_grade', 150));
            $add('secondary_material', fn ($t) => $t->string('secondary_material', 150));
            $add('colour_code', fn ($t) => $t->string('colour_code', 50));
            $add('finish_type', fn ($t) => $t->string('finish_type', 100));
            $add('finish_code', fn ($t) => $t->string('finish_code', 50));
            $add('features', fn ($t) => $t->text('features'));

            // Price, order and lead time
            $add('mrp', fn ($t) => $t->decimal('mrp', 10, 2));
            $add('unit', fn ($t) => $t->string('unit', 20));
            $add('lead_time_days', fn ($t) => $t->unsignedInteger('lead_time_days'));
            $add('is_customizable', fn ($t) => $t->boolean('is_customizable')->default(false));
            $add('made_to_order', fn ($t) => $t->boolean('made_to_order')->default(false));

            // Assembly and installation
            $add('assembly_required', fn ($t) => $t->boolean('assembly_required')->default(false));
            $add('assembly_time', fn ($t) => $t->string('assembly_time', 100));
            $add('assembly_difficulty', fn ($t) => $t->string('assembly_difficulty', 50));
            $add('assembly_instructions', fn ($t) => $t->text('assembly_instructions'));
            $add('installation_included', fn ($t) => $t->boolean('installation_included')->default(false));
            $add('installation_charges', fn ($t) => $t->decimal('installation_charges', 10, 2));

            // Warranty and returns
            $add('warranty_months', fn ($t) => $t->unsignedInteger('warranty_months'));
            $add('warranty_exclusions', fn ($t) => $t->text('warranty_exclusions'));
            $add('return_eligible', fn ($t) => $t->boolean('return_eligible')->default(true));
            $add('return_policy_days', fn ($t) => $t->unsignedInteger('return_policy_days'));
            $add('return_policy', fn ($t) => $t->text('return_policy'));
            $add('replacement_policy', fn ($t) => $t->text('replacement_policy'));
            $add('damage_policy', fn ($t) => $t->text('damage_policy'));

            // Packing and dispatch
            $add('packaging_quantity', fn ($t) => $t->string('packaging_quantity', 100));
            $add('packaging_weight', fn ($t) => $t->decimal('packaging_weight', 8, 2));
            $add('packed_length_cm', fn ($t) => $t->decimal('packed_length_cm', 8, 2));
            $add('packed_width_cm', fn ($t) => $t->decimal('packed_width_cm', 8, 2));
            $add('packed_height_cm', fn ($t) => $t->decimal('packed_height_cm', 8, 2));
            $add('dispatch_from', fn ($t) => $t->string('dispatch_from', 150));
            $add('shipping_restrictions', fn ($t) => $t->text('shipping_restrictions'));
            $add('is_fragile', fn ($t) => $t->boolean('is_fragile')->default(false));
            $add('is_bulky', fn ($t) => $t->boolean('is_bulky')->default(false));

            // Links and notes
            $add('catalogue_url', fn ($t) => $t->string('catalogue_url', 500));
            $add('cad_url', fn ($t) => $t->string('cad_url', 500));
            $add('image_folder_url', fn ($t) => $t->string('image_folder_url', 500));
            $add('vendor_notes', fn ($t) => $t->text('vendor_notes'));

            // Website content and commercial terms
            $add('whats_included', fn ($t) => $t->text('whats_included'));
            $add('whats_not_included', fn ($t) => $t->text('whats_not_included'));
            foreach ([1, 2, 3] as $i) {
                $add("faq_{$i}_question", fn ($t) => $t->string("faq_{$i}_question", 255));
                $add("faq_{$i}_answer", fn ($t) => $t->text("faq_{$i}_answer"));
            }
            $add('payment_terms', fn ($t) => $t->string('payment_terms', 255));
            $add('price_valid_until', fn ($t) => $t->date('price_valid_until'));

            // Details that only make sense for one kind of product (a bed's slats, a lamp's lumens...)
            $add('category_attributes', fn ($t) => $t->json('category_attributes'));
        });
    }

    public function down(): void
    {
        // Columns that the live server already had before this migration are left alone.
        $own = [
            'vendor_sku', 'manufacturer', 'model_no', 'collection_series', 'material_grade', 'secondary_material', 'colour_code',
            'finish_type', 'finish_code', 'mrp', 'unit', 'lead_time_days', 'is_customizable', 'made_to_order', 'assembly_required',
            'installation_included', 'installation_charges', 'warranty_exclusions', 'return_eligible', 'replacement_policy',
            'damage_policy', 'packed_length_cm', 'packed_width_cm', 'packed_height_cm', 'dispatch_from', 'shipping_restrictions',
            'is_fragile', 'is_bulky', 'catalogue_url', 'cad_url', 'image_folder_url', 'vendor_notes', 'category_attributes',
            'whats_included', 'whats_not_included', 'faq_1_question', 'faq_1_answer', 'faq_2_question', 'faq_2_answer', 'faq_3_question', 'faq_3_answer',
            'payment_terms', 'price_valid_until',
        ];
        Schema::table('products', function (Blueprint $table) use ($own) {
            $table->dropColumn(array_values(array_filter($own, fn ($c) => Schema::hasColumn('products', $c))));
        });
    }
};
