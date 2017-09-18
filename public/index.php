<?php
require_once __DIR__ . '/../vendor/autoload.php';

/** @var \Symfony\Component\DependencyInjection\ContainerBuilder $container */
$container = require_once __DIR__ . '/../config/container.php';

//$connection = $container->get('doctrine.connection');
$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    $r->addGroup('/token', function (FastRoute\RouteCollector $r) {
        $r->addRoute('GET', '/validate', 'token.validate.action');
    });
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);


$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        $response = new \Zend\Diactoros\Response\JsonResponse([
            'status' => 'error',
            'message' => '404 Not Found'
        ], 404);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $response = new \Zend\Diactoros\Response\JsonResponse([
            'status' => 'error',
            'message' => '405 Method Not Allowed'
        ], 405);
        break;
    case FastRoute\Dispatcher::FOUND:

        $handler = $container->get($routeInfo[1]);
        try {
            $response = call_user_func($handler, $container->get('request'), $routeInfo[2]);
        } catch (\Exception $exception) {
            $response = new \Zend\Diactoros\Response\JsonResponse([
                'status' => 'error',
                'message' => $exception->getMessage()
            ]);

            // @TODO, logger!
        }

        break;
}

$emitter = new \Zend\Diactoros\Response\SapiEmitter();
$emitter->emit($response);
