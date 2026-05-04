<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(Request $request): View
    {
        $orders = $request->user()
            ->orders()
            ->with('items')
            ->latest()
            ->take(4)
            ->get();

        return view('account.dashboard', [
            'orders' => $orders,
            'totalOrders' => $request->user()->orders()->count(),
            'totalSpent' => $request->user()->orders()->where('status', '!=', 'annulee')->sum('total'),
        ]);
    }

    public function orders(Request $request): View
    {
        return view('account.orders', [
            'orders' => $request->user()
                ->orders()
                ->withCount('items')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function order(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return view('account.order-show', [
            'order' => $order->load('items.product'),
        ]);
    }
}
