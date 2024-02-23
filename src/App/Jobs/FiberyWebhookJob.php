<?php
namespace mvnrsa\FiberyAPIs\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\CheckListItem;
use mvnrsa\FiberyAPIs\App\Models\FiberyMap;
use mvnrsa\FiberyAPIs\App\Models\FiberyID;
use mvnrsa\FiberyAPIs\FiberyClient;
use Str;

class FiberyWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $id;
	protected $payload;
	protected $user_id;
	protected $model_name;
	protected $entities;
	protected $fields;

    /**
     * Create a new job instance.
     *
     * @return void */
    public function __construct($id, $model_name, $payload, $user_id)
    {
		$this->id = $id;
		$this->user_id	= $user_id;
		$this->payload = $payload;
		$this->model_name = $model_name;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
		$results = [];
		$this->entities = FiberyMap::entities();

		// Loop through the effects and handle them
		foreach (($this->payload['effects'] ?? []) as $key => $effect)
			$results[$key] = $this->handleEffect($effect);

		$ci = CheckListItem::create([
										"name"	=> "Fibery Webhook Job for $this->model_name",
										"type"	=> "fibery_webhook_job",
										'details' => $results,
									]);
    }

	private function handleEffect($effect)
	{
		try {
			$action = $effect['effect'] ?? '';
			$fibery_id = $effect['id'] ?? null;
			$entity_name = $effect['type'] ?? null;
			$model_class = "\App\Models\\" . $this->entities[$entity_name]['our_name'] ?? '';
	
			if (empty($this->fields[$entity_name]))
				$this->fields[$entity_name] = FiberyMap::their_fields($entity_name, false)
														->pluck('our_name','their_name');
	
			$values_after = collect($effect['values'] ?? [])->only($this->fields[$entity_name]->keys());
			$values_before = collect($effect['valuesBefore'] ?? [])->only($this->fields[$entity_name]->keys());

			if ($model = $this->getModel($model_class, $action, $fibery_id))
			{
				foreach ($values_after as $their_field_name => $value)
				{
					$our_field_name = $this->fields[$entity_name][$their_field_name];
					$model->$our_field_name = $value;
				}
				$model->save();
			}
			else
				throw new \Exception("Model not found for Fibery entity $entity_name/$action/$fibery_id");
		} catch (\Exception $e) {
			report($e);
			return false;
		}

		return true;
	}

	private function getModel($model_class, $action, $fibery_id)
	{
		if ($action == 'fibery.entity/update')
		{
			if (!$model = FiberyID::find($fibery_id)?->model)
				$model = $this->findModelByRefenceFields($model_class, $fibery_id);
		}
		elseif ($action == 'fibery.entity/create')
		{
			/* 
			 * FIXME - This needs a better solution!!!
			 * Creating a model without any columns will *fail* for most models
			 * unless all columns are either nullable or have default values
			 */
			try {
				$keyName = (new $model_class)->getKeyName();
				$model = $model_class::create([]);
				if ($model)
					FiberyID::create([ 'model_type'=>$model_class, 'model_id'=>$model->$keyName] );
			} catch (\Exception $e) {
				report ($e);
				$model = null;
			}
		}
		else
		{
			report("Unknown Fibery action: $action");
			return false;
		}

		return $model;
	}

	/*
	 * We have to try and find the model using reference fields and a call to the Fibery REST API
	 * This will be *very slow*, but it should only happen once for every entity / model
	 */
	private function findModelByRefenceFields($model_class, $fibery_id)
	{
		$entity_type = FiberyMap::models()[class_basename($model_class)]['their_name'] ?? null;
		$reference_fields = FiberyMap::reference_fields(class_basename($model_class));
		$entity = (new FiberyClient)->get_entity($entity_type, $fibery_id, $reference_fields->keys());
		$keyName = (new $model_class)->getKeyName();

		$q = $model_class::select($keyName);
		foreach ($reference_fields as $their_name => $our_name)
			$q = $q->where($our_name, $entity[$their_name] ?? '');
		$model = $q->first();

		if ($model)
			FiberyID::create([ 'id'=>$fibery_id, 'model_type'=>$model_class, 'model_id'=>$model->$keyName ]);

		return $model;
	}
}
