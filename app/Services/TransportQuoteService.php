<?php

namespace App\Services;

use App\Models\Transport;
use App\Models\TransportBooking;
use App\Models\Vehicle;

class TransportQuoteService
{
    public function __construct(
        protected DistanceApiService $distanceApi
    ) {
    }

    /**
     * Get the smallest active vehicle that can accommodate the given number of passengers.
     */
    public function getVehicleForPassengers(int $passengers): ?Vehicle
    {
        return Vehicle::where('status', 'active')
            ->where('capacity_seats', '>=', $passengers)
            ->orderBy('capacity_seats')
            ->first();
    }

    /**
     * Find transport (pricing) for a vehicle and city/location.
     * Matches by vehicle_id and location containing the city name or exact match.
     */
    public function getTransportForCity(int $vehicleId, string $city): ?Transport
    {
        $city = trim($city);
        return Transport::where('vehicle_id', $vehicleId)
            ->where('status', 'active')
            ->where(function ($q) use ($city) {
                $q->where('location', $city)
                    ->orWhere('location', 'like', '%' . $city . '%');
            })
            ->first();
    }

    /**
     * Get distance in km between two locations via API (Google Distance Matrix) or fallback to LocationDistance table.
     */
    protected function getDistanceKm(string $origin, string $destination): ?float
    {
        return $this->distanceApi->getDistanceKm($origin, $destination);
    }

    /**
     * Build quote: line items and total.
     * Distance is calculated via Distance API from pick to drop. No need for user to send distance_km.
     * - Intercity by our vehicle: one pick + one drop per leg (or use city names if legs not provided).
     * - Not by our vehicle: user can send two within-city pairs (pick/drop in city A, pick/drop in city B); both distances are calculated and charged.
     */
    public function buildQuote(array $input): array
    {
        $passengers = (int) ($input['passengers'] ?? 0);
        $tripType = $input['trip_type'] ?? TransportBooking::TRIP_TYPE_ONE_WAY;
        $cities = $input['cities'] ?? [];
        $daysPerCity = $input['days_per_city'] ?? [];
        $legsByTrain = $input['legs_by_train'] ?? [];
        $legs = $input['legs'] ?? [];

        $cities = array_values(array_map('trim', (array) $cities));
        $daysPerCity = array_map('intval', (array) $daysPerCity);
        $legsByTrain = array_map(function ($v) {
            return filter_var($v, FILTER_VALIDATE_BOOLEAN);
        }, (array) $legsByTrain);
        $legs = array_values((array) $legs);

        $vehicle = $this->getVehicleForPassengers($passengers);
        if (!$vehicle) {
            return [
                'success' => false,
                'message' => 'No vehicle available for the requested passenger count.',
                'line_items' => [],
                'total_amount' => null,
                'currency' => null,
                'vehicle' => null,
            ];
        }

        $vehicleId = $vehicle->id;
        $lineItems = [];
        $total = 0.0;
        $currency = 'INR';

        // Normalize return: A → B → A (cities = [A, B, A], days_per_city = [d1, d2, 0])
        if ($tripType === TransportBooking::TRIP_TYPE_RETURN && count($cities) === 2) {
            $cities = [$cities[0], $cities[1], $cities[0]];
            $daysPerCity = [
                $daysPerCity[0] ?? 0,
                $daysPerCity[1] ?? 0,
                0,
            ];
        }

        $numCities = count($cities);
        if ($numCities < 2) {
            return [
                'success' => false,
                'message' => 'At least two cities (start and end) are required.',
                'line_items' => [],
                'total_amount' => null,
                'currency' => null,
                'vehicle' => $this->formatVehicle($vehicle),
            ];
        }

        $dayIndex = 0;
        $legIndex = 0;

        // Day-by-day: first city full days, then each leg (transfer day consumes 1 day of next city), then remaining days in each next city.
        for ($i = 0; $i < $numCities; $i++) {
            $city = $cities[$i];
            $transport = $this->getTransportForCity($vehicleId, $city);
            $pricePerDay = $transport ? (float) $transport->price_per_day : 0;
            $cityCurrency = $transport && $transport->currency ? $transport->currency : $currency;
            if ($currency === 'INR' && $cityCurrency !== 'INR') {
                $currency = $cityCurrency;
            }

            $daysInThisCity = (int) ($daysPerCity[$i] ?? 0);
            // Full stay days in this city (only price per day; no separate accommodation)
            $fullDaysHere = $daysInThisCity;
            for ($d = 0; $d < $fullDaysHere; $d++) {
                $dayIndex++;
                $amount = $pricePerDay;
                $total += $amount;
                $lineItems[] = [
                    'day' => $dayIndex,
                    'city' => $city,
                    'description' => '12 Hour Disposal',
                    'vehicle_display' => $vehicle->name,
                    'amount' => round($amount, 2),
                    'currency' => $cityCurrency,
                ];
            }

            if ($i < $numCities - 1) {
                $from = $city;
                $to = $cities[$i + 1];
                $byTrain = isset($legsByTrain[$i]) && $legsByTrain[$i];
                $transportFrom = $transport;
                $transportTo = $this->getTransportForCity($vehicleId, $to);
                $dayIndex++;

                if ($byTrain) {
                    // Travel between cities by other vehicle: within-city pick/drop in city A and in city B; distance calculated via API for both
                    $legData = $legs[$legIndex] ?? [];
                    $pickupCityA = $legData['pickup_city_a'] ?? null;
                    $dropCityA = $legData['drop_city_a'] ?? null;
                    $pickupCityB = $legData['pickup_city_b'] ?? null;
                    $dropCityB = $legData['drop_city_b'] ?? null;

                    $chargeForDay = 0.0;
                    $descriptions = [];

                    if ($pickupCityA && $dropCityA) {
                        $distA = $this->getDistanceKm(trim($pickupCityA), trim($dropCityA));
                        if ($distA !== null && $distA > 0) {
                            $pricePerKmA = $transportFrom ? (float) $transportFrom->price_per_km : 0;
                            $chargeA = $distA * $pricePerKmA;
                            $chargeForDay += $chargeA;
                            $descriptions[] = $from . ' (' . round($distA, 0) . ' km)';
                        }
                    }
                    if ($pickupCityB && $dropCityB) {
                        $distB = $this->getDistanceKm(trim($pickupCityB), trim($dropCityB));
                        if ($distB !== null && $distB > 0) {
                            $pricePerKmB = $transportTo ? (float) $transportTo->price_per_km : 0;
                            $chargeB = $distB * $pricePerKmB;
                            $chargeForDay += $chargeB;
                            $descriptions[] = $to . ' (' . round($distB, 0) . ' km)';
                        }
                    }

                    $pricePerDayTo = $transportTo ? (float) $transportTo->price_per_day : 0;
                    $chargeForDay += $pricePerDayTo;
                    $desc = count($descriptions) > 0 ? implode(' + ', $descriptions) . ' + Full day ' . $to : 'Full day in ' . $to;
                    $lineItems[] = [
                        'day' => $dayIndex,
                        'city' => $from . ' → ' . $to . ' (by other vehicle)',
                        'description' => $desc,
                        'vehicle_display' => $vehicle->name,
                        'amount' => round($chargeForDay, 2),
                        'currency' => $transportTo && $transportTo->currency ? $transportTo->currency : $currency,
                    ];
                    $total += $chargeForDay;
                } else {
                    // Intercity by our vehicle: pick location to drop location (from API or legs)
                    $legData = $legs[$legIndex] ?? [];
                    $pickup = isset($legData['pickup']) ? trim((string) $legData['pickup']) : $from;
                    $drop = isset($legData['drop']) ? trim((string) $legData['drop']) : $to;
                    $distance = $this->getDistanceKm($pickup, $drop);

                    $transportLeg = $transportFrom ?? $transportTo;
                    $legCurrency = $transportLeg && $transportLeg->currency ? $transportLeg->currency : $currency;
                    if ($distance === null || $distance < 0) {
                        $lineItems[] = [
                            'day' => $dayIndex,
                            'city' => $from . ' - ' . $to,
                            'description' => 'Distance could not be calculated. Check addresses or add route in Admin → Location Distances.',
                            'vehicle_display' => $vehicle->name,
                            'amount' => 0,
                            'currency' => $legCurrency,
                        ];
                        $legIndex++;
                        continue;
                    }
                    if ($distance === 0.0) {
                        $distance = 0.01;
                    }
                    $pricePerKm = $transportLeg ? (float) $transportLeg->price_per_km : 0;
                    $distanceCharge = $distance * $pricePerKm;
                    $amount = $distanceCharge;
                    $total += $amount;
                    $lineItems[] = [
                        'day' => $dayIndex,
                        'city' => $from . ' - ' . $to,
                        'description' => 'Long Distance (' . round($distance, 0) . ' km)',
                        'vehicle_display' => $vehicle->name,
                        'amount' => round($amount, 2),
                        'currency' => $legCurrency,
                    ];
                    $legIndex++;
                }
            }
        }

        return [
            'success' => true,
            'message' => 'Quote prepared.',
            'line_items' => $lineItems,
            'total_amount' => round($total, 2),
            'currency' => $currency,
            'vehicle' => $this->formatVehicle($vehicle),
        ];
    }

    protected function formatVehicle(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'name' => $vehicle->name,
            'capacity_seats' => $vehicle->capacity_seats,
            'vehicle_category' => $vehicle->vehicle_category,
            'image' => $vehicle->image_url,
        ];
    }
}
