<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('file_activities', function (Blueprint $table) {
            $table->id();
            $table->string('file_id');
            $table->string('file_name');
            $table->string('actor_email'); // Who made the change
            $table->string('creator_email'); // Original file creator
            $table->enum('action_type', ['edit', 'delete', 'restore', 'rename']);
            $table->json('changes')->nullable(); // Store details about the changes
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('file_activities');
    }
}; 