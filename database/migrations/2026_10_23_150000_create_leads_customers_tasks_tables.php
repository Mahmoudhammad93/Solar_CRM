<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lookups', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('value');
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['type', 'value']);
            $table->index(['type', 'is_active', 'sort_order']);
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_number')->unique();
            $table->string('customer_type')->nullable();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('mobile')->nullable()->index();
            $table->string('alternate_phone')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->decimal('gps_lat', 10, 7)->nullable();
            $table->decimal('gps_lng', 10, 7)->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('title')->nullable();
            $table->string('customer_type')->nullable();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('mobile')->nullable()->index();
            $table->string('alternate_phone')->nullable();
            $table->string('email')->nullable()->index();

            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->decimal('gps_lat', 10, 7)->nullable();
            $table->decimal('gps_lng', 10, 7)->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('territory')->nullable();

            $table->string('system_type')->nullable();
            $table->string('property_segment')->nullable();
            $table->string('roof_site_type')->nullable();
            $table->decimal('monthly_bill', 12, 2)->nullable();
            $table->decimal('monthly_units', 12, 2)->nullable();
            $table->decimal('sanctioned_load_kw', 10, 2)->nullable();
            $table->decimal('desired_capacity_kw', 10, 2)->nullable();
            $table->string('priority')->nullable();
            $table->string('budget_range')->nullable();

            $table->string('source')->nullable();
            $table->string('campaign_details')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stage')->nullable()->index();
            $table->string('loss_reason')->nullable();
            $table->unsignedTinyInteger('probability')->nullable();
            $table->decimal('expected_value', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();

            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->timestamp('reminder_at')->nullable();
            $table->string('follow_up_outcome')->nullable();

            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('converted_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('lead_stage_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('from_stage')->nullable();
            $table->string('to_stage');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['lead_id', 'created_at']);
        });

        Schema::create('lead_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->string('outcome')->nullable();
            $table->timestamp('interacted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['lead_id', 'interacted_at']);
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable()->index();
            $table->string('priority')->nullable();
            $table->string('status')->nullable()->index();
            $table->string('task_type')->nullable();
            $table->timestamp('reminder_at')->nullable();
            $table->string('outcome')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->nullableMorphs('taskable');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('lead_interactions');
        Schema::dropIfExists('lead_stage_histories');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('lookups');
    }
};
