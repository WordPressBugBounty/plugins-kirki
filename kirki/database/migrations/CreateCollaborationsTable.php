<?php

namespace Kirki\Database\Migrations;

use Kirki\Framework\Contracts\Migration;
use Kirki\Framework\Database\Schema\Structure;
use Kirki\Framework\Supports\Facades\Schema;

class CreateCollaborationsTable implements Migration
{
    public function up()
    {
        Schema::create('kirki_collaborations', function (Structure $table) {
            $table->big_integer('id', true)->primary();
            $table->big_integer('user_id');
            $table->string('session_id', 50);
            $table->string('parent')->comment('table name');
            $table->big_integer('parent_id')->nullable()->comment('table row id');
            $table->long_text('data')->nullable()->comment('json data');
            $table->integer('status')->comment('1=active, 2=sent, 0=expire');
            $table->timestamp('created_at')->use_current();
            $table->datetime('updated_at')->use_current();
        });
    }

    public function down()
    {
        Schema::drop_if_exists('kirki_collaborations');
    }
}
