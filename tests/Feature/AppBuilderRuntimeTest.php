<?php

namespace Tests\Feature;

use App\Models\AppDefinition;
use App\Models\AppEntityPermission;
use App\Services\ActionRunner;
use App\Services\DataSourceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_join_data_source_merges_entities(): void
    {
        $app = AppDefinition::create([
            'name' => 'Analytics',
            'slug' => 'analytics',
            'status' => 'draft',
        ]);

        $version = $app->versions()->create([
            'version' => 'v1',
            'status' => 'draft',
        ]);

        $left = $version->entities()->create([
            'name' => 'Orders',
            'slug' => 'orders',
            'table_name' => 'entity_orders',
        ]);

        $right = $version->entities()->create([
            'name' => 'Customers',
            'slug' => 'customers',
            'table_name' => 'entity_customers',
        ]);

        $left->records()->create([
            'data' => ['order_id' => 1, 'customer_id' => 10],
        ]);

        $right->records()->create([
            'data' => ['id' => 10, 'name' => 'Globex'],
        ]);

        $source = $version->dataSources()->create([
            'name' => 'Orders+Customers',
            'source_type' => 'join',
            'config' => [
                'left_entity_slug' => 'orders',
                'right_entity_slug' => 'customers',
                'left_key' => 'customer_id',
                'right_key' => 'id',
            ],
        ]);

        $results = app(DataSourceResolver::class)->resolve($source);

        $this->assertSame('Globex', $results->first()['right_name']);
    }

    public function test_permissions_block_read_access(): void
    {
        $app = AppDefinition::create([
            'name' => 'Secure',
            'slug' => 'secure',
            'status' => 'draft',
        ]);

        $version = $app->versions()->create([
            'version' => 'v1',
            'status' => 'draft',
        ]);

        $entity = $version->entities()->create([
            'name' => 'Secrets',
            'slug' => 'secrets',
            'table_name' => 'entity_secrets',
        ]);

        $entity->records()->create([
            'data' => ['name' => 'Hidden'],
        ]);

        $role = $app->roles()->create([
            'name' => 'Viewer',
            'slug' => 'viewer',
        ]);

        AppEntityPermission::create([
            'app_entity_id' => $entity->id,
            'app_role_id' => $role->id,
            'can_read' => false,
            'can_write' => false,
            'can_delete' => false,
            'access_scope' => 'all',
        ]);

        $source = $version->dataSources()->create([
            'name' => 'Secrets source',
            'source_type' => 'entity',
            'config' => [
                'entity_slug' => 'secrets',
                'limit' => 10,
            ],
        ]);

        $results = app(DataSourceResolver::class)->resolve($source, ['role' => 'viewer']);

        $this->assertCount(0, $results);
    }

    public function test_upload_file_action_saves_file(): void
    {
        Storage::fake('local');

        $app = AppDefinition::create([
            'name' => 'Files',
            'slug' => 'files',
            'status' => 'draft',
        ]);

        $version = $app->versions()->create([
            'version' => 'v1',
            'status' => 'draft',
        ]);

        $action = $version->actions()->create([
            'name' => 'Upload',
            'action_type' => 'upload_file',
            'config' => [
                'folder' => 'uploads/files',
            ],
        ]);

        $file = UploadedFile::fake()->create('doc.txt', 10);

        $result = app(ActionRunner::class)->run($action, [], ['file' => $file]);

        $this->assertSame('ok', $result['status']);
        Storage::disk('local')->assertExists($result['file']->path);
    }
}
