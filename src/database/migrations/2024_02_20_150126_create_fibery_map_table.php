<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFiberyMapTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fibery_map', function (Blueprint $table) {
            $table->id();
			$table->string('type')->default('model');
			$table->string('parent')->nullable();
			$table->string('our_name')->nullable();
			$table->string('their_name')->nullable();
			$table->string('fibery_id',36);
			$table->string('fibery_type')->nullable();
			$table->string('webhook_fibery_id')->nullable();
			$table->boolean('is_reference_field')->default(0);
			$table->json('meta_data')->nullable();
            $table->timestamps();
			$table->softDeletes();
			$table->index(['type','parent','our_name'],'idxOurName');
			$table->index(['type','parent','their_name'],'idxTheirName');
			$table->index(['fibery_id'],'idxFiberyID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fibery_map');
    }
}
