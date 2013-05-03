<?php

namespace Mach\Silex\Piwik;

class FileGetContents implements RemoteContentInterface
{
    public function get($url, array $streamOptions = array())
    {
        if (empty($streamOptions)) {
            return file_get_contents($url);
        }

        $ctx = stream_context_create($streamOptions);

        return file_get_contents($url, 0, $ctx);
    }
}