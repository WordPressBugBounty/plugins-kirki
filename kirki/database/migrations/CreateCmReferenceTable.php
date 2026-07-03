<?php

namespace Kirki\Database\Migrations;

use Kirki\Framework\Contracts\Migration;
use Kirki\Framework\Database\Schema\Structure;
use Kirki\Framework\Supports\Facades\Schema;

class CreateCmReferenceTable implements Migration
{
    public function up()
    {
        Schema::create('kirki_cm_reference', function (Structure $table) {
            $table->id();
            $table->unsigned_big_integer('post_id');
            $table->string('field_meta_key');
            $table->unsigned_big_integer('ref_post_id');
            $table->index('post_id');
            $table->index('ref_post_id');
            $table->foreign('post_id')->references('ID')->on('posts')->cascade_on_delete();
            $table->foreign('ref_post_id')->references('ID')->on('posts')->cascade_on_delete();
        });
    }

    public function down()
    {
        Schema::drop_if_exists('kirki_cm_reference');
    }
}
