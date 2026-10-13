<?php

namespace Kirki\Database\Migrations;

use Kirki\Framework\Contracts\Migration;
use Kirki\Framework\Database\Schema\Structure;
use Kirki\Framework\Supports\Facades\Schema;

/**
 * Table for page versions.
 *
 * Replaces the old `kirki_stage_versions` post meta. One row per version of a page.
 * The version's data is in `kirki_stage_data`.
 */
class CreatePageStagesTable implements Migration
{
    public function up()
    {
        Schema::create('kirki_stages', function (Structure $table) {
            $table->id();
            $table->unsigned_big_integer('post_id');
            $table->string('type', 50)->default('page');
            $table->unsigned_big_integer('version');
            $table->string('name');
            $table->unsigned_big_integer('edited_by')->default(0);
            $table->boolean('is_published')->default(0);
            $table->boolean('no_legacy_global_style')->default(0);
            $table->datetime('created_at');
            $table->datetime('updated_at');
            $table->unique(['post_id', 'version'], 'post_version');
            $table->index('post_id', 'post_id');
            $table->index(['post_id', 'is_published'], 'post_published');
            $table->foreign('post_id')->references('ID')->on('posts')->cascade_on_delete();
        });
    }

    public function down()
    {
        Schema::drop_if_exists('kirki_stages');
    }
}
