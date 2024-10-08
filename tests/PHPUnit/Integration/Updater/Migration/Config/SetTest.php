<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Integration\Updater\Migration\Config;

use Piwik\Config;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Updater\Migration\Config\Set;

/**
 * @group Core
 * @group Updater
 * @group Migration
 */
class SetTest extends IntegrationTestCase
{
    public function testToString()
    {
        $config = $this->configSet('General', 'foo', 'bar');

        $this->assertSame('./console config:set --section="General" --key="foo" --value="bar"', '' . $config);
    }

    public function testExecKnownSectionKnownKey()
    {
        $this->configSet('General', 'time_before_today_archive_considered_outdated', 876)->exec();

        $general = Config::getInstance()->General;
        $this->assertEquals('876', $general['time_before_today_archive_considered_outdated']);
    }

    public function testExecKnownSectionUnknownKey()
    {
        $this->configSet('General', 'foobar', '192')->exec();
        $general = Config::getInstance()->General;
        $this->assertEquals('192', $general['foobar']);
    }

    public function testExecUnknownCategory()
    {
        $this->configSet('foobar', 'baz', 'hello')->exec();

        $baz = Config::getInstance()->foobar;
        $this->assertEquals('hello', $baz['baz']);
    }

    private function configSet($section, $key, $value)
    {
        return new Set($section, $key, $value);
    }
}
