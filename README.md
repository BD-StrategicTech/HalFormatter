# HalFormatter

## Installation

`composer require budgetdumpster/hal-formatter:dev-master`

## Tests

Tests can be run using the command `phpunit` in the library root directory

## Usage

### Configuration

If you don't have a resource that has embedded resources, the `$embedded` parameter in the `HalFormatter::formatResource`
is optional. However, if you do want to incorporate embedded resources into your response, the configuration will help you
build out the desired structure.

The library is based on the assumption that related resources, in most ORMs, end up in a property on the model when the 
relationship is retrieved. What the configuration allows you to do is move those related resources to the embedded section
of the response based on the property.

So for example, you might have something like:
`Model::person_id` - as the identifier for the related resource
`Model::person` - the property the actual Person model is pulled into

and as a configuration you have:

```php
$embedded = [
    'person' => [
        'property' => 'person',
        'key' => 'person',
        'uri' => 'person'
    ]
];
```

What this configuration tells us is - we are going to take the data in the `Model::person` property and move 
it to the embedded resources under the `$key` key and any links for that resource will use the value of `$uri`.
The library will work with individual models as well as arrays or collections of models.

### Formatting a resource or collection

The most difficult part of this library is understanding the configuration (and since it's a matrix, you can 
tack on multiple configurations. Let's use the example configuration above and the assumption that our model
will have a property on it called `person`

```
<?php

use BudgetDumpster\Formatters\HalFormatter;
use NameSpace\Models\Address;

$address = new Address();
$collection = $address->where('city', 'like', '%Westlake%');
$embedded = [
    'person' => [
        'property' => 'person',
        'key' => 'person',
        'uri' => 'person'
    ]
];
$formatter = new HalFormatter();
$responseBody = $formatter->formatResource($collection, $uri = 'address', $embedded);
```

This will return an address collection, each entry in the address collection will have the `person` property removed and will have a `person`
collection in the embedded resources.

In addition to the small API available, HalFormatter extends `Nocarrier\Hal`, so all the native functions of `Nocarrier\Hal` are available for use 
