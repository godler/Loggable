<?php

namespace Klik\User;

use Klik\User\Models\User;
use Illuminate\Support\ServiceProvider as SP;
use Illuminate\View\Compilers\BladeCompiler;
use Klik\User\PrivilageRegistrator;


class ServiceProvider extends SP
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
    
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */

    public function boot(PrivilageRegistrator $privilegeRegistrator)
    {
        
    }



  


    
}
