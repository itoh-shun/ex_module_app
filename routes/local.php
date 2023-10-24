
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : "") . "framework/autoload_static.php";
require_once "ex_module/autoload_static.php";

use framework\Routing\Router;
use ex_module\App\Http\Controllers\Web\WelcomeController;

const VIEW_FILE_ROOT = "ex_module/resources";

Router::map("GET", "/", [WelcomeController:: class , "index"]);

$router = new Router();
//$router->middleware();毎回必ずチェックする場合はこっち
$app = new ex_module\ex_moduleApplication();
$exceptionHandler = new ex_module\App\Exceptions\ExceptionHandler();
$kernel = new \framework\Http\Kernel($app, $router ,$exceptionHandler);
$request = new \framework\Http\Request();

$kernel->handle($request);

