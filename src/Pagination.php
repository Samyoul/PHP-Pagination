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
        /*protected $_variables = [
            'classes' => ['clearfix', 'pagination'],
            'crumbs' => 5,
            'key' => 'page',
            'target' => '',
            'next' => 'Next &raquo;',
            'previous' => '&laquo; Previous',
            'alwaysShowPagination' => false,
            'clean' => false
        ];*/

        protected $currentPage = 1;
        protected $totalItems = null;
        protected $rpp;
        protected $itemsPerPage = 10;
        protected $classes = ['clearfix', 'pagination'];
        protected $crumbs = 5;
        protected $key = 'page';
        protected $target = '';
        protected $next = 'Next &raquo;';
        protected $previous = '&laquo; Previous';
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
         * @throws \Exception
         */
        protected function _check()
        {
            if (!isset($this->currentPage)) {
                throw new \Exception('Pagination::currentPage must be set.');
            } elseif (!isset($this->totalItems) OR $this->totalItems === null) {
                throw new \Exception('Pagination::totalItems must be set.');
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
         * @param  mixed $classes
         * @return void
         */
        public function addClasses($classes)
        {
            $this->classes = array_merge(
                $this->classes,
                (array) $classes
            );
        }

        /**
         * alwaysShowPagination
         * 
         * Tells the rendering engine to show the pagination links even if there
         * aren't any pages to paginate through.
         * 
         * @access public
         * @return void
         */
        public function alwaysShowPagination()
        {
            $this->alwaysShowPagination = true;
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
            $pages = ((int) ceil($this->totalItems / $this->rpp));

            // if it's an invalid page request
            if ($this->currentPage < 1) {
                throw new \Exception("Pagination::currentPage must can't be less than 1.");
            } elseif ($this->currentPage > $pages) {
                throw new \Exception("Pagination::currentPage must can't be more than the total number of pages.");
            }

            // if there are pages to be shown
            if ($pages > 1 || $this->alwaysShowPagination === true) {
                ?>
                <ul class="<?= implode(' ', $this->classes) ?>">
                    <?php
                    /**
                     * Previous Link
                     */

                    // anchor classes and target
                    $classes = ['copy', 'previous'];
                    $href = $this->renderURL($this->currentPage - 1);
                    if ($this->currentPage === 1) {
                        $href = '#';
                        array_push($classes, 'disabled');
                    }
                    ?>
                    <li class="<?= implode(' ', $classes) ?>">
                        <a href="<?= ($href) ?>"><?= ($this->previous) ?></a>
                    </li>
                    <?php
                    /**
                     * if this isn't a clean output for pagination (eg. show numerical
                     * links)
                     */
                    if (!$this->clean) {

                        /**
                         * Calculates the number of leading page crumbs based on the minimum
                         *     and maximum possible leading pages.
                         */
                        $max = min($pages, $this->crumbs);
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

                        // calculate trailing crumb count based on inverse of leading
                        $trailing = $max - $leading - 1;

                        // generate/render leading crumbs
                        for ($x = 0; $x < $leading; ++$x) {
                            // class/href setup
                            $href = $this->renderURL($this->currentPage + $x - $leading);
                            ?>
                            <li class="number">
                                <a data-pagenumber="<?= ($this->currentPage + $x - $leading) ?>" href="<?= ($href) ?>">
                                    <?= ($this->currentPage + $x - $leading) ?>
                                </a>
                            </li>
                            <?php
                        }

                        // print current page
                        ?>
                        <li class="number active">
                            <a data-pagenumber="<?= ($this->currentPage) ?>
                            " href="#"><?= ($this->currentPage) ?>
                            </a>
                        </li>
                        <?php
                        // generate/render trailing crumbs
                        for ($x = 0; $x < $trailing; ++$x) {

                            // class/href setup
                            $href = $this->renderURL($this->currentPage + $x + 1);
                            ?>
                            <li class="number">
                                <a data-pagenumber="<?= ($this->currentPage + $x + 1) ?>" href="<?= ($href) ?>">
                                    <?= ($this->currentPage + $x + 1) ?>
                                </a>
                            </li>
                            <?php
                        }
                    }

                    /**
                     * Next Link
                     */

                    // anchor classes and target
                    $href = $this->renderURL($this->currentPage + 1);
                    $classes = ['copy', 'next'];
                    if ($this->currentPage === $pages) {
                        $href = '#';
                        array_push($classes, 'disabled');
                    }
                    ?>
                    <li class="<?= implode(' ', $classes) ?>">
                        <a href="<?= ($href) ?>"><?= ($this->next) ?></a>
                    </li>
                </ul>
                <?php
            }
            return ob_get_contents();
        }

        protected function renderURL($pageNumber)
        {
            return sprintf($this->target, $pageNumber);
        }

        /**
         * setClasses
         * 
         * @see    <http://twitter.github.com/bootstrap/components.html#pagination>
         * @access public
         * @param  mixed $classes
         * @return void
         */
        public function setClasses($classes)
        {
            $this->classes = (array) $classes;
        }

        /**
         * setClean
         * 
         * Sets the pagination to exclude page numbers, and only output
         * previous/next markup. The counter-method of this is self::setFull.
         * 
         * @access public
         * @return void
         */
        public function setClean()
        {
            $this->clean = true;
        }

        /**
         * setCrumbs
         * 
         * Sets the maximum number of 'crumbs' (eg. numerical page items)
         * available.
         * 
         * @access public
         * @param  integer $crumbs
         * @return void
         */
        public function setCrumbs($crumbs)
        {
            $this->crumbs = $crumbs;
        }

        /**
         * setCurrent
         * 
         * Sets the current page being viewed.
         * 
         * @access public
         * @param  integer $current
         * @return void
         */
        public function setCurrent($current)
        {
            $this->currentPage = $current;
        }

        /**
         * setFull
         * 
         * See self::setClean for documentation.
         * 
         * @access public
         * @return void
         */
        public function setFull()
        {
            $this->clean = false;
        }

        /**
         * setKey
         * 
         * Sets the key of the <_GET> array that contains, and ought to contain,
         * paging information (eg. which page is being viewed).
         * 
         * @access public
         * @param  string $key
         * @return void
         */
        public function setKey($key)
        {
            $this->key = $key;
        }

        /**
         * setNext
         * 
         * Sets the copy of the next anchor.
         * 
         * @access public
         * @param  string $str
         * @return void
         */
        public function setNext($str)
        {
            $this->next = $str;
        }

        /**
         * setPrevious
         * 
         * Sets the copy of the previous anchor.
         * 
         * @access public
         * @param  string $str
         * @return void
         */
        public function setPrevious($str)
        {
            $this->previous = $str;
        }

        /**
         * setItemsPerPage
         * 
         * Sets the number of records per page (used for determining total
         * number of pages).
         * 
         * @access public
         * @param  integer $rpp
         * @return void
         */
        public function setItemsPerPage($rpp)
        {
            $this->rpp = $rpp;
        }

        /**
         * setTarget
         * 
         * Sets the leading path for anchors.
         * 
         * @access public
         * @param  string $target
         * @return void
         */
        public function setTarget($target)
        {
            $this->target = $target;
        }

        /**
         * setTotal
         * 
         * Sets the total number of records available for pagination.
         * 
         * @access public
         * @param  integer $total
         * @return void
         */
        public function setTotal($total)
        {
            $this->total = $total;
        }
    }
