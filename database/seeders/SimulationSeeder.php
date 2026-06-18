<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Location;
use App\Models\Company;
use App\Models\Transporter;
use App\Models\Transaction;
use App\Models\PurchaseOrder;
use App\Models\Stock;
use App\Models\ProductionLog;
use App\Models\ProductionLogInput;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\DispatchLog;
use App\Models\DispatchLogItem;
use App\Models\DispatchItemLocation;
use Carbon\Carbon;

class SimulationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting 30-day Factory Simulation...');
        
        $admin = User::where('role', 'ADMIN')->first();
        $rawUser = User::where('role', 'RAW')->first();
        $semiUser = User::where('role', 'SEMI')->first();
        $finishedUser = User::where('role', 'FINISHED')->first();
        $cashierUser = User::where('role', 'CASHIER')->first();
        $salesUser = User::where('role', 'SALES')->first();
        $dispatchUser = User::where('role', 'DISPATCH')->first();
        
        if (!$rawUser || !$semiUser || !$finishedUser || !$cashierUser || !$salesUser || !$dispatchUser) {
            $this->command->error('Users not found. Run UserSeeder first.');
            return;
        }

        $rawProducts = Product::where('type', 'RAW')->get();
        $processedProducts = Product::where('type', 'FINISHED')->get(); 
        
        $location = Location::firstOrCreate(['name' => 'Main Warehouse']);
        
        $company = Company::firstOrCreate(
            ['name' => 'Simulated Client Co.'],
            ['address' => '123 Test St', 'contact' => '9999999999']
        );
        $transporter = Transporter::firstOrCreate(
            ['name' => 'Simulated Transporter'],
            ['contact' => '8888888888']
        );
        
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();
        
        $period = new \DatePeriod(
            $startDate,
            new \DateInterval('P1D'),
            $endDate->copy()->addDay()
        );
        
        foreach ($period as $date) {
            $cdate = Carbon::instance($date);
            $this->command->info("Simulating day: " . $cdate->format('Y-m-d'));
            
            // 1. CASHIER: 2 Transactions per day
            for ($i = 0; $i < 2; $i++) {
                Transaction::create([
                    'user_id' => $cashierUser->id,
                    'type' => (rand(1, 10) > 3) ? 'IN' : 'OUT',
                    'amount' => rand(1000, 50000) / 10,
                    'category' => 'sales',
                    'note' => 'Daily auto entry',
                    'created_at' => $cdate->copy()->addHours(rand(9, 17)),
                    'updated_at' => $cdate->copy()->addHours(rand(9, 17)),
                ]);
            }
            
            // 2. RAW: Purchase Order & Stock IN
            $poItems = [
                ['product_id' => $rawProducts->random()->id, 'quantity' => rand(500, 1500)],
                ['product_id' => $rawProducts->random()->id, 'quantity' => rand(500, 1500)],
            ];

            foreach ($poItems as $item) {
                PurchaseOrder::create([
                    'user_id' => $rawUser->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'status' => 'DONE',
                    'note' => 'Simulated PO',
                    'created_at' => $cdate->copy()->addHours(8),
                    'updated_at' => $cdate->copy()->addHours(8),
                ]);

                Stock::create([
                    'product_id' => $item['product_id'],
                    'user_id' => $rawUser->id,
                    'stage' => 'RAW',
                    'grade' => 'NONE',
                    'location_id' => $location->id,
                    'quantity' => $item['quantity'],
                    'transaction_type' => 'IN',
                    'date' => $cdate->copy()->addHours(9),
                    'notes' => 'Received from PO',
                    'created_at' => $cdate->copy()->addHours(9),
                    'updated_at' => $cdate->copy()->addHours(9),
                ]);
            }
            
            // 3. SEMI: Produce Semi from Raw
            $rawStocks = Stock::where('stage', 'RAW')->where('transaction_type', 'IN')->get();
            $availableRaw = [];
            foreach ($rawStocks as $s) {
                $out = Stock::where('stage', 'RAW')->where('transaction_type', 'OUT')->where('product_id', $s->product_id)->sum('quantity');
                $in = Stock::where('stage', 'RAW')->where('transaction_type', 'IN')->where('product_id', $s->product_id)->sum('quantity');
                if ($in - $out > 100) {
                    $availableRaw[$s->product_id] = $in - $out;
                }
            }
            
            if (!empty($availableRaw)) {
                $randomRawId = array_rand($availableRaw);
                $consumeQty = rand(50, 100);
                
                Stock::create([
                    'product_id' => $randomRawId,
                    'user_id' => $semiUser->id,
                    'stage' => 'RAW',
                    'grade' => 'NONE',
                    'location_id' => $location->id,
                    'quantity' => $consumeQty,
                    'transaction_type' => 'OUT',
                    'date' => $cdate->copy()->addHours(11),
                    'notes' => 'Consumed for Semi',
                    'created_at' => $cdate->copy()->addHours(11),
                    'updated_at' => $cdate->copy()->addHours(11),
                ]);
                
                $semiProduct = $processedProducts->random();
                $plog = ProductionLog::create([
                    'user_id' => $semiUser->id,
                    'type' => 'SEMI',
                    'output_product_id' => $semiProduct->id,
                    'output_grade' => 'A',
                    'output_qty' => $consumeQty * 0.9, 
                    'date' => $cdate->copy()->addHours(11),
                    'created_at' => $cdate->copy()->addHours(11),
                    'updated_at' => $cdate->copy()->addHours(11),
                ]);
                
                ProductionLogInput::create([
                    'production_log_id' => $plog->id,
                    'input_product_id' => $randomRawId,
                    'input_grade' => 'NONE',
                    'quantity' => $consumeQty,
                    'created_at' => $cdate->copy()->addHours(11),
                    'updated_at' => $cdate->copy()->addHours(11),
                ]);
                
                Stock::create([
                    'product_id' => $semiProduct->id,
                    'user_id' => $semiUser->id,
                    'stage' => 'SEMI',
                    'grade' => 'A',
                    'location_id' => $location->id,
                    'quantity' => $consumeQty * 0.9,
                    'transaction_type' => 'IN',
                    'date' => $cdate->copy()->addHours(11),
                    'notes' => 'Produced Semi',
                    'created_at' => $cdate->copy()->addHours(11),
                    'updated_at' => $cdate->copy()->addHours(11),
                ]);
            }
            
            // 4. FINISHED: Produce Finished from Semi
            $semiStocks = Stock::where('stage', 'SEMI')->where('transaction_type', 'IN')->get();
            $availableSemi = [];
            foreach ($semiStocks as $s) {
                $out = Stock::where('stage', 'SEMI')->where('transaction_type', 'OUT')->where('product_id', $s->product_id)->where('grade', $s->grade)->sum('quantity');
                $in = Stock::where('stage', 'SEMI')->where('transaction_type', 'IN')->where('product_id', $s->product_id)->where('grade', $s->grade)->sum('quantity');
                if ($in - $out > 50) {
                    $availableSemi[] = ['id' => $s->product_id, 'grade' => $s->grade, 'qty' => $in - $out];
                }
            }
            
            if (!empty($availableSemi)) {
                $randSemi = $availableSemi[array_rand($availableSemi)];
                $consumeQty = rand(20, 50);
                
                Stock::create([
                    'product_id' => $randSemi['id'],
                    'user_id' => $finishedUser->id,
                    'stage' => 'SEMI',
                    'grade' => $randSemi['grade'],
                    'location_id' => $location->id,
                    'quantity' => $consumeQty,
                    'transaction_type' => 'OUT',
                    'date' => $cdate->copy()->addHours(14),
                    'notes' => 'Consumed for Finished',
                    'created_at' => $cdate->copy()->addHours(14),
                    'updated_at' => $cdate->copy()->addHours(14),
                ]);
                
                $flog = ProductionLog::create([
                    'user_id' => $finishedUser->id,
                    'type' => 'FINISHED',
                    'output_product_id' => $randSemi['id'],
                    'output_grade' => 'A',
                    'output_qty' => $consumeQty, 
                    'date' => $cdate->copy()->addHours(14),
                    'created_at' => $cdate->copy()->addHours(14),
                    'updated_at' => $cdate->copy()->addHours(14),
                ]);
                
                ProductionLogInput::create([
                    'production_log_id' => $flog->id,
                    'input_product_id' => $randSemi['id'],
                    'input_grade' => $randSemi['grade'],
                    'quantity' => $consumeQty,
                    'created_at' => $cdate->copy()->addHours(14),
                    'updated_at' => $cdate->copy()->addHours(14),
                ]);
                
                Stock::create([
                    'product_id' => $randSemi['id'],
                    'user_id' => $finishedUser->id,
                    'stage' => 'FINISHED',
                    'grade' => 'A',
                    'location_id' => $location->id,
                    'quantity' => $consumeQty,
                    'transaction_type' => 'IN',
                    'date' => $cdate->copy()->addHours(14),
                    'notes' => 'Produced Finished',
                    'created_at' => $cdate->copy()->addHours(14),
                    'updated_at' => $cdate->copy()->addHours(14),
                ]);
            }
            
            // 5. SALES & DISPATCH: Create an order and dispatch it
            $finStocks = Stock::where('stage', 'FINISHED')->where('transaction_type', 'IN')->get();
            $availableFin = [];
            foreach ($finStocks as $s) {
                $out = Stock::where('stage', 'FINISHED')->where('transaction_type', 'OUT')->where('product_id', $s->product_id)->where('grade', $s->grade)->sum('quantity');
                $in = Stock::where('stage', 'FINISHED')->where('transaction_type', 'IN')->where('product_id', $s->product_id)->where('grade', $s->grade)->sum('quantity');
                if ($in - $out > 10) {
                    $availableFin[] = ['id' => $s->product_id, 'grade' => $s->grade, 'qty' => $in - $out];
                }
            }
            
            if (!empty($availableFin) && rand(1,10) > 5) {
                $randFin = $availableFin[array_rand($availableFin)];
                $orderQty = rand(5, 10);
                
                $order = Order::create([
                    'created_by' => $salesUser->id,
                    'company_id' => $company->id,
                    'transporter_id' => $transporter->id,
                    'total' => $orderQty * 150,
                    'date' => $cdate->copy()->addHours(15),
                    'status' => 'CLOSED',
                    'dispatch_status' => 'FULLY_DISPATCHED',
                    'created_at' => $cdate->copy()->addHours(15),
                    'updated_at' => $cdate->copy()->addHours(15),
                ]);
                
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $randFin['id'],
                    'grade' => $randFin['grade'],
                    'quantity' => $orderQty,
                    'dispatched_qty' => $orderQty,
                    'price' => 150,
                    'created_at' => $cdate->copy()->addHours(15),
                    'updated_at' => $cdate->copy()->addHours(15),
                ]);
                
                $dlog = DispatchLog::create([
                    'user_id' => $dispatchUser->id,
                    'order_id' => $order->id,
                    'transporter_id' => $transporter->id,
                    'created_at' => $cdate->copy()->addHours(16),
                    'updated_at' => $cdate->copy()->addHours(16),
                ]);
                
                $ditem = DispatchLogItem::create([
                    'dispatch_log_id' => $dlog->id,
                    'order_item_id' => $orderItem->id,
                    'quantity' => $orderQty,
                    'created_at' => $cdate->copy()->addHours(16),
                    'updated_at' => $cdate->copy()->addHours(16),
                ]);
                
                $outStock = Stock::create([
                    'product_id' => $randFin['id'],
                    'user_id' => $dispatchUser->id,
                    'stage' => 'FINISHED',
                    'grade' => $randFin['grade'],
                    'location_id' => $location->id,
                    'quantity' => $orderQty,
                    'transaction_type' => 'OUT',
                    'date' => $cdate->copy()->addHours(16),
                    'notes' => 'Dispatched Order #' . $order->id,
                    'created_at' => $cdate->copy()->addHours(16),
                    'updated_at' => $cdate->copy()->addHours(16),
                ]);

                DispatchItemLocation::create([
                    'dispatch_log_item_id' => $ditem->id,
                    'location_id' => $location->id,
                    'stock_id' => $outStock->id,
                    'quantity' => $orderQty,
                    'created_at' => $cdate->copy()->addHours(16),
                    'updated_at' => $cdate->copy()->addHours(16),
                ]);
            }
        }
        
        $this->command->info('Simulation complete!');
    }
}
