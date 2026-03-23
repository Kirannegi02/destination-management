<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Souvenir;
use App\Models\SouvenirRestockRequest;
use Illuminate\Http\Request;

class SouvenirRestockController extends Controller
{
    /**
     * Store a restock request for an out-of-stock souvenir.
     */
    public function store(Request $request, int $souvenirId)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $souvenir = Souvenir::find($souvenirId);
        if (!$souvenir) {
            return response()->json([
                'success' => false,
                'message' => 'Souvenir not found.',
            ], 404);
        }

        if (is_null($souvenir->stock)) {
            return response()->json([
                'success' => false,
                'message' => 'This item does not use stock tracking.',
            ], 422);
        }

        $validated = $request->validate([
            'message' => 'nullable|string|max:1000',
            'requested_quantity' => 'nullable|integer|min:1',
        ]);

        $effectiveMin = $souvenir->effectiveMinOrderQuantity();
        $requestedQty = isset($validated['requested_quantity']) ? (int) $validated['requested_quantity'] : null;

        $needsRestock = false;
        if ($requestedQty !== null && $requestedQty > $souvenir->stock) {
            $needsRestock = true;
        } elseif ($souvenir->stock < $effectiveMin) {
            $needsRestock = true;
        }

        if (!$needsRestock) {
            return response()->json([
                'success' => false,
                'message' => 'This item has enough stock for the minimum order quantity.',
            ], 422);
        }

        $baseMessage = $validated['message'] ?? null;
        $autoNote = sprintf(
            'Manual request. Minimum order quantity: %d. Current stock: %d.',
            $effectiveMin,
            (int) $souvenir->stock
        );
        $fullMessage = trim(($baseMessage ? $baseMessage . ' ' : '') . $autoNote);

        $restockRequest = SouvenirRestockRequest::create([
            'souvenir_id' => $souvenir->id,
            'user_id' => $user->id,
            'message' => $fullMessage,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your stock replenishment request has been submitted. Our team will update inventory.',
            'data' => [
                'id' => $restockRequest->id,
                'souvenir_id' => $souvenir->id,
                'status' => $restockRequest->status,
            ],
        ], 201);
    }
}

