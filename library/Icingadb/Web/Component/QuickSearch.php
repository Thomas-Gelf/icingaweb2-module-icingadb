<?php

namespace Icinga\Module\Icingadb\Web\Component;

use Icinga\Web\Request;
use ipl\Html\BaseElement;
use ipl\Html\Html;
use ipl\Translation\TranslationHelper;

class QuickSearch extends BaseElement
{
    use TranslationHelper;

    protected $tag = 'form';

    protected $defaultAttributes = [
        'method' => 'GET',
        'class'  => 'quicksearch'
    ];

    protected $request;

    protected $keyName;

    protected $searchString;

    public function __construct(Request $request, $keyName = 'search')
    {
        $url = $request->getUrl();
        $this->request = $request;
        $this->keyName = $keyName;
        $this->searchString = $url->shift($keyName);
        $this->attributes()->set('action', $url);
    }

    public function hasValue()
    {
        return strlen($this->searchString) > 0;
    }

    public function getValue()
    {
        return $this->searchString;
    }

    public function renderContent()
    {
        $this->add(
            Html::tag('input', [
                'type'        => 'text',
                'placeholder' => $this->translate('Search'),
                'name'        => 'search',
                'class'       => 'search-link-like',
                'value'       => $this->searchString,
            ])
        );

        return parent::renderContent();
    }
}
