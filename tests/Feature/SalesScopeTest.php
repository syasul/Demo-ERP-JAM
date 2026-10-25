<?php

declare(strict_types=1);

use App\Models\SalesProfile;
use App\Models\User;
use App\Traits\BelongsToSales;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    // Create a temporary table for testing BelongsToSales trait
    if (! Schema::hasTable('test_sales_orders')) {
        Schema::create('test_sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->nullable();
            $table->string('title');
            $table->timestamps();
        });
    }
});

// Anonymous class model using BelongsToSales
class TestSalesOrder extends Model
{
    use BelongsToSales;

    protected $table = 'test_sales_orders';

    protected $fillable = ['sales_id', 'title'];
}

test('sales user only sees records belonging to their sales profile', function () {
    // 1. Setup Sales A
    $userA = User::factory()->create(['email' => 'salesA@erp.test']);
    $userA->assignRole('sales');
    $profileA = SalesProfile::create([
        'user_id' => $userA->id,
        'employee_code' => 'SLS-001',
    ]);

    // 2. Setup Sales B
    $userB = User::factory()->create(['email' => 'salesB@erp.test']);
    $userB->assignRole('sales');
    $profileB = SalesProfile::create([
        'user_id' => $userB->id,
        'employee_code' => 'SLS-002',
    ]);

    // 3. Create orders for both
    TestSalesOrder::create(['sales_id' => $profileA->id, 'title' => 'Order A']);
    TestSalesOrder::create(['sales_id' => $profileB->id, 'title' => 'Order B']);

    // When logged in as Sales A:
    $this->actingAs($userA);
    $ordersForA = TestSalesOrder::all();

    expect($ordersForA)->toHaveCount(1)
        ->and($ordersForA->first()->title)->toBe('Order A');

    // Finding Order B returns null (resulting in 404 in route model binding, not leaking ID)
    expect(TestSalesOrder::find(2))->toBeNull();

    // When logged in as Sales B:
    $this->actingAs($userB);
    $ordersForB = TestSalesOrder::all();

    expect($ordersForB)->toHaveCount(1)
        ->and($ordersForB->first()->title)->toBe('Order B');
    expect(TestSalesOrder::find(1))->toBeNull();

    // When logged in as Owner: sees all orders without scope restriction
    $ownerUser = User::factory()->create(['email' => 'owner@erp.test']);
    $ownerUser->assignRole('owner');

    $this->actingAs($ownerUser);
    expect(TestSalesOrder::all())->toHaveCount(2);
});
