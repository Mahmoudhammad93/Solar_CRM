<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_identifier')->nullable();
            $table->string('status')->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('category')->index();
            $table->string('attribute_key');
            $table->string('label');
            $table->string('data_type')->default('string'); // string|number|boolean
            $table->string('unit')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['category', 'attribute_key']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('sku')->unique();
            $table->string('category')->index();
            $table->string('brand')->nullable()->index();
            $table->string('model')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('specs')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('purchase_cost', 14, 2)->default(0);
            $table->decimal('sale_price', 14, 2)->default(0);
            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->unsignedInteger('warranty_months')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active')->index(); // active|archived
            $table->string('image_path')->nullable();
            $table->boolean('tracks_serial_batch')->default(false);
            $table->decimal('reorder_threshold', 14, 3)->default(0);
            $table->boolean('is_archived')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('available', 14, 3)->default(0);
            $table->decimal('reserved', 14, 3)->default(0);
            $table->decimal('allocated', 14, 3)->default(0);
            $table->decimal('issued', 14, 3)->default(0);
            $table->decimal('returned', 14, 3)->default(0);
            $table->decimal('damaged', 14, 3)->default(0);
            $table->timestamps();
            $table->unique(['warehouse_id', 'product_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->timestamp('moved_at')->index();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->nullable()->index();
            $table->string('movement_type')->index(); // opening|receive|reserve|unreserve|allocate|deallocate|issue|return|damage|adjust
            $table->decimal('quantity', 14, 3); // signed delta applied to primary state
            $table->decimal('available_delta', 14, 3)->default(0);
            $table->decimal('reserved_delta', 14, 3)->default(0);
            $table->decimal('allocated_delta', 14, 3)->default(0);
            $table->decimal('issued_delta', 14, 3)->default(0);
            $table->decimal('returned_delta', 14, 3)->default(0);
            $table->decimal('damaged_delta', 14, 3)->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'warehouse_id', 'moved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('vendors');
    }
};
