<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;

class CartController extends Controller
{
    public function addItem(Request $request, Product $product)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'qty' => 'required|integer|min:1|max:99',
        ]);

        $qty = (int) $data['qty'];

        [$cart, $item] = DB::transaction(function () use ($user, $product, $qty) {

            // ✅ 1) Buscar o crear carrito del usuario (1 carrito por user)
            $cart = Cart::firstOrCreate([
                'user_id' => $user->id,
            ]);

            // ✅ 2) Buscar item (incluye soft-deleted para revivirlo si existía)
            $item = CartItem::withTrashed()
                ->where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();

            if ($item) {
                // si estaba borrado soft, revive
                if ($item->trashed()) {
                    $item->restore();
                    $item->quantity = 0; // opcional: si quieres reiniciar al revivir
                }

                $item->quantity += $qty;
                $item->unit_price = $item->unit_price ?? $product->price;
                $item->save();
            } else {
                $item = CartItem::create([
                    'cart_id'    => $cart->id,
                    'product_id' => $product->id,
                    'quantity'   => $qty,
                    'unit_price' => $product->price,
                ]);
            }

            return [$cart, $item];
        });

        return response()->json([
            'message' => 'Added to cart',
            'item' => [
                'product_id' => $item->product_id,
                'name'       => $product->name,
                'quantity'   => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ],
        ], 200);
    }

    // GET /cartAuth/Vista del carrito
    public function cartAuth(Request $request)
    {
        $user = $request->user();

        $cart = Cart::where('user_id', $user->id)
            ->latest('id')
            ->with('items:id,cart_id,quantity') // solo lo necesario
            ->first();

        if (!$cart) {
            return response()->json(['count' => 0]);
        }

        $count = $cart->items->sum('quantity');

        return response()->json(['count' => (int) $count]);
    }

    //GET Vista para mi carrito autenticado.
    public function fetchCart(Request $request)
    {
        $user = $request->user();

        $cart = Cart::where('user_id', $user->id)
            ->latest('id')
            ->with(['items.product'])
            ->first();

        if (!$cart) {
            return response()->json([
                'cart_id' => null,
                'items'   => [],
                'count'   => 0,
            ]);
        }

        $items = $cart->items->map(function ($it) {
            return [
                'id' => $it->id,              
                'product_id'   => $it->product_id,
                'name'         => $it->product?->name,
                'quantity'     => (int) $it->quantity,
                'unit_price'   => (float) $it->unit_price,
                'subtotal'     => (float) ($it->quantity * $it->unit_price),
            ];
        })->values();

        $count = $items->sum('quantity');

        return response()->json([
            'cart_id' => $cart->id,  // ✅ lo mandas
            'items'   => $items,
            'count'   => $count,
        ]);
    }

    public function updateQty(Request $request, CartItem $cartItem)
    {
        // 1) validar usuario
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // 2) validar qty (es el nuevo quantity)
        $data = $request->validate([
            'qty' => 'required|integer|min:1|max:99',
        ]);

        // 3) validar que el cartItem pertenezca a un cart del usuario
        $owns = Cart::where('id', $cartItem->cart_id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$owns) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // 4) actualizar quantity
        $cartItem->quantity = (int) $data['qty'];
        $cartItem->save();

        return response()->json([
            'cart_item_id' => $cartItem->id,
            'cart_id'      => $cartItem->cart_id,
            'quantity'     => (int) $cartItem->quantity,
        ]);
    }



    public function destroy(Request $request, CartItem $cartItem)
    {
        // 1) validar usuario
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // 2) borrar el cart_item por id (viene en la URL)
        $cartItem->delete(); // si tienes SoftDeletes, esto es soft-delete
        // $cartItem->forceDelete(); // si lo quieres borrar definitivo

        return response()->json([
            'message' => 'ok',
            'cart_item_id' => $cartItem->id,
        ]);
    }



}
