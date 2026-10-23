<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->foreignId('assigned_surveyor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->nullable()->index();
            $table->string('site_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->decimal('gps_lat', 10, 7)->nullable();
            $table->decimal('gps_lng', 10, 7)->nullable();
            $table->text('reschedule_cancel_reason')->nullable();

            // Survey
            $table->string('property_type')->nullable();
            $table->string('roof_type_material')->nullable();
            $table->decimal('usable_area_sqm', 12, 2)->nullable();
            $table->string('orientation_tilt')->nullable();
            $table->text('shading_obstructions')->nullable();
            $table->text('structural_concerns')->nullable();
            $table->text('site_access_notes')->nullable();
            $table->text('safety_notes')->nullable();

            // Utility
            $table->text('bill_consumption_evidence')->nullable();
            $table->decimal('monthly_units', 12, 2)->nullable();
            $table->decimal('sanctioned_load_kw', 10, 2)->nullable();
            $table->string('phase_type')->nullable();
            $table->string('utility_discom')->nullable();
            $table->string('meter_connection_type')->nullable();
            $table->string('proposed_hybrid_system')->nullable();
            $table->decimal('tentative_capacity_kw', 10, 2)->nullable();

            // Outcome
            $table->text('measurements')->nullable();
            $table->string('feasibility')->nullable();
            $table->text('customer_needs')->nullable();
            $table->text('objections')->nullable();
            $table->text('feedback')->nullable();
            $table->string('recommended_next_step')->nullable();
            $table->text('preliminary_bom')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('visit_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('reason')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['visit_id', 'created_at']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->morphs('documentable');
            $table->string('document_type')->nullable()->index();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->boolean('is_sensitive')->default(false);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('visit_histories');
        Schema::dropIfExists('visits');
    }
};
