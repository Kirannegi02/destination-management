@extends('admin.layouts.app')

@section('title', 'Invoice - Order #' . $order->id)
@section('page-title', 'Invoice - Order #' . $order->id)

@push('styles')
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .invoice-print-wrapper,
            .invoice-print-wrapper * {
                visibility: visible;
            }
            .invoice-print-wrapper {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
            .invoice-print-actions {
                display: none !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="invoice-print-wrapper" style="max-width: 420px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 10px 30px rgba(15,23,42,0.12); padding: 16px; font-family: 'Courier New', monospace; font-size: 13px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <button type="button"
                    onclick="window.history.back()"
                    class="invoice-print-actions"
                    style="padding:6px 12px; background:#dc2626; color:#fff; border:none; border-radius:4px; font-size:12px; cursor:pointer;">
                Back
            </button>
            <button type="button"
                    onclick="window.print()"
                    class="invoice-print-actions"
                    style="flex:1; margin:0 8px; padding:6px 12px; background:#16a34a; color:#fff; border:none; border-radius:4px; font-size:12px; cursor:pointer;">
                Proceed  If thermal printer is ready.
            </button>
        </div>

        <div style="text-align:center; margin-bottom:8px;">
            <div style="font-size:24px;">🏬</div>
            <div style="font-weight:bold; font-size:16px; margin-top:4px;">DMC STORE</div>
            <div>{{ $order->userAddress->city ?? 'City' }}, {{ $order->userAddress->country ?? 'India' }}</div>
            @if($order->user && $order->user->phone)
                <div>Phone:{{ $order->user->phone }}</div>
            @endif
        </div>

        <div style="text-align:center; margin:6px 0;">
            <div>*******************************</div>
            <div style="font-weight:bold; margin:2px 0;">CASH RECEIPT</div>
            <div>*******************************</div>
        </div>

        <div style="margin-bottom:8px;">
            <div>Order id:{{ $order->id }}</div>
            <div>{{ $order->created_at?->format('d/M/Y  h:i a') }}</div>
        </div>

        <div style="margin-bottom:8px;">
            <div>Contact Name:{{ $order->user?->name ?? 'Guest' }}</div>
            @if($order->user && $order->user->phone)
                <div>Phone:{{ $order->user->phone }}</div>
            @endif
            @if($order->userAddress)
                <div>Address:{{ $order->userAddress->city }}, {{ $order->userAddress->state }} {{ $order->userAddress->pincode }}, {{ $order->userAddress->country }}</div>
            @endif
        </div>

        <hr style="border:0; border-top:1px dashed #000; margin:8px 0;">

        <div style="display:flex; justify-content:space-between; font-weight:bold; margin-bottom:4px;">
            <span>Desc</span>
            <span>Price</span>
        </div>

        @foreach($order->items as $item)
            <div style="margin-bottom:6px;">
                <div style="display:flex; justify-content:space-between;">
                    <span>{{ $item->souvenir?->name ?? 'Souvenir #' . $item->souvenir_id }}</span>
                    <span>{{ $order->currency }} {{ number_format((float)$item->line_total, 2) }}</span>
                </div>
                <div style="margin-left:4px;">
                    <div>Qty : {{ $item->quantity }}</div>
                    <div>Price : {{ $order->currency }} {{ number_format((float)$item->unit_price, 2) }}</div>
                </div>
            </div>
        @endforeach

        <hr style="border:0; border-top:1px dashed #000; margin:8px 0;">

        <div style="margin-bottom:4px;">
            <div>Subtotal:      {{ $order->currency }} {{ number_format((float)$order->subtotal, 2) }}</div>
            <div>Shipping:      {{ $order->currency }} {{ number_format((float)$order->shipping_cost, 2) }}</div>
            <div>Total:         {{ $order->currency }} {{ number_format((float)$order->total, 2) }}</div>
        </div>

        <div style="text-align:center; margin-top:10px; font-size:12px;">
            Thank you for shopping with us.
        </div>
    </div>
@endsection

