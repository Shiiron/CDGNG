<?php
namespace CDGNG\Statistics;

class Day extends Statistic
{
    protected function getData($calendar)
    {
        return $calendar->getData('day');
    }
}
