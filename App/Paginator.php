<?php

    namespace App;
    
    /**
     * Paginator
     * Data for selecting a page of records
     */
    class Paginator
    {
        /** 
         * Number placeholder
         */
        const NUM_PLACEHOLDER = '(:num)';

        /** 
         * Number of records to return
         * @var integer
         */
        public $limit;

        /** 
         * Number of records to skip before the page
         * @var integer
         */
        public $offset;

        /** 
         * Previous page number
         * @var integer
         */
        public $previous;

        /** 
         * Next page number
         * @var integer
         */
        public $next;

        /** 
         * Current page
         * @var integer
         */
        public $page;

        /** 
         * Total pages number
         * @var integer
         */
        public $total;

        /** 
         * Pagination buttons
         * @var array
         */
        public $pagination = [];

        /**
         * Maximum buttons for pagination
         * 10 e.g.: 1 ... 2 3 |4| 5 6 7 8 9 ... 35
         */
        protected $maxPagesToShow = 10;

        /**
         * URL pattern for building pagination
         * e.g.: users/all/(:num)
         */
        protected $urlPattern;

        /**
         * Number of items on current page
         */
        protected $itemsOnPage = 0;


        /** 
         * Class constructor
         *
         * @param integer $page - Page number
         * @param integer $records_per_page - Number of records per page
         * @param integer $total - Total number of records
         * @param string $urlPattern - URL Pattern for pagination links (optional)
         *
         * @return void
         */
        public function __construct(int $page, int $records_per_page, int $total_records, string $urlPattern = '')
        {
            $this->page = $page;
            $this->limit = $records_per_page;
            $this->urlPattern = $urlPattern;

            $page = filter_var($page, FILTER_VALIDATE_INT, [
                'options' => [
                    'default'   => 1,
                    'min_range' => 1
                ]
            ]);

            if ($page > 1) {
                $this->previous = $page - 1;
            }

            $total_pages = ceil($total_records / $records_per_page);

            $this->total = $total_pages;

            if ($page < $total_pages) {
                $this->next = $page + 1;
            }

            $this->offset = $records_per_page * ($page - 1); 

            $this->buildPagination();

            if ($this->previous > 0) {
                $this->previousUrl = $this->getPageUrl($this->previous);
            }

            if ($this->next <= $this->total) {
                $this->nextUrl = $this->getPageUrl($this->next);
            }
        }

        /**
         * @param int $pageNum - Page number
         *
         * @return string
         */
        public function getPageUrl($pageNum)
        {
            return str_replace(self::NUM_PLACEHOLDER, $pageNum, $this->urlPattern);
        }


        /**
         * Create a page data structure.
         *
         * @param int $pageNum - Page number
         * @param bool $isCurrent - True if is current page
         *
         * @return array - array with values
         */
        protected function createPage(int $pageNum, bool $isCurrent = false)
        {
            return array(
                'num' => $pageNum,
                'url' => $this->getPageUrl($pageNum),
                'isCurrent' => $isCurrent,
            );
        }


        /**
         * @return array
         */
        protected function createPageEllipsis()
        {
            return array(
                'num' => '...',
                'url' => null,
                'isCurrent' => false,
            );
        }


        /**
         * Get an array of paginated page data.
         *
         * Example:
         * array(
         *     array ('num' => 1,     'url' => '/example/page/1',  'isCurrent' => false),
         *     array ('num' => '...', 'url' => NULL,               'isCurrent' => false),
         *     array ('num' => 3,     'url' => '/example/page/3',  'isCurrent' => false),
         *     array ('num' => 4,     'url' => '/example/page/4',  'isCurrent' => true ),
         *     array ('num' => 5,     'url' => '/example/page/5',  'isCurrent' => false),
         *     array ('num' => '...', 'url' => NULL,               'isCurrent' => false),
         *     array ('num' => 10,    'url' => '/example/page/10', 'isCurrent' => false),
         * )
         *
         * @return array
         */
        private function buildPagination()
        {
            $pages = array();

            if ($this->total <= 1) {
                return array();
            }

            if ($this->total <= $this->maxPagesToShow) {
                
                for ($i = 1; $i <= $this->total; $i++) {
                    $pages[] = $this->createPage($i, $i == $this->page);
                }

            } else {

                /* Determine the sliding range, centered around the current page. */
                $numAdjacents = (int) floor(($this->maxPagesToShow - 3) / 2);

                if ($this->page + $numAdjacents > $this->total) {
                    $slidingStart = $this->total - $this->maxPagesToShow + 2;
                } else {
                    $slidingStart = $this->page - $numAdjacents;
                }

                if ($slidingStart < 2) $slidingStart = 2;
                
                $slidingEnd = $slidingStart + $this->maxPagesToShow - 3;
                
                if ($slidingEnd >= $this->total) $slidingEnd = $this->total - 1;
                
                /* Build the list of pages. */
                $pages[] = $this->createPage(1, $this->page == 1);
                
                if ($slidingStart > 2) {
                    $pages[] = $this->createPageEllipsis();
                }

                for ($i = $slidingStart; $i <= $slidingEnd; $i++) {
                    $pages[] = $this->createPage($i, $i == $this->page);
                }

                if ($slidingEnd < $this->total - 1) {
                    $pages[] = $this->createPageEllipsis();
                }

                $pages[] = $this->createPage($this->total, $this->page == $this->total);
            }

            $this->pagination = $pages;
        }
    }