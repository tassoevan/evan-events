<?php
use PHPUnit_Framework_TestCase as TestCase;

class EventHandlerTest extends TestCase
{
    private $handler;

    public function setUp()
    {
        $this->handler = new EventHandlerMock();
    }

    public function testOnValidEventIds()
    {
        $this->handler->on("show", function () {});
        $this->handler->on("show.namespace", function () {});
        $this->handler->on("show.namespaceB.namespaceA", function () {});
        $this->handler->on("show_1-2-3", function () {});
    }

    public function testOffValidEventIdsPatterns()
    {
        $this->handler->off("show");
        $this->handler->off("show.namespace");
        $this->handler->off("*.namespace");
        $this->handler->off("*");
    }

    public function testOnInvalidEventIdType()
    {
        $this->setExpectedException("InvalidArgumentException");
        $this->handler->on(false, function () {});
    }

    public function testOnInvalidEventIdFormat()
    {
        $this->setExpectedException("InvalidArgumentException");
        $this->handler->on("show.*", function () {});
    }

    public function testOnInvalidCallback()
    {
        $this->setExpectedException("InvalidArgumentException");
        $this->handler->on("show", null);
    }

    public function testOnceInvalidEventIdType()
    {
        $this->setExpectedException("InvalidArgumentException");
        $this->handler->once(false, function () {});
    }

    public function testOnceInvalidEventIdFormat()
    {
        $this->setExpectedException("InvalidArgumentException");
        $this->handler->once("show.*", function () {});
    }

    public function testOnceInvalidCallback()
    {
        $this->setExpectedException("InvalidArgumentException");
        $this->handler->once("show", null);
    }

    public function testOffInvalidEventIdPatternFormat()
    {
        $this->setExpectedException("InvalidArgumentException");
        $this->handler->off("show.*", function () {});
    }

    public function testSingleEvent()
    {
        $this->handler->on("show", function () {
            echo "show";
        });

        $this->expectOutputString("show");

        $this->handler->trigger("show");
    }

    public function testMultipleEvents()
    {
        for ($i = 0; $i < 100; ++$i) {
            $this->handler->on("show", function () {
                echo "show";
            });
        }

        $this->expectOutputString(str_repeat("show", 100));

        $this->handler->trigger("show");
    }

    public function testOff()
    {
        $this->handler->on("show", function () {
            echo "show";
        });

        $this->handler->off("show");

        $this->expectOutputString("");

        $this->handler->trigger("show");
    }

    public function testTrigger()
    {
        $this->handler->on("show", function () {
            echo "show";
        });

        $this->expectOutputString(str_repeat("show", 100));

        for ($i = 0; $i < 100; ++$i) {
            $this->handler->trigger("show");
        }
    }

    public function testOnce()
    {
        $this->handler->once("show", function () {
            echo "show";
        });

        $this->expectOutputString("show");

        for ($i = 0; $i < 100; ++$i) {
            $this->handler->trigger("show");
        }
    }
}
