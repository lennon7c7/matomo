<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Integration\Concurrency\LockBackend;

use Piwik\Concurrency\LockBackend\MySqlLockBackend;
use Piwik\Db;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

class MysqlLockBackendTest extends IntegrationTestCase
{
    /**
     * @var MySqlLockBackend
     */
    private $backend;
    private $key = 'testKeyValueKey';

    public function setUp(): void
    {
        if (!$this->hasDependencies()) {
            parent::setUp();
        }

        // ensure tracker DB will be used
        Db::destroyDatabaseObject();
        $GLOBALS['PIWIK_TRACKER_MODE'] = true;

        $this->backend = $this->createMysqlBackend();
    }

    public function tearDown(): void
    {
        $GLOBALS['PIWIK_TRACKER_MODE'] = false;
        Db::destroyDatabaseObject();
        parent::tearDown();
    }

    protected function createMysqlBackend()
    {
        return new MySqlLockBackend();
    }

    public function testDeleteIfKeyHasValueShouldNotWorkIfKeyDoesNotExist()
    {
        $success = $this->backend->deleteIfKeyHasValue('inVaLidKeyTest', '1');
        $this->assertFalse($success);
    }

    public function testDeleteIfKeyHasValueShouldWorkShouldBeAbleToDeleteARegularKey()
    {
        $success = $this->backend->setIfNotExists($this->key, 'test', 60);
        $this->assertTrue($success);

        $success = $this->backend->deleteIfKeyHasValue($this->key, 'test');
        $this->assertTrue($success);
    }

    public function testDeleteIfKeyHasValueShouldNotWorkIfValueIsDifferent()
    {
        $this->backend->setIfNotExists($this->key, 'test', 60);

        $success = $this->backend->deleteIfKeyHasValue($this->key, 'test2');
        $this->assertFalse($success);
    }

    public function testSetIfNotExistsShouldWorkIfNoValueIsSetYet()
    {
        $success = $this->backend->setIfNotExists($this->key, 'value', 60);
        $this->assertTrue($success);
    }

    /**
     * @depends testSetIfNotExistsShouldWorkIfNoValueIsSetYet
     */
    public function testSetIfNotExistsShouldNotWorkIfValueIsAlreadySet()
    {
        $success = $this->backend->setIfNotExists($this->key, 'value', 60);
        $this->assertFalse($success);
    }

    /**
     * @depends testSetIfNotExistsShouldNotWorkIfValueIsAlreadySet
     */
    public function testSetIfNotExistsShouldAlsoNotWorkIfTryingToSetDifferentValue()
    {
        $success = $this->backend->setIfNotExists($this->key, 'another val', 60);
        $this->assertFalse($success);
    }

    public function testGetShouldReturnFalseIfKeyNotSet()
    {
        $value = $this->backend->get($this->key);
        $this->assertFalse($value);
    }

    public function testGetShouldReturnTheSetValueIfOneIsSet()
    {
        $this->backend->setIfNotExists($this->key, 'mytest', 60);
        $value = $this->backend->get($this->key);
        $this->assertEquals('mytest', $value);
    }

    public function testKeyExistsShouldReturnFalseIfKeyNotSet()
    {
        $value = $this->backend->keyExists($this->key);
        $this->assertFalse($value);
    }

    public function testGetShouldReturnTrueIfValueIsSet()
    {
        $this->backend->setIfNotExists($this->key, 'mytest', 60);
        $this->assertTrue($this->backend->keyExists($this->key));
    }

    public function testExpireShouldWork()
    {
        $success = $this->backend->setIfNotExists($this->key, 'test', 60);
        $this->assertTrue($success);

        $success = $this->backend->expireIfKeyHasValue($this->key, 'test', $seconds = 1);
        $this->assertTrue($success);

        // should not work as value still saved and not expired yet
        $success = $this->backend->setIfNotExists($this->key, 'test', 60);
        $this->assertFalse($success);

        sleep($seconds + 1);

        // value is expired and should work now!
        $success = $this->backend->setIfNotExists($this->key, 'test', 60);
        $this->assertTrue($success);
    }

    public function testExpireShouldNotWorkIfValueIsDifferent()
    {
        $success = $this->backend->setIfNotExists($this->key, 'test', 60);
        $this->assertTrue($success);

        $success = $this->backend->expireIfKeyHasValue($this->key, 'test2', $seconds = 1);
        $this->assertFalse($success);
    }

    public function testExpireShouldStilReturnTrueEvenWhenSettingSameTimeout()
    {
        $success = $this->backend->setIfNotExists($this->key, 'test', 60);
        $this->assertTrue($success);

        $success = $this->backend->expireIfKeyHasValue($this->key, 'test', 60);
        $this->assertTrue($success);

        $success = $this->backend->expireIfKeyHasValue($this->key, 'test', 60);
        $this->assertTrue($success);
    }

    public function testGetKeysMatchingPatternShouldReturnMatchingKeys()
    {
        $backend = $this->createMysqlBackend();
        $backend->setIfNotExists('abcde', 'val0', 100);
        $backend->setIfNotExists('test1', 'val1', 100);
        $backend->setIfNotExists('Test3', 'val2', 100);
        $backend->setIfNotExists('Test1', 'val3', 100);
        $backend->setIfNotExists('Test2', 'val4', 100);

        $keys = $backend->getKeysMatchingPattern('Test*');
        sort($keys);
        $this->assertEquals(array('Test2', 'Test3', 'test1'), $keys);

        $keys = $backend->getKeysMatchingPattern('test1*');
        sort($keys);
        $this->assertEquals(array('test1'), $keys);

        $keys = $backend->getKeysMatchingPattern('*est*');
        sort($keys);
        $this->assertEquals(array('Test2', 'Test3', 'test1'), $keys);
    }

    public function testGetKeysMatchingPatternShouldReturnAnEmptyArrayIfNothingMatches()
    {
        $backend = $this->createMysqlBackend();
        $keys    = $backend->getKeysMatchingPattern('*fere*');
        $this->assertEquals(array(), $keys);
    }
}
