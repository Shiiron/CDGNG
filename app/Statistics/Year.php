<?php
namespace CDGNG\Statistics;

class Year extends Statistic
{
    protected function getData($calendar)
    {
        return $calendar->getData('year');
    }
}
