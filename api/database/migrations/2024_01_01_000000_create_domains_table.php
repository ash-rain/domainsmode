<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * This migration creates the domains table for testing environments (SQLite).
 * In production/staging MySQL environments, this table is already created
 * and populated by the docker/mysql/init.sql dump.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('domains')) {
            Schema::create('domains', function (Blueprint $table) {
                $table->id();
                $table->string('domain')->unique();
                $table->string('nameserver_1')->nullable();
                $table->string('nameserver_2')->nullable();
                $table->string('nameserver_3')->nullable();
                $table->string('nameserver_4')->nullable();
                $table->string('mx_record')->nullable();
                $table->string('a_record')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
