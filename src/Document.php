<?php

/*
 * This file is part of JSON-API.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tobscure\JsonApi;

use JsonSerializable;

/**
 * This is the document class.
 *
 * @author Toby Zerner <toby.zerner@gmail.com>
 */
class Document implements JsonSerializable
{
    /**
     * The links array.
     *
     * @var array
     */
    protected $links;

    /**
     * The included array.
     *
     * @var array
     */
    protected $included = array();

    /**
     * The meta data array.
     *
     * @var array
     */
    protected $meta;

    /**
     * The errors array.
     *
     * @var array
     */
    protected $errors;

    /**
     * The data object.
     *
     * @var \Tobscure\JsonApi\Elements\ElementInterface
     */
    protected $data;

    /**
     * Add included.
     *
     * @param $link
     *
     * @return $this
     */
    public function addIncluded($link)
    {
        $resources = $link->getData()->getResources();

        foreach ($resources as $k => $resource) {
            // If the resource doesn't have any attributes, then we don't need to
            // put it into the included part of the document.
            if (! $resource->getAttributes()) {
                unset($resources[$k]);
            } else {
                foreach ($resource->getIncluded() as $link) {
                    $this->addIncluded($link);
                }
            }
        }

        foreach ($resources as $k => $resource) {
            foreach ($this->included as $includedResource) {
                if ($includedResource->getType() === $resource->getType() && $includedResource->getId() === $resource->getId()) {
                    $includedResource->merge($resource);
                    unset($resources[$k]);
                    break;
                }
            }
        }

        if ($resources) {
            $this->included = array_merge($this->included, $resources);
        }

        return $this;
    }

    /**
     * Set the data object.
     *
     * @param $element
     *
     * @return $this
     */
    public function setData($element)
    {
        $this->data = $element;

        if ($element) {
            foreach ($element->getResources() as $resource) {
                foreach ($resource->getIncluded() as $link) {
                    $this->addIncluded($link);
                }
            }
        }

        return $this;
    }

    /**
     * Add a link.
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function addLink($key, $value)
    {
        $this->links[$key] = $value;

        return $this;
    }

    /**
     * Add meta data.
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function addMeta($key, $value)
    {
        $this->meta[$key] = $value;

        return $this;
    }

    /**
     * Set the meta data array.
     *
     * @param array $meta
     *
     * @return $this
     */
    public function setMeta($meta)
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Set the errors array.
     *
     * @param array $errors
     *
     * @return $this
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * Map everything to arrays.
     *
     * @return array
     */
    public function toArray()
    {
        $document = array();

        if (! empty($this->links)) {
            ksort($this->links);
            $document['links'] = $this->links;
        }

        if (! empty($this->data)) {
            $document['data'] = $this->data->toArray();
        }

        if (! empty($this->included)) {
            $document['included'] = array();
            foreach ($this->included as $resource) {
                $document['included'][] = $resource->toArray();
            }
        }

        if (! empty($this->meta)) {
            $document['meta'] = $this->meta;
        }

        if (! empty($this->errors)) {
            $document['errors'] = $this->errors;
        }

        return $document;
    }

    /**
     * Map to string.
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toArray());
    }

    /**
     * Serialize for JSON usage.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
