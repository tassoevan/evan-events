<?php
/**
* Evan Events
*
* Support for event-driven programming in PHP classes.
*
* @author  Tasso Evangelista <tasso@tassoevan.me>
* @license MIT http://opensource.org/licenses/MIT
* @php     5.4
*/

namespace Evan\Events;

/**
* Interface to identify event handlers.
*
* @package evan/events
*/
interface EventHandlerInterface
{
    /**
     * Registers an event callback to event IDs.
     *
     * @param  string|string[] $eventIds Event IDs.
     * @param  callable $callback Event callback.
     */
    public function on($eventIds, $callback);

    /**
     * Registers an event callback to event IDs that fires once.
     *
     * @param  string|string[] $eventIds Event IDs.
     * @param  callable $callback Event callback.
     */
    public function once($eventIds, $callback);

    /**
     * Unregisters event callbacks based in their event IDs.
     *
     * @param string|string[] $eventIds Event IDs.
     */
    public function off($eventIds);

    /**
     * Fires event callbacks based in their event IDs.
     *
     * @param string|string[] $eventIds Event IDs.
     * @param mixed|null $data Optional data passed to the event callbacks.
     */
    public function trigger($eventIds, $data = null);
}
