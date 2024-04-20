<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TestRunner;

class Controller extends \Piwik\Plugin\Controller
{
    public function check()
    {
        return 'OK';
    }
}
