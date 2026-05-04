<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/produits', [ProductController::class, 'index'])->name('products.index');
Route::get('/produits/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/panier', [CartController::class, 'show'])->name('cart.show');
Route::post('/panier/ajouter/{product:slug}', [CartController::class, 'add'])->name('cart.add');
Route::post('/panier/acheter/{product:slug}', [CartController::class, 'buyNow'])->name('cart.buy-now');
Route::patch('/panier/{product:slug}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/panier/{product:slug}', [CartController::class, 'remove'])->name('cart.remove');

Route::middleware('guest')->group(function (): void {
    Route::get('/connexion', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/connexion', [AuthController::class, 'login'])->name('login.store');
    Route::get('/inscription', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/inscription', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/deconnexion', [AuthController::class, 'logout'])->name('logout');
    Route::get('/paiement', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/paiement/confirmer', [CheckoutController::class, 'confirm'])->name('checkout.confirm');
    Route::get('/compte', [AccountController::class, 'dashboard'])->name('account.dashboard');
    Route::get('/compte/commandes', [AccountController::class, 'orders'])->name('account.orders');
    Route::get('/compte/commandes/{order}', [AccountController::class, 'order'])->name('account.orders.show');
});
