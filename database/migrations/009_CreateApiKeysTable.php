<?php

namespace Glueful\Database\Migrations;

use Glueful\Database\Migrations\MigrationInterface;
use Glueful\Database\Schema\Interfaces\SchemaBuilderInterface;

/**
 * CreateApiKeysTable Migration
 *
 * Creates the api_keys table for hardened API key authentication.
 * Supports scopes, IP allowlist, expiration, rotation grace period,
 * and environment-prefixed keys (gf_live_* / gf_test_*).
 *
 * @package Glueful\Database\Migrations
 */
class CreateApiKeysTable implements MigrationInterface
{
    public function up(SchemaBuilderInterface $schema): void
    {
        if ($schema->hasTable('api_keys')) {
            return;
        }

        $schema->createTable('api_keys', function ($table) {
            $table->bigInteger('id')->primary()->autoIncrement();
            $table->string('uuid', 12);
            $table->string('user_id', 12);
            $table->string('name', 255);
            $table->string('key_prefix', 24);
            $table->string('key_hash', 64);
            $table->text('scopes')->nullable();
            $table->text('allowed_ips')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->bigInteger('rotated_from_id')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('created_at')->default('CURRENT_TIMESTAMP');
            $table->timestamp('updated_at')->default('CURRENT_TIMESTAMP');

            // Uniqueness + lookup indexes
            $table->unique('uuid');
            $table->unique('key_hash');
            $table->index('user_id');
            $table->index('key_prefix');
        });
    }

    public function down(SchemaBuilderInterface $schema): void
    {
        $schema->dropTableIfExists('api_keys');
    }

    public function getDescription(): string
    {
        return 'Creates api_keys table for hardened API key authentication '
            . '(scopes, IP allowlist, expiration, rotation grace period, '
            . 'environment-prefixed keys).';
    }
}
