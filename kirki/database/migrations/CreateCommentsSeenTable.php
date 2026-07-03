<?php

namespace Kirki\Database\Migrations;

use Kirki\Framework\Contracts\Migration;
use Kirki\Framework\Database\Schema\Structure;
use Kirki\Framework\Supports\Facades\Schema;

class CreateCommentsSeenTable implements Migration
{
    public function up()
    {
        Schema::create('kirki_comments_seen', function (Structure $table) {
            $table->id();
            $table->unsigned_big_integer('comment_id');
            $table->unsigned_big_integer('user_id');
            $table->unique(['user_id', 'comment_id'], 'user_comment_unique');
            $table->foreign('comment_id')->references('id')->on('kirki_comments')->cascade_on_delete();
        });
    }

    public function down()
    {
        Schema::drop_if_exists('kirki_comments_seen');
    }
}
