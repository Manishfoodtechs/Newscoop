<?php
/**
 * @package Newscoop\Gimme
 * @author Paweł Mikołajczuk <pawel.mikolajczuk@sourcefabric.org>
 * @copyright 2012 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

if (!file_exists(__DIR__ . '/../vendor')) {
    echo "Missing dependency! Please install all dependencies with composer.";
    echo "<pre>curl -s https://getcomposer.org/installer | php <br/>php composer.phar install</pre>";
    die;
}

require_once __DIR__ . '/../constants.php';
require_once __DIR__ . '/../app/bootstrap.php.cache';
require_once __DIR__ . '/../app/AppKernel.php';
require_once __DIR__ . '/../db_connect.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing;
use Newscoop\Gimme\Framework;
use Symfony\Component\Routing\Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

error_reporting(error_reporting() & ~E_STRICT & ~E_DEPRECATED);

// goes to install process if configuration files does not exist yet
if (php_sapi_name() !== 'cli'
    && !defined('INSTALL')
    && (!file_exists(APPLICATION_PATH . '/../conf/configuration.php')
        || !file_exists(APPLICATION_PATH . '/../conf/database_conf.php'))
) {
    $subdir = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/', -2));
    if (strpos($subdir, 'install') === false) {
        header("Location: $subdir/install/");
        exit;
    }
}

/**
 * Create Symfony kernel
 */
if (APPLICATION_ENV === 'production') {
	$kernel = new AppKernel('prod', false);
} else if (APPLICATION_ENV === 'development') {
	$kernel = new AppKernel('dev', true);
} else {
	$kernel = new AppKernel('APPLICATION_ENV', true);
}

$kernel->loadClassCache();

/**
 * Create request object from global variables
 */
$request = Request::createFromGlobals();

try {
	$response = $kernel->handle($request, \Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST, false);
	$response->send();
	$kernel->terminate($request, $response);
} catch (NotFoundHttpException $e) {
	$containerFactory = new \Newscoop\DependencyInjection\ContainerFactory();
	$containerFactory->setContainer($kernel->getContainer());
	$container = $containerFactory->getContainer();
	\Zend_Registry::set('container', $container);
	
	require_once __DIR__ . '/../application.php';

	$application->bootstrap();
	$application->run();
}