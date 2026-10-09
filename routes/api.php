<?php

use App\Http\Controllers\CoffeeInventoryController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\OfferingController;
use App\Http\Controllers\TaxonomyController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckTokenForAnyScope;

/**EDB 09/10/26: Public routes */
Route::post('/register', [UserController::class, 'store']);
Route::post('/api/login', [UserController::class, 'login']);
Route::get('/taxonomies', [TaxonomyController::class, 'index']);
Route::get('/offerings/{offering}', [OfferingController::class, 'show']);
Route::get('/offerings', [OfferingController::class, 'index']);
Route::get('/evaluations/{evaluation}', [EvaluationController::class, 'show']);
Route::get('/evaluations', [EvaluationController::class, 'index']);


Route::middleware(['auth:api', CheckTokenForAnyScope::using('profile:read', 'profile:write')]) //EDB 09/16/26: adding the read general scope, RejectWildcardScope force client to request a valid scope.
    ->group(function () {

        /**EDB 09/10/26: Routes to collect user profile data and logs out */
        Route::post('/logout', [UserController::class, 'logout']);
        Route::get('/user', [UserController::class, 'show']);

        /**EDB 09/10/26: Routes for coffeeshops to retrive information to create an offering*/
        Route::middleware('role:coffeeshop')->group(function () {
            Route::get('/locations', [LocationController::class, 'index']);
            Route::get('/coffeeInventory', [CoffeeInventoryController::class, 'index']);
            Route::post('/offerings', [OfferingController::class, 'store'])
                ->middleware('owns.location:locations');
            Route::delete('/offerings/{offering}', [OfferingController::class, 'destroy'])
                ->middleware(['owns.offering', CheckTokenForAnyScope::using('profile:write')]);
            Route::delete('/offerings', [OfferingController::class, 'massDestroy'])
                ->middleware(['owns.offering:offerings', CheckTokenForAnyScope::using('profile:write')]);
        });

        /**EDB 09/17/26: Routes for specialist to manage evaluations */
        Route::middleware('role:specialist')->group(function () {
            Route::post('/evaluations', [EvaluationController::class, 'store']);
            Route::middleware('can:update,evaluation')->group(function () {
                Route::put('/evaluations/{evaluation}', [EvaluationController::class, 'update']);
                Route::patch('/evaluations/{evaluation}/close', [EvaluationController::class, 'close']);
            });
            Route::delete('/evaluations/{evaluation}', [EvaluationController::class, 'destroy'])
                ->middleware('can:delete,evaluation');
        });

        /**EDB 09/10/26: Routes for user to administer theri own profile*/
        Route::middleware(CheckTokenForAnyScope::using('profile:write'))->group(function () {
            Route::put('/users/{user}', [UserController::class, 'update'])
                ->middleware('can:update,user');
            Route::put('/users/{user}/password', [UserController::class, 'updatePassword'])
                ->middleware('can:update,user');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])
                ->middleware('can:delete,user');
            Route::delete('/users/{user}/force', [UserController::class, 'forceDestroy'])
                ->middleware('can:delete,user')->withTrashed();
        });
    });
