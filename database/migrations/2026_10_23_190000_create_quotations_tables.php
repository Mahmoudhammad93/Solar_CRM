<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('public_token', 64)->unique();
            $table->string('proposal_type')->default('hybrid')->index(); // hybrid|pump
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('calculation_run_id')->nullable()->constrained('calculation_runs')->nullOnDelete();
            $table->foreignId('current_revision_id')->nullable();
            $table->timestamps();
        });

        Schema::create('quotation_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('revision')->default(1);
            $table->string('status')->default('draft')->index();
            $table->boolean('is_locked')->default(false);
            $table->date('valid_until')->nullable();
            $table->string('system_type')->nullable();
            $table->decimal('system_capacity_kw', 12, 3)->nullable();
            $table->string('site_address')->nullable();
            $table->string('customer_state')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('customer_terms')->nullable();
            $table->json('payment_schedule')->nullable();
            $table->decimal('advance_amount', 14, 2)->default(0);
            $table->json('pump_details')->nullable();
            $table->json('hybrid_assumptions')->nullable();
            $table->json('lines')->nullable();
            $table->json('totals')->nullable();
            $table->json('tax_config')->nullable();
            $table->json('branding_snapshot')->nullable();
            $table->json('template_texts')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['quotation_id', 'revision']);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->foreign('current_revision_id')->references('id')->on('quotation_revisions')->nullOnDelete();
        });

        Schema::create('quotation_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('revision_id')->nullable()->constrained('quotation_revisions')->nullOnDelete();
            $table->string('action');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_activities');
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropForeign(['current_revision_id']);
        });
        Schema::dropIfExists('quotation_revisions');
        Schema::dropIfExists('quotations');
    }
};
