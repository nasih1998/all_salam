<?php

namespace App\Http\Controllers\Api;

use App\Events\CartUpdated;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CustomerDisplayController extends Controller
{
    /**
     * Generate a temporary access token for the public customer display page.
     * The token is stored in cache for 24 hours and returned alongside the full URL
     * and an optional QR code SVG (if the QR library is installed).
     */
    public function generate(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'customer_display_screen_setup', Sale::class);

        $token = Str::random(40);
        // Store token for 24 hours
        cache(['customer_display_token' => $token], now()->addDay());

        $url = url('/customer-display') . '?token=' . $token;

        // Generate QR if the optional package is available; otherwise return null
        $qrSvg = null;
        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->generate($url);
        }

        return response()->json([
            'token' => $token,
            'url' => $url,
            'qr' => $qrSvg,
        ]);
    }

    /**
     * Persist the latest cart for polling fallback and broadcast to channel.
     * This is optional; POS can just whisper via Echo with no backend.
     */
    public function broadcastCart(Request $request)
    {
        $cart = $request->input('cart', []);
        $completed = (bool) $request->boolean('completed', false);

        // Keep only safe shape
        $payload = [
            'currency' => $cart['currency'] ?? ($cart['symbol'] ?? ''),
            'discount' => (float)($cart['discount'] ?? 0),
            'TaxNet'   => (float)($cart['TaxNet'] ?? ($cart['tax'] ?? 0)),
            'shipping' => (float)($cart['shipping'] ?? 0),
            'GrandTotal' => (float)($cart['GrandTotal'] ?? ($cart['total'] ?? 0)),
            'details'  => array_map(function ($row) {
                return [
                    'name' => $row['name'] ?? $row['product_name'] ?? '',
                    'quantity' => (float)($row['quantity'] ?? $row['Qty'] ?? $row['qte'] ?? 0),
                    'Net_price' => (float)($row['Net_price'] ?? $row['price'] ?? 0),
                    'unit_price' => (float)($row['unit_price'] ?? ($row['Net_price'] ?? $row['price'] ?? 0)),
                    'line_total' => (float)($row['line_total'] ?? $row['total'] ?? $row['subtotal'] ?? 0),
                    'subtotal' => (float)($row['total'] ?? $row['subtotal'] ?? 0),
                ];
            }, is_array($cart['details'] ?? null) ? $cart['details'] : ($cart['items'] ?? [])),
        ];

        // Cache last cart for 2 minutes to support polling
        Cache::put('customer_display:last_cart', [
            'cart' => $payload,
            'completed' => $completed,
            'ts' => now()->timestamp,
        ], now()->addMinutes(2));

        // Broadcast for realtime screens
        event(new CartUpdated($payload, $completed));

        return response()->json(['ok' => true]);
    }

    /** Return the last cart for polling clients */
    public function lastCart()
    {
        $data = Cache::get('customer_display:last_cart');
        if (!$data) return response()->json(['cart' => null, 'completed' => false]);
        return response()->json($data);
    }
}


