<?php

namespace Kirki\Database\Migrations;

use Kirki\Framework\Contracts\Migration;
use Kirki\Framework\Database\Schema\Structure;
use Kirki\Framework\Supports\Facades\Schema;

class CreateCollaborationsConnectedTable implements Migration
{
    public function up()
    {
        Schema::create('kirki_collaborations_connected', function (Structure $table) {
            $table->big_integer('id', true)->primary();
            $table->big_integer('user_id');
            $table->big_integer('post_id');
            $table->string('session_id', 50);
            $table->timestamp('created_at')->use_current();
            $table->datetime('updated_at')->use_current();
        });
    }

    public function down()
    {
        Schema::drop_if_exists('kirki_collaborations_connected');
    }
}
