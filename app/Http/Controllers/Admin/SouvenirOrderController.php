<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SouvenirOrder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SouvenirOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = SouvenirOrder::with(['user', 'userAddress', 'items.souvenir'])->orderBy('created_at', 'desc');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('userAddress', function ($addrQuery) use ($search) {
                        $addrQuery->where('city', 'like', "%{$search}%")
                            ->orWhere('country', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('delivery_date')) {
            $query->whereDate('requested_delivery_date', $request->delivery_date);
        }

        $orders = $query->paginate(15)->appends($request->query());

        return view('admin.souvenir-orders.index', compact('orders'));
    }

    public function show(string $id)
    {
        $order = SouvenirOrder::with(['user', 'userAddress', 'items.souvenir'])->findOrFail($id);
        return view('admin.souvenir-orders.show', compact('order'));
    }

    public function invoice(string $id)
    {
        $order = SouvenirOrder::with(['user', 'userAddress', 'items.souvenir'])->findOrFail($id);
        return view('admin.souvenir-orders.invoice', compact('order'));
    }

    public function updateStatus(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'])],
        ]);

        $order = SouvenirOrder::findOrFail($id);
        $order->status = $validated['status'];
        $order->save();

        return redirect()
            ->route('admin.souvenir-orders.show', $id)
            ->with('success', 'Order status updated to ' . str_replace('_', ' ', ucfirst($validated['status'])) . '.');
    }
}
