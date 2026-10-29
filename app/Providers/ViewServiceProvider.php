<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        View::composer('layouts.app', function ($view) {
            $unreadCount = 0;
            $latestNotifications = collect();

            if (auth()->check()) {
                try {
                    if (Schema::hasTable('notifications')) {
                        $user = auth()->user();
                        $unreadCount = $user->unreadNotifications->count();
                        $latestNotifications = $user->notifications()->latest()->take(5)->get();
                    }
                } catch (\Throwable $e) {
                    \Log::warning('ViewServiceProvider notifications query failed', [
                        'user_id' => auth()->id(),
                        'error'   => $e->getMessage(),
                    ]);
                }
            }

            $view->with('unreadNotificationsCount', $unreadCount)
                 ->with('latestNotifications', $latestNotifications);
        });
    }

    public function register()
    {
        //
    }
}
