<?php

class DashboardController extends Controller
{
    private Wallet $wallets;
    private Booking $bookings;
    private Pitch $pitches;

    public function __construct()
    {
        $this->wallets = new Wallet();
        $this->bookings = new Booking();
        $this->pitches = new Pitch();
    }

    public function index(): void
    {
        $this->requireAuth();
        $user = $this->user();
        $wallet = $this->wallets->getByUser((int) $user['id']);
        $walletTx = $this->wallets->recentTransactions((int) $user['id'], 10);
        $bookings = $this->bookings->myBookings((int) $user['id']);
        $myPitches = [];
        if (($user['role'] ?? '') === 'admin') {
            $myPitches = $this->pitches->adminPitches();
        }

        $this->view('dashboard/index', [
            'user' => $user,
            'wallet' => $wallet,
            'walletTx' => $walletTx,
            'bookings' => $bookings,
            'myPitches' => $myPitches,
        ]);
    }

    public function topUp(): void
    {
        $this->requireAuth();
        $this->ensureCsrf('/dashboard');

        $amount = (float) ($_POST['amount'] ?? 0);
        $userId = $this->userId();
        $result = $this->wallets->topUp($userId, $amount);
        if (!($result['ok'] ?? false)) {
            $this->redirectWith('/dashboard#wallet', 'danger', (string) ($result['error'] ?? 'Top-up failed.'));
        }

        activity_log(
            'wallet_topup',
            'Wallet top-up of ' . number_format($amount, 2) . ' DH',
            'wallet',
            $userId,
            $userId
        );
        $this->redirectWith('/dashboard#wallet', 'success', 'Wallet top-up completed.');
    }

    public function buyTickets(): void
    {
        $this->requireAuth();
        $this->ensureCsrf('/dashboard');

        $ticketCount = (int) ($_POST['ticket_count'] ?? 0);
        $userId = $this->userId();
        $result = $this->wallets->buyTickets($userId, $ticketCount);
        if (!($result['ok'] ?? false)) {
            $this->redirectWith('/dashboard#wallet', 'danger', (string) ($result['error'] ?? 'Ticket purchase failed.'));
        }

        activity_log(
            'wallet_ticket_purchase',
            'Purchased ' . $ticketCount . ' ticket(s) from wallet balance',
            'wallet',
            $userId,
            $userId
        );
        $this->redirectWith('/dashboard#wallet', 'success', 'Tickets purchased successfully.');
    }

    public function withdraw(): void
    {
        $this->requireAuth();
        $this->ensureCsrf('/dashboard');

        $amount = (float) ($_POST['amount'] ?? 0);
        $userId = $this->userId();
        $result = $this->wallets->withdraw($userId, $amount);

        if (!($result['ok'] ?? false)) {
            $this->redirectWith('/dashboard', 'danger', (string) ($result['error'] ?? 'Withdraw failed.'));
        }

        activity_log(
            'wallet_withdraw',
            'Wallet withdrawal of ' . number_format($amount, 2) . ' DH',
            'wallet',
            $userId,
            $userId
        );
        $this->redirectWith('/dashboard#wallet', 'success', 'Withdrawal completed successfully.');
    }
}
