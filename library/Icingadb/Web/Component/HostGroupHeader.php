<?php

namespace Icinga\Module\Icingadb\Web\Component;

use Icinga\Date\DateFormatter;
use ipl\Html\BaseElement;
use ipl\Html\Container;
use ipl\Html\Element;
use ipl\Html\HtmlTag;
use Icinga\Module\Icingadb\IcingaConfigObject\IcingaHostGroupConfig;

class HostGroupHeader extends BaseElement
{
    protected $contentSeparator = "\n";

    /** @var string */
    protected $tag = 'div';

    /** @inheritdoc */
    protected $defaultAttributes = array(
        // 'class' => 'object-header',
    );

    public function __construct(IcingaHostGroupConfig $object)
    {
        $this->add(
            $this->renderHostGroupHeaderDetails($object)
        );
    }

    protected function renderHostGroupHeaderDetails(IcingaHostGroupConfig $host)
    {
        $name = $host->get('name');
        $label = $host->get('label');
        if ($label !== null && $label !== $name) {
            $h1 = HtmlTag::h1([
                $label,
                Element::create('small')->addContent('(' . $name . ')')
            ]);
        } else {
            $h1 = HtmlTag::h1($name);
        }

        return $h1->setSeparator(' ');
    }
}
