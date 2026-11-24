<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sham Cash payments are now manual: the seller transfers the package price
// in the Sham Cash app, uploads a screenshot of the receipt with the
// request, and an admin approves (the ad becomes featured) or rejects it.
//
// payments: + receipt (private file), seller note, review fields; status
// becomes a plain string so it can hold 'rejected' (pending / paid /
// rejected; the old 'failed'/'cancelled' were never used).
// settings: small key/value table for things the admin edits in the panel
// (the Sham Cash account details buyers pay to, the contact info shown in
// the privacy policy) without touching the server's .env.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
            $table->string('receipt_path')->nullable()->after('provider_reference');
            $table->text('note')->nullable()->after('receipt_path');
            $table->string('rejection_reason', 500)->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['receipt_path', 'note', 'rejection_reason', 'reviewed_at']);
        });
    }
};
