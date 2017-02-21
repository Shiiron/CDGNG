<?php
namespace CDGNG\Statistics;

class All extends Statistic
{
    protected function getData($calendar)
    {
        return $calendar->getData();
    }

    protected function getSlotName()
    {
        return('All');
    }

    public function exportAsCsv()
    {
        // Supprime la colonne Slot (inutile dans ce cas)
        $csv = parent::exportAsCsv();
        $csv->removeColumn(1);
        return $csv;
    }
}
