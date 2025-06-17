<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ResponseFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/config.php';
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/utils.php';
require __DIR__ . '/../src/routes.php';

$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add CORS middleware
$app->add(function (Request $request, $handler) use ($responseFactory): Response {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
});

// Add authentication middleware
$responseFactory = new ResponseFactory();
$app->add(function (Request $request, $handler) use ($config, $responseFactory): Response {
    $path = $request->getUri()->getPath();
    
    // Skip auth for OPTIONS requests (CORS preflight)
    if ($request->getMethod() === 'OPTIONS') {
        return $handler->handle($request);
    }

    $auth = $request->getHeaderLine('Authorization');
    if (!preg_match('/^Bearer (.+)$/', $auth, $matches)) {
        return createJsonResponse(
            $responseFactory->createResponse(), 
            ['error' => 'Missing or invalid Authorization header'], 
            401
        );
    }

    $apiKey = $matches[1];
    if ($apiKey !== $config['API_KEY']) {
        return createJsonResponse(
            $responseFactory->createResponse(), 
            ['error' => 'Invalid API key'], 
            401
        );
    }

    return $handler->handle($request);
});

registerRoutes($app);
$app->run();
