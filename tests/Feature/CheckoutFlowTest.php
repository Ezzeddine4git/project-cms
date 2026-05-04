<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_logged_in_customer_can_create_order_from_checkout(): void
    {
        $this->seed();

        $user = User::where('email', 'client@camping-vibes.test')->firstOrFail();
        $product = Product::where('slug', 'tente-aurora-2-places')->firstOrFail();

        $response = $this
            ->actingAs($user)
            ->withSession(['cart' => [$product->id => 2]])
            ->post(route('checkout.confirm'), [
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'address_line' => '8 avenue Habib Bourguiba',
                'postal_code' => '1000',
                'city' => 'Tunis',
                'country' => 'Tunisie',
            ]);

        $order = Order::latest('id')->firstOrFail();

        $response->assertRedirect(route('account.orders.show', $order));
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $user->id,
            'status' => 'payee',
            'total' => 1598,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'total' => 1598,
        ]);
    }
}
