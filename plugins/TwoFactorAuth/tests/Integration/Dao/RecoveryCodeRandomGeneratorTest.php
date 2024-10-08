<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TwoFactorAuth\tests\Integration\Dao;

use Piwik\Plugins\TwoFactorAuth\Dao\RecoveryCodeRandomGenerator;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group TwoFactorAuth
 * @group RecoveryCodeRandomGeneratorTest
 * @group Plugins
 */
class RecoveryCodeRandomGeneratorTest extends IntegrationTestCase
{
    /**
     * @var RecoveryCodeRandomGenerator
     */
    private $generator;

    public function setUp(): void
    {
        parent::setUp();

        $this->generator = new RecoveryCodeRandomGenerator();
    }

    public function testGeneratorCodeLength()
    {
        $this->assertSame(16, mb_strlen($this->generator->generateCode()));
    }

    public function testGeneratorCodeAlwaysDifferent()
    {
        $this->assertNotEquals($this->generator->generateCode(), $this->generator->generateCode());
    }
}
