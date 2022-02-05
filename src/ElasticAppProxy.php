<?php

namespace Chrysanthos\ScoutElasticAppSearch;

use Elastic\EnterpriseSearch\AppSearch\Request\CreateEngine;
use Elastic\EnterpriseSearch\AppSearch\Request\DeleteDocuments;
use Elastic\EnterpriseSearch\AppSearch\Request\GetEngine;
use Elastic\EnterpriseSearch\AppSearch\Request\IndexDocuments;
use Elastic\EnterpriseSearch\AppSearch\Request\Search;
use Elastic\EnterpriseSearch\AppSearch\Schema\Engine;
use Elastic\EnterpriseSearch\AppSearch\Schema\SearchRequestParams;
use Elastic\EnterpriseSearch\Client;

class ElasticAppProxy
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $engine;

    public function __construct(Client $client)
    {
        $this->client = $client->appSearch();
    }

    public function setEngine($name): self
    {
        $this->engine = str_replace('_', '-', $name);

        return $this;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getEngine()
    {
        $this->client->getEngine(new GetEngine($this->engine));
    }

    public function indexDocuments($objects)
    {
        $this->client->indexDocuments(new IndexDocuments($this->engine, $objects));
    }

    public function deleteDocuments($objects)
    {
        $this->client->deleteDocuments(new DeleteDocuments($this->engine, $objects));
    }

    /**
     * Ensure the Engine exists by checking for it and if not there creating it.
     *
     * @param $name
     */
    public function ensureEngine($name)
    {
        $this->setEngine($name);

        try {
            $this->getEngine();
        } catch (\Elastic\EnterpriseSearch\Exception\ClientErrorResponseException $e) {
            $this->client->createEngine(new CreateEngine(new Engine($name)));
        }
    }

    /**
     * Ensure the Engine exists by checking for it and if not there creating it.
     *
     * @param $name
     */
    public function search($name)
    {
        $this->setEngine($name);

        try {
            $this->getEngine();
        } catch (\Elastic\EnterpriseSearch\Exception\ClientErrorResponseException $e) {
            $this->client->search(new Search($name, new SearchRequestParams()));
        }
    }

    /*
     * Flush the engine
     */
    public function flushEngine($name = null)
    {
        if ($name) {
            $this->setEngine($name);
        }

        $this->deleteEngine();
        $this->createEngine();
    }

    /**
     * Dynamically call the Elastic client instance. Add the engine name to methods that require it.
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     * @throws EngineNotInitialisedException
     */
    public function __call($method, $parameters)
    {
        if (!method_exists($this->client, $method)) {
            throw new \BadMethodCallException($method . ' method not found on ' . get_class($this->client));
        }

        if ($method !== 'listEngines' && !$this->engine) {
            throw new EngineNotInitialisedException('Unable to proxy call to Elastic App Client, no Engine initialised');
        }

        if ($method !== 'listEngines' && $this->engine) {
            array_unshift($parameters, $this->engine);
        }

        return $this->client->$method(...$parameters);
    }
}
