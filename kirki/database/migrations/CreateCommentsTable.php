<?php

namespace Kirki\Database\Migrations;

use Kirki\Framework\Contracts\Migration;
use Kirki\Framework\Database\Schema\Structure;
use Kirki\Framework\Supports\Facades\Schema;

class CreateCommentsTable implements Migration
{
    public function up()
    {
        Schema::create('kirki_comments', function (Structure $table) {
            $table->id();
            $table->unsigned_big_integer('user_id');
            $table->unsigned_big_integer('post_id');
            $table->unsigned_big_integer('parent_id')->default(0);
            $table->text('comment');
            $table->string('meta_data');
            $table->integer('status')->comment('1=active, 2=resolved, 0=deleted');
            $table->timestamp('created_at')->use_current();
            $table->datetime('updated_at')->use_current();
            $table->index('post_id', 'post_id');
            $table->foreign('post_id')->references('ID')->on('posts')->cascade_on_delete();
        });
    }

    public function down()
    {
        Schema::drop_if_exists('kirki_comments');
    }
}
