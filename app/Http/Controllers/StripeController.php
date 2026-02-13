<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeController extends Controller
{
    public function makePaymentCartAuth(Request $request)
    {
        // 1) Validar body
        $data = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.name' => 'required|string',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            // opcional:
            // 'products.*.image' => 'nullable|url',
        ]);

        Stripe::setApiKey(config('services.stripe.secret'));

        // 2) Armar line_items
        $lineItems = array_map(function ($p) {
            $unitAmount = (int) round(((float)$p['unit_price']) * 100); // centavos

            $productData = [
                'name' => $p['name'],
            ];

            // OJO: Stripe Checkout pide URLs públicas https para images
            // si mandas localhost o rutas internas, a veces no las muestra.
            // if (!empty($p['image'])) {
            //     $productData['images'] = [$p['image']];
            // }

            return [
                'price_data' => [
                    'currency' => 'mxn',
                    'product_data' => $productData,
                    'unit_amount' => $unitAmount,
                ],
                'quantity' => (int) $p['quantity'],
            ];
        }, $data['products']);

        $frontUrl = rtrim(env('FRONT_URL', 'http://localhost:5173'), '/');

        // 3) Crear sesión
        $session = \Stripe\Checkout\Session::create([
            'mode' => 'payment',
            'ui_mode' => 'hosted', // opcional, pero deja claro el modo
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'success_url' => $frontUrl . '/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $frontUrl . '/cancel',
        ]);

        return response()->json([
            'id'  => $session->id,
            'url' => $session->url,
        ]);

    }
    public function getSessionStatus($id)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::retrieve($id);

        return response()->json([
            'id' => $session->id,
            'payment_status' => $session->payment_status, // paid/unpaid
            'paid' => $session->payment_status === 'paid',
        ]);
    }
}
