<?php
namespace CDGNG\Views;

class Main extends TwigView
{
    protected function getTemplateFilename()
    {
        return 'main.twig';
    }

    protected function getData()
    {
        return array(
            'actions' => $GLOBALS['actions'],
            'modes' => $GLOBALS['modalites'],
            'calendars' => $this->model->getCalList(),
        );
    }
}
