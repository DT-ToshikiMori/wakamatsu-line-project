<?php

namespace Tests\Feature;

use App\Filament\Pages\StoreDashboard;
use App\Services\PostalCodeGeocodeService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StoreDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        Carbon::setTestNow('2026-11-18 12:00:00');
        Http::preventStrayRequests();
        $geocoder=\Mockery::mock(PostalCodeGeocodeService::class);
        $geocoder->shouldReceive('getApiKey')->andReturn('offline-key');
        $this->app->instance(PostalCodeGeocodeService::class,$geocoder);
        Schema::create('stores',function(Blueprint $t){$t->id();$t->string('name');$t->string('address')->nullable();$t->boolean('is_active')->default(true);});
        Schema::create('users',function(Blueprint $t){$t->id();$t->string('gender')->nullable();$t->integer('birth_year')->nullable();$t->string('visit_frequency')->nullable();$t->string('postal_code')->nullable();});
        Schema::create('visits',function(Blueprint $t){$t->id();$t->integer('user_id');$t->integer('store_id');$t->timestamp('visited_at');});
        DB::table('stores')->insert([
            ['id'=>1,'name'=>'Alpha','address'=>'大阪府大阪市テスト1','is_active'=>true],
            ['id'=>2,'name'=>'Beta','address'=>null,'is_active'=>true],
            ['id'=>3,'name'=>'Closed','address'=>'非公開','is_active'=>false],
        ]);
        DB::table('users')->insert([['id'=>1],['id'=>2]]);
        foreach ([[1,1,'2026-10-01'],[1,1,'2026-11-05'],[1,1,'2026-11-10'],[2,1,'2026-11-15'],[1,2,'2026-11-16']] as [$user,$store,$date]) {
            DB::table('visits')->insert(['user_id'=>$user,'store_id'=>$store,'visited_at'=>$date.' 12:00:00']);
        }
    }
    protected function tearDown(): void { Carbon::setTestNow(); parent::tearDown(); }
    private function dashboard(?string $store): StoreDashboard
    {
        $page=new StoreDashboard; $page->storeId=$store; $page->startDate='2026-11-01';$page->endDate='2026-11-30';
        (new \ReflectionMethod($page,'computeDashboard'))->invoke($page);
        return $page;
    }
    public function test_selected_store_location_is_available_without_postal_visitors_and_metrics_stay_the_same(): void
    {
        $page=$this->dashboard('1');
        $this->assertSame(['id'=>1,'name'=>'Alpha','address'=>'大阪府大阪市テスト1'],$page->selectedStore);
        $this->assertSame([], $page->storeRows);
        $this->assertSame([], $page->mapRows);
        $this->assertSame(['visit_count'=>3,'visitor_count'=>2,'repeat_visitor_count'=>1,'new_visitor_count'=>1,'avg_visits_per_user'=>1.5,'repeat_rate'=>50.0],$page->summary);
    }
    public function test_all_stores_has_no_pin_and_preserves_cross_store_deduplication(): void
    {
        $page=$this->dashboard(null);
        $this->assertNull($page->selectedStore);
        $this->assertSame(4,$page->summary['visit_count']);
        $this->assertSame(2,$page->summary['visitor_count']);
        $this->assertCount(2,$page->storeRows);
        $this->assertSame(3,$page->storeRows[0]['visit_count']);
    }
    public function test_switching_store_resets_location_including_all_stores_and_invalid_selections(): void
    {
        $page=$this->dashboard('1');
        $page->storeId='2';$page->updatedStoreId();
        $this->assertSame('Beta',$page->selectedStore['name']);
        $this->assertNull($page->selectedStore['address']);
        $page->storeId=null;$page->updatedStoreId();$this->assertNull($page->selectedStore);
        $page->storeId='3';$page->updatedStoreId();$this->assertNull($page->selectedStore);
    }
    public function test_livewire_view_renders_store_selection_map_data_and_clarified_metrics(): void
    {
        \Livewire\Livewire::test(StoreDashboard::class)
            ->set('storeId','1')
            ->assertSee('期間内の複数回来店率')
            ->assertSee('郵便番号エリアマップ（上位20件）')
            ->assertSeeHtml('data-selected-store=')
            ->assertSeeHtml('store-postal-map.js');
    }

}
