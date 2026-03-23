<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Souvenir;
use App\Models\SouvenirOrder;
use App\Models\SouvenirOrderItem;
use App\Models\SouvenirRestockRequest;
use App\Models\UserAddress;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SouvenirOrderController extends Controller
{
    /**
     * Preview: total price, expected delivery, shipping, and "delivery too close" message.
     */
    public function preview(Request $request)
    {
        $this->mergeSouvenirOrderBooleanFlags($request);

        try {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.souvenir_id' => 'required|exists:souvenirs,id',
                'items.*.quantity' => 'required|integer|min:' . (int) config('souvenir.min_purchase_quantity', 10),
                'requested_delivery_date' => 'required|date|after_or_equal:today',
                'delivery_location' => 'nullable|string|max:255',
                'user_address_id' => 'nullable|exists:user_addresses,id',
                'within_city' => 'nullable|boolean',
                'distance_km' => 'nullable|numeric|min:0',
            ]);
        } catch (ValidationException $e) {
            Log::warning('Souvenir order preview: validation failed', [
                'errors' => $e->errors(),
                'user_id' => optional(auth('api')->user())->id,
            ]);
            throw $e;
        }

        Log::info('Souvenir order preview: request validated', [
            'user_id' => optional(auth('api')->user())->id,
            'item_lines' => count($validated['items']),
            'requested_delivery_date' => $validated['requested_delivery_date'],
            'user_address_id' => $validated['user_address_id'] ?? null,
            'souvenir_ids' => array_column($validated['items'], 'souvenir_id'),
        ]);

        $user = auth('api')->user();
        if (!$user) {
            Log::warning('Souvenir order preview: unauthenticated request after validation');

            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $subtotal = 0;
        $currency = 'EUR';
        $itemsDetail = [];
        $souvenirsById = [];

        // Aggregate quantities per souvenir for safe stock checks
        $quantities = [];
        foreach ($validated['items'] as $item) {
            $quantities[$item['souvenir_id']] = ($quantities[$item['souvenir_id']] ?? 0) + (int) $item['quantity'];
        }

        foreach ($quantities as $souvenirId => $totalQty) {
            $souvenir = Souvenir::where('status', 'active')->find($souvenirId);
            if (!$souvenir) {
                return response()->json([
                    'success' => false,
                    'message' => 'Souvenir not found or inactive: ' . $souvenirId,
                ], 404);
            }
            $qty = $totalQty;
            $effectiveMin = $souvenir->effectiveMinOrderQuantity();
            if ($qty < $effectiveMin) {
                return response()->json([
                    'success' => false,
                    'message' => "Minimum order quantity for {$souvenir->name} is {$effectiveMin}.",
                ], 422);
            }
            $currency = $souvenir->currency;
            $lineTotal = (float) $souvenir->price * $qty;
            $subtotal += $lineTotal;
            $souvenirsById[$souvenir->id] = $souvenir;
            $itemsDetail[] = [
                'souvenir_id' => $souvenir->id,
                'name' => $souvenir->name,
                'quantity' => $qty,
                'unit_price' => (float) $souvenir->price,
                'line_total' => $lineTotal,
            ];
        }

        $stockLines = $this->buildPartialStockLinesForQuantities($quantities);
        $partialStock = count($stockLines) > 0;

        // Determine within-city based on address vs souvenir city & country
        $withinCity = false;
        $address = null;
        if ($request->filled('user_address_id')) {
            $address = UserAddress::where('user_id', $user->id)->find($request->user_address_id);
        }
        if ($address && !empty($souvenirsById)) {
            $addrCity = trim(mb_strtolower((string) $address->city));
            $addrCountry = trim(mb_strtolower((string) $address->country));
            $withinCity = true;
            foreach ($souvenirsById as $souvenir) {
                $souvenirCity = trim(mb_strtolower((string) $souvenir->city));
                $souvenirCountry = trim(mb_strtolower((string) $souvenir->country));
                if ($souvenirCity === '' || $souvenirCountry === '' ||
                    $souvenirCity !== $addrCity || $souvenirCountry !== $addrCountry) {
                    $withinCity = false;
                    break;
                }
            }
        }

        $distanceKm = $this->calculateDistanceKm($address, $souvenirsById);
        [$shippingCost, $freeApplied] = $this->calculateShippingCost($subtotal, $withinCity, $distanceKm);

        $requestedDate = Carbon::parse($validated['requested_delivery_date'])->startOfDay();
        $minDays = (int) config('souvenir.min_delivery_days', 3);
        $daysUntilDelivery = Carbon::today()->startOfDay()->diffInDays($requestedDate, false);
        $deliveryTooClose = $daysUntilDelivery < $minDays;
        $expectedDeliveryAt = $requestedDate->copy()->setTime(10, 0, 0);

        $total = $subtotal + $shippingCost;

        $deliveryMessage = null;
        if ($deliveryTooClose) {
            $deliveryMessage = 'Place a request as your expected delivery date is too close and we will contact you.';
        }

        $partialPreviewMsg = $this->buildPartialStockMessagePreview($stockLines);
        $topMessage = $partialPreviewMsg ?? 'Preview calculated.';
        if ($deliveryTooClose && $deliveryMessage) {
            $topMessage .= ' ' . $deliveryMessage;
        }

        Log::info('Souvenir order preview: success', [
            'user_id' => $user->id,
            'subtotal' => round($subtotal, 2),
            'shipping_cost' => round($shippingCost, 2),
            'total' => round($total, 2),
            'currency' => $currency,
            'within_city' => $withinCity,
            'delivery_too_close' => $deliveryTooClose,
            'free_shipping_applied' => $freeApplied,
            'requested_delivery_date' => $requestedDate->format('Y-m-d'),
            'partial_stock' => $partialStock,
        ]);

        return response()->json([
            'success' => true,
            'message' => $topMessage,
            'data' => [
                'items' => $itemsDetail,
                'subtotal' => round($subtotal, 2),
                'shipping_cost' => round($shippingCost, 2),
                'total' => round($total, 2),
                'currency' => $currency,
                'requested_delivery_date' => $requestedDate->format('Y-m-d'),
                'expected_delivery_at' => $expectedDeliveryAt->toIso8601String(),
                'delivery_too_close' => $deliveryTooClose,
                'delivery_message' => $deliveryMessage,
                'within_city' => $withinCity,
                'free_shipping_applied' => $freeApplied,
                'partial_stock' => $partialStock,
                'stock_lines' => $stockLines,
            ],
        ], 200);
    }

    /**
     * Create souvenir order (booking).
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            Log::warning('Souvenir order store: unauthenticated');

            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        if ($user->status === 'pending') {
            Log::warning('Souvenir order store: rejected pending user', ['user_id' => $user->id]);

            return response()->json([
                'success' => false,
                'message' => 'Your agent account is pending verification. You cannot place souvenir orders until you are approved by admin.',
            ], 403);
        }

        $this->mergeSouvenirOrderBooleanFlags($request);

        try {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.souvenir_id' => 'required|exists:souvenirs,id',
                'items.*.quantity' => 'required|integer|min:' . (int) config('souvenir.min_purchase_quantity', 10),
                'requested_delivery_date' => 'required|date|after_or_equal:today',
                'delivery_location' => 'nullable|string|max:255',
                'user_address_id' => 'nullable|exists:user_addresses,id',
                'within_city' => 'nullable|boolean',
                'distance_km' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string|max:500',
            ]);
        } catch (ValidationException $e) {
            Log::warning('Souvenir order store: validation failed', [
                'user_id' => $user->id,
                'errors' => $e->errors(),
            ]);
            throw $e;
        }

        Log::info('Souvenir order store: request validated', [
            'user_id' => $user->id,
            'item_lines' => count($validated['items']),
            'requested_delivery_date' => $validated['requested_delivery_date'],
            'user_address_id' => $validated['user_address_id'] ?? null,
            'souvenir_ids' => array_column($validated['items'], 'souvenir_id'),
        ]);

        $subtotal = 0;
        $currency = 'EUR';
        $souvenirsById = [];

        $quantities = [];
        foreach ($validated['items'] as $item) {
            $quantities[$item['souvenir_id']] = ($quantities[$item['souvenir_id']] ?? 0) + (int) $item['quantity'];
        }

        foreach ($quantities as $souvenirId => $totalQty) {
            $souvenir = Souvenir::where('status', 'active')->find($souvenirId);
            if (!$souvenir) {
                Log::warning('Souvenir order store: souvenir not found or inactive', [
                    'user_id' => $user->id,
                    'souvenir_id' => $souvenirId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Souvenir not found or inactive: ' . $souvenirId,
                ], 404);
            }
            $qty = $totalQty;
            $effectiveMin = $souvenir->effectiveMinOrderQuantity();
            if ($qty < $effectiveMin) {
                Log::info('Souvenir order store: below minimum quantity', [
                    'user_id' => $user->id,
                    'souvenir_id' => $souvenir->id,
                    'quantity' => $qty,
                    'effective_min' => $effectiveMin,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => "Minimum order quantity for {$souvenir->name} is {$effectiveMin}.",
                ], 422);
            }
            $currency = $souvenir->currency;
            $subtotal += (float) $souvenir->price * $qty;
            $souvenirsById[$souvenir->id] = $souvenir;
        }

        $partialLines = $this->buildPartialStockRowsForOrder($quantities);

        // Determine within-city based on address vs souvenir city & country
        $withinCity = false;
        $address = null;
        if ($request->filled('user_address_id')) {
            $address = UserAddress::where('user_id', $user->id)->find($request->user_address_id);
        }
        if ($address && !empty($souvenirsById)) {
            $addrCity = trim(mb_strtolower((string) $address->city));
            $addrCountry = trim(mb_strtolower((string) $address->country));
            $withinCity = true;
            foreach ($souvenirsById as $souvenir) {
                $souvenirCity = trim(mb_strtolower((string) $souvenir->city));
                $souvenirCountry = trim(mb_strtolower((string) $souvenir->country));
                if ($souvenirCity === '' || $souvenirCountry === '' ||
                    $souvenirCity !== $addrCity || $souvenirCountry !== $addrCountry) {
                    $withinCity = false;
                    break;
                }
            }
        }

        $distanceKm = $this->calculateDistanceKm($address, $souvenirsById);
        [$shippingCost, $freeApplied] = $this->calculateShippingCost($subtotal, $withinCity, $distanceKm);
        $total = $subtotal + $shippingCost;

        $requestedDate = Carbon::parse($validated['requested_delivery_date'])->startOfDay();
        $minDays = (int) config('souvenir.min_delivery_days', 3);
        $daysUntilDelivery = Carbon::today()->startOfDay()->diffInDays($requestedDate, false);
        $deliveryTooClose = $daysUntilDelivery < $minDays;
        $expectedDeliveryAt = $requestedDate->copy()->setTime(10, 0, 0);

        $summaryText = $this->buildPartialStockSummaryText($partialLines);
        $userNotes = $validated['notes'] ?? null;
        $combinedNotes = $userNotes;
        if ($summaryText !== null && $summaryText !== '') {
            $combinedNotes = trim(($userNotes ? $userNotes . "\n\n" : '') . '[System — partial stock] ' . $summaryText);
        }
        $pendingRestock = count($partialLines) > 0;

        $restockRecords = [];
        $order = null;

        DB::transaction(function () use (
            &$order,
            &$restockRecords,
            $user,
            $validated,
            $requestedDate,
            $expectedDeliveryAt,
            $deliveryTooClose,
            $subtotal,
            $shippingCost,
            $total,
            $currency,
            $withinCity,
            $combinedNotes,
            $pendingRestock,
            $summaryText,
            $partialLines,
            $quantities
        ) {
            $order = SouvenirOrder::create([
                'user_id' => $user->id,
                'user_address_id' => $validated['user_address_id'] ?? null,
                'requested_delivery_date' => $requestedDate,
                'delivery_location' => $validated['delivery_location'] ?? null,
                'expected_delivery_at' => $expectedDeliveryAt,
                'delivery_too_close' => $deliveryTooClose,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'currency' => $currency,
                'within_city' => $withinCity,
                'status' => $deliveryTooClose ? 'request_review' : 'pending',
                'pending_restock' => $pendingRestock,
                'partial_stock_summary' => $summaryText,
                'notes' => $combinedNotes,
            ]);

            foreach ($validated['items'] as $item) {
                $souvenir = Souvenir::lockForUpdate()->find($item['souvenir_id']);
                $qty = (int) $item['quantity'];
                $unitPrice = (float) $souvenir->price;
                SouvenirOrderItem::create([
                    'souvenir_order_id' => $order->id,
                    'souvenir_id' => $souvenir->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $qty,
                ]);
            }

            foreach ($quantities as $souvenirId => $need) {
                $souvenir = Souvenir::lockForUpdate()->find($souvenirId);
                if (!$souvenir || $souvenir->stock === null) {
                    continue;
                }
                $take = min((int) $souvenir->stock, $need);
                if ($take > 0) {
                    $souvenir->decrement('stock', $take);
                }
            }

            foreach ($partialLines as $row) {
                if ($row['shortfall'] <= 0) {
                    continue;
                }
                $souvenir = Souvenir::find($row['souvenir']->id);
                if ($souvenir) {
                    $restockRecords[] = $this->createRestockRequest(
                        $souvenir,
                        $row['shortfall'],
                        $user,
                        'order_shortfall'
                    );
                }
            }
        });

        $order->load(['items.souvenir', 'userAddress']);

        $partialMsg = $this->buildPartialStockOrderMessage($partialLines);
        if ($partialMsg) {
            $successMessage = $partialMsg;
            if ($deliveryTooClose) {
                $successMessage .= ' We will contact you regarding the delivery date.';
            }
        } else {
            $successMessage = $deliveryTooClose
                ? 'Request placed. We will contact you regarding the delivery date.'
                : 'Order placed successfully.';
        }

        Log::info('Souvenir order store: order created', [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'status' => $order->status,
            'subtotal' => (float) $order->subtotal,
            'shipping_cost' => (float) $order->shipping_cost,
            'total' => (float) $order->total,
            'currency' => $order->currency,
            'within_city' => $withinCity,
            'delivery_too_close' => $deliveryTooClose,
            'free_shipping_applied' => $freeApplied,
            'requested_delivery_date' => $requestedDate->format('Y-m-d'),
            'pending_restock' => $pendingRestock,
        ]);

        $responseData = $this->transformOrder($order);
        if (!empty($restockRecords)) {
            $responseData['restock_requests'] = array_map(fn ($r) => [
                'id' => $r->id,
                'souvenir_id' => $r->souvenir_id,
                'status' => $r->status,
            ], $restockRecords);
        }

        return response()->json([
            'success' => true,
            'message' => $successMessage,
            'data' => $responseData,
        ], 201);
    }

    public function index(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $orders = SouvenirOrder::with(['items.souvenir', 'userAddress'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        $orders->getCollection()->transform(fn ($o) => $this->transformOrder($o));

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully.',
            'data' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ], 200);
    }

    public function show(int $id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $order = SouvenirOrder::with(['items.souvenir', 'userAddress'])->where('user_id', $user->id)->find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully.',
            'data' => $this->transformOrder($order),
        ], 200);
    }

    public function cancel(int $id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $order = SouvenirOrder::where('user_id', $user->id)->find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        if ($order->status === 'cancelled') {
            return response()->json([
                'success' => true,
                'message' => 'Order already cancelled.',
                'data' => $this->transformOrder($order->load(['items.souvenir', 'userAddress'])),
            ], 200);
        }

        if (in_array($order->status, ['shipped', 'delivered'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel order that is already shipped or delivered.',
            ], 400);
        }

        $order->status = 'cancelled';
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully.',
            'data' => $this->transformOrder($order->load(['items.souvenir', 'userAddress'])),
        ], 200);
    }

    /**
     * multipart/form-data sends booleans as strings; normalize so "true"/"1"/"false" validate.
     */
    /**
     * @param  array<int, int>  $quantities  souvenir_id => total quantity
     * @return array<int, array<string, mixed>>
     */
    private function buildPartialStockLinesForQuantities(array $quantities): array
    {
        $lines = [];
        foreach ($quantities as $souvenirId => $totalQty) {
            $souvenir = Souvenir::where('status', 'active')->find($souvenirId);
            if (!$souvenir || $souvenir->stock === null) {
                continue;
            }
            if ((int) $souvenir->stock >= $totalQty) {
                continue;
            }
            $avail = max(0, (int) $souvenir->stock);
            $lines[] = [
                'souvenir_id' => $souvenir->id,
                'name' => $souvenir->name,
                'requested_quantity' => $totalQty,
                'available_now' => $avail,
                'shortfall' => $totalQty - $avail,
            ];
        }

        return $lines;
    }

    /**
     * @param  array<int, int>  $quantities
     * @return array<int, array{souvenir: Souvenir, ordered: int, available: int, shortfall: int}>
     */
    private function buildPartialStockRowsForOrder(array $quantities): array
    {
        $rows = [];
        foreach ($quantities as $souvenirId => $totalQty) {
            $souvenir = Souvenir::where('status', 'active')->find($souvenirId);
            if (!$souvenir || $souvenir->stock === null) {
                continue;
            }
            if ((int) $souvenir->stock >= $totalQty) {
                continue;
            }
            $avail = max(0, (int) $souvenir->stock);
            $rows[] = [
                'souvenir' => $souvenir,
                'ordered' => $totalQty,
                'available' => $avail,
                'shortfall' => $totalQty - $avail,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $stockLines
     */
    private function buildPartialStockMessagePreview(array $stockLines): ?string
    {
        if (empty($stockLines)) {
            return null;
        }
        $parts = [];
        foreach ($stockLines as $line) {
            $parts[] = sprintf(
                '%s: we currently have %d in stock; %d will be restocked to fulfill your requested quantity of %d.',
                $line['name'],
                $line['available_now'],
                $line['shortfall'],
                $line['requested_quantity']
            );
        }

        return implode(' ', $parts) . ' You can place the order; it will be processed further.';
    }

    /**
     * @param  array<int, array{souvenir: Souvenir, ordered: int, available: int, shortfall: int}>  $partialLines
     */
    private function buildPartialStockOrderMessage(array $partialLines): ?string
    {
        if (empty($partialLines)) {
            return null;
        }
        $parts = [];
        foreach ($partialLines as $row) {
            $name = $row['souvenir']->name;
            $parts[] = sprintf(
                '%s: we currently have %d in stock; the remaining %d will be restocked and your full order of %d will be processed.',
                $name,
                $row['available'],
                $row['shortfall'],
                $row['ordered']
            );
        }

        return implode(' ', $parts);
    }

    /**
     * @param  array<int, array{souvenir: Souvenir, ordered: int, available: int, shortfall: int}>  $partialLines
     */
    private function buildPartialStockSummaryText(array $partialLines): ?string
    {
        if (empty($partialLines)) {
            return null;
        }
        $lines = [];
        foreach ($partialLines as $row) {
            $s = $row['souvenir'];
            $lines[] = "{$s->name}: ordered {$row['ordered']}, available now {$row['available']}, restock needed {$row['shortfall']}.";
        }

        return implode(' ', $lines);
    }

    private function mergeSouvenirOrderBooleanFlags(Request $request): void
    {
        if (!$request->exists('within_city')) {
            return;
        }

        $v = $request->input('within_city');
        if ($v === '' || $v === null) {
            $request->merge(['within_city' => null]);

            return;
        }
        if (is_bool($v)) {
            return;
        }

        $s = strtolower(trim((string) $v));
        if (in_array($s, ['1', 'true', 'yes', 'on'], true)) {
            $request->merge(['within_city' => true]);
        } elseif (in_array($s, ['0', 'false', 'no', 'off'], true)) {
            $request->merge(['within_city' => false]);
        }
    }

    private function createRestockRequest(
        Souvenir $souvenir,
        int $requestedQty,
        $user,
        string $reason = 'order_attempt'
    ): SouvenirRestockRequest {
        $effectiveMin = $souvenir->effectiveMinOrderQuantity();
        $available = $souvenir->stock;
        if ($reason === 'order_shortfall') {
            $parts = [
                'Reason: order_shortfall (units to restock after order allocation)',
                "Souvenir: {$souvenir->name}",
                "Units to restock: {$requestedQty}",
            ];
            if ($available !== null) {
                $parts[] = 'Stock after allocation: ' . $available;
            }
        } else {
            $parts = [
                "Reason: {$reason}",
                "Requested quantity: {$requestedQty}",
                "Minimum order quantity: {$effectiveMin}",
            ];
            if ($available !== null) {
                $parts[] = 'Current available stock: ' . $available;
            }
        }
        $text = implode('. ', $parts) . '.';

        return SouvenirRestockRequest::create([
            'souvenir_id' => $souvenir->id,
            'user_id' => $user->id,
            'message' => $text,
            'status' => 'pending',
        ]);
    }

    private function transformOrder(SouvenirOrder $order): array
    {
        $items = $order->items->map(fn ($i) => [
            'souvenir_id' => $i->souvenir_id,
            'souvenir_name' => $i->souvenir?->name,
            'quantity' => $i->quantity,
            'unit_price' => (float) $i->unit_price,
            'line_total' => (float) $i->line_total,
        ])->all();

        $address = null;
        if ($order->userAddress) {
            $address = [
                'id' => $order->userAddress->id,
                'address_line1' => $order->userAddress->address_line1,
                'address_line2' => $order->userAddress->address_line2,
                'city' => $order->userAddress->city,
                'state' => $order->userAddress->state,
                'country' => $order->userAddress->country,
                'pincode' => $order->userAddress->pincode,
            ];
        }

        return [
            'id' => $order->id,
            'requested_delivery_date' => $order->requested_delivery_date?->format('Y-m-d'),
            'expected_delivery_at' => $order->expected_delivery_at?->toIso8601String(),
            'delivery_too_close' => (bool) $order->delivery_too_close,
            'delivery_message' => $order->delivery_too_close
                ? 'Place a request as your expected delivery date is too close and we will contact you.'
                : null,
            'subtotal' => (float) $order->subtotal,
            'shipping_cost' => (float) $order->shipping_cost,
            'total' => (float) $order->total,
            'currency' => $order->currency,
            'within_city' => (bool) $order->within_city,
            'pending_restock' => (bool) ($order->pending_restock ?? false),
            'partial_stock_summary' => $order->partial_stock_summary,
            'status' => $order->status,
            'notes' => $order->notes,
            'shipping_address' => $address,
            'items' => $items,
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }

    /**
     * Calculate shipping cost based on subtotal, within-city flag and distance.
     *
     * Free shipping rule:
     *  - within city AND subtotal >= free_shipping_min_amount (from settings, fallback to config).
     *
     * Otherwise:
     *  - if distance is provided: cost = distance_km * per_km_rate (from settings, default 1.0)
     *  - if distance is not provided: cost = base shipping charge (from settings or config)
     *
     * @return array [shipping_cost, free_shipping_applied]
     */
    private function calculateShippingCost(float $subtotal, bool $withinCity, ?float $distanceKm): array
    {
        $freeMin = (float) Setting::get('souvenir_free_shipping_min_amount', config('souvenir.free_shipping_min_amount'));
        $perKmRate = (float) Setting::get('souvenir_per_km_rate', 1.0);
        $baseCharge = (float) Setting::get('souvenir_base_shipping_charge', config('souvenir.default_shipping_charge'));

        // Free shipping when within city AND subtotal exceeds threshold
        if ($withinCity && $subtotal >= $freeMin) {
            return [0.0, true];
        }

        // Distance-based calculation if distance is provided
        if (!is_null($distanceKm)) {
            $shipping = max(0.0, $distanceKm * $perKmRate);
            return [$shipping, false];
        }

        // Fallback to base charge
        return [$baseCharge, false];
    }

    /**
     * Calculate distance (in km) between user address and souvenirs using Haversine.
     * If multiple souvenirs are present, returns the minimum distance among them.
     * Returns null if coordinates are missing.
     *
     * @param UserAddress|null $address
     * @param array<int,\App\Models\Souvenir> $souvenirsById
     */
    private function calculateDistanceKm(?UserAddress $address, array $souvenirsById): ?float
    {
        if (!$address || is_null($address->latitude) || is_null($address->longitude)) {
            return null;
        }

        $lat1 = deg2rad((float) $address->latitude);
        $lon1 = deg2rad((float) $address->longitude);
        $earthRadius = 6371.0; // km

        $distances = [];

        foreach ($souvenirsById as $souvenir) {
            if (is_null($souvenir->latitude) || is_null($souvenir->longitude)) {
                continue;
            }

            $lat2 = deg2rad((float) $souvenir->latitude);
            $lon2 = deg2rad((float) $souvenir->longitude);

            $dLat = $lat2 - $lat1;
            $dLon = $lon2 - $lon1;

            $a = sin($dLat / 2) ** 2 +
                cos($lat1) * cos($lat2) * sin($dLon / 2) ** 2;
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $distance = $earthRadius * $c;

            $distances[] = $distance;
        }

        if (empty($distances)) {
            return null;
        }

        return min($distances);
    }
}
