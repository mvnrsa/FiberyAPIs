<?php
namespace mvnrsa\FiberyAPIs\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use mvnrsa\FiberyAPIs\FiberyClient;

class FiberyMap extends Model
{
    use HasFactory;
    use SoftDeletes;

	public $table = 'fibery_map';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'type',
        'parent',
        'our_name',
        'their_name',
        'fibery_id',
        'fibery_type',
        'webhook_fibery_id',
        'meta_data',
    ];

	protected $casts = [
		'meta_data' => 'array',
	];

	public static function models()
	{
		return self::where('type','model')
							->whereNotNull('our_name')
							->orderBy('our_name')
							->get()
							->keyBy('our_name');
	}

	public static function entities()
	{
		return self::where('type','model')
							->orderBy('their_name')
							->get()
							->keyBy('their_name');
	}

	public static function our_fields($parent)
	{
		return self::where('type','field')
							->where('parent',$parent)
							->whereNotNull('our_name')
							->orderBy('our_name')
							->get()
							->keyBy('our_name');
	}

	public static function their_fields($parent, $all=true)
	{
		$retval = self::where('type','field')
							->where('parent',$parent)
							->orderBy('their_name')
							->get()
							->keyBy('their_name');

		if (!$all)
			return $retval->filter( function($value) {
										return !empty($value['our_name']);
									});

		return $retval;
	}

	public static function reference_fields($our_name)
	{
		$their_name = self::where('type','model')->where('our_name',$our_name)->first()?->their_name;

		return self::their_fields($their_name)
						->filter( function($value) {
										return $value->is_reference_field;
									})
						->pluck('our_name','their_name');
	}

	public static function populate()
	{
		$cnt = 0;
		$fc = new FiberyClient();
		$entities = $fc->entities();

		foreach ($entities as $entity_name => $meta)
		{
			$new = self::updateOrCreate([ 'fibery_id'=>$meta['fibery/id'] ],
										[ 'type'=>'model','their_name'=>$entity_name,
											'fibery_type'=>$meta['fibery/type']??null, 'meta_data'=>$meta ]);
			if ($new->wasRecentlyCreated === true)
				$cnt++;

			$cnt += self::populate_fields($entity_name, $fc->fields($entity_name, false));
		}

		return $cnt;
	}

	private static function populate_fields($parent, $fields)
	{
		$cnt = 0;

		foreach ($fields as $field_name => $meta)
		{
			$new = self::updateOrCreate([ 'fibery_id'=>$meta['fibery/id'] ],
										[ 'type'=>'field', 'parent'=>$parent, 'their_name'=>$field_name,
											'fibery_type'=>$meta['fibery/type']??null, 'meta_data'=>$meta ]);
			if ($new->wasRecentlyCreated === true)
				$cnt++;
		}

		return $cnt;
	}
}
