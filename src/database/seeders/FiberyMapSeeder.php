<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use mvnrsa\FiberyAPIs\App\Models\FiberyMap;

class FiberyMapSeeder extends Seeder
{
    public function run()
    {
        FiberyMap::populate();
    }
}
