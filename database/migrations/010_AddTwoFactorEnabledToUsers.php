<?php

namespace Glueful\Database\Migrations;

use Glueful\Database\Migrations\MigrationInterface;
use Glueful\Database\Schema\Interfaces\SchemaBuilderInterface;

/**
 * AddTwoFactorEnabledToUsers Migration
 *
 * Adds the two_factor_enabled boolean column to the users table for the
 * core email-PIN 2FA feature. Idempotent: re-running after a manual
 * ALTER TABLE that already added the column is a no-op.
 *
 * Uses the no-callback alterTable() form so it works against both the
 * released framework (1.44.x) and newer versions — the callback form is a
 * newer addition. Column registration happens via ColumnBuilder::__destruct,
 * so we force collection before flushing (mirrors the framework's own
 * createTable() callback handling).
 *
 * @package Glueful\Database\Migrations
 */
class AddTwoFactorEnabledToUsers implements MigrationInterface
{
    public function up(SchemaBuilderInterface $schema): void
    {
        if ($schema->hasColumn('users', 'two_factor_enabled')) {
            return;
        }

        $table = $schema->alterTable('users');
        $table->boolean('two_factor_enabled')->notNull()->default(false);

        // ColumnBuilder registers its column on __destruct; force it before
        // the alteration is generated so the ADD COLUMN statement is emitted.
        gc_collect_cycles();

        $table->execute();   // generate + queue the ALTER TABLE ADD COLUMN
        $schema->execute();  // flush pending SQL to the database
    }

    public function down(SchemaBuilderInterface $schema): void
    {
        if (!$schema->hasColumn('users', 'two_factor_enabled')) {
            return;
        }

        // Schema-level helper alters + executes in one call.
        $schema->dropColumn('users', 'two_factor_enabled');
    }

    public function getDescription(): string
    {
        return 'Adds two_factor_enabled boolean column to users for the core email-PIN 2FA feature.';
    }
}
