<?php

namespace Icinga\Module\Icingadb\IcingaStateObject;

use Icinga\Exception\ProgrammingError;
use Icinga\Module\Icingadb\DdoObject;

abstract class StateObject extends DdoObject
{
    const FLAG_DOWNTIME   = 1;
    const FLAG_ACK        = 2;
    const FLAG_HOST_ISSUE = 4;
    const FLAG_NONE       = 8;
    const SHIFT_FLAGS     = 4;

    const ICINGA_OK          = 0;
    const ICINGA_WARNING     = 1;
    const ICINGA_CRITICAL    = 2;
    const ICINGA_UNKNOWN     = 3;
    const ICINGA_UP          = 0;
    const ICINGA_DOWN        = 2;
    const ICINGA_UNREACHABLE = 3; // TODO: re-think "reachable"
    const ICINGA_PENDING     = 99;

    protected static $hostStateSortMap = [
        self::ICINGA_UP          => 0,
        self::ICINGA_PENDING     => 1,

        self::ICINGA_UNKNOWN     => 8,
        self::ICINGA_DOWN        => 8,

        // Hint: exit code 3 is mapped to down, "unreachable" needs to be calculated.
        // TODO: let "reachable" flow into severity and state calculation
        self::ICINGA_UNREACHABLE => 8,

        // Hint: exit code 1 is OK for Icinga 2.
        // Fits Icinga 1.x unless aggressive_host_checking is set
        self::ICINGA_WARNING     => 0,
    ];

    // Reversing the above:
    protected static $hostSortStateMap = [
        0 => self::ICINGA_UP,
        1 => self::ICINGA_PENDING,
        8 => self::ICINGA_DOWN,

        // TODO: these do currently not exist:
        2 => self::ICINGA_UNREACHABLE,
        4 => self::ICINGA_UNKNOWN,
    ];

    protected static $serviceStateSortMap = [
        self::ICINGA_OK       => 0,
        self::ICINGA_PENDING  => 1,
        self::ICINGA_WARNING  => 2,
        self::ICINGA_UNKNOWN  => 4,
        self::ICINGA_CRITICAL => 8,
    ];

    protected static $serviceSortStateMap = [
        0 => self::ICINGA_OK,
        1 => self::ICINGA_PENDING,
        2 => self::ICINGA_WARNING,
        4 => self::ICINGA_UNKNOWN,
        8 => self::ICINGA_CRITICAL,
    ];

    protected static $hostStateNames = [
        self::ICINGA_UP          => 'up',
self::ICINGA_WARNING        => 'down', // Really?
        self::ICINGA_DOWN        => 'down',
        self::ICINGA_UNREACHABLE => 'unreachable',
        self::ICINGA_UNKNOWN     => 'unknown',
        self::ICINGA_PENDING     => 'pending',
    ];

    protected static $serviceStateNames = [
        self::ICINGA_OK       => 'ok',
        self::ICINGA_WARNING  => 'warning',
        self::ICINGA_CRITICAL => 'critical',
        self::ICINGA_UNKNOWN  => 'unknown',
        self::ICINGA_PENDING  => 'pending',
    ];

    protected static $namesToState = [
        'up'          => self::ICINGA_UP,
        'ok'          => self::ICINGA_OK,
        'down'        => self::ICINGA_DOWN,
        'unreachable' => self::ICINGA_UNREACHABLE,
        'warning'     => self::ICINGA_WARNING,
        'critical'    => self::ICINGA_CRITICAL,
        'unknown'     => self::ICINGA_UNKNOWN,
        'pending'     => self::ICINGA_PENDING,
    ];

    protected static $stateTypes = [
        'soft',
        'hard',
    ];

    protected $keyName = 'global_checksum';

    protected $defaultProperties = array(
        'global_checksum'       => null,
        'env_checksum'          => null, // TODO: remove?
        'severity'              => null,
        'state'                 => null,
        'hard_state'            => null,
        'state_type'            => null,
        'reachable'             => 'y',
        'attempt'               => null,
        'problem'               => 'n',
        'acknowledged'          => 'n',
        'in_downtime'           => 'n',
        'handled'               => 'n',
        'ack_comment_checksum'  => null,
        'last_update'           => null, // only on store if modified
        'last_state_change'     => null,
        'check_source_checksum' => null,
    );

    protected $booleans = array(
        'problem',
        'reachable',
        'acknowledged',
        'in_downtime'
    );

    protected $timestamps = array(
        'last_update',
        'last_state_change',
    );

    protected $volatile;

    public function processCheckResult($result, $timestamp)
    {
        $vars = $result->vars_after;
        $currentState = (int) $result->state;

        if ($this->state === null || $currentState !== (int) $this->state) {
            $this->last_state_change = $timestamp;
        }
        $this->state        = $currentState;
        $this->state_type   = $vars->state_type;
        $this->problem      = $currentState > 0;
        $this->reachable    = $vars->reachable;
        $this->attempt      = $vars->attempt;
        $this->check_source_checksum = sha1($result->check_source, true);

        if ($currentState === 0) {
            $this->problem      = 'n';
            $this->acknowledged = 'n';
            $this->in_downtime  = 'n'; // TODO: This is not correct
            $this->handled      = 'n';
        }

        // TODO: Handle those
        $this->severity = $this->calculateSeverity();

        $volatileKeys = array(
            'command',
            'execution_start',
            'execution_end',
            'schedule_start',
            'schedule_end',
            'exit_status',
            'output',
            'performance_data'
        );
        $this->volatile = array();
        foreach ($volatileKeys as $key) {
            $this->volatile[$key] = $result->$key;
        }

        if ($this->hasBeenModified()) {
            $this->last_update = time();
        }
    }

    abstract public function setState($state);

    public function getGlobalHexChecksum()
    {
        return bin2hex($this->get('global_checksum'));
    }

    public static function getStateForName($name)
    {
        return self::$namesToState[$name];
    }

    public static function hostSeverityStateName($severity)
    {
        $state = self::$hostSortStateMap[$severity >> self::SHIFT_FLAGS];
        return self::$hostStateNames[$state];
    }

    public static function serviceSeverityStateName($severity)
    {
        $state = self::$serviceSortStateMap[$severity >> self::SHIFT_FLAGS];
        return self::$serviceStateNames[$state];
    }

    public function processDowntimeAdded($result, $timestamp)
    {
        echo "Got downtime\n";
        print_r($result);
    }

    public function processDowntimeRemoved($result, $timestamp)
    {
        echo "Remove downtime\n";
        print_r($result);
    }

    public function processDowntimeTriggered($result, $timestamp)
    {
        echo "Triggered downtime\n";
        print_r($result);
    }

    public function processAcknowledgementSet($result, $timestamp)
    {
        $this->set('acknowledged', true);
        $this->set('severity', $this->calculateSeverity());
    }

    public function processAcknowledgementCleared($result, $timestamp)
    {
        $this->set('acknowledged', false);
        $this->set('severity', $this->calculateSeverity());
    }

    public function setState_type($type)
    {
        if (ctype_digit((string) $type)) {
            $type = self::$stateTypes[(int) $type];
        }

        return $this->reallySet('state_type', $type);
    }

    /*
    // Draft for history updates
    public function storeStateChange()
    {
        $this->db->insert('state_history', array(
            'timestamp' => $this->timestamp,
            'state'     => '',
            '' => '',
            '' => '',
        ));
    }
    */

    /*
    // Draft, showing how we could deal with sla history
    public function refreshSlaTable()
    {
        $db = $this->db;

        'UPDATE sla_table SET duration = ? - start_time, end_time = ?'
        . ' WHERE object_checksum = ? AND end_time = ?',
        $this->timestamp,
        $this->checksum,
        self::TIME_INFINITY

        $db->insert(
            'sla_table',
            array(
                'object_checksum' => $this->checksum,
                'acknowledged'    => $this->acknowledged,
                'in_downtime'     => $this->in_downtime,
            )
        );
    }
    */

    public function recalculateSeverity()
    {
        $this->set('severity', $this->calculateSeverity());
        return $this;
    }

    protected function calculateSeverity()
    {
        $sev = $this->getSortingState() << self::SHIFT_FLAGS;
        $flag = self::FLAG_NONE;

        if ((int) $this->get('state') !== 0) {
            if ($this->isInDowntime()) {
                if ($this->isAcknowledged()) {
                    $flag = self::FLAG_DOWNTIME | self::FLAG_ACK;
                } else {
                    $flag = self::FLAG_DOWNTIME;
                }
            } elseif ($this->isAcknowledged()) {
                $flag = self::FLAG_ACK;
            }
        }

        $sev |= $flag;

        return $sev;
    }

    public function isProblem()
    {
        return $this->get('problem') === 'y';
    }

    public function isInDowntime()
    {
        return $this->get('in_downtime') === 'y';
    }

    public function isAcknowledged()
    {
        return $this->get('acknowledged') === 'y';
    }

    /**
    // Hint: name has been removed
    public function getUniqueName()
    {
        $key = $this->get('host');
        if ($this->hasProperty('service')) {
            $key .= '!' . $this->get('service');
        }

        return $key;
    }
     */

    protected function getSortingState()
    {
        return $this->getSortingStateFor($this->state);
    }

    static public function getSortingStateFor($state)
    {
        throw new ProgrammingError(
            'Classes extending %s must implement getSortingStateFor()',
            __CLASS__
        );
    }
}
