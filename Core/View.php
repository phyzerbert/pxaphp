<?php

    namespace Core;

    /**
     * Main View Class
     */
    class View
    {
        /**
         * Render a view file
         *
         * @param string $view - The view file
         * @param array $args - Associative array of data to display in the view (optional)
         *
         * @return void
         */
        public static function render(string $view, array $args = [])
        {
            extract($args, EXTR_SKIP);

            $file = "../App/Views/$view";

            if (is_readable($file)) {
                require $file;
            } else {
                echo "$file not found";
            }
        }


        /**
         * Custom filters for Twig
         *
         * @param string $twig - Twig Object
         *
         * @return void
         */
        public static function twigFilters($twig)
        {
            $timeagoFilter = new \Twig_Filter('timeago', function ($timestamp) {

                $time = time() - $timestamp; 

                if ($time == 0) {

                    return 'just now';

                } else {

                    $units = array (
                        31536000 => 'year',
                        2592000 => 'month',
                        604800 => 'week',
                        86400 => 'day',
                        3600 => 'hour',
                        60 => 'minute',
                        1 => 'second'
                    );

                    foreach ($units as $unit => $val) {
                        if ($time < $unit) continue;
                        $numberOfUnits = floor($time / $unit);
                        return ($val == 'second')? 'a few seconds ago' : 
                               (($numberOfUnits>1) ? $numberOfUnits : 'a')
                               .' '.$val.(($numberOfUnits>1) ? 's' : '').' ago';
                    }
                }
            });

            $twig->addFilter($timeagoFilter);

            $getUrlFilter = new \Twig_Filter('include_path_url', function ($link) {

                return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$link;
            });

            $twig->addFilter($getUrlFilter);
        }


        /**
         * Render a view template using Twig
         *
         * @param string $template - The template file
         * @param array $args - Associative array of data to display in the view (optional)
         *
         * @return void
         */
        public static function renderTemplate(string $template, array $args = [])
        {
            static $twig = null;

            if ($twig === null) {

                $loader = new \Twig_Loader_Filesystem('../App/Views');
                $twig = new \Twig_Environment($loader);

                self::twigFilters($twig);

                /* Adding global variables, which will be available in any template */
                $twig->addGlobal('current_user', \App\Auth::getUser());
                $twig->addGlobal('flash_messages', \App\Flash::getMessages());
                $twig->addGlobal('settings', \App\Config::getValues());
                $twig->addGlobal('pages', \App\Models\Page::getActive());
            }

            echo $twig->render($template, $args);
        }


        /**
         * Get the contents of a view template using Twig
         *
         * @param string $template - The template file
         * @param array $args - Associative array of data to display in the view (optional)
         *
         * @return string
         */
        public static function getTemplate(string $template, array $args = [])
        {
            static $twig = null;

            if ($twig === null) {

                $loader = new \Twig_Loader_Filesystem(dirname(__DIR__) . '/App/Views');
                $twig = new \Twig_Environment($loader);

                self::twigFilters($twig);

                /* Adding global variables, which will be available in any template */
                $twig->addGlobal('current_user', \App\Auth::getUser());
                $twig->addGlobal('flash_messages', \App\Flash::getMessages());
                $twig->addGlobal('settings', \App\Config::getValues());
                $twig->addGlobal('pages', \App\Models\Page::getActive());
            }

            return $twig->render($template, $args);
        }
    }