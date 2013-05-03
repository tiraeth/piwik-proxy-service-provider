<?php

namespace Mach\Silex\Piwik;

interface RemoteContentInterface
{
    public function get($url, array $streamOptions = array());
}