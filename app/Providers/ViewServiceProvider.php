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
            // Defaults for guests AND for any DB hiccup
            $unreadCount = 0;
            $latestNotifications = collect();

            if (auth()->check()) {
                try {
                    // Guard: notifications table may not exist yet on fresh
                    // deploys (migration race) or in degraded DB states.
                    // Without this guard, a missing table crashes every page
                    // via ViewServiceProvider -> 500 on all authenticated views.
                    if (Schema::hasTable('notifications')) {
                        $user = auth()->user();
                        $unreadCount = $user->unreadNotifications->count();
                        $latestNotifications = $user->notifications()->latest()->take(5)->get();
                    }
                } catch (\Throwable $e) {
                    // Fail closed: page still renders, just no notification badge
                    // Logged so we can see it in Render logs if it recurs
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
