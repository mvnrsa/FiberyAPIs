<?php
namespace mvnrsa\FiberyAPIs\App\Controllers;

use Log;
use Str;
use Auth;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;
use App\Http\Controllers\Controller;
use App\Models\CheckListItem;
use mvnrsa\FiberyAPIs\App\Jobs\FiberyWebhookJob;

class FiberyWebhookController extends Controller
{
	public function webhook(string $token, string $tag, Request $request)
	{
		$user = $this->get_user($token);
		$id = Str::uuid();

		$job = FiberyWebhookJob::dispatch($id, $tag, $request->all(), $user->id);

		return response()->json([ 'message' => "OK", "id" => $id ]);
	}

	private function get_user($token)
	{
		if (empty($token) || (!$token = PersonalAccessToken::findToken($token)))
			abort(response()->json(['message'=>'Unauthorized'],401));

		Auth::login($user = $token->tokenable);

		$gate = config('fibery.gate','fibery_access');
		if (Gate::denies($gate))
			abort(response()->json(['message'=>'Unauthorized'],401));

		return $user;
	}
}
