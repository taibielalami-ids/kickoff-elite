<?php

class HomeController extends Controller
{
    public function index(): void
    {
        if (Auth::check()) {
            $role = (string) (Auth::user()['role'] ?? '');
            if ($role === 'admin') {
                redirect('/admin/dashboard');
            }
        }

        $this->view('home', [
            'appName' => config('app.name'),
        ]);
    }
}
