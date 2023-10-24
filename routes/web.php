
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : "") . "framework/autoload_static.php";
require_once "ex_module/autoload_static.php";

use ex_module\App\Http\Controllers\Web\ExModuleController;
use framework\Routing\Router;

const VIEW_FILE_ROOT = "ex_module/resources";

$request = new \framework\Http\Request();

if($customPath){
    $request->setRequestUri($customPath);
}

Router::map("GET", "ex_module/input", [ExModuleController:: class , "input"]);
Router::map("GET", "ex_module/confirm", [ExModuleController:: class , "confirm"]);
Router::map("GET", "ex_module/thanks", [ExModuleController:: class , "thanks"]);
Router::map("GET", "ex_module/close", [ExModuleController:: class , "close"]);

$router = new Router();
//$router->middleware();毎回必ずチェックする場合はこっち
$app = new ex_module\ex_moduleApplication();
$exceptionHandler = new ex_module\App\Exceptions\ExceptionHandler();
$kernel = new \framework\Http\Kernel($app, $router ,$exceptionHandler);

$kernel->handle($request);

