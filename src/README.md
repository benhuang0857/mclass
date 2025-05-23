## Laravel 11 注意事項
- 因為官方已經將RouteServiceProvider捨棄，因此你需要先執行
- `php artisan install:api`
- `bootstrap/app.php`使用下面代碼進行取代:
```
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Route Not found',
                ], 404);
            }
        });
    })->create();
```

- 執行`php artisan route:cache`
- 執行`php artisan route:list`

## 指令
- `php artisan db:init-combo`：提供用戶快速生成需要的tables與測試資料
- `php artisan db:init-necessary`：當有修改`init_necessary.sql`時，可以執行此指令來快速修改tables