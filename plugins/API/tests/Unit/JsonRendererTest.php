<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\API\tests\Unit;

use Piwik\DataTable;
use Piwik\Plugins\API\Renderer\Json;

/**
 * @group Plugin
 * @group API
 * @group API_JsonRendererTest
 * @group JsonRenderer
 */
class JsonRendererTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Json
     */
    private $jsonBuilder;

    public function setUp(): void
    {
        $this->jsonBuilder = $this->makeBuilder(array());
        DataTable\Manager::getInstance()->deleteAll();
    }

    public function testRenderSuccessShouldIncludeMessage()
    {
        $response = $this->jsonBuilder->renderSuccess('ok');

        $this->assertEquals('{"result":"success","message":"ok"}', $response);
        $this->assertEquals((array) array('result' => 'success', 'message' => 'ok'), json_decode($response, true));
        $this->assertNoJsonError($response);
    }

    public function testRenderSuccessShouldWrapIfEnabledAndCallbackShouldBePreferred()
    {
        $builder  = $this->makeBuilder(array('callback' => 'myName', 'jsoncallback' => 'myOther'));
        $response = $builder->renderSuccess('ok');

        $this->assertEquals('myName({"result":"success","message":"ok"})', $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderSuccessShouldWrapIfEnabledAndFallbackToJsonCallbackIfCallbackNotSet()
    {
        $builder  = $this->makeBuilder(array('jsoncallback' => 'myOther'));
        $response = $builder->renderSuccess('ok');

        $this->assertEquals('myOther({"result":"success","message":"ok"})', $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderSuccessShouldNotWrapIfCallbackContainsInvalidCharacters()
    {
        $builder  = $this->makeBuilder(array('callback' => 'myOther#?._kek'));
        $response = $builder->renderSuccess('ok');

        $this->assertEquals('{"result":"success","message":"ok"}', $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderExceptionShouldIncludeTheMessageAndNotExceptionMessage()
    {
        $response = $this->jsonBuilder->renderException("The error message", new \Exception('The other message'));

        $this->assertEquals('{"result":"error","message":"The error message"}', $response);
        $this->assertEquals((array) array('result' => 'error', 'message' => 'The error message'), json_decode($response, true));
        $this->assertNoJsonError($response);
    }

    public function testRenderExceptionShouldRemoveNewlines()
    {
        $response = $this->jsonBuilder->renderException("The\nerror\r\nmessage", new \Exception());

        $this->assertEquals('{"result":"error","message":"The error message"}', $response);
        $this->assertEquals((array) array('result' => 'error', 'message' => 'The error message'), json_decode($response, true));
        $this->assertNoJsonError($response);
    }

    public function testRenderExceptionShouldWrapIfEnabled()
    {
        $builder  = $this->makeBuilder(array('callback' => 'myName'));
        $response = $builder->renderException('error', new \Exception());

        $this->assertEquals('myName({"result":"error","message":"error"})', $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderObjectShouldReturAnError()
    {
        $response = $this->jsonBuilder->renderObject(new \stdClass());

        $this->assertEquals('{"result":"error","message":"The API cannot handle this data structure."}', $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderResourceShouldReturAnError()
    {
        $response = $this->jsonBuilder->renderResource(new \stdClass());

        $this->assertEquals('{"result":"error","message":"The API cannot handle this data structure."}', $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderScalarShouldReturnABooleanWrappedInValue()
    {
        $response = $this->jsonBuilder->renderScalar(true);

        $this->assertEquals('{"value":true}', $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderScalarShouldReturnAnIntegerWrappedInValue()
    {
        $response = $this->jsonBuilder->renderScalar(5);

        $this->assertEquals('{"value":5}', $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderScalarShouldReturnAStringWrappedInValue()
    {
        $response = $this->jsonBuilder->renderScalar('The Output');

        $this->assertEquals('{"value":"The Output"}', $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderScalarShouldNotRemoveLineBreaks()
    {
        $response = $this->jsonBuilder->renderScalar('The\nOutput');

        $this->assertEquals('{"value":"The\\\\nOutput"}', $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderScalarShouldWrapJsonIfNeeded()
    {
        $builder  = $this->makeBuilder(array('callback' => 'myName'));
        $response = $builder->renderScalar(true);

        $this->assertEquals('myName({"value":true})', $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderDataTableShouldRenderABasicDataTable()
    {
        $dataTable = new DataTable();
        $dataTable->addRowFromSimpleArray(array('nb_visits' => 5, 'nb_random' => 10));

        $response = $this->jsonBuilder->renderDataTable($dataTable);

        $this->assertEquals('[{"nb_visits":5,"nb_random":10}]', $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderDataTableShouldRenderSubtables()
    {
        $subtable = new DataTable();
        $subtable->addRowFromSimpleArray(array('nb_visits' => 2, 'nb_random' => 6));

        $dataTable = new DataTable();
        $dataTable->addRowFromSimpleArray(array('nb_visits' => 5, 'nb_random' => 10));
        $dataTable->getFirstRow()->setSubtable($subtable);

        $response = $this->jsonBuilder->renderDataTable($dataTable);

        $this->assertEquals('[{"nb_visits":5,"nb_random":10,"idsubdatatable":1}]', $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderDataTableShouldRenderDataTableMaps()
    {
        $map = new DataTable\Map();

        $dataTable = new DataTable();
        $dataTable->addRowFromSimpleArray(array('nb_visits' => 5, 'nb_random' => 10));

        $dataTable2 = new DataTable();
        $dataTable2->addRowFromSimpleArray(array('nb_visits' => 3, 'nb_random' => 6));

        $map->addTable($dataTable, 'table1');
        $map->addTable($dataTable2, 'table2');

        $response = $this->jsonBuilder->renderDataTable($map);

        $this->assertEquals('{"table1":[{"nb_visits":5,"nb_random":10}],"table2":[{"nb_visits":3,"nb_random":6}]}', $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderDataTableShouldRenderSimpleDataTable()
    {
        $dataTable = new DataTable\Simple();
        $dataTable->addRowsFromArray(array('nb_visits' => 3, 'nb_random' => 6));

        $response = $this->jsonBuilder->renderDataTable($dataTable);

        $this->assertEquals('{"nb_visits":3,"nb_random":6}', $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderDataTableShouldWrapADataTable()
    {
        $builder  = $this->makeBuilder(array('callback' => 'myName'));
        $dataTable = new DataTable();
        $dataTable->addRowFromSimpleArray(array('nb_visits' => 5, 'nb_random' => 10));

        $response = $builder->renderDataTable($dataTable);

        $this->assertEquals('myName([{"nb_visits":5,"nb_random":10}])', $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderArrayShouldConvertSimpleArrayToJson()
    {
        $input = array(1, 2, 5, 'string', 10);

        $response = $this->jsonBuilder->renderArray($input);

        $this->assertEquals('[1,2,5,"string",10]', $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderArrayShouldWrapJsonIfRequested()
    {
        $input = array(1, 2, 5, 'string', 10);

        $builder  = $this->makeBuilder(array('jsoncallback' => 'myName'));
        $response = $builder->renderArray($input);

        $this->assertEquals('myName([1,2,5,"string",10])', $response);
    }

    public function testRenderArrayWithAssociativeArrayJsonpCorrectlyFormatted()
    {
        $input = array('key' => 'value');
        $renderer  = $this->makeBuilder(array('callback' => '__myfunc', 'jsoncallback' => '__myfunc'));
        $result = $renderer->renderArray($input);

        $this->assertEquals('__myfunc({"key":"value"})', $result);
        $this->assertNoJsonError($result);
    }

    public function testRenderArrayWithMultidimensionalArrayJsonpCorrectlyFormatted()
    {
        $input = array('key' => 'value', 'deepKey' => array('deeper' => 'deepValue'));
        $renderer  = $this->makeBuilder(array('callback' => '__myfunc', 'jsoncallback' => '__myfunc'));
        $result = $renderer->renderArray($input);

        $this->assertEquals('__myfunc({"key":"value","deepKey":{"deeper":"deepValue"}})', $result);
        $this->assertNoJsonError($result);
    }

    public function testRenderArrayShouldRenderAnEmptyArray()
    {
        $response = $this->jsonBuilder->renderArray(array());

        $this->assertEquals('[]', $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderArrayShouldConvertAssociativeArrayToJson()
    {
        $input = array('nb_visits' => 6, 'nb_random' => 8);

        $response = $this->jsonBuilder->renderArray($input);
        $expected = json_encode($input);

        $this->assertEquals($expected, $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderArrayShouldConvertsIndexedAssociativeArrayToJson()
    {
        $input = array(
            array('nb_visits' => 6, 'nb_random' => 8),
            array('nb_visits' => 3, 'nb_random' => 4)
        );

        $response = $this->jsonBuilder->renderArray($input);
        $expected = json_encode($input);

        $this->assertEquals($expected, $response);
        $this->assertNoJsonError($response);
    }

    public function testRenderArrayShouldConvertMultiDimensionalStandardArrayToJson()
    {
        $input = array("firstElement",
            array(
                "firstElement",
                "secondElement",
            ),
            "thirdElement");

        $expected = json_encode($input);

        $actual = $this->jsonBuilder->renderArray($input);
        $this->assertEquals($expected, $actual);
        $this->assertNoJsonError($actual);
    }

    public function testRenderArrayShouldConvertMultiDimensionalAssociativeArrayToJson()
    {
        $input = array(
            "firstElement"  => "isFirst",
            "secondElement" => array(
                "firstElement"  => "isFirst",
                "secondElement" => "isSecond",
            ),
            "thirdElement"  => "isThird");

        $expected = json_encode($input);

        $actual = $this->jsonBuilder->renderArray($input);
        $this->assertEquals($expected, $actual);
        $this->assertNoJsonError($actual);
    }

    public function testRenderArrayShouldConvertSingleDimensionalAssociativeArrayToJson()
    {
        $input = array(
            "fistElement" => "isFirst",
            "secondElement" => "isSecond"
        );

        $expected = json_encode($input);

        $actual = $this->jsonBuilder->renderArray($input);
        $this->assertEquals($expected, $actual);
        $this->assertNoJsonError($actual);
    }

    public function testRenderArrayShouldConvertMultiDimensionalIndexArrayToJson()
    {
        $input = array(array("firstElement",
            array(
                "firstElement",
                "secondElement",
            ),
            "thirdElement"));

        $expected = json_encode($input);

        $actual = $this->jsonBuilder->renderArray($input);
        $this->assertEquals($expected, $actual);
        $this->assertNoJsonError($actual);
    }

    public function testRenderArrayShouldConvertMultiDimensionalMixedArrayToJson()
    {
        $input = array(
            "firstElement" => "isFirst",
            array(
                "firstElement",
                "secondElement",
            ),
            "thirdElement" => array(
                "firstElement"  => "isFirst",
                "secondElement" => "isSecond",
            )
        );

        $expected = json_encode($input);

        $actual = $this->jsonBuilder->renderArray($input);
        $this->assertEquals($expected, $actual);
        $this->assertNoJsonError($actual);
    }

    public function testJsonRenderArrayShouldConvertSingleDimensionalAssociativeArray()
    {
        $input = array(
            "firstElement" => "isFirst",
            "secondElement" => "isSecond"
        );

        $expected = '{"firstElement":"isFirst","secondElement":"isSecond"}';

        $oldJsonBuilder = new Json($input);
        $actual = $oldJsonBuilder->renderArray($input);
        $this->assertEquals($expected, $actual);
        $this->assertNoJsonError($actual);
    }

    public function testRenderWithNestedEmptyArrayWorks()
    {
        $input = [[]];
        $render = new Json($input);
        $result = $render->renderArray($input);
        $this->assertEquals('[[]]', $result);
    }

    private function makeBuilder($request)
    {
        return new Json($request);
    }

    private function assertNoJsonError($response)
    {
        return null !== json_decode($response);
    }
}
