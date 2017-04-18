<?php

namespace Icinga\Module\Icingadb\Web\Component;

use ipl\Html\Renderable;
use Icinga\Module\Director\Web\Form\QuickForm;

class Form extends QuickForm implements Renderable
{
/*
    public function init()
    {
        $this->setAttrib('class', 'inline');
    }

    public function setup()
    {
        $this->addHtml(
            $this->getView()->icon(
                $deployIcon,
                $label,
                array('class' => 'link-color')
            ) . '<nobr>'
        );

        $el = $this->createElement('submit', 'btn_deploy', array(
            'label' => $label,
            'escape' => false,
            'decorators' => array('ViewHelper'),
            'class' => 'link-button',
            ));

        $this->addHtml('</nobr>');

    }
*/
}
