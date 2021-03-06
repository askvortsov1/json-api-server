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

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Tobyz\JsonApiServer\Exception\ForbiddenException;
use Tobyz\JsonApiServer\JsonApi;
use Tobyz\JsonApiServer\ResourceType;
use function Tobyz\JsonApiServer\evaluate;
use function Tobyz\JsonApiServer\has_value;
use function Tobyz\JsonApiServer\run_callbacks;
use function Tobyz\JsonApiServer\set_value;

class Create implements RequestHandlerInterface
{
    use Concerns\SavesData;

    private $api;
    private $resource;

    public function __construct(JsonApi $api, ResourceType $resource)
    {
        $this->api = $api;
        $this->resource = $resource;
    }

    /**
     * Handle a request to create a resource.
     *
     * @throws ForbiddenException if the resource is not creatable.
     */
    public function handle(Request $request): Response
    {
        $schema = $this->resource->getSchema();

        if (! evaluate($schema->isCreatable(), [$request])) {
            throw new ForbiddenException;
        }

        $model = $this->createModel($request);
        $data = $this->parseData($request->getParsedBody());

        $this->validateFields($data, $model, $request);
        $this->fillDefaultValues($data, $request);
        $this->loadRelatedResources($data, $request);
        $this->assertDataValid($data, $model, $request, true);
        $this->setValues($data, $model, $request);

        run_callbacks($schema->getListeners('creating'), [$model, $request]);

        $this->save($data, $model, $request);

        run_callbacks($schema->getListeners('created'), [$model, $request]);

        return (new Show($this->api, $this->resource, $model))
            ->handle($request)
            ->withStatus(201);
    }

    private function createModel(Request $request)
    {
        $createModel = $this->resource->getSchema()->getCreateModelCallback();

        return $createModel ? $createModel($request) : $this->resource->getAdapter()->create();
    }

    private function fillDefaultValues(array &$data, Request $request)
    {
        foreach ($this->resource->getSchema()->getFields() as $field) {
            if (! has_value($data, $field) && ($defaultCallback = $field->getDefaultCallback())) {
                set_value($data, $field, $defaultCallback($request));
            }
        }
    }
}
