<?php

namespace Kirki\Database\Migrations;

use Kirki\Framework\Contracts\Migration;
use Kirki\Framework\Database\Schema\Structure;
use Kirki\Framework\Supports\Facades\Schema;

class CreateFormsDataTable implements Migration
{
    public function up()
    {
        Schema::create('kirki_forms_data', function (Structure $table) {
            $table->id();
            $table->unsigned_big_integer('form_id');
            $table->unsigned_big_integer('user_id')->nullable();
            $table->string('session_id');
            $table->big_integer('timestamp');
            $table->string('input_key');
            $table->string('input_value', 2000);
            $table->string('input_type', 100)->default('text');
            $table->timestamp('created_at')->use_current();
            $table->datetime('updated_at')->use_current();
            $table->index('form_id', 'form_id');
        });
    }

    public function down()
    {
        Schema::drop_if_exists('kirki_forms_data');
    }
}
