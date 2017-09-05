<?php

namespace Samyoul\Pagination;

    /**
     * Pagination
     * 
     * Supplies an API for setting pagination details, and renders the resulting
     * pagination markup (html) through the included render.inc.php file.
     * 
     * @note    The SEO methods (canonical/rel) were written following Google's
     *          suggested patterns. Namely, the canoical url excludes any 
     *          peripheral parameters that don't relate to the pagination
     *          series. Whereas the prev/next rel link tags include any params
     *          found in the request.
     * @author  Oliver Nassar <onassar@gmail.com>
     * @todo    add setter parameter type and range checks w/ exceptions
     * @example
     * <code>
     *     // source inclusion
     *     require_once APP . '/vendors/PHP-Pagination/Pagination.class.php';
     *     
     *     // determine page (based on <_GET>)
     *     $page = isset($_GET['page']) ? ((int) $_GET['page']) : 1;
     *     
     *     // instantiate with page and records as constructor parameters
     *     $pagination = (new Pagination($page, 200));
     *     $markup = $pagination->parse();
     * </code>
     * @example
     * <code>
     *     // source inclusion
     *     require_once APP . '/vendors/PHP-Pagination/Pagination.class.php';
     *     
     *     // determine page (based on <_GET>)
     *     $page = isset($_GET['page']) ? ((int) $_GET['page']) : 1;
     *     
     *     // instantiate; set current page; set number of records
     *     $pagination = (new Pagination());
     *     $pagination->setCurrent($page);
     *     $pagination->setTotal(200);
     *     
     *     // grab rendered/parsed pagination markup
     *     $markup = $pagination->parse();
     * </code>
     */
    class Pagination
    {
        /**
         * _variables
         * 
         * Sets default variables for the rendering of the pagination markup.
         * 
         * @var    array
         * @access protected
         */
        protected $currentPage = 1;
        protected $totalItems = null;
        protected $rpp;
        protected $itemsPerPage = 10;
        protected $pageCount=null;
        protected $crumbs = 5;
        protected $leadingCount;
        protected $trailingCount;
        protected $maxCrumbs;

        protected $classes = ['clearfix', 'pagination'];
        protected $key = 'page';
        protected $target = '';
        protected $next = 'Next &raquo;';
        protected $previous = '&laquo; Previous';
        protected $last = 'Last &raquo;&raquo;';
        protected $first = '&laquo;&laquo; First';

        protected $alwaysShowPagination = false;
        protected $clean = false;

        /**
         * __construct
         * 
         * @access public
         * @param  integer $currentPage (default: 1)
         * @param  integer $totalItems (default: null)
         * @param  integer $itemsPerPage (default: 10)
         */
        public function __construct($currentPage = 1, $totalItems = null, $itemsPerPage = 10)
        {
            // current instantiation setting
            $this->setCurrent($currentPage);

            // total instantiation setting
            if (!is_null($totalItems)) {
                $this->setTotal($totalItems);
            }

            // total instantiation setting
            $this->setItemsPerPage($itemsPerPage);
        }

        /**
         * _check
         * 
         * Checks the current (page) and total (records) parameters to ensure
         * they've been set. Throws an exception otherwise.
         * 
         * @access protected
         * @return void
         * @throws PaginationException
         */
        protected function _check()
        {
            if (!isset($this->currentPage)) {
                throw new PaginationException('Pagination::currentPage must be set.');
            } elseif (!isset($this->totalItems) OR $this->totalItems === null) {
                throw new PaginationException('Pagination::totalItems must be set.');
            }
        }

        /**
         * addClasses
         * 
         * Sets the classes to be added to the pagination div node.
         * Useful with Twitter Bootstrap (eg. pagination-centered, etc.)
         * 
         * @see    <http://twitter.github.com/bootstrap/components.html#pagination>
         * @access public
         * @param  array $classes
         * @return self
         */
        public function addClasses(array $classes)
        {
            $this->classes = array_merge($this->classes, $classes);
            return $this;
        }

        /**
         * alwaysShowPagination
         * 
         * Tells the rendering engine to show the pagination links even if there
         * aren't any pages to paginate through.
         * 
         * @access public
         * @return self
         */
        public function alwaysShowPagination()
        {
            $this->alwaysShowPagination = true;
            return $this;
        }

        /**
         * getCanonicalUrl
         * 
         * @access public
         * @return string
         */
        public function getCanonicalUrl()
        {
            $target = $this->target;
            if (empty($target)) {
                $target = $_SERVER['PHP_SELF'];
            }
            $page = (int) $this->currentPage;
            if ($page !== 1) {
                return 'http://' . ($_SERVER['HTTP_HOST']) . ($target) . $this->getPageParam();
            }
            return 'http://' . ($_SERVER['HTTP_HOST']) . ($target);
        }

        /**
         * getPageParam
         * 
         * @access public
         * @param  boolean|integer $page (default: false)
         * @return string
         */
        public function getPageParam($page = false)
        {
            if ($page === false) {
                $page = (int) $this->currentPage;
            }
            $key = $this->key;
            return '?' . ($key) . '=' . ((int) $page);
        }

        /**
         * getPageUrl
         * 
         * @access public
         * @param  boolean|integer $page (default: false)
         * @return string
         */
        public function getPageUrl($page = false)
        {
            $target = $this->target;
            if (empty($target)) {
                $target = $_SERVER['PHP_SELF'];
            }
            return 'http://' . ($_SERVER['HTTP_HOST']) . ($target) . ($this->getPageParam($page));
        }

        /**
         * getRelPrevNextLinkTags
         * 
         * @see    http://support.google.com/webmasters/bin/answer.py?hl=en&answer=1663744
         * @see    http://googlewebmastercentral.blogspot.ca/2011/09/pagination-with-relnext-and-relprev.html
         * @see    http://support.google.com/webmasters/bin/answer.py?hl=en&answer=139394
         * @access public
         * @return array
         */
        public function getRelPrevNextLinkTags()
        {
            // Pages
            $currentPage = (int) $this->currentPage;
            $numberOfPages = (
                (int) ceil( $this->totalItems / $this->rpp )
            );

            // On first page
            if ($currentPage === 1) {

                // There is a page after this one
                if ($numberOfPages > 1) {
                    $href = $this->renderURL(2);
                    return ['<link rel="next" href="' . ($href) . '" />'];
                }
                return [];
            }

            // Store em
            $prevNextTags = [
                '<link rel="prev" href="' . ($this->renderURL($currentPage - 1)) . '" />'
            ];

            // There is a page after this one
            if ($numberOfPages > $currentPage) {
                array_push(
                    $prevNextTags,
                    '<link rel="next" href="' . ($this->renderURL($currentPage + 1)) . '" />'
                );
            }
            return $prevNextTags;
        }

        /**
         * parse
         * 
         * Parses the pagination markup based on the parameters set and the
         * logic found in the render.inc.php file.
         * 
         * @access public
         * @return string
         */
        public function parse()
        {
            // ensure required parameters were set
            $this->_check();

            $_response = $this->render();
            ob_end_clean();

            return $_response;
        }

        protected function render(){
            ob_start();
            // total page count calculation
            $pages = $this->getPageCount();

            // if it's an invalid page request
            if ($this->currentPage < 1) {
                throw new PaginationException("Pagination::currentPage must can't be less than 1.");
            } elseif ($this->currentPage > $pages) {
                throw new PaginationException("Pagination::currentPage must can't be more than the total number of pages.");
            }

            // if there are pages to be shown
            if ($pages > 1 || $this->alwaysShowPagination === true) {
                //TODO Remove the last vestiges of HTML in this class
                ?>
                <ul class="<?= implode(' ', $this->classes) ?>">

                    <?php
                    $this->renderFirstLink();
                    $this->renderPreviousLink();

                    /**
                     * if this isn't a clean output for pagination (eg. show numerical
                     * links)
                     */
                    if (!$this->clean) {

                        $this->renderLeadingCrumbLinks();
                        $this->renderCurrentLink();
                        $this->renderTrailingCrumbLinks();

                    }

                    $this->renderNextLink();
                    $this->renderLastLink();
                    ?>

                </ul>
                <?php
            }
            return ob_get_contents();
        }

        protected function renderLink($classes, $href, $text, $pageNumber="")
        {
            $classes = implode(' ', $classes);
            $link = new Link($classes, $href, $text, $pageNumber);

            return $link->getHTML();
        }

        protected function renderFirstLink()
        {
            $classes = ['copy', 'previous'];
            $href = $this->renderURL(1);
            if ($this->currentPage === 1) {
                $href = '#';
                array_push($classes, 'disabled');
            }

            echo $this->renderLink($classes, $href, $this->first);
        }

        protected function renderPreviousLink()
        {
            $classes = ['copy', 'previous'];
            $href = $this->renderURL($this->currentPage - 1);
            if ($this->currentPage === 1) {
                $href = '#';
                array_push($classes, 'disabled');
            }

            echo $this->renderLink($classes, $href, $this->previous);
        }

        protected function renderLeadingCrumbLinks()
        {
            for ($x = 0; $x < $this->getLeadingCount(); ++$x) {
                $pageNumber = $this->currentPage + $x - $this->getLeadingCount();
                $href = $this->renderURL($pageNumber);

                echo $this->renderLink(['number'], $href, $pageNumber, $pageNumber);
            }
        }

        protected function renderCurrentLink()
        {
            echo $this->renderLink(["number", "active"], "#", $this->currentPage, $this->currentPage);
        }

        protected function renderTrailingCrumbLinks()
        {
            for ($x = 0; $x < $this->getTrailingCount(); ++$x) {
                $pageNumber = $this->currentPage + $x + 1;
                $href = $this->renderURL($pageNumber);

                echo $this->renderLink(['number'], $href, $pageNumber, $pageNumber);
            }
        }

        protected function renderNextLink()
        {
            $href = $this->renderURL($this->currentPage + 1);
            $classes = ['copy', 'next'];
            if ($this->currentPage === $this->getPageCount()) {
                $href = '#';
                array_push($classes, 'disabled');
            }

            echo $this->renderLink($classes, $href, $this->next);
        }

        protected function renderLastLink()
        {
            $pages = $this->getPageCount();

            $href = $this->renderURL($pages);
            $classes = ['copy', 'next'];
            if ($this->currentPage === $pages) {
                $href = '#';
                array_push($classes, 'disabled');
            }

            echo $this->renderLink($classes, $href, $this->last);
        }

        protected function renderURL($pageNumber)
        {
            return sprintf($this->target, $pageNumber);
        }

        protected function getLeadingCount()
        {
            /**
             * Calculates the number of leading page crumbs based on the minimum
             * and maximum possible leading pages.
             */
            if(isset($this->leadingCount)){
                return $this->leadingCount;
            }

            $pages = $this->getPageCount();

            $max = $this->getMaxCrumbs();
            $limit = ((int) floor($max / 2));
            $leading = $limit;
            for ($x = 0; $x < $limit; ++$x) {
                if ($this->currentPage === ($x + 1)) {
                    $leading = $x;
                    break;
                }
            }
            for ($x = $pages - $limit; $x < $pages; ++$x) {
                if ($this->currentPage === ($x + 1)) {
                    $leading = $max - ($pages - $x);
                    break;
                }
            }

            $this->leadingCount = $leading;
            return $leading;
        }

        protected function getTrailingCount()
        {
            if(isset($this->trailingCount)){
                return $this->trailingCount;
            }

            $this->trailingCount = $this->getMaxCrumbs() - $this->getLeadingCount() - 1;
            return $this->trailingCount;
        }

        protected function getMaxCrumbs()
        {
            if(isset($this->maxCrumbs)){
                return $this->maxCrumbs;
            }

            $this->maxCrumbs = min($this->getPageCount(), $this->crumbs);
            return $this->maxCrumbs;
        }

        protected function getPageCount()
        {
            if(is_null($this->pageCount)){
                $this->pageCount = ((int) ceil($this->totalItems / $this->rpp));
                return $this->pageCount;
            }
            return $this->pageCount;
        }

        /**
         * setClasses
         * 
         * @see    <http://twitter.github.com/bootstrap/components.html#pagination>
         * @access public
         * @param  mixed $classes
         * @return self
         */
        public function setClasses($classes)
        {
            $this->classes = (array) $classes;
            return $this;
        }

        /**
         * setClean
         * 
         * Sets the pagination to exclude page numbers, and only output
         * previous/next markup. The counter-method of this is self::setFull.
         * 
         * @access public
         * @return self
         */
        public function setClean()
        {
            $this->clean = true;
            return $this;
        }

        /**
         * setCrumbs
         * 
         * Sets the maximum number of 'crumbs' (eg. numerical page items)
         * available.
         * 
         * @access public
         * @param  integer $crumbs
         * @return self
         */
        public function setCrumbs($crumbs)
        {
            $this->crumbs = $crumbs;
            return $this;
        }

        /**
         * setCurrent
         * 
         * Sets the current page being viewed.
         * 
         * @access public
         * @param  integer $current
         * @return self
         */
        public function setCurrent($current)
        {
            $this->currentPage = (int) $current;
            return $this;
        }

        /**
         * setFull
         * 
         * See self::setClean for documentation.
         * 
         * @access public
         * @return self
         */
        public function setFull()
        {
            $this->clean = false;
            return $this;
        }

        /**
         * setKey
         * 
         * Sets the key of the <_GET> array that contains, and ought to contain,
         * paging information (eg. which page is being viewed).
         * 
         * @access public
         * @param  string $key
         * @return self
         */
        public function setKey($key)
        {
            $this->key = $key;
            return $this;
        }

        /**
         * setNext
         * 
         * Sets the copy of the next anchor.
         * 
         * @access public
         * @param  string $str
         * @return self
         */
        public function setNext($str)
        {
            $this->next = $str;
            return $this;
        }

        /**
         * setPrevious
         * 
         * Sets the copy of the previous anchor.
         * 
         * @access public
         * @param  string $str
         * @return self
         */
        public function setPrevious($str)
        {
            $this->previous = $str;
            return $this;
        }

        /**
         * setItemsPerPage
         * 
         * Sets the number of records per page (used for determining total
         * number of pages).
         * 
         * @access public
         * @param  integer $rpp
         * @return self
         */
        public function setItemsPerPage($rpp)
        {
            $this->rpp = $rpp;
            return $this;
        }

        /**
         * setTarget
         * 
         * Sets the leading path for anchors.
         * 
         * @access public
         * @param  string $target eg "protocol://sub-domain.domain.tld/resource/page/%d/category/5"
         * @return self
         */
        public function setTarget($target)
        {
            $this->target = $target;
            return $this;
        }

        /**
         * setTotal
         * 
         * Sets the total number of records available for pagination.
         * 
         * @access public
         * @param  integer $total
         * @return self
         */
        public function setTotal($total)
        {
            $this->totalItems = $total;
            return $this;
        }
    }
