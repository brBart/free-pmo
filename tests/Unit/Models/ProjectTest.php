<?php

namespace Tests\Unit\Models;

use App\Entities\Partners\Customer;
use App\Entities\Payments\Payment;
use App\Entities\Projects\Feature;
use App\Entities\Projects\Project;
use App\Entities\Projects\Task;
use App\Entities\Subscriptions\Subscription;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    /** @test */
    public function a_project_has_many_features()
    {
        $project = factory(Project::class)->create();
        $feature = factory(Feature::class)->create(['project_id' => $project->id]);
        $this->assertInstanceOf(Collection::class, $project->features);
        $this->assertInstanceOf(Feature::class, $project->features->first());
    }

    /** @test */
    public function a_project_has_many_main_features()
    {
        $project = factory(Project::class)->create();
        $feature = factory(Feature::class)->create(['project_id' => $project->id, 'type_id' => 1]);
        $this->assertInstanceOf(Collection::class, $project->mainFeatures);
        $this->assertInstanceOf(Feature::class, $project->mainFeatures->first());
    }

    /** @test */
    public function a_project_has_many_additional_features()
    {
        $project = factory(Project::class)->create();
        $feature = factory(Feature::class)->create(['project_id' => $project->id, 'type_id' => 2]);
        $this->assertInstanceOf(Collection::class, $project->additionalFeatures);
        $this->assertInstanceOf(Feature::class, $project->additionalFeatures->first());
    }

    /** @test */
    public function a_project_has_feature_tasks()
    {
        $project = factory(Project::class)->create();
        $feature = factory(Feature::class)->create(['project_id' => $project->id, 'type_id' => 2]);
        $tasks   = factory(Task::class, 2)->create(['feature_id' => $feature->id]);
        $this->assertInstanceOf(Collection::class, $project->tasks);
        $this->assertInstanceOf(Task::class, $project->tasks->first());
    }

    /** @test */
    public function a_project_has_many_payments()
    {
        $project = factory(Project::class)->create();
        $payment = factory(Payment::class)->create(['project_id' => $project->id]);
        $this->assertInstanceOf(Collection::class, $project->payments);
        $this->assertInstanceOf(Payment::class, $project->payments->first());
    }

    /** @test */
    public function a_project_has_many_subscriptions()
    {
        $project      = factory(Project::class)->create();
        $subscription = factory(Subscription::class)->create(['project_id' => $project->id]);
        $this->assertInstanceOf(Collection::class, $project->subscriptions);
        $this->assertInstanceOf(Subscription::class, $project->subscriptions->first());
    }

    /** @test */
    public function a_project_belongs_to_a_customer()
    {
        $customer = factory(Customer::class)->create();
        $project  = factory(Project::class)->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $project->customer);
        $this->assertEquals($project->customer_id, $customer->id);
    }

    /** @test */
    public function a_project_has_cash_in_total_method()
    {
        $project  = factory(Project::class)->create();
        $payments = factory(Payment::class, 2)->create(['project_id' => $project->id, 'in_out' => 1, 'amount' => 20000]);
        $this->assertEquals(40000, $project->cashInTotal());
    }

    /** @test */
    public function a_project_has_cash_out_total_method()
    {
        $project  = factory(Project::class)->create();
        $payments = factory(Payment::class, 2)->create(['project_id' => $project->id, 'in_out' => 0, 'amount' => 10000]);
        factory(Payment::class)->create(['project_id' => $project->id, 'in_out' => 1, 'amount' => 10000]);
        $this->assertEquals(20000, $project->cashOutTotal());
    }

    /** @test */
    public function a_project_has_feature_overall_progress_method()
    {
        $project = factory(Project::class)->create();

        $feature = factory(Feature::class)->create(['project_id' => $project->id, 'type_id' => 1, 'price' => 2000]);
        factory(Task::class)->create(['feature_id' => $feature->id, 'progress' => 20]);

        $feature = factory(Feature::class)->create(['project_id' => $project->id, 'type_id' => 1, 'price' => 3000]);
        factory(Task::class)->create(['feature_id' => $feature->id, 'progress' => 30]);

        $feature = factory(Feature::class)->create(['project_id' => $project->id, 'type_id' => 1, 'price' => 1500]);
        factory(Task::class)->create(['feature_id' => $feature->id, 'progress' => 100]);

        $feature = factory(Feature::class)->create(['project_id' => $project->id, 'type_id' => 1, 'price' => 1500]);
        factory(Task::class)->create(['feature_id' => $feature->id, 'progress' => 100]);

        $this->assertEquals(53.75, $project->getFeatureOveralProgress());
    }

    /** @test */
    public function a_project_returns_0_on_feature_overall_progress_method_if_all_feature_is_free()
    {
        $project = factory(Project::class)->create();

        factory(Feature::class)->create(['project_id' => $project->id, 'type_id' => 1, 'price' => 0]);
        factory(Feature::class)->create(['project_id' => $project->id, 'type_id' => 1, 'price' => 0]);
        factory(Feature::class)->create(['project_id' => $project->id, 'type_id' => 1, 'price' => 0]);
        factory(Feature::class)->create(['project_id' => $project->id, 'type_id' => 1, 'price' => 0]);

        $this->assertEquals(0, $project->getFeatureOveralProgress());
    }

    /** @test */
    public function a_project_has_many_files()
    {
        $project = factory(Project::class)->create();
        $this->assertInstanceOf(Collection::class, $project->files);
    }

    /** @test */
    public function a_project_has_name_link_method()
    {
        $project = factory(Project::class)->make();
        $this->assertEquals(link_to_route('projects.show', $project->name, [$project->id]), $project->nameLink());
    }

    /** @test */
    public function a_project_has_collectible_earnings_method()
    {
        // Collectible earnings is total of (price * avg task progress of each feature)
        $project = factory(Project::class)->create();

        $collectibeEarnings = 0;

        $feature = factory(Feature::class)->create(['project_id' => $project->id, 'type_id' => 1, 'price' => 2000]);
        factory(Task::class)->create(['feature_id' => $feature->id, 'progress' => 20]);
        $collectibeEarnings += (2000 * (20 / 100)); // feature price * avg task progress

        $feature = factory(Feature::class)->create(['project_id' => $project->id, 'type_id' => 1, 'price' => 3000]);
        factory(Task::class)->create(['feature_id' => $feature->id, 'progress' => 30]);
        $collectibeEarnings += (3000 * (30 / 100));

        $feature = factory(Feature::class)->create(['project_id' => $project->id, 'type_id' => 1, 'price' => 1500]);
        factory(Task::class)->create(['feature_id' => $feature->id, 'progress' => 100]);
        $collectibeEarnings += (1500 * (100 / 100));

        $feature = factory(Feature::class)->create(['project_id' => $project->id, 'type_id' => 1, 'price' => 1500]);
        factory(Task::class)->create(['feature_id' => $feature->id, 'progress' => 100]);
        $collectibeEarnings += (1500 * (100 / 100));

        // $collectibeEarnings = 400 + 900 + 1500 + 1500;

        $this->assertEquals($collectibeEarnings, $project->getCollectibeEarnings());
    }
}
