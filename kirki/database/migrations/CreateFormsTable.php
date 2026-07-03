<?php

namespace Kirki\Database\Migrations;

use Kirki\Framework\Contracts\Migration;
use Kirki\Framework\Database\Schema\Structure;
use Kirki\Framework\Supports\Facades\Schema;

class CreateFormsTable implements Migration
{
    public function up()
    {
        Schema::create('kirki_forms', function (Structure $table) {
            $table->id();
            $table->unsigned_big_integer('post_id');
            $table->string('form_ele_id');
            $table->string('name')->default('My Kirki Form');
            $table->timestamp('created_at')->use_current();
            $table->datetime('updated_at')->use_current();
        });
    }

    public function down()
    {
        Schema::drop_if_exists('kirki_forms');
    }
}
