<?php

namespace Icinga\Module\Icingadb\Web\Component;

use ipl\Html\BaseElement;
use ipl\Html\Element;
use Traversable;

class Table extends BaseElement
{
    protected $contentSeparator = ' ';

    /** @var string */
    protected $tag = 'table';

    /** @var Element */
    private $caption;

    /** @var Element */
    private $header;

    /** @var Element */
    private $body;

    /** @var Element */
    private $footer;

    /**
     * Set the table title
     *
     * Will be rendered as a "caption" HTML element
     *
     * @param $content
     * @return $this
     */
    public function setCaption($content)
    {
        $this->caption = Element::create('caption')->addContent(
            $content
        );

        return $this;
    }

    public function generateHeader()
    {
        return Element::create('thead')->add(
            $this->addHeaderColumnsTo(Element::create('tr'))
        );
    }

    public function generateFooter()
    {
        return Element::create('tfoot')->add(
            $this->addHeaderColumnsTo(Element::create('tr'))
        );
    }

    protected function addHeaderColumnsTo(Element $parent)
    {
        foreach ($this->getColumnsToBeRendered() as $column) {
            $parent->add(
                Element::create('th')->setContent($column)
            );
        }

        return $parent;
    }

    public function renderRow($row)
    {
        $tr = $this->addRowClasses(Element::create('tr'), $row);
        foreach ($this->getColumnsToBeRendered() as $column) {
            $td = Element::create('td');
            if (property_exists($row, $column)) {
                $td->setContent($row->$column);
            }
            $tr->add($td);
        }

        return $tr;
    }

    public function addRowClasses(Element $tr, $row)
    {
        $classes = $this->getRowClasses($row);
        if (! empty($classes)) {
            $tr->attributes()->add('class', $classes);
        }

        return $tr;
    }

    /**
     * @return null|string|array
     */
    public function getRowClasses($row)
    {
        return null;
    }

    public function body()
    {
        if ($this->body === null) {
            $this->body = Element::create('tbody')->setSeparator("\n");
        }

        return $this->body;
    }

    public function renderRows(Traversable $rows)
    {
        $body = $this->body();
        foreach ($rows as $row) {
            $body->add($this->renderRow($row));
        }

        return $body;
    }

    public function header()
    {
        if ($this->header === null) {
            $this->header = $this->generateHeader();
        }

        return $this->header;
    }

    public function footer()
    {
        if ($this->footer === null) {
            $this->footer = $this->generateFooter();
        }

        return $this->footer;
    }

    public function renderContent()
    {
        if (null !== $this->caption) {
            $this->add($this->caption);
        }

        if (null !== $this->header) {
            $this->add($this->header);
        }

        if (null !== $this->body) {
            $this->add($this->body());
        }

        if (null !== $this->footer) {
            $this->add($this->footer);
        }

        return parent::renderContent();
    }
}
