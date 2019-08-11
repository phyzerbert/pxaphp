<?php
	
	namespace App\Controllers;

    use Core\View;
    use App\Models\Page;
    use App\Auth;
    use App\Flash;

    /**
     * Pages Controller
     */
    class Pages extends \Core\Controller
    {
        /**
         * Load requested page
         * URL: /page/{{ page.url }}
         *
         * @return void
         */
        public function viewAction()
        {
            /* Getting page by URL */
            $page = Page::getByURL($this->route_params['url']);

            /* Checking if page exists */
            if ($page) {

                /* Displaying view template */
                View::renderTemplate('Pages/view.twig',[
                    'this_page'         => [
                        'title'       => $page->title,
                        'content'     => $page->content,
                        'url'         => $page->url,
                        'page_id'     => $page->id,
                    ],
                ]);

            } else {

                /* Displaying view template */
                View::renderTemplate('Pages/error.twig',[
                    'this_page'         => [
                        'title'    => 'Page was not found',
                        'menu'     => '',
                        'url'      => '',
                    ],
                ]);
            }
        }
    }