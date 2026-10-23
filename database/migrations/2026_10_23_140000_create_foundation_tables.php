<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('module');
            $table->string('action');
            $table->string('key')->unique();
            $table->string('label')->nullable();
            $table->timestamps();
            $table->unique(['module', 'action']);
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->foreignId('role_id')->nullable()->after('phone')->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('role_id')->constrained()->nullOnDelete();
            $table->foreignId('team_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('team_id');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group');
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('unit')->nullable();
            $table->date('effective_from')->nullable();
            $table->string('status')->default('active');
            $table->string('source_reference')->nullable();
            $table->boolean('is_sample')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['group', 'status']);
        });

        Schema::create('setting_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setting_id')->constrained()->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('unit')->nullable();
            $table->date('effective_from')->nullable();
            $table->string('status')->default('active');
            $table->string('source_reference')->nullable();
            $table->boolean('is_sample')->default(true);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_summary')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_reference')->nullable();
            $table->text('summary')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });

        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->string('entity')->unique();
            $table->string('prefix');
            $table->string('format')->default('{PREFIX}-{YYYY}-{####}');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->boolean('reset_yearly')->default(false);
            $table->unsignedSmallInteger('last_reset_year')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequences');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('setting_versions');
        Schema::dropIfExists('settings');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['phone', 'is_active', 'last_login_at']);
        });

        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('branches');
    }
};
