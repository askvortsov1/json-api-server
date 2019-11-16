<?php

/*
 * This file is part of tobyz/json-api-server.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tobyz\JsonApiServer\Schema;

final class Attribute extends Field
{
    private $sortable = false;

    public function getLocation(): string
    {
        return 'attributes';
    }

    /**
     * Allow this attribute to be used for sorting the resource listing.
     */
    public function sortable()
    {
        $this->sortable = true;

        return $this;
    }

    /**
     * Disallow this attribute to be used for sorting the resource listing.
     */
    public function notSortable()
    {
        $this->sortable = false;

        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }
}
