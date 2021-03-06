<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAclSettingsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_acl_settings', function (Blueprint $table): void {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('filter_name');
            $table->jsonb('value');
            $table->timestamps();

            $table->unique(['user_id', 'filter_name']);
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_acl_settings');
    }
}
