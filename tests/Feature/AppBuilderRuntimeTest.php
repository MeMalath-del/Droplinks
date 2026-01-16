<?php

namespace Tests\Feature;

use App\Models\AppDefinition;
use App\Services\ActionRunner;
use App\Services\DataSourceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppBuilderRuntimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_source_resolves_entity_records(): void
    {
        $app = AppDefinition::create([
            'name' => 'HR',
            'slug' => 'hr',
            'status' => 'draft',
        ]);

        $version = $app->versions()->create([
            'version' => 'v1',
            'status' => 'draft',
        ]);

        $entity = $version->entities()->create([
            'name' => 'Employees',
            'slug' => 'employees',
            'table_name' => 'entity_employees',
        ]);

        $entity->fields()->create([
            'name' => 'Name',
            'slug' => 'name',
            'field_type' => 'string',
        ]);

        $entity->records()->create([
            'data' => ['name' => 'Ada'],
        ]);

        $source = $version->dataSources()->create([
            'name' => 'Employees source',
            'source_type' => 'entity',
            'config' => [
                'entity_slug' => 'employees',
                'limit' => 10,
            ],
        ]);

        $results = app(DataSourceResolver::class)->resolve($source);

        $this->assertCount(1, $results);
        $this->assertSame('Ada', $results->first()['name']);
    }

    public function test_action_runner_creates_record(): void
    {
        $app = AppDefinition::create([
            'name' => 'CRM',
            'slug' => 'crm',
            'status' => 'draft',
        ]);

        $version = $app->versions()->create([
            'version' => 'v1',
            'status' => 'draft',
        ]);

        $entity = $version->entities()->create([
            'name' => 'Leads',
            'slug' => 'leads',
            'table_name' => 'entity_leads',
        ]);

        $entity->fields()->create([
            'name' => 'Company',
            'slug' => 'company',
            'field_type' => 'string',
        ]);

        $action = $version->actions()->create([
            'name' => 'Create lead',
            'action_type' => 'create_record',
            'config' => [
                'entity_slug' => 'leads',
            ],
        ]);

        $result = app(ActionRunner::class)->run($action, ['company' => 'Acme']);

        $this->assertSame('ok', $result['status']);
        $this->assertDatabaseHas('app_records', [
            'app_entity_id' => $entity->id,
        ]);
    }
}
