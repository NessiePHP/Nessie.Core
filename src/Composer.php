<?php
namespace Nessie\Core;

use Composer\Script\Event;

class Composer
{
    public static function updatePublicFolder(Event $event)
    {
        //print_r($event->getComposer()->getConfig()-> getConfigSource()->getName());
    }
}
