<?php
    
    namespace App\Controllers\Admin;

    use Core\View;
    use App\Models\Setting;
    use App\Models\User;
    use App\Models\Topic;
    use App\Models\Report;
    use App\Auth;
    use App\Config;
    use App\Flash;
    use App\Paginator;

    /**
     * Admin Panel. Reports Controller
     */
    class Reports extends \Core\Controller
    {
        /**
         * Checking if signed in user is admin
         *
         * @return void
         */
        protected function before()
        {
            /* Getting current user */
            $user = Auth::getUser();

            /* Checking if signed in user is admin */
            if (! isset($user) || $user->is_admin <> 1) {
                die("You do not have access to that page!");
                exit;
            }
        }


        /**
         * Displaying the manage reports page
         * URL: /admin/reports/index
         *
         * @return void
         */
        public function indexAction()
        {
            /* Checking if is used search form */
            $search = $_GET['search'] ?? null;

            /* Getting total reports's number */
            $totalReports = Report::count([
                'search' => $search
            ]);

            /* Adding pagination */
            $paginator = new Paginator($_GET['page'] ?? 1, intval(Config::getValues('per_page_admin')), $totalReports, '/admin/reports/index?page=(:num)');

            /* Getting reports list */
            $reports = Report::get([
                'offset' => $paginator->offset, 
                'limit' => $paginator->limit,
                'search' => $search
            ]);
        
            /* Displaying view template */
            View::renderTemplate('Admin/Reports/reports.twig', [
                'admin_tab'      => 'reports', 
                'is_admin_panel' => 1,
                'reports_number' => $totalReports,
                'reports' => $reports,
                'paginator' => $paginator,
                'search' => $search,
                'new_reports' => $totalReports,
            ]); 
        }


        /**
         * Displaying resolved reports page
         * URL: /admin/topics/resolved
         *
         * @return void
         */
        public function resolvedAction()
        {
            /* Checking if is used search form */
            $search = $_GET['search'] ?? null;
            
            /* Getting total resolved report's number */
            $totalReports = Report::count([
                'status' => 1, 
                'search' => $search
            ]);

            /* Adding pagination */
            $paginator = new Paginator($_GET['page'] ?? 1, intval(Config::getValues('per_page_admin')), $totalReports, '/admin/reports/resolved?page=(:num)');

            /* Getting resolved reports list */
            $reports = Report::get([
                'status' => 1, 
                'offset' => $paginator->offset, 
                'limit' => $paginator->limit,
                'search' => $search
            ]);
    
            /* Displaying view template */
            View::renderTemplate('Admin/Reports/resolved.twig', [
                'admin_tab'      => 'reports', 
                'is_admin_panel' => 1,
                'reports_number' => $totalReports,
                'reports' => $reports,
                'paginator' => $paginator,
                'search' => $search,
                'new_reports' => Report::count(),
            ]); 
        }


        /**
         * Close report function
         * Will set it as resolved
         *
         * @return void
         */
        public function closeAction()
        {
            $reportId = intval($_POST['id']);

            /* Checking for report id value */
            if ($reportId > 0) {

                /* Setting report as resolved */
                if (Report::close($reportId)) {

                    Flash::addMessage('Report was set as resolved!', Flash::INFO);

                } else {

                    Flash::addMessage('Error! Try again', Flash::INFO);
                } 

                /* Redirect to manage reports page and show messages */
                $this->redirect('/admin/reports/index');
            }
        }


        /**
         * Delete report function
         *
         * @return void
         */
        public function deleteAction()
        {
            $reportId = intval($_POST['id']);

            /* Checking for report id value */
            if ($reportId > 0) {

                /* Deleting report */
                if (Report::delete($reportId)) {

                    Flash::addMessage('Report was deleted!', Flash::INFO);

                } else {

                    Flash::addMessage('Error! Try again', Flash::INFO);
                } 

                /* Redirect to resolved reports page and show messages */
                $this->redirect('/admin/reports/resolved');
            }
        }
    }