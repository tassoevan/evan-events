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

use InvalidArgumentException;

/**
 * Trait for objects that handle events
 *
 * @package evan/events
 */
trait EventHandler
{
    /**
     * @var array Callbacks of events.
     */
    private $callbacks = [];

    /**
     * Parses event IDs.
     *
     * Transforms and validates event IDs. If $patterns is true, the event IDs
     * are parsed like event ID patterns.
     *
     * @param  string|string[] $eventIds Event IDs or event ID patterns.
     * @param  boolean $patterns Defines if the event IDs must be parsed as
     *         event ID patterns.
     * @return string[] Event IDs or event ID regular expressions.
     * @throws InvalidArgumentException if event IDs are not of type string or
     *         array.
     */
    private function parseEventIds($eventIds, $patterns)
    {
        if (is_string($eventIds)) {
            $eventIds = explode(" ", $eventIds);
        } elseif (!is_array($eventIds)) {
            throw new InvalidArgumentException("invalid argument type");
        }

        foreach ($eventIds as $i => $eventId) {
            if ($patterns) {
                $eventIds[$i] = $this->parseEventIdPattern($eventId);
            } else {
                $eventIds[$i] = $this->parseEventId($eventId);
            }
        }

        return $eventIds;
    }

    /**
     * Validates an event ID.
     *
     * An event ID must be a sequence of identifiers splited by dots (".").
     *
     * @param  string $eventId Event ID.
     * @return string The same event ID.
     * @throws InvalidArgumentException if the event ID format is invalid.
     */
    private function parseEventId($eventId)
    {
        static $regexp = "/^[A-Za-z_][A-Za-z0-9_]*(\\.[A-Za-z_][A-Za-z0-9_]*)*$/";

        if (!preg_match($regexp, $eventId)) {
            throw new InvalidArgumentException("illegal event ID: {$eventId}");
        }

        return $eventId;
    }

    /**
     * Parses an event ID pattern
     *
     * An event ID pattern must be a sequence of identifiers splited by dots
     * (".") optionally started by a star ("*"), representing any identifier.
     *
     * @param  string $eventIdPattern Event ID pattern.
     * @return string A regular expression to match event IDs.
     * @throws InvalidArgumentException if the event ID pattern format is
     *         invalid.
     */
    private function parseEventIdPattern($eventIdPattern)
    {
        static $regexp = "/^([A-Za-z_][A-Za-z0-9_]*|\\*)(\\.[A-Za-z_][A-Za-z0-9_]*)*$/";

        if (!preg_match($regexp, $eventIdPattern)) {
            throw new InvalidArgumentException("illegal event ID pattern: {$eventIdPattern}");
        }

        $parts = array_map(
            function ($part) {
                if ($part === "*") {
                    return "([A-Za-z_][A-Za-z0-9_]*(\\.[A-Za-z_][A-Za-z0-9_]*)*?)";
                } else {
                    return preg_quote($part);
                }
            },
            explode(".", $eventIdPattern)
        );

        return "/^" . implode("\\.", $parts) . "$/";
    }

    /**
     * Attaches an callback to the event IDs.
     *
     * Optionally, it's possible to define a callable to be invoked after the
     * callback when event is triggered.
     *
     * @param  string[] $eventIds Event IDs.
     * @param  callable $callback Event callback.
     * @param  callable|null $afterTrigger Callback invoked after the event
     *         callback. It must accept the triggered event ID and the event
     *         index in event callbacks list.
     */
    private function attachEventHandler(array $eventIds, $callback, $afterTrigger = null)
    {
        foreach ($eventIds as $eventId) {
            if (!isset($this->callbacks[$eventId])) {
                $this->callbacks[$eventId] = [];
            }

            $idx = count($this->callbacks[$eventId]);



            if ($afterTrigger) {
                $callback = function ($data = null) use ($callback, $afterTrigger, $eventId, $idx) {
                    $return = call_user_func($callback, $data);
                    call_user_func($afterTrigger, $eventId, $idx);

                    return $return;
                };
            }

            $this->callbacks[$eventId][$idx] = $callback;
        }
    }

    /**
     * Matches event ID patterns against the registered events.
     *
     * @param  string|string[] $eventIdPatterns Event ID patterns.
     * @return string[] Matched event IDs.
     */
    private function matchEventIds($eventIdPatterns)
    {
        $eventIdPatterns = $this->parseEventIds($eventIdPatterns, true);

        $eventIds = [];

        foreach ($eventIdPatterns as $eventIdPattern) {
            foreach ($this->callbacks as $eventId => $callback) {
                if (preg_match($eventIdPattern, $eventId)) {
                    $eventIds[] = $eventId;
                }
            }
        }

        return $eventIds;
    }

    /**
     * Registers an event callback to event IDs.
     *
     * @param  string|string[] $eventIds Event IDs.
     * @param  callable $callback Event callback.
     * @throws InvalidArgumentException if $callback is not callable.
     */
    public function on($eventIds, $callback)
    {
        $eventIds = $this->parseEventIds($eventIds, false);
        if (!is_callable($callback)) {
            throw new InvalidArgumentException("invalid argument type");
        }

        $this->attachEventHandler($eventIds, $callback);
    }

    /**
     * Registers an event callback to event IDs that fires once.
     *
     * When some of there event ID is triggered, the callback is invoked and
     * delete itself in event callbacks list.
     *
     * @param  string|string[] $eventIds Event IDs.
     * @param  callable $callback Event callback.
     * @throws InvalidArgumentException if $callback is not callable.
     */
    public function once($eventIds, $callback)
    {
        $eventIds = $this->parseEventIds($eventIds, false);
        if (!is_callable($callback)) {
            throw new InvalidArgumentException("invalid argument type");
        }

        $this->attachEventHandler($eventIds, $callback, function ($eventId, $idx) {
            unset($this->callbacks[$eventId][$idx]);
        });
    }

    /**
     * Unregisters event callbacks based in their event IDs.
     *
     * @param string|string[] $eventIdPatterns Event ID patterns.
     */
    public function off($eventIdPatterns)
    {
        $eventIds = $this->matchEventIds($eventIdPatterns);

        foreach ($eventIds as $eventId) {
            unset($this->callbacks[$eventId]);
        }
    }

    /**
     * Fires event callbacks based in their event IDs.
     *
     * @param string|string[] $eventIdPatterns Event ID patterns.
     * @param mixed|null $data Optional data passed to the event callbacks.
     */
    public function trigger($eventIdPatterns, $data = null)
    {
        $eventIds = $this->matchEventIds($eventIdPatterns);

        foreach ($eventIds as $eventId) {
            foreach ($this->callbacks[$eventId] as $callback) {
                call_user_func($callback, $data);
            }
        }
    }
}
