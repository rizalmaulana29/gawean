<?php
use App\Http\Controllers\JurnalController;

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();
/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();

$app->withEloquent();

// $app->configure('cors');


$app->configure('image');
$app->configure('Services');
$app->configure('mail');
$app->configure('filesystems');
// $app->configure('dompdf');

$app->alias('Storage',Illuminate\Support\Facades\Storage::class);
$app->alias('mailer', Illuminate\Mail\Mailer::class);
$app->alias('mailer', Illuminate\Contracts\Mail\Mailer::class);
$app->alias('mailer', Illuminate\Contracts\Mail\MailQueue::class);
$app->alias('image', Intervention\Image\Facades\Image::class);

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/


$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
Illuminate\Contracts\Filesystem\Factory::class,
function ($app) {
return new Illuminate\Filesystem\FilesystemManager($app);
});

$app->singleton(App\Providers\JurnalApiServiceProvider::class, function ($app) {
    return new App\Providers\JurnalApiServiceProvider(
        config('services.jurnal.username'),
        config('services.jurnal.secret'),
        config('services.jurnal.environment', 'sandbox')
    );
});

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

// $app->middleware([
//     // ...
//     // 'cors'=>\Barryvdh\Cors\HandleCors::class,
// ]);
// $app->middleware([
//     // ...
//     \Fruitcake\Cors\HandleCors::class,
// ]);

// $app->middleware([
//     App\Http\Middleware\ExampleMiddleware::class
// ]);

$app->routeMiddleware([
    // 'jwt.auth' => App\Http\Middleware\JwtMiddleware::class,
    'survey.auth' => App\Http\Middleware\SurveyAuth::class,
    'jwt.auth' => App\Http\Middleware\JWTAccess::class,
    'all.cors' => App\Http\Middleware\CorsAllMiddleware::class,
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

// $app->register(Barryvdh\Cors\ServiceProvider::class);
// $app->register(\Fruitcake\Cors\CorsServiceProvider::class);

$app->register(App\Providers\JurnalApiServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);
$app->register(Intervention\Image\ImageServiceProvider::class);
$app->register(Illuminate\Mail\MailServiceProvider::class);
$app->register(Illuminate\Filesystem\FilesystemServiceProvider::class);
$app->register(App\Providers\GoogleCloudStorageServiceProvider::class);


//$app->register(\Barryvdh\DomPDF\ServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/



$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

return $app;
