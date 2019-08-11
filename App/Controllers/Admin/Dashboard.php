<?php
    
    namespace App\Controllers\Admin;

    use Core\View;
    use App\Models\User;
    use App\Models\Setting;
    use App\Models\Topic;
    use App\Models\Question;
    use App\Models\Answer;
    use App\Models\Report;
    use App\Models\Page;
    use App\Auth;
    use App\Config;
    use App\Flash;

    /**
     * Admin Panel. Main Dashboard
     */
    class Dashboard extends \Core\Controller
    {
        /**
         * "Before" filter. Checking if signed in user is admin
         *
         * @return void
         */
        protected function before()
        {
            /* Getting signed in user profile */
            $user = Auth::getUser();

            /* Checking if signed in user is admin */
            if (! isset($user) || $user->is_admin <> 1) {
                die("You do not have access to that page!");
                exit;
            }
        }


        /**
         * Displaying the dashboard page
         * URL: /admin/dashboard/index
         *
         * @return void
         */
        public function indexAction()
        {
            /* Loading view template */
            View::renderTemplate('Admin/dashboard.twig', [
                'admin_tab'         => 'dashboard', 
                'is_admin_panel'    => 1,
                'php_version'       => phpversion(),
                'app_version'       => Config::getValues('app_version'),
                'answers_number'    => Answer::countByTime(0),
                'answers_today_number' => Answer::countByTime(strtotime('today midnight')),
                'topics_number'     => Topic::count(),
                'questions_number'  => Question::count(),
                'questions_today_number' => Question::countByTime(strtotime('today midnight')),
                'users_number'      => User::countByTime(0),
                'users_today_number'  => User::countByTime(strtotime('today midnight')),
                'pages_number'      => Page::count(),
                'new_reports'       => Report::count(),
            ]); 
        }


        /**
         * Displaying the settings page
         * URL: /admin/dashboard/settings
         *
         * @return void
         */
        public function settingsAction()
        {
            /* Loading view template */
            View::renderTemplate('Admin/settings.twig', [
                'admin_tab'     => 'settings', 
                'is_admin_panel' => 1,
                'php_version'   => phpversion(),
                'app_settings'  => Config::getValues(),
                'new_reports'   => Report::count(),
            ]); 
        }


        /**
         * Save settings form
         *
         * @return void
         */
        public function saveAction()
        {
            /* Creating settings object with post parameters */
            $setting = new Setting($_POST);

            /* Trying to save settings */
            if ($setting->update()) {

                /* Show successful message and refresh page */ 
                Flash::addMessage('Settings updated', Flash::INFO);

                $this->redirect('/admin/dashboard/settings');

            } else {

                /* Show error message */
                Flash::addMessage('Changes was not update. Try again', Flash::INFO);
            }

            /* Loading view template */
            View::renderTemplate('Admin/settings.twig', [
                'admin_tab'     => 'settings', 
                'is_admin_panel' => 1,
                'php_version'   => phpversion(),
                'app_settings'  => Config::getValues()
            ]); 
        }


        /**
         * Checking for latest version of qxaphp
         *
         * @return string - last version
         */
        public function getLastVersionAction()
        {
            $url = 'http://xandr.co/apps/qxaphp/version';
            $return = file_get_contents($url);

            header('Content-Type: application/json');
            echo json_encode($return);
        }
    }