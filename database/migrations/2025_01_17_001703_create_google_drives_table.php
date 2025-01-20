<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('google_drives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained();
            $table->string('drive_id');
            $table->string('name');
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('token_expires_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('google_drives');
    }
};