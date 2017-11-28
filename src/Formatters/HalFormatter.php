<?php
/**
 * @author Matt Frost<mattf@budgetdumpster.com>
 * @package BudgetDumpster
 * @subpackage Formatters
 * @subpackage HAL
 * @copyright Budget Dumpster, LLC 2017
 */
namespace BudgetDumpster\Formatters;

use Nocarrier\Hal;

class HalFormatter extends Hal
{
    /**
     * @var array
     */
    protected $data;

    /**
     * Method to return a HAL formatted version of the data
     * that was passed into this method
     *
     * @param mixed $model
     * @param string $uri
     * @param array embedded
     * @return string
     */
    public function formatResource($model, $uri = '', array $embedded = [])
    {
        $this->data = json_decode(json_encode($model), true);
        $this->prepareEmbeddedResources($embedded, $model);
        $this->setData($this->data);
        $this->setUri($uri);
        return $this->asJson(true);
    }

    /**
     * Attempts to use an embedded resource configuration file to 
     * access the model properties that contain the embedded data
     *
     * @param array $embedded - embedded config file
     *   - property - required, identifies property embedded resoureces are stored at
     *   - key - required, the key that will group those resources in the response
     *   - uri - required, the URI that is needed to build _self links for the embedded resources
     * @param mixed $model
     */
    private function prepareEmbeddedResources(array $embedded, $model)
    {
        foreach ($embedded as $key => $value) {
            $model_property = $value['property'];
            if (!is_null($model->$model_property)) {
                $this->addEmbeddedResource($model->$model_property, $key, $value['uri']);
            }

            if (isset($this->data[$value['property']])) {
                $this->removeProperty($value['property']);
            }
        }
    }

    /**
     * Recursively look at every resource being passed in, if it's
     * a single model, it will be added, if it's a collection or
     * an array, it will try to loop through all those as well
     *
     * @param mixed $resource
     * @param string $key
     * @param string $uri
     */
    private function addEmbeddedResource($resource, $key, $uri)
    {
        if ($resource instanceof \Illuminate\Database\Eloquent\Collection || is_array($resource)) {
            $this->addEmbeddedResources($resource, $key, $uri);
            return;
        }

        $resource_data = json_decode(json_encode($resource), true);
        $resource_id = isset($resource_data['id']) ? $resource_data['id'] : '';
        $embeddedResource = new Hal('/' . $uri .'/' . $resource_id, $resource_data);
        $this->addResource($key, $embeddedResource);
    }

    /**
     * Loop through all the embedded resources, utilizing the
     * addEmbeddedResource method to batch add them
     *
     * @param mixed $resources - a Eloquent collection or array of data
     * @param string $key - the key that will be used to identify the collection
     * @param string $uri - the uri needed to build the link properly
     */
    private function addEmbeddedResources($resources, $key, $uri)
    {
        foreach ($resources as $resource) {
            $this->addEmbeddedResource($resource, $key, $uri);
        }
    }

    /**
     * Method to attempt to remove the property from the main response
     * object, which will allow the data to be represented as an 
     * embedded resource and prevent the same resource from being
     * presented twice
     *
     * @param string $property
     */
    private function removeProperty($property)
    {
        if (isset($this->data[$property])) {
            unset($this->data[$property]);
        }
    }
}
