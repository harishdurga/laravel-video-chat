<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoogleLanguagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('google_languages', function (Blueprint $table) {
            $table->id();
            $table->string('eng_name',191)->nullable();
            $table->string('trans_name',191)->nullable();
            $table->tinyInteger('enabled')->default(1);
            $table->string('lang_code',10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('google_languages');
    }
}
