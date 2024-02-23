<?php
namespace mvnrsa\FiberyAPIs\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Str;

class FiberyID extends Model
{
    use HasFactory;

    public $table = 'fibery_ids';

	public $keyType = 'string';
	public $incrementing = false;

	protected $fillable = [
		'id',
		'model_type',
		'model_id',
	];

	public static function boot()
	{
		parent::boot();

		static::creating( function($item)
		{
			if (empty($item->id))
				$item->id = Str::uuid();
		});
	}

	public function model()
	{
		return $this->morphTo();
	}
}
