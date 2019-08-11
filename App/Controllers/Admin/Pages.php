<?php
    
    namespace App\Controllers\Admin;

    use Core\View;
    use App\Models\Setting;
    use App\Models\User;
    use App\Models\Page;
    use App\Models\Report;
    use App\Auth;
    use App\Config;
    use App\Flash;
    use App\Upload;
    use App\Paginator;

    /**
     * Admin Panel. Pages Controller
     */
    class Pages extends \Core\Controller
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
         * Displaying the manage pages page
         * URL: /admin/pages/index
         *
         * @return void
         */
        public function indexAction()
        {
            /* Checking if is used search form */
            $search = $_GET['search'] ?? null;

            /* Getting total page's number */
            $totalPages = Page::count([
                'search' => $search
            ]);

            /* Adding pagination */
            $paginator = new Paginator($_GET['page'] ?? 1, intval(Config::getValues('per_page_admin')), $totalPages, '/admin/pages/index?page=(:num)');

            /* Getting pages list */
            $pages = Page::get([
                'offset'            => $paginator->offset, 
                'limit'             => $paginator->limit,
                'search'            => $search
            ]);
        
            /* Displaying view template */
            View::renderTemplate('Admin/Pages/pages.twig', [
                'admin_tab'         => 'pages', 
                'is_admin_panel'    => 1,
                'pages_number'      => $totalPages,
                'pages'              => $pages,
                'paginator'         => $paginator,
                'search'            => $search,
                'new_reports'       => Report::count(),
            ]); 
        }


        /**
         * Displaying hidden pages page
         * URL: /admin/pages/hidden
         *
         * @return void
         */
        public function hiddenAction()
        {
            /* Checking if is used search form */
            $search = $_GET['search'] ?? null;
            
            /* Getting total hidden pages's number */
            $totalPages = Page::count([
                'active' => 0, 
                'search' => $search
            ]);

            /* Adding pagination */
            $paginator = new Paginator($_GET['page'] ?? 1, intval(Config::getValues('per_page_admin')), $totalPages, '/admin/pages/hidden?page=(:num)');

            /* Getting disabled pages list */
            $pages = Page::get([
                'active'            => 0, 
                'offset'            => $paginator->offset, 
                'limit'             => $paginator->limit,
                'search'            => $search
            ]);
    
            /* Displaying view template */
            View::renderTemplate('Admin/Pages/hidden.twig', [
                'admin_tab'         => 'pages', 
                'is_admin_panel'    => 1,
                'pages'             => $pages,
                'pages_number'      => $totalPages,
                'paginator'         => $paginator,
                'search'            => $search,
                'new_reports'       => Report::count(),
            ]); 
        }


        /**
         * Hide page function
         *
         * @return void
         */
        public function hideAction()
        {
            $id = $_POST['id'];

            /* Checking if page was hidden */
            if (Page::hide($id)) {

                Flash::addMessage('Page successfully hidden!', Flash::INFO);

            } else {

                Flash::addMessage('Error! Try to hide again', Flash::INFO);
            } 

            /* Redirect to redirect url or manage pages page and show messages */
            if (isset($_POST['redirect_url'])) {
                $this->redirect($_POST['redirect_url']);
            } else {
                $this->redirect('/admin/pages/index');
            }
        }


        /**
         * Restore page function
         *
         * @return void
         */
        public function restoreAction()
        {
            $id = $_POST['id'];

            /* Checking if page was restored */
            if (Page::restore($id)) {

                Flash::addMessage('Page successfully restored!', Flash::INFO);

            } else {

                Flash::addMessage('Error! Try to restore again', Flash::INFO);
            } 

            /* Redirect to manage hidden pages page and show messages */
            $this->redirect('/admin/pages/hidden');
        }


        /**
         * Delete page function
         *
         * @return void
         */
        public function deleteAction()
        {
            $id = $_POST['id'];

            /* Checking if page was deleted */
            if (Page::delete($id)) {

                Flash::addMessage('Page successfully deleted!', Flash::INFO);

            } else {

                Flash::addMessage('Error! Try to delete again', Flash::INFO);
            } 

            /* Redirect to redirect url or manage pages page and show messages */
            if (isset($_POST['redirect_url'])) {
                $this->redirect($_POST['redirect_url']);
            } else {
                $this->redirect('/admin/pages/index');
            }
        }


        /**
         * Displaying "Add page" page
         * URL: /admin/pages/add
         *
         * @return void
         */
        public function addAction()
        {
            /* Displaying view template */
            View::renderTemplate('Admin/Pages/add.twig', [
                'admin_tab'      => 'pages', 
                'is_admin_panel' => 1,
                'new_reports' => Report::count(),
            ]); 
        }


        /**
         * Add new page function
         *
         * @return void
         */
        public function newAction()
        {
            /* Creating page's object */
            $page = new Page($_POST);

            /* Checking if page was create */
            if ($page->add()) {

                /* Show success message and redirect to manage pages page */
                Flash::addMessage('Page successfully created!', Flash::INFO);

                $this->redirect('/admin/pages/index');

            } else {

                Flash::addMessage('Error! '.implode('; ', $page->errors), Flash::INFO);
            }

            /* If is any error - redirect again to "Add page" page with displaying errors */
            $this->redirect('/admin/pages/add');
        }


        /**
         * Displaying "Edit page" page
         * URL: /admin/pages/edit?id={{ id }}
         *
         * @return void
         */
        public function editAction()
        {
            /* Getting page by id */
            $page = Page::getById($_GET['id'], 1);

            /* Displaying view template */
            View::renderTemplate('Admin/Pages/edit.twig', [
                'admin_tab'         => 'pages', 
                'is_admin_panel'    => 1,
                'page'              => $page,
                'new_reports'       => Report::count(),
            ]); 
        }


        /**
         * Save page function
         *
         * @return void
         */
        public function saveAction()
        {
            /* Getting Page by id */
            $page_id = intval($_POST['page_id']);

            /* Updating page details */
            $page = new Page($_POST);
            
            if ($page->update()) {

                /* Show success message */
                Flash::addMessage("Done: Page was updated!", Flash::INFO);
                
            } else {
            
                /* Show error messages */
                Flash::addMessage("Error: ".implode(", ", $page->errors), Flash::INFO);
            }

            /* Redirect to "Edit page" page */
            $this->redirect("/admin/pages/edit?id=".$page_id);
        }


        /** 
         * Function for uploading image for pages
         * from Quill editor
         *
         * @return json with image url or false if image was not upload
         */
        public function uploadImage()
        {
            /* Getting current user's data */
            $user = Auth::getUser();

            /* Checking if image was upload */
            if (is_uploaded_file($_FILES['image']['tmp_name'])) {

                /* Creating new Upload's object */
                $upload = new Upload([
                    'subfolder' => 'pages/',
                    'name' => $_FILES['image']['name'],
                    'type' => $_FILES['image']['type'],
                    'tmp_name' => $_FILES['image']['tmp_name'],
                    'error' => $_FILES['image']['error'],
                    'size' => $_FILES['image']['size'],
                ]);

                /* Setting new random title for image */
                $upload->name = $upload->getRandomFilename(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

                /* Uploading image */
                if ($upload->upload()) {

                    /* Return response */
                    header('Content-Type: application/json');
                    echo json_encode('/media/images/pages/'.$upload->name);
                }
            }

            return false;
        }
    }