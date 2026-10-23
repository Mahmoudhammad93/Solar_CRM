<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('whatsapp_consent_status', 32)->default('unknown')->after('notes'); // opted_in|opted_out|unknown
            $table->timestamp('whatsapp_consent_at')->nullable()->after('whatsapp_consent_status');
            $table->string('whatsapp_consent_source')->nullable()->after('whatsapp_consent_at');
        });

        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 32); // whatsapp|email
            $table->string('template_key')->nullable();
            $table->string('recipient');
            $table->string('status', 32); // demo_simulated|failed only unless real provider responds
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_reference')->nullable();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->text('provider_response')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['channel', 'status']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_logs');
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_consent_status', 'whatsapp_consent_at', 'whatsapp_consent_source']);
        });
    }
};
