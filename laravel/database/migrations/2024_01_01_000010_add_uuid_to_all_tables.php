<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add UUID columns to all tables.
 *
 * UUIDs are used as public identifiers while auto-increment IDs remain as internal primary keys.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add uuid to users
        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id')->unique();
        });

        // Add uuid to categories
        Schema::table('categories', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id')->unique();
        });

        // Add uuid to tags
        Schema::table('tags', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id')->unique();
        });

        // Add uuid to articles + UUID foreign keys
        Schema::table('articles', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id')->unique();
            $table->uuid('category_uuid')->nullable()->after('category_id');
            $table->uuid('author_uuid')->nullable()->after('author_id');
            $table->uuid('cover_image_uuid')->nullable()->after('cover_image_id');

            // Indexes for UUID foreign keys
            $table->index('category_uuid');
            $table->index('author_uuid');
            $table->index('cover_image_uuid');
        });

        // Add uuid to media_files
        Schema::table('media_files', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id')->unique();
            $table->uuid('uploader_uuid')->nullable()->after('size_bytes');
            $table->index('uploader_uuid');
        });

        // Add uuid to contact_messages
        Schema::table('contact_messages', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id')->unique();
        });

        // Add uuid to site_settings
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id')->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropColumn(['uuid', 'category_uuid', 'author_uuid', 'cover_image_uuid']);
        });

        Schema::table('media_files', function (Blueprint $table): void {
            $table->dropColumn(['uuid', 'uploader_uuid']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('uuid');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn('uuid');
        });

        Schema::table('tags', function (Blueprint $table): void {
            $table->dropColumn('uuid');
        });

        Schema::table('contact_messages', function (Blueprint $table): void {
            $table->dropColumn('uuid');
        });

        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn('uuid');
        });
    }
};