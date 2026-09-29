<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Schema::defaultStringLength(191);
        \Illuminate\Pagination\Paginator::defaultView('vendor.pagination.custom');

        \Illuminate\Support\Facades\View::composer(['layouts.admin', 'layouts.app', 'layouts.attendance'], function ($view) {
            try {
                $pendingPoCount = \App\Models\PurchaseOrder::where('status', 'PENDING')->count();
            } catch (\Throwable $e) {
                $pendingPoCount = 0;
            }

            try {
                $pendingDispatchCount = \App\Models\Order::where(function($q) {
                    $q->whereIn('dispatch_status', ['PENDING', 'OPEN', 'UNASSIGNED', 'PARTIAL', 'PARTIAL_PENDING', 'PARTIAL PENDING'])
                      ->orWhereNull('dispatch_status');
                })->where('status', '!=', 'CANCELLED')->count();
            } catch (\Throwable $e) {
                $pendingDispatchCount = 0;
            }

            try {
                $pendingSalesCount = \App\Models\Order::whereIn('status', ['OPEN', 'PENDING'])
                    ->whereNotIn('dispatch_status', ['DONE', 'COMPLETED', 'FULLY DISPATCHED', 'FULLY_DISPATCHED'])
                    ->count();
            } catch (\Throwable $e) {
                $pendingSalesCount = 0;
            }

            try {
                $user = session('auth_user') ? \App\Models\User::find(session('auth_user')['id']) : auth()->user();
                if ($user) {
                    $unreadNotifCount = $user->unreadNotifications()->count();
                } else {
                    $unreadNotifCount = \Illuminate\Support\Facades\DB::table('notifications')->whereNull('read_at')->count();
                }
            } catch (\Throwable $e) {
                $unreadNotifCount = 0;
            }

            try {
                $pendingAttendanceCount = \App\Models\AttendanceSubmission::where('status', 'PENDING')->count();
            } catch (\Throwable $e) {
                $pendingAttendanceCount = 0;
            }

            try {
                $lowStockCount = \Illuminate\Support\Facades\DB::table('stocks')
                    ->join('products', 'stocks.product_id', '=', 'products.id')
                    ->select('stocks.product_id', 'stocks.stage')
                    ->whereIn('stocks.stage', ['RAW', 'FINISHED'])
                    ->groupBy('stocks.product_id', 'stocks.stage', 'products.threshold')
                    ->havingRaw("SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) < products.threshold")
                    ->havingRaw("SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) > 0")
                    ->havingRaw("products.threshold > 0")
                    ->get()->count();
            } catch (\Throwable $e) {
                $lowStockCount = 0;
            }

            $view->with([
                'sidebarPendingPoCount'         => $pendingPoCount,
                'sidebarPendingDispatchCount'   => $pendingDispatchCount,
                'sidebarPendingSalesCount'      => $pendingSalesCount,
                'sidebarUnreadNotifCount'       => $unreadNotifCount,
                'sidebarPendingAttendanceCount' => $pendingAttendanceCount,
                'sidebarLowStockCount'          => $lowStockCount,
            ]);
        });
    }
}
