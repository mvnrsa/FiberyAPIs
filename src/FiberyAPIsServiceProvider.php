<?php
namespace mvnrsa\FiberyAPIs;

use Illuminate\Support\ServiceProvider;

class FiberyAPIsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
		// Routes
        include __DIR__."/routes/api.php";

		// Config file
		$this->publishes([
							__DIR__.'/config/fibery.php' => config_path('fibery.php')
						], 'fibery-config');

		// Migration for FiberyMap 
		$this->publishes([
							__DIR__.'/database/migrations/' => database_path('migrations')
						], 'fibery-migrations');

		// Migration for FiberyMap 
		$this->publishes([
							__DIR__.'/database/seeders/' => database_path('seeders')
						], 'fibery-seeders');
    }
}
