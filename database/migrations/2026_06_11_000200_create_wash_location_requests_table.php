<?php

use App\Models\WashLocationRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wash_location_requests', function (Blueprint $table) {
            $table->id();
            $table->string('responsible_name');
            $table->string('email');
            $table->string('phone', 30);
            $table->string('business_name');
            $table->string('zip_code', 20)->nullable();
            $table->string('address');
            $table->string('district')->nullable();
            $table->string('city');
            $table->string('state', 2);
            $table->unsignedSmallInteger('employees_count')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default(WashLocationRequest::STATUS_PENDING_REVIEW)->index();
            $table->timestamps();

            $table->index(['email', 'status']);
            $table->index(['phone', 'status']);
            $table->index(['business_name', 'city', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wash_location_requests');
    }
};
