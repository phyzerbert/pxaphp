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
    use App\Upload;
    use App\Paginator;

    /**
     * Admin Panel. Topics Controller
     */
    class Topics extends \Core\Controller
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
         * Displaying the manage topics page (active topics)
         * URL: /admin/topics/index
         *
         * @return void
         */
        public function indexAction()
        {
            /* Checking if is used search form */
            $search = $_GET['search'] ?? null;

            /* Getting total topic's number */
            $totalTopics = Topic::count([
                'search' => $search
            ]);

            /* Adding pagination */
            $paginator = new Paginator($_GET['page'] ?? 1, intval(Config::getValues('per_page_admin')), $totalTopics, '/admin/topics/index?page=(:num)');

            /* Getting topics list */
            $topics = Topic::get([
                'offset' => $paginator->offset, 
                'limit' => $paginator->limit,
                'search' => $search
            ]);
        
            /* Displaying view template */
            View::renderTemplate('Admin/Topics/topics.twig', [
                'admin_tab'      => 'topics', 
                'is_admin_panel' => 1,
                'topics_number' => $totalTopics,
                'topics' => $topics,
                'paginator' => $paginator,
                'search' => $search,
                'new_reports' => Report::count(),
            ]); 
        }


        /**
         * Displaying hidden topics page
         * URL: /admin/topics/hidden
         *
         * @return void
         */
        public function hiddenAction()
        {
            /* Checking if is used search form */
            $search = $_GET['search'] ?? null;
            
            /* Getting total hidden topic's number */
            $totalTopics = Topic::count([
                'active' => 0, 
                'search' => $search
            ]);

            /* Adding pagination */
            $paginator = new Paginator($_GET['page'] ?? 1, intval(Config::getValues('per_page_admin')), $totalTopics, '/admin/topics/hidden?page=(:num)');

            /* Getting hidden topics list */
            $topics = Topic::get([
                'active' => 0, 
                'offset' => $paginator->offset, 
                'limit' => $paginator->limit,
                'search' => $search
            ]);
    
            /* Displaying view template */
            View::renderTemplate('Admin/Topics/hidden.twig', [
                'admin_tab'      => 'topics', 
                'is_admin_panel' => 1,
                'topics_number' => $totalTopics,
                'topics' => $topics,
                'paginator' => $paginator,
                'search' => $search,
                'new_reports' => Report::count(),
            ]); 
        }


        /**
         * Displaying add topic page
         * URL: /admin/topics/add
         *
         * @return void
         */
        public function addAction()
        {
            /* Displaying view template */
            View::renderTemplate('Admin/Topics/add.twig', [
                'admin_tab'      => 'topics', 
                'is_admin_panel' => 1,
                'topics_number' => Topic::count(),
                'new_reports' => Report::count(),
            ]); 
        }


        /**
         * Displaying edit topic page
         * URL: /admin/topics/edit?id={{ id }}
         *
         * @return void
         */
        public function editAction()
        {
            /* Getting topic by id */
            $topic = Topic::getById($_GET['id']);

            /* Displaying view template */
            View::renderTemplate('Admin/Topics/edit.twig', [
                'admin_tab'      => 'topics', 
                'is_admin_panel' => 1,
                'topic' => $topic,
                'new_reports' => Report::count(),
            ]); 
        }


        /**
         * Add new topic function
         *
         * @return void
         */
        public function newAction()
        {
            /* Creating topic's object */
            $topic = new Topic($_POST);

            /* Creating upload's object */
            $upload = new Upload($_FILES['image']);
            $upload->subfolder = 'topics/';

            /* Getting random filename */
            $fileName = $upload->getRandomFilename('jpg');

            /* Setting topic's URL as image name */
            $upload->name = $fileName;
            $topic->image = $fileName;

            /* As topic's image is required - checking if image was upload */
            if ($upload->upload()) {

                /* Checking if topic was create */
                if ($topic->add()) {

                    /* Show success message and redirect to manage topics page */
                    Flash::addMessage('Topic successfully created!', Flash::INFO);

                    $this->redirect('/admin/topics/index');

                } else {

                    Flash::addMessage('Error! Try to add again', Flash::INFO);
                }

            } else {

                Flash::addMessage('Error! Image was not uploaded', Flash::INFO);
            }

            /* If is any error - redirect again to add topic page with displaying errors */
            $this->redirect('/admin/topics/add');
        }


        /**
         * Hide topic function
         *
         * @return void
         */
        public function hideAction()
        {
            /* Creating topic's object */
            $topic = new Topic($_POST);

            /* Checking if topic was hidden */
            if ($topic->hide()) {

                Flash::addMessage('Topic successfully hidden!', Flash::INFO);

            } else {

                Flash::addMessage('Error! Try to hide again', Flash::INFO);
            } 

            /* Redirect to manage topics page and show messages */
            $this->redirect('/admin/topics/index');
        }


        /**
         * Activate topic function
         *
         * @return void
         */
        public function activateAction()
        {
            /* Creating topic's object */
            $topic = new Topic($_POST);

            /* Checking if topic is activated */
            if ($topic->activate()) {

                Flash::addMessage('Topic successfully restored!', Flash::INFO);

            } else {

                Flash::addMessage('Error! Try to restore again', Flash::INFO);
            } 

            /* Redirect to manage topics page and show messages */
            $this->redirect('/admin/topics/index');
        }


        /**
         * Delete topic function
         *
         * @return void
         */
        public function deleteAction()
        {
            /* Creating topic's object */
            $topic = new Topic($_POST);

            /* Checking if topic was delete */
            if ($topic->delete()) {

                Flash::addMessage('Topic successfully deleted!', Flash::INFO);

            } else {

                Flash::addMessage('Error! Try to delete again', Flash::INFO);
            } 

            /* Redirect to hidden topics page and show messages */
            $this->redirect('/admin/topics/hidden');
        }


        /**
         * Save topic function
         *
         * @return void
         */
        public function saveAction()
        {
            /* Creating topic's object */
            $topic = new Topic($_POST);

            /* Checking if file was upload if is needed to change topic's image */
            if (is_uploaded_file($_FILES['image']['tmp_name'])) {

                /* If image was upload - creating upload's object */
                $upload = new Upload($_FILES['image']);
                $upload->subfolder = 'topics/';

                /* Getting random filename */
                $fileName = $upload->getRandomFilename('jpg');

                /* Set topic's URL as image name */
                $upload->name = $fileName;
                $topic->image = $fileName;

                /* Checking if file was upload with success. If was not - show error and redirect back */
                if (! $upload->upload()) {

                    Flash::addMessage('Error! Image was not uploaded', Flash::INFO);

                    $this->redirect('/admin/topics/add');
                }
            }

            /* Trying to update topic's details and redirect to manage topics page if was save with success */
            if ($topic->update()) {

                Flash::addMessage('Topic successfully saved!', Flash::INFO);

                $this->redirect('/admin/topics/index');

            } else {

                Flash::addMessage('Error! Try to save again', Flash::INFO);
            }

            /* Redirecting to add topic page if was any errors */
            $this->redirect('/admin/topics/add');
        }
    }