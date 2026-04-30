<?php

namespace App\Console\Commands;

use App\CustomClass\Rbac;
use Illuminate\Console\Command;

class GenRoleModule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:permission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan routes and generate role module permissions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Scanning routes for permissions...');
        
        $count = Rbac::generateRoleModule();
        
        $this->info("Successfully processed {$count} permissions and synced them with Menus.");
        
        return 0;
    }
}
