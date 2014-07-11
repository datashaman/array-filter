<?php namespace DataShaman;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ArrayFilter
{
    private $log;

    public function __construct($log=null)
    {
        if (is_null($log)) {
            $log = new Logger('datashaman.array-filter');
            $log->pushHandler(new StreamHandler('php://stdout'), Logger::DEBUG);
        }

        $this->log = $log;
    }

    public function isAssocArray($thing) {
        if (is_array($thing)) {
            $keys = array_keys($thing);
            foreach($keys as $key) {
                if (is_string($key)) return true;
            }
        }
        return false;
    }

    public function isNumericArray($thing) {
        return !$this->isAssocArray($thing);
    }

    public function isObject($thing) {
        return is_object($thing) || is_array($thing);
    }

    public function isNotMeta($key) {
        return ($key[0] !== '$');
    }

    private function matchSpecifiedKeys($keys, $source, $filter, $options) {
        foreach ($keys as $key) {
            $sourceValue = @$source[$key];
            $filterValue = @$filter[$key];

            if ($this->isNotMeta($key) && !$this->checkFilter($sourceValue, $filterValue, $options)) {
                return false;
            }
        }
        return true;
    }

    public function checkFilter($source, $filter, $options=array()) {
        $checkConditionals = empty($options) || @$options['match'] !== 'all';

        $options = array_merge(array(
            'match' => 'filter',
        ), $options);

        if ($checkConditionals) {
            $this->log->debug('Check conditionals', get_defined_vars());
            if ($filter && is_array($filter) && array_key_exists('$present', $filter) && $filter['$present']) {
                $this->log->debug('Filter is $present', get_defined_vars());
                return !empty($source);
            } elseif ($filter && is_array($filter) && array_key_exists('$present', $filter) && $filter['$present'] === false) {
                $this->log->debug('Filter is not $present', get_defined_vars());
                return empty($source);
            } elseif (!isset($filter)) {
                $this->log->debug('Filter is undefined', get_defined_vars());
                return 'undefined';
            } elseif (is_null($filter)) {
                $this->log->debug('Filter is null', get_defined_vars());
                return true;
            } elseif (!empty($filter['$any']) && $filter['$any'] === true) {
                $this->log->debug('Filter', $filter);
                $this->log->debug('First');
                $this->log->debug('Filter is $any', get_defined_vars());
                return true;
            } elseif (!empty($filter['$query']) && !empty($options['queryHandler'])) {
                $this->log->debug('Filter is $query', get_defined_vars());
                $queryHandler = $options['queryHandler'];
                $context = array_key_exists('context', $options) ? $options['context'] : $source;
                $queryValue = $queryHandler($filter['$query'], $context);
                return $source == $queryValue;
            }
        } else {
            $this->log->debug('No check conditionals', get_defined_vars());
            if ($source === $filter) {
                $this->log->debug('Source is filter, return true', get_defined_vars());
                return true;
            } elseif (is_null($filter)) {
                $this->log->debug('Filter is null, return false', get_defined_vars());
                return false;
            }
        }

        if ($this->isObject($source)) {
            if ($this->isObject($filter)) {
                if (!empty($filter['$any']) && $filter['$any'] === true && $checkConditionals) {
                    $this->log->debug('Second');
                    $this->log->debug('Filter is $any', get_defined_vars());
                    return true;
                } elseif ($this->isNumericArray($source)) {
                    $this->log->debug('Source is an array', get_defined_vars());

                    if (array_key_exists('$contains', $filter) && $this->isNumericArray($filter['$contains']) && $checkConditionals) {
                        $this->log->debug('Filter is $contains', get_defined_vars());
                        foreach ($filter['$contains'] as $value) {
                            if (array_search($value, $source) === false) {
                                return false;
                            }
                        }
                        return true;
                    } elseif (array_key_exists('$excludes', $filter) && $this->isNumericArray($filter['$excludes']) && $checkConditionals) {
                        $this->log->debug('Filter is $excludes', get_defined_vars());
                        foreach ($filter['$excludes'] as $value) {
                            if (array_search($value, $source) !== false) {
                                return false;
                            }
                        }
                        return true;
                    } elseif ($this->isNumericArray($filter)) {
                        $this->log->debug('Filter is numeric array', get_defined_vars());
                        return $this->matchKeys($source, $filter, $options) && (count($filter) == count($source));
                    } else {
                        $this->log->debug('Filter match keys', get_defined_vars());
                        return $this->matchKeys($source, $filter, $options);
                    }
                } else {
                    $this->log->debug('Filter match keys', get_defined_vars());
                    return $this->matchKeys($source, $filter, $options);
                }
            }
        } else {
            if (is_array($filter) && array_key_exists('$only', $filter) && $this->isNumericArray($filter['$only']) && $checkConditionals) {
                $this->log->debug('Filter is $only', get_defined_vars());
                $key = array_search($source, $filter['$only']);
                return !($key === false);
            } elseif (is_array($filter) && array_key_exists('$not', $filter) && $this->isNumericArray($filter['$not']) && $checkConditionals) {
                $this->log->debug('Filter is $not', get_defined_vars());
                $key = array_search($source, $filter['$not']);
                return ($key === false);
            } else {
                $this->log->debug('Source is filter', get_defined_vars());
                return $source === $filter;
            }
        }
    }

    private function matchSpecifiedKeysWithOptional($keys, $source, $filter, $options)
    {
        $result = true;

        foreach ($keys as $key) {
            if ($this->isNotMeta($key)) {
                $sourceValue = @$source[$key];
                $filterValue = @$filter[$key];

                $res = $this->checkFilter($sourceValue, $filterValue, $options);

                if (array_key_exists('$optional', $filter) && $filter['$optional'] && array_search($key, $filter['$optional']) !== false || $res !== 'undefined') {
                    $result = $res;
                } else {
                    $result = false;
                }

                if (!$result) {
                    break;
                }
            }
        }

        return $result;
    }

    private function keyUnion($a, $b)
    {
        return array_unique(array_merge(array_keys($a), array_keys($b)));
    }

    private function matchKeys($source, $filter, $options) {
        $result = false;

        if ($options['match'] === 'all') {
            $keys = $this->keyUnion($source, $filter);
            $result = $this->matchSpecifiedKeys($keys, $source, $filter, $options);
        } elseif (!empty($filter['$matchAny'])) {
            foreach ($filter['$matchAny'] as $innerFilter) {
                $combinedFilter = $this->mergeClone($filter, $innerFilter);
                unset($combinedFilter['$matchAny']);
                if ($this->matchKeys($source, $combinedFilter, $options)) {
                    $result = true;
                    break;
                }
            }
        } else {
            if ($options['match'] === 'filter') {
                $keys = array_keys($filter);
                $result = $this->matchSpecifiedKeys($keys, $source, $filter, $options);
            } elseif ($options['match'] === 'source') {
                $keys = array_keys($source);
                $options = array_merge($options, array(
                    'match' => 'filter',
                ));
                $result = $this->matchSpecifiedKeysWithOptional($keys, $source, $filter, $options);
            } else {
                $keys = array_keys($source);
                $options = array_merge($options, array(
                    'match' => 'filter',
                ));
                $result = $this->matchSpecifiedKeys($keys, $source, $filter, $options);
            }
        }

        return $result;
    }
}
