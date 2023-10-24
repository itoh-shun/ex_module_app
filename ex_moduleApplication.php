<?php

namespace ex_module;

use framework\Application;

class ex_moduleApplication extends Application
{
    public function __construct()
    {
        config_path("ex_module/config/app");
        parent::__construct();
    }

    public function boot()
    {
    }
}
