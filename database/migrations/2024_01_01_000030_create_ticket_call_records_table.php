<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_call_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('support_executive_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_mobile');
            $table->datetime('call_datetime');
            $table->string('call_purpose')->nullable();
            $table->enum('call_status', ['completed', 'missed', 'follow_up', 'voicemail'])->default('completed');
            $table->text('notes')->nullable();
            $table->text('resolution')->nullable();
            $table->boolean('follow_up_required')->default(false);
            $table->date('follow_up_date')->nullable();
            $table->string('recording_url')->nullable();
            $table->timestamps();

            $table->index(['ticket_id', 'call_datetime']);
            $table->index('support_executive_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_call_records');
    }
};
