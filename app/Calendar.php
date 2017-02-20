<?php
namespace CDGNG;

/**
 * Class Calendar
 *
 * @author Florestan Bredow <florestan.bredow@daiko.fr>
 *
 * @version GIT: $Id$
 *
 */
class Calendar
{
    /**
     * Path to calendar file
     * @var string $path
     */
    private $path;
    /**
     * Total duration for all valid events
     * @var int $totalLength
     */
    private $totalLength;
    /**
     * Time per day per action/code [day][action][modalite][length]
     * @var array $dailyTime
     */
    private $dailyTime;
    /**
     * List of bad events with error type [level][summary][description]
     * @var array $errors
     */
    private $errors;


    public function __construct($path)
    {
        $this->path = $path;
        $this->totalLength = 0;
        $this->dailyTime = array();
        $this->errors = array();
    }

    /**
     * Parse calendar and check events
     *
     * @param timestamp $tsStart time slot start
     * @param timestamp $tsEnd time slot end
     */

    public function parse($tsStart, $tsEnd)
    {
        $calendar = new Parser\Calendar($this->path);
        $calendar->parse();

        $tabEvents = array();
        foreach ($calendar->events as $eventDesc) {

            $event = new Event($eventDesc);

            // Event is in time slot and not a full day event
            if (($event->getStart() >= $tsStart)
                and ($event->getEnd() <= $tsEnd)
                and !$event->isFullDay()) {

                if ($event->isValid($tabEvents, $error)) {
                    if ($event->isSelected()) {
                        array_push($tabEvents, $event);
                        $this->addEvent($event);
                    }
                    continue;
                }
                // 0 -> level ; 1 -> description
                $this->addToError($error[0], $event, $error[1]);
            }
        }
    }

    /**
     * Add event to valid event array
     *
     * @param Event $event Event to add
     */

    private function addEvent($event)
    {
        $code = $event->getCode();
        $result = $event->cutByDay();

        foreach ($result as $day => $time) {
            if (isset($this->dailyTime[$day][$code["act"]][$code["mod"]])) {
                $this->dailyTime[$day][$code["act"]][$code["mod"]] += $time;
                continue;
            }
            $this->dailyTime[$day][$code["act"]][$code["mod"]] = $time;
        }
        $this->totalLength += $event->getLength();
    }

    /**
     * Add error to error array
     *
     * @param int    $level         Error level (0-2)
     * @param Event  $event         Unvalid event
     * @param string $description   Error description
     *
     */
    private function addToError($level, $event, $description)
    {
        $this->errors[] = array(
            "LEVEL"       => $level,
            "SUMMARY"     => $event->getSummary(),
            "DESCRIPTION" => $description,
            "DTSTART"     => date("d-m-Y H:i", $event->getStart()),
            "DTEND"       => date("H:i", $event->getEnd())
        );
    }

    /**
     * Return calendar name from calendar file (X-WR-CALNAME field)
     *
     * @return string
     */
    public function getName()
    {
        return basename($this->path, '.ics');
    }

    public function getData($slot = "total")
    {
        switch ($slot) {
            case 'day':
                return $this->getDataBy("Y/m/d");
                break;

            case 'week':
                return $this->getDataBy("Y/W");
                break;

            case 'month':
                return $this->getDataBy("Y/m");
                break;

            case 'year':
                return $this->getDataBy("Y");
                break;

            default:
                return $this->getDataBy("All");
                break;
        }
    }

    /**
     * return data grouped by slot time
     *
     * @param string $format Use date(format)
     * cf. http://php.net/manual/en/function.date.php
     *
     * @return array
     */
    private function getDataBy($format)
    {
        $output = array();
        $output['duration'] = 0;

        foreach ($this->dailyTime as $date => $dayCodes) {

            $slot = "All";
            if ($format !== "All") {
                $slot = date($format, $date);
            }

            if (!isset($output[$slot]['duration'])) {
                $output[$slot]['duration'] = 0;
            }

            foreach ($dayCodes as $action => $subCode) {

                foreach ($subCode as $modalite => $duration) {
                    if (!isset($output[$slot]['actions'][$action][$modalite])) {
                        $output[$slot]['actions'][$action][$modalite] = 0;
                        $output[$slot]['modalites'][$modalite][$action] = 0;
                    }
                    $output[$slot]['actions'][$action][$modalite] += $duration;
                    $output[$slot]['modalites'][$modalite][$action] += $duration;

                    if (!isset($output[$slot]['actions'][$action]['duration'])) {
                        $output[$slot]['actions'][$action]['duration'] = 0;
                    }
                    $output[$slot]['actions'][$action]['duration'] += $duration;

                    if (!isset($output[$slot]['modalites'][$modalite]['duration'])) {
                        $output[$slot]['modalites'][$modalite]['duration'] = 0;
                    }
                    $output[$slot]['modalites'][$modalite]['duration']  += $duration;

                    $output[$slot]['duration'] += $duration;
                    $output['duration'] += $duration;
                }
            }
        }

        ksort($output);
        return $output;
    }

    /**
     * Return calendar's path
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Return all valid events's duration
     */
    public function getTotalLength()
    {
        return $this->totalLength;
    }

    /**
     * Return error detected when parsing calendar
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
