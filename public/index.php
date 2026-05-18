<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Core/Helpers.php';

$GLOBALS['config'] = require BASE_PATH . '/config/config.php';

session_name((string) config('app.session_name', 'kickoff_session'));
session_start();

require BASE_PATH . '/app/Core/Database.php';
require BASE_PATH . '/app/Core/Router.php';
require BASE_PATH . '/app/Core/Controller.php';
require BASE_PATH . '/app/Core/Model.php';
require BASE_PATH . '/app/Core/Csrf.php';
require BASE_PATH . '/app/Core/Auth.php';
require BASE_PATH . '/app/Core/Mailer.php';

$loadPhpFiles = static function (string $directory): void {
    $files = glob($directory . '/*.php') ?: [];
    sort($files);
    foreach ($files as $file) {
        require_once $file;
    }
};

$loadPhpFiles(BASE_PATH . '/app/Models');
$loadPhpFiles(BASE_PATH . '/app/Controllers');

$router = new Router();

$router->get('/', [HomeController::class, 'index']);

$router->get('/auth/register', [AuthController::class, 'showRegister']);
$router->post('/auth/register', [AuthController::class, 'register']);

$router->get('/auth/verify-email', [AuthController::class, 'showVerifyEmail']);
$router->post('/auth/verify-email', [AuthController::class, 'verifyEmail']);
$router->post('/auth/verify-email/resend', [AuthController::class, 'resendVerifyEmailCode']);

$router->get('/auth/login', [AuthController::class, 'showLogin']);
$router->post('/auth/login', [AuthController::class, 'login']);

$router->get('/auth/verify-login', [AuthController::class, 'showVerifyLogin']);
$router->post('/auth/verify-login', [AuthController::class, 'verifyLogin']);

$router->post('/auth/logout', [AuthController::class, 'logout']);

$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/pitches', [PitchController::class, 'index']);
$router->get('/pitches/profile', [PitchController::class, 'profile']);
$router->get('/bookings', [BookingController::class, 'index']);
$router->post('/bookings/lock', [BookingController::class, 'lock']);
$router->post('/bookings/lock-bulk', [BookingController::class, 'lockBulk']);
$router->get('/bookings/confirm', [BookingController::class, 'showConfirm']);
$router->post('/bookings/confirm', [BookingController::class, 'confirm']);
$router->get('/bookings/confirm-bulk', [BookingController::class, 'showConfirmBulk']);
$router->post('/bookings/confirm-bulk', [BookingController::class, 'confirmBulk']);
$router->post('/bookings/pay', [BookingController::class, 'pay']);
$router->post('/bookings/cancel', [BookingController::class, 'cancel']);
$router->post('/dashboard/topup', [DashboardController::class, 'topUp']);
$router->post('/dashboard/buy-tickets', [DashboardController::class, 'buyTickets']);
$router->post('/dashboard/withdraw', [DashboardController::class, 'withdraw']);
$router->get('/team-ads', [CommunityController::class, 'teamAds']);
$router->post('/team-ads/create', [CommunityController::class, 'createTeamAd']);
$router->post('/team-ads/join', [CommunityController::class, 'joinTeamAd']);
$router->post('/team-ads/leave', [CommunityController::class, 'leaveTeamAd']);
$router->post('/team-ads/close', [CommunityController::class, 'closeTeamAd']);
$router->get('/backup-calls', [CommunityController::class, 'backupCalls']);
$router->post('/backup-calls/create', [CommunityController::class, 'createBackupCall']);
$router->post('/backup-calls/respond', [CommunityController::class, 'respondBackupCall']);
$router->post('/backup-calls/select', [CommunityController::class, 'selectBackupResponder']);
$router->post('/backup-calls/close', [CommunityController::class, 'closeBackupCall']);

$router->get('/admin/dashboard', [AdminDashboardController::class, 'index']);
$router->get('/admin/verify-code', [AdminPitchController::class, 'verifyCodePage']);
$router->post('/admin/verify-code', [AdminPitchController::class, 'verifyCode']);
$router->get('/admin/pitches', [AdminPitchController::class, 'pitchesIndex']);
$router->get('/admin/pitches/create', [AdminPitchController::class, 'createPitchPage']);
$router->post('/admin/pitches/create', [AdminPitchController::class, 'storePitch']);
$router->get('/admin/pitches/manage', [AdminPitchController::class, 'managePitchPage']);
$router->post('/admin/pitches/update', [AdminPitchController::class, 'updatePitch']);
$router->post('/admin/pitches/photos/add', [AdminPitchController::class, 'addPhoto']);
$router->post('/admin/pitches/photos/delete', [AdminPitchController::class, 'deletePhoto']);
$router->post('/admin/pitches/blocks/add', [AdminPitchController::class, 'addBlockedSlot']);
$router->post('/admin/pitches/blocks/delete', [AdminPitchController::class, 'deleteBlockedSlot']);

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = rtrim((string) config('app.base_path', ''), '/');

if ($basePath !== '' && str_starts_with($requestUri, $basePath)) {
    $requestUri = substr($requestUri, strlen($basePath));
}

if ($requestUri === '') {
    $requestUri = '/';
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$router->dispatch($method, $requestUri);

