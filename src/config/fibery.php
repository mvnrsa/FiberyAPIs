<?php
return [
	'host' => env('FIBERY_HOST'),
	'token' => env('FIBERY_API_TOKEN'),
	// 'api_id' => 'your-api-id',		// Defaults to app.name-fibery-integration
	// 'api_name' => 'Your API Name',	// Defaults to app.name Fibery Integration
	"api_version" => "0.0.1",			// Increase whenever you make changes to the schema
	"integrations_enabled" => false,
	"webhooks_enabled" => false,
	// "webhooks_user_id" => 1,			// Defaults to current logged in user
	"gate"	=> "fibery_access",
	"log_requests" => false,
	"schema" => [
		/*
		 * Currencies as sample
		 *
		"currencies" => [
			'model' => '\App\Models\Currency',
			'name'  => 'Currencies',
			'fields' => [
					"id" => [
						"name" => "ID",
						"type" => "id",
					],
					"iso_4217" => [
						"name" => "Code",
						"type" => "text",
					],
					"name" => [
						"name" => "Name",
						"type" => "text",
					],
					"symbol" => [
						"name" => "Symbol",
						"type" => "text",
					],
					"conversion_rate" => [
						"name" => "Current Rate",
						"type" => "number",
					],
				],
			],
		 */
	],
];
