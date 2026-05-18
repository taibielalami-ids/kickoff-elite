<?php

class AdminDashboardController extends Controller
{
    private AdminAnalytics $analytics;
    private User $users;

    public function __construct()
    {
        $this->analytics = new AdminAnalytics();
        $this->users = new User();
    }

    public function index(): void
    {
        $this->requireAuth(['admin']);

        $summary = $this->analytics->adminSummary();
        $users = $this->users->adminListUsers(500);

        $this->view('admin/dashboard', [
            'summary' => $summary,
            'users' => $users,
        ]);
    }
}

