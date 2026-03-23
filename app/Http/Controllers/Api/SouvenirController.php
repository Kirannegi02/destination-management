<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Souvenir;
use App\Services\ImageService;
use Illuminate\Http\Request;

class SouvenirController extends Controller
{
    /**
     * List souvenirs (e.g. by country). Public or authenticated.
     */
    public function index(Request $request)
    {
        $query = Souvenir::query()->where('status', 'active');

        if ($request->filled('country')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('country')
                    ->orWhere('country', 'like', '%' . $request->country . '%');
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $souvenirs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $souvenirs->getCollection()->transform(fn ($s) => $this->transformSouvenir($s));

        return response()->json([
            'success' => true,
            'message' => 'Souvenirs retrieved successfully.',
            'data' => $souvenirs->items(),
            'pagination' => [
                'current_page' => $souvenirs->currentPage(),
                'last_page' => $souvenirs->lastPage(),
                'per_page' => $souvenirs->perPage(),
                'total' => $souvenirs->total(),
            ],
        ], 200);
    }

    /**
     * Single souvenir by ID (for "Buy now" detail page).
     */
    public function show(int $id)
    {
        $souvenir = Souvenir::where('status', 'active')->find($id);

        if (!$souvenir) {
            return response()->json([
                'success' => false,
                'message' => 'Souvenir not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Souvenir retrieved successfully.',
            'data' => $this->transformSouvenir($souvenir),
        ], 200);
    }

    private function transformSouvenir(Souvenir $s): array
    {
        $images = [];
        if ($s->images && is_array($s->images)) {
            foreach ($s->images as $path) {
                $images[] = ImageService::getUrl($path);
            }
        }

        $effectiveMin = $s->effectiveMinOrderQuantity();

        return [
            'id' => $s->id,
            'name' => $s->name,
            'description' => $s->description,
            'price' => (float) $s->price,
            'currency' => $s->currency,
            'min_order_quantity' => $effectiveMin,
            'stock' => $s->stock !== null ? (int) $s->stock : null,
            'can_fulfill_minimum_order' => $s->canFulfillMinimumOrder(),
            'stock_below_minimum_order' => $s->stockBelowMinimumOrder(),
            'city' => $s->city,
            'country' => $s->country,
            'images' => $images,
            'status' => $s->status,
        ];
    }
}
