<?php
namespace CDGNG\Statistics;

use CDGNG\Csv;

class Realised extends Day
{
    private $year;

    private $dayName = array(
        'Mon' => 'L', 'Tue' => 'M', 'Wed' => 'M', 'Thu' => 'J',
        'Fri' => 'V', 'Sat' => 'S', 'Sun' => 'D',
    );

    public function __construct($date)
    {
        $this->year = (int)explode('-', $date)[2];
        $month = (int)explode('-', $date)[1];
        if ($month <= 8) {
            $this->year -= 1;
        }
        $this->dtstart = mktime(0, 0, 0, 9, 1, $this->year);
        $this->dtend = mktime(23, 59,59, 8, 31, $this->year + 1);
    }

    public function exportAsCsv()
    {
        $csv = new Csv();
        $csv->insert(
            array(
                'Septembre', '', '',
                'Octobre', '', '',
                'Novembre', '', '',
                'Décembre', '', '',
                'Janvier', '', '',
                'Février', '', '',
                'Mars', '', '',
                'Avril', '', '',
                'Mai', '', '',
                'Juin', '', '',
                'Juillet', '', '',
                'Aout', '', '',
            )
        );

        $months = array(9, 10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8);

        $data = current($this->calendars)['data'];

        for ($day=1; $day <= 31; $day++) {
            $row = array();
            foreach ($months as $month) {
                $timestamp = $this->getTimestamp($day, $month);

                $date = date("Y/m/d", $timestamp);
                $add = array();
                // Vérifie si le jour existe dans le mois.
                if (date("m", $timestamp) != $month) {
                    $row = array_merge($row, array('', '', ''));
                    continue;
                }

                if (isset($data[$date]['duration'])) {
                    $add = array(
                        $day,
                        $this->getDayName($timestamp),
                        number_format($data[$date]['duration']/3600, 2, ',', ' ')
                    );
                    $row = array_merge($row, $add);
                    continue;
                }
                // Vérifie si des heures ont été contabilisée pour ce jour
                $add = array($day, $this->getDayName($timestamp), '');
                $row = array_merge($row, $add);
            }
            $csv->insert($row);
        }
        return $csv;
    }

    private function getTimestamp($day, $month)
    {
        if ($month <= 8) {
            return mktime(0, 0, 0, $month, $day, $this->year + 1);
        }
        return mktime(0, 0, 0, $month, $day, $this->year);
    }

    private function getDayName($timestamp)
    {
        return $this->dayName[date("D", $timestamp)];
    }
}
