<?php
/**
 * @author Matt Frost<mattf@budgetdumpster.com>
 * @package BudgetDumpster
 * @subpackage Tests
 * @copyright BudgetDumpster LLC, 2017
 */
namespace BudgetDumpster\Tests\Formatters;
use PHPUnit\Framework\TestCase;
use BudgetDumpster\Formatters\HalFormatter;
use Illuminate\Database\Eloquent\Model;

class HalFormatterTest extends TestCase
{
    /**
     * @var TextMessaging\Formatter\HalFormatter
     */
    private $formatter;

    /**
     * @var Illuminate\Database\Eloquent\Collection
     */
    private $collection;

    /**
     * Test Setup method
     */
    protected function setUp()
    {
        $this->formatter = new HalFormatter();
        $this->collection = $this->getMockBuilder('\Illuminate\Database\Eloquent\Collection')
            ->setMethods(['count'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Test tear down method
     */
    protected function tearDown()
    {
        unset($this->formatter);
        unset($this->collection);
    }

    /**
     * Test to ensure a single resource with no relationships
     * is formatted correctly
     *
     * @group formatters
     */
    public function testSingleResourceWithNoRelationshipsFormatsCorrectly()
    {
        $model = new \Stdclass;
        $model->id = '1234567890abcde';
        $model->name = 'test 1,2 3';
        $model->date = '2017-05-03 12:23:54';

        $uri = '/test/' . $model->id;

        $result = $this->formatter->formatResource($model, $uri);

        $result_array = json_decode($result, true);

        $this->assertEquals($result_array['id'], $model->id);
        $this->assertEquals($result_array['name'], $model->name);
        $this->assertEquals($result_array['date'], $model->date);
        $this->assertEquals($result_array['_links']['self']['href'], $uri);
    } 

    /**
     * Test to ensure a single resource with relationships is formatted correctly
     * @group formatters
     */
    public function testSingleResourceWithRelationshipsFormatsCorrectly()
    {
        $model = new \Stdclass;
        $model->id = '1234567890abcde';
        $model->name = 'Test 2';
        $model->date = '2017-05-03 12:03:23';
        $model->foo_id = 'abcde0941238765';

        $foo = new \Stdclass;
        $foo->id = 'abcde0941238765';
        $foo->name = 'Foo 1';
        $foo_collection = [$foo];
        $model->foo = $foo_collection;

        $embedded = ['foo' => [
            'property' => 'foo',
            'key' => 'foo',
            'uri' => 'foo'
        ]];

        $response = $this->formatter->formatResource($model, 'test', $embedded);
        $response_array = json_decode($response, true);

        $this->assertEquals($model->id, $response_array['id']);
        $this->assertEquals($model->name, $response_array['name']);
        $this->assertEquals($model->date, $response_array['date']);
        $this->assertEquals($model->foo_id, $response_array['foo_id']);
        $this->assertArrayHasKey('_embedded', $response_array);
        $this->assertEquals($response_array['_embedded']['foo'][0]['id'], $foo->id);
        $this->assertEquals($response_array['_embedded']['foo'][0]['_links']['self']['href'], '/foo/'. $foo->id);
    }

    /**
     * Test to ensure the link property can be set
     */
    public function testLinkPropertyCanBeSet()
    {
        $property = 'links';
        $this->formatter->setLinkProperty($property);
        $this->assertEquals($this->formatter->getLinkProperty(), $property);
    }

    /**
     * Test to ensure the set links method will generate links
     */
    public function testSetLinksMethodGeneratesLinks()
    {
        $model = new \stdClass;
        $model->id = 1;
        $model->name = 'test';
        $model->doodad_id = '123456';
        $model->linkedModels = ['collection' => ['widgets'], 'individual' => ['doodad' => 'doodad_id']];
        $data = $this->formatter->formatResource($model, '/test/123');
        $array = json_decode($data, true);
        $this->assertContains('/test/123/widgets', $data);
        $this->assertContains('/doodad/123456', $data);
    }

    /**
     * Test to ensure an empty collection inserts an empty item
     */
    public function testEmptyCollectionInsertsItem()
    {
        $this->collection->expects($this->once())
            ->method('count')
            ->will($this->returnValue(0));

        $embedded = [
            'test' => [
                'property' => 'test',
                'key' => 'test',
                'uri' => 'test'
            ]
        ]; 

        $model = new \stdClass;
        $model->id = 1;
        $model->name = 'test';
        $model->test = $this->collection;

        $resource = $this->formatter->formatResource($model, '/widget/1234', $embedded);
        $array = json_decode($resource, true);
        $this->assertEmpty($array['_embedded']['test']);
    }
}
