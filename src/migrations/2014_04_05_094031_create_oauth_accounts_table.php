<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOauthAccountsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('oauth_accounts', function(Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->increments('id')->unsigned();

			$table->integer('user_id')->index();
			$table->string('provider');

			$table->string('access_token')->nullable();
			$table->string('refresh_token')->nullable();
			$table->string('expires_in')->nullable();
			$table->string('created')->nullable();
			$table->text('signature')->nullable();
			$table->text('scopes')->nullable();

			$table->string('uid')->nullable()->default(NULL);
            $table->string('username')->nullable()->default(NULL);
            $table->string('location')->nullable()->default(NULL);
            $table->string('description')->nullable()->default(NULL);
            $table->string('image_url')->nullable()->default(NULL);
            $table->string('url')->nullable()->default(NULL);
            $table->string('channel')->nullable()->default(NULL);

            $table->string('gender')->nullable()->default(NULL);
            $table->string('birthday')->nullable()->default(NULL);

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
		Schema::drop('oauth_accounts');
	}

}
