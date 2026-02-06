<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeoController extends Controller
{
    private function nominatimHeaders(): array
    {
        // Nominatim pide un User-Agent identificable (y mejor si incluyes contacto)
        $email = env('NOMINATIM_EMAIL', 'dev@linguini.local');

        return [
            'User-Agent' => "LinguiniApp/1.0 ({$email})",
            'Accept'     => 'application/json',
        ];
    }

    // ✅ Dirección -> Coordenadas (Nominatim)
    public function geocode(Request $request)
    {
        $request->validate([
            'address' => 'required|string|min:3',
        ]);

        $resp = Http::withHeaders($this->nominatimHeaders())
            ->get('https://nominatim.openstreetmap.org/search', [
                'q' => $request->address,
                'format' => 'jsonv2',
                'limit' => 1,
                'addressdetails' => 1,
            ]);

        if (!$resp->successful()) {
            return response()->json(['message' => 'Geocode failed'], 502);
        }

        $data = $resp->json();

        if (!is_array($data) || count($data) === 0) {
            return response()->json(['message' => 'Address not found'], 422);
        }

        $item = $data[0];

        return response()->json([
            'lat'   => (float) $item['lat'],
            'lng'   => (float) $item['lon'],
            'label' => $item['display_name'] ?? $request->address,
        ]);
    }

    // ✅ Coordenadas -> Dirección bonita (Nominatim reverse)
    public function reverse(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $resp = Http::withHeaders($this->nominatimHeaders())
            ->get('https://nominatim.openstreetmap.org/reverse', [
                'lat' => $request->lat,
                'lon' => $request->lng,
                'format' => 'jsonv2',
                'addressdetails' => 1,
            ]);

        if (!$resp->successful()) {
            return response()->json(['message' => 'Reverse geocode failed'], 502);
        }

        $data = $resp->json();

        return response()->json([
            'label' => $data['display_name'] ?? null,
        ]);
    }

    public function directions(Request $request)
    {
        $request->validate([
            'start' => ['required', 'string', 'regex:/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/'],
            'end'   => ['required', 'string', 'regex:/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/'],
        ]);

        $key = env('ORS_API_KEY');
        if (!$key) return response()->json(['message' => 'ORS_API_KEY is missing'], 500);

        $resp = Http::withHeaders([
                'Authorization' => $key,
            ])->get('https://api.openrouteservice.org/v2/directions/driving-car', [
                'start' => $request->start,
                'end'   => $request->end,
            ]);

        if (!$resp->successful()) {
            return response()->json([
                'message' => 'ORS directions failed',
                'details' => $resp->json(),
            ], 502);
        }

        $json = $resp->json();

        $feature = $json['features'][0] ?? null;
        $summary = $feature['properties']['summary'] ?? null;
        $coords  = $feature['geometry']['coordinates'] ?? []; // 👈 array de [lng, lat]

        if (!$summary) return response()->json(['message' => 'ORS returned no summary'], 502);

        return response()->json([
            'distance_m' => (float) $summary['distance'],
            'duration_s' => (float) $summary['duration'],
            'route'      => $coords, // 👈 la ruta completa para dibujarla
        ]);
    }

}
