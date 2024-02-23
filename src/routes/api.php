<?php
// Fibery Integrations API
Route::group(['prefix'=>'api/fibery', 'as'=>'fibery.', 'namespace'=>'mvnrsa\FiberyAPIs\App\Controllers',
				'middleware'=>['api']], function ()
{
	if (config('fibery.integrations_enabled'))
	{
		Route::get('/','FiberyIntegrationController@fibery_index')->name('index');
		Route::post('/validate','FiberyIntegrationController@fibery_validate')->name('validate');
		Route::post('/api/v1/synchronizer/config','FiberyIntegrationController@fibery_config')
																			->name('synchronizer.config');
		Route::post('/api/v1/synchronizer/schema','FiberyIntegrationController@fibery_schema')
																			->name('synchronizer.schema');
		Route::post('/api/v1/synchronizer/data','FiberyIntegrationController@fibery_data')->name('synchronizer.data');
	}
	if (config('fibery.webhooks_enabled'))
		Route::post('webhook/{token}/{tag}','FiberyWebhookController@webhook')->name('webhook');
});
