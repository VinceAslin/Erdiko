<?php
define('WEBROOT', dirname(__DIR__));

$core = WEBROOT.'/erdiko';
$app = WEBROOT.'/app';
$vendor = dirname(__DIR__)."/libraries";

// Static functions / Factories
require_once WEBROOT.'/Erdiko.php';

// Required Libraries
require_once WEBROOT.'/libraries/ToroPHP/toro.php';

// Interfaces
require_once $core.'/core/interfaces/Theme.php';
require_once $core.'/core/interfaces/Session.php';

// Core
require_once $core.'/core/Handler.php';
require_once $core.'/core/Module.php';
require_once $core.'/core/ModelAbstract.php';
require_once $core.'/core/theme/ThemeEngine.php';

// Autoloader(s)
require_once $core.'/core/autoload.php';

// Session
// require_once $core.'/core/session.php';

// load the application bootstrapper (user defined)
require_once $app.'/appstrap.php';