<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFiberyIdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fibery_ids', function (Blueprint $table) {
			$table->string('id',36)->primary();
			$table->string('model_type')->nullable();
			$table->string('model_id',50)->nullable();	// Caters for models with string and int IDs
            $table->timestamps();
			$table->index(['model_type','model_id'],'idxModel');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fibery_ids');
    }
}
