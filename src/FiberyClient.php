<?php
namespace mvnrsa\FiberyAPIs;

use Http;
use Auth;
use Log;
use Str;

class FiberyClient {
	private $debug = false;
	private $url = '';
	private $webhook_url = '';
	private $token = '';
	private $entity_collection = null;

	public $last_request = '';
	public $last_response = '';

	function __construct($debug=false)
	{
		$this->url = "https://" . config('fibery.host') . "/api/commands";
		$this->webhook_url = "https://" . config('fibery.host') . "/api/webhooks/v2";
		$this->token = config('fibery.token');
		$this->debug = $debug;

		return true;
	}

	function set_debug($debug)
	{
		$this->debug = $debug;
	}

	private function callAPI($command, $batch=false, $args=[], $params=[])
	{
		$request_data = [];
		$request_data[0] = new \stdClass();
		$request_data[0]->command = $command;
		if (!empty($args))
			$request_data[0]->args = $args;
		if (!empty($params))
			$request_data[0]->params = $params;

		if ($batch)
		{
			$batch_data = [];
			$batch_data[0] = new \stdClass();
			$batch_data[0]->command = 'fibery.schema/batch';
			$batch_data[0]->args = new \stdClass();
			$batch_data[0]->args->commands = $request_data;

			$request_data = $batch_data;
		}

		if ($this->debug)
			Log::debug("FiberyClient POSTing to $this->url: " . json_encode($request_data));

		$response = Http::withToken($this->token)->post($this->url,$request_data);

		$this->last_request = $request_data;
		$this->last_response = $response;

		if ($this->debug)
			Log::debug("FiberyClient response: " . json_encode($response->json()));

		if ($response->failed())
			return false;

		$result = $response->json()[0] ?? null;

		if (!isset($result['success']) || $result['success'] !== true || !isset($result['result']))
			return false;

		return $result['result'];
	}

	public function entities($all=false)
	{
		if (isset($this->entity_collection))
			return $this->entity_collection;

		$retval = $this->callAPI("fibery.schema/query");

		$this->entity_collection = collect($retval['fibery/types'])
											->keyBy('fibery/name')
											->filter(function ($value, $key) {
												return !$value['fibery/deleted?'];
											});

		if (!$all)
			return $this->entity_collection->filter(function ($value, $key) {
												return preg_match("/^[A-Z]/",$key);
											});

		return $this->entity_collection;
	}

	public function types($all=false)
	{
		return $this->entities($all);
	}

	public function entity_exists($entity_name)
	{
		return empty($this->fields($entity_name));
	}

	public function get_entity($type, $fibery_id, $fields=['fibery/id'])
	{
		$args = [
					'query' => [
									"q/from" => $type,
									"q/select" => [ "fibery/id" ],
									"q/where" => [ "=", "fibery/id", "\$fibery_id" ],
									"q/limit" => 1,
								],
				  	'params' => [ "\$fibery_id" => $fibery_id ],
				];

		foreach ($fields as $field)
			$args['query']['q/select'][] = $field;

		$retval = $this->callAPI('fibery.entity/query', false, $args);

		return collect($retval[0] ?? []);
	}

	public function create_entity($type, $fields_array)
	{
		// We explicitly set the fibery id to a guid ourselves - not really needed
		if (is_array($fields_array) && !isset($fields_array['fibery/id']))
			$fields_array['fibery/id'] = Str::uuid();

		$args = [ "type" => $type, "entity" => $fields_array ];

		$retval = $this->callAPI('fibery.entity/create', false, $args);

		if (!isset($retval['fibery/id']))
			return false;

		return [ 'id' => $retval['fibery/id'], 'entity' => collect($retval) ];
	}

	public function update_entity($type, $fibery_id, $fields_array)
	{
		if (!is_array($fields_array))
			return false;

		$fields_array['fibery/id'] = $fibery_id;

		$args = [ "type" => $type, "entity" => $fields_array ];

		$retval = $this->callAPI('fibery.entity/update', false, $args);

		if (!isset($retval['fibery/id']))
			return false;

		return [ 'id' => $retval['fibery/id'], 'entity' => collect($retval) ];
	}

	public function fields($entity_name, $all=true)
	{
		$entity = $this->entities()[$entity_name] ?? null;

		if (!isset($entity['fibery/fields']))
			return [];

		$fields = collect($entity['fibery/fields'])
						->keyBy('fibery/name')
						->filter(function ($value, $key) {
									return !$value['fibery/deleted?'];
								});

		if (!$all)
			return $fields->filter(function ($value, $key) {
										return preg_match("/^[A-Z]/",$key);
									});

		return $fields;
	}

	public function add_field($entity_name, $field_name, $type, $read_only=false,
									$default_value='', $uom='', $decimals=0)
	{
		if (strtolower($type) == 'number')
			$type = 'decimal';

		$args = [];
		$args["fibery/holder-type"] = $entity_name;
		$args["fibery/name"] 		= basename($entity_name) . "/" . $field_name;
		$args["fibery/type"] 		= "fibery/" . strtolower($type);
		$args["fibery/meta"] 		= [];
		$args["fibery/meta"]["fibery/readonly?"] = $read_only;
		if ($default_value === 0 || !empty($default_value))
			$args["fibery/meta"]["fibery/default-value"] = $default_value;
		if (!empty($uom))
			$args["fibery/meta"]["ui/number-unit"] = $uom;
		if (strtolower($type) == 'decimal')
			$args["fibery/meta"]["ui/number-format"] = 'Number';
		if (is_numeric($decimals))
			$args["fibery/meta"]["ui/number-precision"] = $decimals;

		$args = (object)$args;

		$retval = $this->callAPI('schema.field/create', true, $args);

		return ($retval === 'ok');
	}

	public function webhooks()
	{
		$response = Http::withToken($this->token)->get($this->webhook_url);
		if ($response->failed())
			return false;

		return collect($response->json())->keyBy('id');
	}

	public function add_webhook($type, $tag=null)
	{
		$user_id = config('fibery.webhooks_user_id');
		if (!$user = (!empty($user_id) ? User::find($user_id) : Auth::user()))
			return false;

		if (empty($tag))
			$tag = Str::slug(basename($type));

		$token = $user->createToken('auth-token')->plainTextToken;
		$url = route('fibery.webhook',[ 'tag'=>$tag, 'token'=>urlencode($token) ]);

		$request_data = [ 'type'=>$type, 'url'=>$url ];
		$response = Http::withToken($this->token)->post($this->webhook_url,$request_data);
		if ($response->failed())
			return false;

		return $response->json()['id'] ?? null;
	}

	public function delete_webhook($webhook_id)
	{
		$response = Http::withToken($this->token)->delete($this->webhook_url . "/$webhook_id");

		return !$response->failed();
	}
}
