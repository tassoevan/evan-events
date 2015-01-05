<?php
require __DIR__ . "/../vendor/autoload.php";

class EventHandlerMock implements Evan\Events\EventHandlerInterface
{
    use Evan\Events\EventHandler;
}
