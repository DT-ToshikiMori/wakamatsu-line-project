<?php

namespace Tests\Feature;

use App\Http\Controllers\CouponController;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ScenarioCouponExpiryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Carbon::setTestNow(Carbon::parse('2026-11-18 12:00:00', 'Asia/Tokyo'));
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('line_user_id'); $t->integer('store_id')->nullable(); });
        Schema::create('visit_scenarios', function (Blueprint $t) { $t->id(); $t->integer('coupon_template_id')->nullable(); $t->integer('expires_days')->nullable(); $t->boolean('reminder_enabled')->default(false); });
        Schema::create('coupon_templates', function (Blueprint $t) { $t->id(); $t->boolean('is_active')->default(true); $t->string('title')->default('Coupon'); $t->string('note')->nullable(); $t->string('image_url')->nullable(); $t->string('type')->default('normal'); });
        Schema::create('message_bubbles', function (Blueprint $t) { $t->id(); $t->string('parent_type'); $t->integer('parent_id'); $t->string('bubble_type'); $t->integer('coupon_template_id'); $t->integer('position')->default(0); $t->integer('coupon_expires_days')->nullable(); $t->timestamp('coupon_expires_at')->nullable(); $t->text('text_content')->nullable(); $t->timestamps(); });
        Schema::create('visit_scenario_sends', function (Blueprint $t) { $t->id(); $t->integer('user_id'); $t->integer('scenario_id'); $t->timestamp('sent_at')->nullable(); $t->timestamp('coupon_issued_at')->nullable(); $t->integer('user_coupon_id')->nullable(); $t->string('delivery_token')->nullable(); $t->timestamp('reminder_scheduled_at')->nullable(); $t->timestamps(); });
        Schema::create('user_coupons', function (Blueprint $t) { $t->id(); $t->integer('store_id')->nullable(); $t->integer('user_id'); $t->integer('coupon_template_id'); $t->integer('message_bubble_id')->nullable(); $t->string('status'); $t->timestamp('issued_at'); $t->timestamp('used_at')->nullable(); $t->timestamp('expires_at')->nullable(); $t->timestamps(); });
        Schema::create('coupon_events', function (Blueprint $t) { $t->id(); $t->integer('user_coupon_id'); $t->string('event'); $t->string('actor'); $t->timestamp('created_at'); });
        DB::table('users')->insert(['id'=>1,'line_user_id'=>'test']);
        foreach ([3,4,5,6] as $id) {
            DB::table('visit_scenarios')->insert(['id'=>$id,'coupon_template_id'=>$id]);
            DB::table('coupon_templates')->insert(['id'=>$id]);
            DB::table('message_bubbles')->insert(['parent_type'=>'visit_scenario','parent_id'=>$id,'bubble_type'=>'coupon','coupon_template_id'=>$id,'coupon_expires_days'=>$id===6 ? null : ($id===5 ? 120 : 60)]);
        }
    }

    protected function tearDown(): void { Carbon::setTestNow(); parent::tearDown(); }

    private function send(int $days, int $scenario=3, ?string $token='test-delivery'): int
    {
        return DB::table('visit_scenario_sends')->insertGetId(['user_id'=>1,'scenario_id'=>$scenario,'sent_at'=>now()->subDays($days),'delivery_token'=>$token]);
    }

    private function claim(int $scenario=3, ?string $token='test-delivery')
    {
        $request=Request::create('/coupons','GET',array_filter(['scenario_id'=>$scenario,'delivery'=>$token],fn($x)=>$x!==null));
        $request->attributes->set('line_user_id','test');
        return app(CouponController::class)->index($request);
    }

    public function test_both_initial_scenarios_expire_from_send_not_claim(): void
    {
        foreach ([3,4] as $id) {
            $this->send(50,$id);
            $this->claim($id);
            $coupon=DB::table('user_coupons')->where('coupon_template_id',$id)->first();
            $this->assertSame(now()->addDays(10)->toDateTimeString(),$coupon->expires_at);
        }
    }

    public function test_exactly_sixty_days_and_older_show_expiry_without_issuing(): void
    {
        $this->send(60);
        $response=$this->claim();
        $this->assertStringContainsString('このクーポンの有効期限が過ぎています。',$response->getContent());
        $this->assertSame(0,DB::table('user_coupons')->count());
        $this->assertSame(0,DB::table('coupon_events')->count());
        DB::table('visit_scenario_sends')->update(['sent_at'=>now()->subDays(61)]);
        $this->assertStringContainsString('このクーポンの有効期限が過ぎています。',$this->claim()->getContent());
    }

    public function test_just_before_expiry_can_claim(): void
    {
        $this->send(60);
        DB::table('visit_scenario_sends')->update(['sent_at'=>now()->subDays(60)->addSecond()]);
        $this->claim();
        $this->assertSame(1,DB::table('user_coupons')->count());
    }

    public function test_already_claimed_coupon_is_not_reissued_or_rewritten_after_deadline(): void
    {
        $this->send(1); $this->claim();
        $before=(array)DB::table('user_coupons')->first();
        Carbon::setTestNow(now()->addDays(80));
        $response=$this->claim();
        $this->assertSame(1,DB::table('user_coupons')->count());
        $this->assertSame($before,(array)DB::table('user_coupons')->first());
        $this->assertInstanceOf(\Illuminate\View\View::class,$response);
    }

    public function test_old_link_cannot_claim_a_new_delivery(): void
    {
        $this->send(70,3,null); $this->send(1,3,'new-delivery');
        $this->assertStringContainsString('このクーポンの有効期限が過ぎています。',$this->claim(3,null)->getContent());
        $this->assertSame(0,DB::table('user_coupons')->count());
        $this->claim(3,'new-delivery');
        $this->assertSame(1,DB::table('user_coupons')->count());
    }

    public function test_token_selects_exact_delivery_and_unknown_token_does_not_fall_back(): void
    {
        $this->send(70,3,'older'); $this->send(1,3,'newer');
        $this->claim(3,'unknown');
        $this->assertSame(0,DB::table('user_coupons')->count());
        $this->assertStringContainsString('このクーポンの有効期限が過ぎています。',$this->claim(3,'older')->getContent());
        $this->assertSame(0,DB::table('user_coupons')->count());
    }

    public function test_gold_and_other_scenarios_keep_existing_policy(): void
    {
        $this->send(100,6); $this->claim(6);
        $this->assertNull(DB::table('user_coupons')->where('coupon_template_id',6)->value('expires_at'));
        $this->send(100,5); $this->claim(5);
        $this->assertSame(now()->addDays(120)->toDateTimeString(),DB::table('user_coupons')->where('coupon_template_id',5)->value('expires_at'));
    }
    public function test_delivery_token_is_in_message_and_only_saved_on_success(): void
    {
        foreach ([false, true] as $success) {
            DB::table('visit_scenario_sends')->delete();
            $sendId=$this->send(0,3,null);
            DB::table('visit_scenario_sends')->where('id',$sendId)->update(['sent_at'=>null]);
            $bot=\Mockery::mock(\App\Services\LineBotService::class);
            $messages=[];
            $bot->shouldReceive('multicast')->once()->andReturnUsing(function ($users,$payload) use ($success,&$messages) { $messages=$payload; return $success; });
            $command=new \App\Console\Commands\SendVisitScenarioBatch;
            $command->setOutput(new \Illuminate\Console\OutputStyle(new \Symfony\Component\Console\Input\ArrayInput([]),new \Symfony\Component\Console\Output\BufferedOutput));
            (new \ReflectionMethod($command,'processScenarioGroup'))->invoke($command,$bot,3,DB::table('visit_scenario_sends')->get());
            $serialized=json_encode($messages,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $this->assertStringContainsString('配信から60日間有効',$serialized);
            $this->assertStringContainsString('delivery=',$serialized);
            $row=DB::table('visit_scenario_sends')->first();
            if ($success) {
                $this->assertNotNull($row->sent_at);
                $this->assertStringContainsString('delivery='.$row->delivery_token,$serialized);
            } else {
                $this->assertNull($row->sent_at);
                $this->assertNull($row->delivery_token);
            }
        }
    }

    public function test_migration_changes_only_target_settings_and_not_existing_coupons(): void
    {
        $this->send(1); $this->claim();
        $before=(array)DB::table('user_coupons')->first();
        DB::table('message_bubbles')->whereIn('parent_id',[3,4])->update(['coupon_expires_days'=>40]);
        Schema::table('visit_scenario_sends',fn(Blueprint $t)=>$t->dropColumn('delivery_token'));
        $migration=require database_path('migrations/2026_11_18_000001_add_scenario_delivery_token_and_extend_initial_coupons.php');
        $migration->up();
        $this->assertTrue(Schema::hasColumn('visit_scenario_sends','delivery_token'));
        $this->assertSame([60,60],DB::table('message_bubbles')->whereIn('parent_id',[3,4])->pluck('coupon_expires_days')->all());
        $this->assertSame(120,DB::table('message_bubbles')->where('parent_id',5)->value('coupon_expires_days'));
        $this->assertNull(DB::table('message_bubbles')->where('parent_id',6)->value('coupon_expires_days'));
        $this->assertSame($before,(array)DB::table('user_coupons')->first());
    }

    public function test_shared_preview_uses_send_origin_only_for_target_scenario_records(): void
    {
        $scenario=(new \App\Models\VisitScenario)->forceFill(['id'=>3]);
        $other=(new \App\Models\Broadcast)->forceFill(['id'=>3]);
        foreach ([[$scenario,'true'],[$other,'false']] as [$record,$expected]) {
            $html=view('filament.forms.components.bubble-preview',['getRecord'=>fn()=>$record])->render();
            $this->assertStringContainsString('('.$expected.' ?',$html);
        }
    }

}
