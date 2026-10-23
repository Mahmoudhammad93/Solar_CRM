<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->date('ordered_on')->nullable();
            $table->foreignId('quotation_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('quotation_revision_id')->nullable()->constrained('quotation_revisions')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('site_address')->nullable();
            $table->string('project_type')->nullable();
            $table->decimal('capacity_kw', 12, 3)->nullable();
            $table->decimal('contract_value', 14, 2)->default(0);
            $table->text('confirmed_scope')->nullable();
            $table->json('payment_schedule')->nullable();
            $table->json('lines_snapshot')->nullable();
            $table->json('totals_snapshot')->nullable();
            $table->date('delivery_target')->nullable();
            $table->string('status')->default('pending')->index(); // pending|active|completed|cancelled
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sales_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('engineering_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('installation_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->date('planned_start')->nullable();
            $table->date('planned_end')->nullable();
            $table->date('actual_start')->nullable();
            $table->date('actual_end')->nullable();
            $table->string('priority')->nullable();
            $table->string('current_milestone_key')->nullable()->index();
            $table->decimal('percent_complete', 5, 2)->default(0);
            $table->text('risks_blockers')->nullable();
            $table->json('customer_updates')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
        });

        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('milestone_key');
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('weight', 5, 2)->default(10);
            $table->string('status')->default('pending'); // pending|active|completed|hold|exception
            $table->text('hold_reason')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'milestone_key']);
        });

        Schema::create('installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('crew')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->json('checklist')->nullable();
            $table->json('serial_numbers')->nullable();
            $table->text('safety_notes')->nullable();
            $table->json('commissioning_readings')->nullable();
            $table->string('customer_signoff_path')->nullable();
            $table->timestamp('signed_off_at')->nullable();
            $table->string('completion_report_path')->nullable();
            $table->text('handover_notes')->nullable();
            $table->string('status')->default('planned');
            $table->timestamps();
        });

        Schema::create('project_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku')->nullable();
            $table->string('title');
            $table->string('unit')->nullable();
            $table->decimal('quantity_required', 14, 3)->default(0);
            $table->decimal('quantity_reserved', 14, 3)->default(0);
            $table->decimal('quantity_allocated', 14, 3)->default(0);
            $table->decimal('quantity_issued', 14, 3)->default(0);
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_service')->default(false);
            $table->timestamps();
            $table->index(['project_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_materials');
        Schema::dropIfExists('installations');
        Schema::dropIfExists('project_milestones');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });
        Schema::dropIfExists('projects');
        Schema::dropIfExists('orders');
    }
};
