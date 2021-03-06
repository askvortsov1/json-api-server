<?php

/*
 * This file is part of tobyz/json-api-server.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tobyz\JsonApiServer\Handler;

use JsonApiPhp\JsonApi\CompoundDocument;
use JsonApiPhp\JsonApi\Included;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Tobyz\JsonApiServer\JsonApi;
use Tobyz\JsonApiServer\JsonApiResponse;
use Tobyz\JsonApiServer\ResourceType;
use Tobyz\JsonApiServer\Serializer;
use function Tobyz\JsonApiServer\run_callbacks;

class Show implements RequestHandlerInterface
{
    use Concerns\IncludesData;

    private $api;
    private $resource;
    private $model;

    public function __construct(JsonApi $api, ResourceType $resource, $model)
    {
        $this->api = $api;
        $this->resource = $resource;
        $this->model = $model;
    }

    /**
     * Handle a request to show a resource.
     */
    public function handle(Request $request): Response
    {
        $include = $this->getInclude($request);

        $this->loadRelationships([$this->model], $include, $request);

        run_callbacks($this->resource->getSchema()->getListeners('show'), [$this->model, $request]);

        $serializer = new Serializer($this->api, $request);
        $serializer->add($this->resource, $this->model, $include, true);

        return new JsonApiResponse(
            new CompoundDocument(
                $serializer->primary()[0],
                new Included(...$serializer->included())
            )
        );
    }
}
