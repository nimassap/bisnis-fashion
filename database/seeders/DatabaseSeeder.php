<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Cashbook\SettleVendorPayment;
use App\Enums\FabricStatus;
use App\Enums\Gender;
use App\Enums\ProductStatus;
use App\Enums\SampleStatus;
use App\Enums\VendorStatus;
use App\Enums\VendorType;
use App\Models\Business;
use App\Models\User;
use App\Services\Sales\OrderRecorder;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * One owner, one business and its first couple set with a few vendors
     * and fabrics, enough to click through the app locally.
     * Sign in with owner@example.test / password.
     */
    public function run(): void
    {
        $owner = User::factory()->create([
            'name' => 'Pemilik Brand',
            'email' => 'owner@example.test',
        ]);

        $business = Business::factory()->for($owner, 'owner')->create();

        $collection = $business->collections()->create([
            'name' => 'Linen Senja',
            'target_launch_on' => now()->addMonths(3)->toDateString(),
        ]);

        $maker = $business->vendors()->create([
            'type' => VendorType::Maker, 'name' => 'Konveksi Jaya Abadi', 'contact_name' => 'Bu Rini',
            'phone' => '081234567890', 'location' => 'Bandung', 'moq' => 50, 'unit_price' => '35000',
            'price_unit' => 'pcs', 'lead_time_days' => 21, 'rating' => 4, 'status' => VendorStatus::Active,
        ]);
        $business->vendors()->create([
            'type' => VendorType::Maker, 'name' => 'Rumah Jahit Solo', 'location' => 'Solo', 'moq' => 30,
            'unit_price' => '42000', 'price_unit' => 'pcs', 'lead_time_days' => 14, 'rating' => 5,
        ]);
        $supplier = $business->vendors()->create([
            'type' => VendorType::Fabric, 'name' => 'Toko Kain Cigondewah', 'location' => 'Bandung',
            'unit_price' => '48000', 'price_unit' => 'meter', 'rating' => 4,
        ]);
        $business->vendors()->create([
            'type' => VendorType::Label, 'name' => 'Woven Label Nusantara', 'moq' => 500,
            'unit_price' => '450', 'price_unit' => 'pcs', 'lead_time_days' => 10,
        ]);

        $linen = $business->fabrics()->create([
            'vendor_id' => $supplier->id, 'name' => 'Linen Rami', 'composition' => '55% linen, 45% rayon',
            'gsm' => 160, 'width_cm' => 145, 'price_per_meter' => '48000', 'colors' => 'Sage, krem, hitam',
            'shrinkage_percent' => '2.0', 'colorfast' => true, 'comfort_rating' => 5, 'status' => FabricStatus::Chosen,
        ]);
        $business->fabrics()->create([
            'name' => 'Katun Toyobo', 'composition' => '100% katun', 'gsm' => 120, 'width_cm' => 150,
            'price_per_meter' => '32000', 'shrinkage_percent' => '4.5', 'colorfast' => false, 'comfort_rating' => 3,
            'status' => FabricStatus::Rejected,
        ]);

        $dress = $collection->products()->create([
            'gender' => Gender::Women, 'name' => 'Dress Senja', 'colors' => 'Sage, krem', 'status' => ProductStatus::Sampling,
            'fabric_id' => $linen->id, 'fabric_consumption_m' => '2.2',
            'sewing_cost' => '35000', 'planned_quantity' => 50, 'price' => '349000',
        ]);
        $dress->samples()->create([
            'vendor_id' => $maker->id, 'round' => 1, 'status' => SampleStatus::Revision,
            'requested_on' => now()->subDays(10)->toDateString(), 'received_on' => now()->subDays(3)->toDateString(),
            'cost' => '150000', 'notes' => 'Lengan kepanjangan 2 cm.',
        ]);

        $collection->products()->create([
            'gender' => Gender::Men, 'name' => 'Kemeja Senja', 'colors' => 'Sage, krem',
            'fabric_id' => $linen->id, 'fabric_consumption_m' => '1.8',
            'sewing_cost' => '30000', 'planned_quantity' => 50, 'price' => '299000',
        ]);

        foreach ($collection->products as $product) {
            foreach (['Label' => '500', 'Hangtag' => '1000', 'Packaging' => '2500'] as $name => $amount) {
                $product->costItems()->where('name', $name)->update(['amount' => $amount]);
            }
        }

        $collection->update(['bundle_price' => '599000']);
        $collection->costs()->create(['name' => 'Foto produk', 'amount' => '1500000', 'position' => 1]);
        $collection->costs()->create(['name' => 'Iklan launching', 'amount' => '1200000', 'position' => 2]);

        // Cash book: fabric bought, the sample paid, a deposit paid through
        // the payment schedule and the balance still to come.
        $out = fn (string $name) => $business->cashCategories()->where('type', 'out')->where('name', $name)->value('id');

        $business->cashEntries()->create([
            'type' => 'out', 'amount' => '4800000', 'occurred_on' => now()->subDays(12)->toDateString(),
            'description' => 'Kain Linen Rami 100 meter', 'cash_category_id' => $out('Kain'),
            'collection_id' => $collection->id, 'vendor_id' => $supplier->id,
        ]);
        $business->cashEntries()->create([
            'type' => 'out', 'amount' => '150000', 'occurred_on' => now()->subDays(10)->toDateString(),
            'description' => 'Sampel Dress Senja #1', 'cash_category_id' => $out('Sampel'),
            'collection_id' => $collection->id, 'vendor_id' => $maker->id,
        ]);

        $deposit = $business->vendorPayments()->create([
            'title' => 'DP 50% produksi Linen Senja', 'amount' => '1625000', 'due_on' => now()->subDays(2)->toDateString(),
            'vendor_id' => $maker->id, 'collection_id' => $collection->id, 'cash_category_id' => $out('Ongkos jahit'),
        ]);
        app(SettleVendorPayment::class)->pay($deposit, now()->subDays(2)->toDateString());

        $business->vendorPayments()->create([
            'title' => 'Pelunasan produksi Linen Senja', 'amount' => '1625000', 'due_on' => now()->addDays(5)->toDateString(),
            'vendor_id' => $maker->id, 'collection_id' => $collection->id, 'cash_category_id' => $out('Ongkos jahit'),
        ]);

        // Stock from the first production run, two campaigns, a few orders
        // and the content planned around them.
        $dress = $collection->products()->where('gender', 'women')->first();
        $shirt = $collection->products()->where('gender', 'men')->first();

        foreach (['S' => 8, 'M' => 12, 'L' => 10, 'XL' => 5] as $size => $qty) {
            $dress->variants()->create(['size' => $size, 'color' => 'Sage', 'produced_quantity' => (int) ceil($qty / 2)]);
            $dress->variants()->create(['size' => $size, 'color' => 'Krem', 'produced_quantity' => intdiv($qty, 2)]);
            $shirt->variants()->create(['size' => $size, 'color' => 'Sage', 'produced_quantity' => $qty]);
            $shirt->variants()->create(['size' => $size, 'color' => 'Krem', 'produced_quantity' => intdiv($qty, 2)]);
        }

        $shopee = $business->salesChannels()->where('name', 'Shopee')->value('id');
        $tiktok = $business->salesChannels()->where('name', 'TikTok Shop')->value('id');

        $endorse = $business->campaigns()->create([
            'name' => 'Endorse @kopisenja.couple', 'type' => 'endorsement', 'starts_on' => now()->subDays(6)->toDateString(),
            'ends_on' => now()->addDays(8)->toDateString(), 'budget' => '750000', 'spend' => '500000',
            'kol_handle' => '@kopisenja.couple', 'voucher_code' => 'SENJA10', 'collection_id' => $collection->id,
            'sales_channel_id' => $tiktok,
        ]);
        $business->campaigns()->create([
            'name' => 'Iklan Shopee launching', 'type' => 'ads', 'starts_on' => now()->subDays(4)->toDateString(),
            'budget' => '1200000', 'spend' => '420000', 'collection_id' => $collection->id, 'sales_channel_id' => $shopee,
        ]);

        $bundle = fn (string $w, string $wc, string $m, string $mc, int $qty = 1) => [
            'kind' => 'bundle', 'collectionId' => $collection->id, 'womenSize' => $w, 'womenColor' => $wc,
            'menSize' => $m, 'menColor' => $mc, 'quantity' => $qty, 'price' => '599000',
        ];

        $recorder = app(OrderRecorder::class);
        $orders = [
            [$shopee, null, 5, [$bundle('M', 'Sage', 'L', 'Sage')]],
            [$tiktok, $endorse->id, 4, [$bundle('S', 'Krem', 'M', 'Krem')]],
            [$tiktok, $endorse->id, 3, [$bundle('M', 'Sage', 'XL', 'Sage')]],
            [$shopee, null, 2, [['kind' => 'single', 'productId' => $dress->id, 'size' => 'S', 'color' => 'Sage', 'quantity' => 2, 'price' => '349000']]],
            [$shopee, null, 1, [['kind' => 'single', 'productId' => $shirt->id, 'size' => 'L', 'color' => 'Sage', 'quantity' => 1, 'price' => '299000']]],
            [$tiktok, $endorse->id, 0, [$bundle('XL', 'Sage', 'XL', 'Sage', 2)]],
        ];

        foreach ($orders as $i => [$channel, $campaign, $daysAgo, $lines]) {
            $recorder->save($business, null, [
                'channelId' => $channel, 'campaignId' => $campaign, 'orderedOn' => now()->subDays($daysAgo)->toDateString(),
                'reference' => sprintf('%s%05d', $channel === $shopee ? 'SHP' : 'TTS', 1201 + $i), 'customer' => null,
                'discount' => '0', 'notes' => null, 'recordCash' => true,
            ], $lines);
        }

        foreach ([
            [-3, 'instagram', 'reel', 'Reels OOTD couple Linen Senja', 'posted'],
            [-1, 'tiktok', 'video', 'Behind the scene jahit kemeja', 'posted'],
            [2, 'instagram', 'feed', 'Carousel size guide cewek & cowok', 'scheduled'],
            [5, 'tiktok', 'live', 'Live jualan paket couple', 'draft'],
            [9, 'instagram', 'story', 'Testimoni pembeli pertama', 'idea'],
        ] as [$offset, $platform, $format, $title, $status]) {
            $business->contentPosts()->create([
                'platform' => $platform, 'format' => $format, 'title' => $title, 'status' => $status,
                'scheduled_on' => now()->addDays($offset)->toDateString(), 'collection_id' => $collection->id,
            ]);
        }

        foreach (['Kain' => '5000000', 'Ongkos jahit' => '3250000', 'Sampel' => '300000', 'Foto & konten' => '1500000', 'Iklan' => '1200000'] as $name => $amount) {
            $collection->budgets()->create(['cash_category_id' => $out($name), 'amount' => $amount]);
        }
    }
}
