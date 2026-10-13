<?php

namespace Kirki\Database\Migrations;

use Kirki\Framework\Contracts\Migration;
use Kirki\Framework\Database\Schema\Structure;
use Kirki\Framework\Supports\Facades\Schema;

/**
 * Table for the data of each version.
 *
 * Replaces the old `staged_{version}_{meta_key}` post meta rows. Each row belongs to one version
 * (`stage_id`). `meta_key` is the same key the published data uses in wp_postmeta, e.g. `kirki`.
 */
class CreatePageStageDataTable implements Migration
{
    public function up()
    {
        Schema::create('kirki_stage_data', function (Structure $table) {
            $table->id();
            $table->unsigned_big_integer('stage_id');
            $table->string('meta_key', 191);
            $table->long_text('meta_value')->nullable();
            $table->unique(['stage_id', 'meta_key'], 'stage_meta_key');
            $table->foreign('stage_id')->references('id')->on('kirki_stages')->cascade_on_delete();
        });
    }

    public function down()
    {
        Schema::drop_if_exists('kirki_stage_data');
    }
}
