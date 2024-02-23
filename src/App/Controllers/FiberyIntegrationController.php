<?php
namespace mvnrsa\FiberyAPIs\App\Controllers;

use App\Http\Controllers\Controller;

use Log;
use Str;
use Auth;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;

class FiberyIntegrationController extends Controller
{
	public function fibery_index(Request $request)
	{
		if (config('fibery.log_requests'))
			Log::info("Fibery Integration API " . __FUNCTION__ .": " . json_encode($request->all()));

        return response()->json([
					"id" => config('fibery.api_id') ?? strtolower(config("app.name")) . '-fibery-integration',
					"name" => config('fibery.api_name') ?? Str::title(config("app.name")) . ' Fibery Integration',
					"version" => config('fibery.api_version',"0.0.1"),
					"type" => 'crunch',
					"description" => title(config("app.name")) . ' Fibery Integration',
					"authentication" => [
											[
												"description" => 'Provide Token',
												"name" => 'Token Authentication',
												"id" => 'token',
												"fields" => [ [
																"type" => 'text',
																"description" => 'Personal Token',
																"id" => 'token',
															] ],
											],
										],
					"sources" => [],
					"responsibleFor" => [
											"dataSynchronization" => true,
										],
				]);
	}

	public function fibery_validate(Request $request)
	{
		$user = $this->get_user($request, __FUNCTION__);

		return response()->json([ 'name' => $user?->name ?? null ]);
	}

	public function fibery_config(Request $request)
	{
		$user = $this->get_user($request, __FUNCTION__);

		$merged_array = [];
        $data = [];
        foreach ( config('fibery.schema') as $key => $field)
			$merged_array[] = [ 'id' => $key, 'name' => ($field['name'] ?? ucfirst($key)) ];

        $data['types'] = $merged_array;
        $data['filters'] = [];

        return response()->json($data);
	}

	public function fibery_schema(Request $request)
	{
		$user = $this->get_user($request, __FUNCTION__);

		$schema = collect([]);
        $tmp = config('fibery.schema');
		foreach ($tmp as $type => $data)
			$schema[$type] = $data['fields'];

		$types = $request->types ?? [];

		return response()->json($schema->only($types));
	}

	public function fibery_data(Request $request)
	{
		$user = $this->get_user($request, __FUNCTION__);

		$cnt    = 0;
		$items  = [];
		$models = collect([]);
        $fields = [];

		$type = $request->requestedType;
        $fibery_data = config('fibery.schema');

        if (array_key_exists($type, $fibery_data))
        {
            $className = $fibery_data[$type]['model'] ?? null;

            if (!empty($className) && class_exists($className) && method_exists($className, 'getFiberyData'))
				$items = $className::getFiberyData();
            elseif (!empty($className) && class_exists($className))
			{
                $models = $className::all();
                $fields = array_keys($fibery_data[$type]['fields']);

				foreach ($models as $model)
					$items[] = $model->only($fields);
            }
        }

		return response()->json([ "items" => $items ]);
	}

	private function get_user(Request $request, $method)
	{
		if (config('fibery.log_requests'))
			Log::info("Fibery Integration API $method: " . json_encode($request->all()));

		$token = '';

		if ($request->has('account.token'))
			$token = $request->account['token'];
		elseif ($request->has('fields.token'))
			$token = $request->fields['token'];

		if (empty($token) || (!$token = PersonalAccessToken::findToken($token)))
			abort(response()->json(['message'=>'Unauthorized'],401));

		Auth::login($user = $token->tokenable);

		$gate = config('fibery.gate','fibery_access');
		if (Gate::denies($gate))
			abort(response()->json(['message'=>'Unauthorized'],401));


		return $user;
	}
}
