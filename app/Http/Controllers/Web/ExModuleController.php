<?php

namespace ex_module\App\Http\Controllers\Web ;

use ex_module\App\Interfaces\ExModuleSettings;
use framework\Http\Request;
use framework\Http\Controller;
use framework\Http\View;
use framework\Support\ServiceProvider;

class ExModuleController extends Controller
{

    public function input(array $vars)
    {
        echo view("html/form" , [
            'exModuleSetting' => new ExModuleSettings($this->request->get('SMPFORM'), 1)
        ])->render();
    }

    public function confirm(array $vars)
    {
        echo view("html/form" , [
            'exModuleSetting' => new ExModuleSettings($this->request->get('SMPFORM'), 2)
        ])->render();
    }

    public function thanks(array $vars)
    {
        echo view("html/form" , [
            'exModuleSetting' => new ExModuleSettings($this->request->get('SMPFORM'), 3)
        ])->render();
    }

    public function close(array $vars)
    {
        echo view("html/form" , [
            'exModuleSetting' => new ExModuleSettings($this->request->get('SMPFORM'), 4)
        ])->render();
    }

}
