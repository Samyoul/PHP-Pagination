<?php
/**
 * Created by IntelliJ IDEA.
 * User: samyo
 * Date: 05/09/2017
 * Time: 17:20
 */

namespace Samyoul\Pagination;


class Link
{
    protected $html;

    public function __construct($classes, $href, $text, $pageNumber="")
    {
        $this->html = <<<HTML
<li data-pagenumber="{$pageNumber}" class="{$classes}">
    <a href="{$href}">"{$text}"</a>
</li>
HTML;
    }

    public function getHTML()
    {
        return $this->html;
    }
}