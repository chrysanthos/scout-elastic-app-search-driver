<?php

namespace Chrysanthos\ScoutElasticAppSearch\Tests;

use Chrysanthos\ScoutElasticAppSearch\ElasticAppProxy;
use Chrysanthos\ScoutElasticAppSearch\ScoutElasticAppSearchEngine;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;
use Laravel\Scout\Searchable;
use Mockery as m;
use Orchestra\Testbench\TestCase;
use stdClass;

class AppSearchEngineTests extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::shouldReceive('get')->with('scout.after_commit', m::any())->andReturn(false);
        Config::shouldReceive('get')->with('scout.soft_delete', m::any())->andReturn(false);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function test_update_adds_objects_to_index()
    {
        $client = m::mock(ElasticAppProxy::class);
        $client->shouldReceive('ensureEngine')->with('table');
        $client->shouldReceive('initIndex')->with('table')->andReturn($index = m::mock(\stdClass::class));
        $index->shouldReceive('saveObjects')->with([[
            'id' => 1,
            'objectID' => 1,
        ]]);

        $engine = new ScoutElasticAppSearchEngine($client);
        $engine->update(Collection::make([new SearchableModel]));
    }

    public function test_delete_removes_objects_to_index()
    {
        $client = m::mock(ElasticAppProxy::class);
        $client->shouldReceive('ensureEngine')->with('table');
        $client->shouldReceive('initIndex')->with('table')->andReturn($index = m::mock(stdClass::class));
        $client->shouldReceive('deleteDocuments')->andReturn([1]);

        $engine = new ScoutElasticAppSearchEngine($client);
        $engine->delete(Collection::make([new SearchableModel(['id' => 1])]));
    }

    public function test_search_sends_correct_parameters_to_appsearch()
    {
        $client = m::mock(ElasticAppProxy::class);
        $client->shouldReceive('ensureEngine')->with('table');
        $client->shouldReceive('initIndex')->with('table')->andReturn($index = m::mock(stdClass::class));
        $client->shouldReceive('search')->with('zonda', [
            'filters' => ['foo' => [1]],
        ]);

        $engine = new ScoutElasticAppSearchEngine($client);
        $builder = new Builder(new SearchableModel, 'zonda');
        $builder->where('foo', 1);
        $engine->search($builder);
    }

    public function test_search_sends_correct_parameters_to_appsearch_for_where_in_search()
    {
        $this->markTestIncomplete('Search is not implemented');

        $client = m::mock(ElasticAppProxy::class);
        $client->shouldReceive('ensureEngine')->with('table');
        $client->shouldReceive('initIndex')->with('table')->andReturn($index = m::mock(stdClass::class));
        $index->shouldReceive('search')->with('zonda', [
            'filters' => ['foo' => [1], ['bar' => [1, 2]]],
        ]);

        $engine = new ScoutElasticAppSearchEngine($client);
        $builder = new Builder(new SearchableModel, 'zonda');
        $builder->where('foo', 1)->whereIn('bar', [1, 2]);
        $engine->search($builder);
    }

    public function test_map_correctly_maps_results_to_models()
    {
        $client = m::mock(ElasticAppProxy::class);
        $engine = new ScoutElasticAppSearchEngine($client);

        $model = m::mock(stdClass::class);
        $model->shouldReceive('getScoutModelsByIds')->andReturn($models = Collection::make([
            new SearchableModel(['id' => 1]),
        ]));

        $builder = m::mock(Builder::class);

        $results = $engine->map($builder, ['nbHits' => 1, 'hits' => [
            ['objectID' => 1, 'id' => 1],
        ]], $model);

        $this->assertCount(1, $results);
    }

    public function test_map_method_respects_order()
    {
        $client = m::mock(ElasticAppProxy::class);
        $engine = new ScoutElasticAppSearchEngine($client);

        $model = m::mock(stdClass::class);
        $model->shouldReceive('getScoutModelsByIds')->andReturn($models = Collection::make([
            new SearchableModel(['id' => 1]),
            new SearchableModel(['id' => 2]),
            new SearchableModel(['id' => 3]),
            new SearchableModel(['id' => 4]),
        ]));

        $builder = m::mock(Builder::class);

        $results = $engine->map($builder, ['nbHits' => 4, 'hits' => [
            ['objectID' => 1, 'id' => 1],
            ['objectID' => 2, 'id' => 2],
            ['objectID' => 4, 'id' => 4],
            ['objectID' => 3, 'id' => 3],
        ]], $model);

        $this->assertCount(4, $results);

        // It's important we assert with array keys to ensure
        // they have been reset after sorting.
        $this->assertEquals([
            0 => ['id' => 1],
            1 => ['id' => 2],
            2 => ['id' => 4],
            3 => ['id' => 3],
        ], $results->toArray());
    }

    public function test_lazy_map_correctly_maps_results_to_models()
    {
        $this->markTestIncomplete('Lazy map is not implemented yet');

        $client = m::mock(ElasticAppProxy::class);
        $engine = new ScoutElasticAppSearchEngine($client);

        $model = m::mock(stdClass::class);
        $model->shouldReceive('queryScoutModelsByIds->cursor')->andReturn($models = LazyCollection::make([
            new SearchableModel(['id' => 1]),
        ]));

        $builder = m::mock(Builder::class);

        $results = $engine->lazyMap($builder, ['nbHits' => 1, 'hits' => [
            ['objectID' => 1, 'id' => 1],
        ]], $model);

        $this->assertCount(1, $results);
    }

    public function test_lazy_map_method_respects_order()
    {
        $this->markTestIncomplete('Lazy map is not implemented yet');

        $client = m::mock(ElasticAppProxy::class);
        $engine = new ScoutElasticAppSearchEngine($client);

        $model = m::mock(stdClass::class);
        $model->shouldReceive('queryScoutModelsByIds->cursor')->andReturn($models = LazyCollection::make([
            new SearchableModel(['id' => 1]),
            new SearchableModel(['id' => 2]),
            new SearchableModel(['id' => 3]),
            new SearchableModel(['id' => 4]),
        ]));

        $builder = m::mock(Builder::class);

        $results = $engine->lazyMap($builder, ['nbHits' => 4, 'hits' => [
            ['objectID' => 1, 'id' => 1],
            ['objectID' => 2, 'id' => 2],
            ['objectID' => 4, 'id' => 4],
            ['objectID' => 3, 'id' => 3],
        ]], $model);

        $this->assertCount(4, $results);

        // It's important we assert with array keys to ensure
        // they have been reset after sorting.
        $this->assertEquals([
            0 => ['id' => 1],
            1 => ['id' => 2],
            2 => ['id' => 4],
            3 => ['id' => 3],
        ], $results->toArray());
    }

    public function test_a_model_is_indexed_with_a_custom_key()
    {
        $client = m::mock(ElasticAppProxy::class);
        $client->shouldReceive('ensureEngine')->with('table');
        $client->shouldReceive('initIndex')->with('table')->andReturn($index = m::mock(stdClass::class));
        $index->shouldReceive('saveObjects')->with([[
            'id' => 1,
            'objectID' => 'my-key.1',
        ]]);

        $engine = new ScoutElasticAppSearchEngine($client);
        $engine->update(Collection::make([new AppSearchCustomKeySearchableModel]));
    }

    public function test_a_model_is_removed_with_a_custom_appsearch_key()
    {
        $client = m::mock(ElasticAppProxy::class);
        $client->shouldReceive('ensureEngine')->with('table');
        $client->shouldReceive('initIndex')->with('table')->andReturn($index = m::mock(stdClass::class));
        $client->shouldReceive('deleteDocuments')->with(['my-key.1']);

        $engine = new ScoutElasticAppSearchEngine($client);
        $engine->delete(Collection::make([new AppSearchCustomKeySearchableModel(['id' => 1])]));
    }

    public function test_flush_a_model_with_a_custom_appsearch_key()
    {
        $client = m::mock(ElasticAppProxy::class);
        $client->shouldReceive('initIndex')->with('table')->andReturn($index = m::mock(stdClass::class));
        $client->shouldReceive('flushEngine');
        $index->shouldReceive(['deleteEngine', 'createEngine']);

        $engine = new ScoutElasticAppSearchEngine($client);
        $engine->flush(new AppSearchCustomKeySearchableModel);
    }

    public function test_update_empty_searchable_array_does_not_add_objects_to_index()
    {
        $client = m::mock(ElasticAppProxy::class);
        $client->shouldReceive('ensureEngine')->with('table');
        $client->shouldReceive('initIndex')->with('table')->andReturn($index = m::mock(stdClass::class));
        $index->shouldNotReceive('saveObjects');

        $engine = new ScoutElasticAppSearchEngine($client);
        $engine->update(Collection::make([new EmptySearchableModel]));
    }

    public function test_update_empty_searchable_array_from_soft_deleted_model_does_not_add_objects_to_index()
    {
        $client = m::mock(ElasticAppProxy::class);
        $client->shouldReceive('ensureEngine')->with('table');
        $client->shouldReceive('initIndex')->with('table')->andReturn($index = m::mock('StdClass'));
        $index->shouldNotReceive('saveObjects');

        $engine = new ScoutElasticAppSearchEngine($client, true);
        $engine->update(Collection::make([new SoftDeletedEmptySearchableModel]));
    }
}

class SearchableModel extends Model
{
    use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id'];

    public function searchableAs()
    {
        return 'table';
    }

    public function scoutMetadata()
    {
        return [];
    }
}

class AppSearchCustomKeySearchableModel extends SearchableModel
{
    public function getScoutKey()
    {
        return 'my-key.' . $this->getKey();
    }
}

class EmptySearchableModel extends SearchableModel
{
    public function toSearchableArray()
    {
        return [];
    }
}

class SoftDeletedEmptySearchableModel extends SearchableModel
{
    public function toSearchableArray()
    {
        return [];
    }

    public function pushSoftDeleteMetadata()
    {
        //
    }

    public function scoutMetadata()
    {
        return ['__soft_deleted' => 1];
    }
}