# Fibery APIs

An implementation of the Fibery APIs for Laravel.

(c) 2024 - Marnus van Niekerk - fibery@mjvn.net

## APIs Included

- Integrations API - For automatically including your models as entities inside Fibery.
- Webhooks - For updating your models from Fibery webhooks whenever fields are changed in Fibery.
- REST API - For querying the schema, retrieving, creating and updating entities and creating fields in Fibery.

## Two-Way Sync

A *minimalist* form of Two-Way sync can be achieved by combining the Integrations API with Webhooks:

1. Use the Integrations API to include your model as an entity inside Fibery - the fields from Laravel will be **read only**.
2. Add some (shadow) field(s) to that entity in Fibery - these fields will **not** be read only.
3. Set up a webhook to publish changes to the entity back to your Laravel.
4. Configure the webhook handler to update your model's real field(s) from changes to the shadow field(s).
5. Any fields (not the shadow fields) that change in Laravel will be updated in Fibery the next time the
Integrations sync runs.

This is **not** ideal or perfect, but it's the best that the Fibery APIs can do at the moment.  
Unfortunately the Fibery Integrations API does not support any updates and it probably won't any time soon.  
More details are included in the Webhooks section below.

### Caution!!

Watch out for creating infinte loops.  **Do not** include fields that depend on each other in your webhook config in any way.

## Installation

Install via composer:

```
composer require mvnrsa/fibery-apis
```

Publish the config file:
```
php artisan vendor:publish --tag=fibery-config
```

Set your Fibery host and api token in `config/fibery.php`.
```
FIBERY_HOST=yourhost.fibery.io
FIBERY_API_TOKEN=your_token
```

If you are planning to use the webhooks you have to publish and run the migrations and seeder.
```
php artisan vendor:publish --tag=fibery-migrations
php artisan vendor:publish --tag=fibery-seeders
php artisan migrate
php artisan db:seed FiberyMapSeeder
```

The `FiberyMap` seeder will connect to Fibery via the API and populate the `fibery_map` table with all of your
Fibery types and fields, but without the Laravel model and field names set.  
You can run this seeder as often as you need to if you add types or fields to Fibery.

## Authentication and Authorisation

Unfortunately the Fibery Integrations and Webhooks APIs do not cater for bearer token authentication (yet),
so authentication on the Laravel side is with personal access tokens passed as part of the url (for webhooks)
or request variables (for Integrations).

As such your application has to support personal access tokens to these two APIs.

Authorisation is via a `Gate` that can be configured in the config file.  The default gate is  `fibery_access`
so you either have to add this gate to your applicaiton or configure it to use another, existing gate.

## Integrations API

If all you want is to publish your models to Fibery all you need to do is add your types with the corresponding
model classes and fields to the config file.  
There is a sensible sample using a hypothetical currencies type in the config file.

Then simply point your Fibery integration to `https://your.laravel.host/api/fibery` and let Fibery do the rest. :-)

## REST API

The package provides a `mvnrsa\FiberyAPIs\FiberyClient` class that has methods for:

1. Querying the Fibery schema;
2. Listing the fields for any entity type;
3. Adding, querying and updating entities; and
4. Adding, listing and deleting webhooks.

## Webhooks

If you are planning to use the webhooks you have to publish and run the migrations and seeder as above.   
Then you have to edit the rows in the `fibery_map` table to set the laravel model and fields names
and mark some fields a reference fields.  Reference fields are used by the API client to match entities
in Fibery to models in Laravel.

Then you can call the `add_webhook` method with a type name to generate a unique url and set up the webhook
in Fibery.

The method accepts a second `tag` parameter which adds a tag to the webhook url to make finding calls in log files
easier.

Use the `webhooks` method to list any existing webhooks and the `delete_webhook` method to deleted one.

### Caution

Note that for the webhooks to really work, you have to have the `fibery_map` set up properly with the
Fibery and Laravel entity/model and field names and have some fields marked a reference fields so that
the API client can figure out which enities map to which models.

Unfortunately the webhook payloads (effects) do not contain enough data on their own to make such a determination
so the client has to fetch more fields from Fibery to match the first time a specific model is updated.
The fibery IDs of the models are stored using a FiberyMap model and the fibery_map table.

### Example

Using our hypothetical Currency model as an example again, Fibery may have a `Code` field that matched the model's
`code` column.  Then the `Currency/Code` field can be set up to match `code` and `Currency` and if is marked as
a refernce field the API client will use the Code/code to figur out which model in Laravel matches which entity
in Fibery.
