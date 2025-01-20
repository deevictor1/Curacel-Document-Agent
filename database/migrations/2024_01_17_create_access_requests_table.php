<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('access_requests', function (Blueprint $table) {
            $table->id();
            $table->string('file_id');
            $table->string('file_name');
            $table->string('requester_email');
            $table->string('owner_email');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('access_requests');
    }
}; 