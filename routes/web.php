<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketMessageController;
use App\Http\Controllers\TwoFactorSettingsController;
use App\Http\Controllers\WhoIsWhoController;
use App\Http\Middleware\EnsureKnowledgeBaseIsEnabled;
use App\Http\Middleware\EnsureRegistrationIsOpen;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::get('/language/{locale}', LocaleController::class)->name('locale');
Route::get('/who-is-who', WhoIsWhoController::class)->name('who-is-who');

Route::middleware(EnsureKnowledgeBaseIsEnabled::class)->prefix('kb')->name('kb.')->group(function () {
    Route::get('/', [KnowledgeBaseController::class, 'index'])->name('index');
    Route::get('/category/{category}', [KnowledgeBaseController::class, 'category'])->name('category');
    Route::get('/faq/{faq}', [KnowledgeBaseController::class, 'show'])->name('show');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:6,1');
    Route::get('/register', [RegisteredUserController::class, 'create'])->middleware(EnsureRegistrationIsOpen::class)->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware([EnsureRegistrationIsOpen::class, 'throttle:6,1']);
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.store');

    // Second login step for accounts with two-step verification.
    Route::get('/two-factor', [TwoFactorChallengeController::class, 'create'])->name('two-factor.challenge');
    Route::post('/two-factor', [TwoFactorChallengeController::class, 'store'])->middleware('throttle:6,1')->name('two-factor.verify');
    Route::post('/two-factor/email', [TwoFactorChallengeController::class, 'sendEmail'])->middleware('throttle:3,1')->name('two-factor.send-email');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->middleware('throttle:20,1')->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/messages', [TicketMessageController::class, 'store'])->middleware('throttle:30,1')->name('tickets.messages.store');
    Route::post('/tickets/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close');
    Route::post('/tickets/{ticket}/reopen', [TicketController::class, 'reopen'])->name('tickets.reopen');
    Route::get('/attachments/{attachment}', [TicketAttachmentController::class, 'show'])->name('attachments.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/profile/two-factor/app', [TwoFactorSettingsController::class, 'createApp'])->name('two-factor.app.create');
    Route::post('/profile/two-factor/app', [TwoFactorSettingsController::class, 'storeApp'])->middleware('throttle:6,1')->name('two-factor.app.store');
    Route::delete('/profile/two-factor/app', [TwoFactorSettingsController::class, 'destroyApp'])->name('two-factor.app.destroy');
    Route::post('/profile/two-factor/recovery-codes', [TwoFactorSettingsController::class, 'regenerateRecoveryCodes'])->name('two-factor.recovery-codes');
    Route::post('/profile/two-factor/email/send', [TwoFactorSettingsController::class, 'sendEmail'])->middleware('throttle:3,1')->name('two-factor.email.send');
    Route::get('/profile/two-factor/email', [TwoFactorSettingsController::class, 'createEmail'])->name('two-factor.email.create');
    Route::post('/profile/two-factor/email', [TwoFactorSettingsController::class, 'storeEmail'])->middleware('throttle:6,1')->name('two-factor.email.store');
    Route::delete('/profile/two-factor/email', [TwoFactorSettingsController::class, 'destroyEmail'])->name('two-factor.email.destroy');
});
