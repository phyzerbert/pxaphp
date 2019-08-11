<?php
    
    namespace App\Controllers\Admin;

    use Core\View;
    use App\Models\Setting;
    use App\Models\User;
    use App\Models\Report;
    use App\Auth;
    use App\Config;
    use App\Flash;
    use App\Upload;
    use App\Paginator;

    /**
     * Admin Panel. Users Controller
     */
    class Users extends \Core\Controller
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
         * Displaying the manage users page
         * URL: /admin/users/index
         *
         * @return void
         */
        public function indexAction()
        {
            /* Checking if is used search form */
            $search = $_GET['search'] ?? null;

            /* Getting total users's number */
            $totalUsers = User::count([
                'search' => $search
            ]);

            /* Adding pagination */
            $paginator = new Paginator($_GET['page'] ?? 1, intval(Config::getValues('per_page_admin')), $totalUsers, '/admin/users/index?page=(:num)');

            /* Getting users list */
            $users = User::get([
                'offset' => $paginator->offset, 
                'limit' => $paginator->limit,
                'search' => $search
            ]);
        
            /* Displaying view template */
            View::renderTemplate('Admin/Users/users.twig', [
                'admin_tab'      => 'users', 
                'is_admin_panel' => 1,
                'users_number' => $totalUsers,
                'users' => $users,
                'paginator' => $paginator,
                'search' => $search,
                'new_reports' => Report::count(),
            ]); 
        }


        /**
         * Displaying banned users page
         * URL: /admin/users/banned
         *
         * @return void
         */
        public function bannedAction()
        {
            /* Checking if is used search form */
            $search = $_GET['search'] ?? null;
            
            /* Getting total banned user's number */
            $totalUsers = User::count([
                'active' => 0, 
                'search' => $search
            ]);

            /* Adding pagination */
            $paginator = new Paginator($_GET['page'] ?? 1, intval(Config::getValues('per_page_admin')), $totalUsers, '/admin/users/banned?page=(:num)');

            /* Getting banned users list */
            $users = User::get([
                'active' => 0, 
                'offset' => $paginator->offset, 
                'limit' => $paginator->limit,
                'search' => $search
            ]);
    
            /* Displaying view template */
            View::renderTemplate('Admin/Users/banned.twig', [
                'admin_tab'      => 'users', 
                'is_admin_panel' => 1,
                'users_number' => $totalUsers,
                'users' => $users,
                'paginator' => $paginator,
                'search' => $search,
                'new_reports' => Report::count(),
            ]); 
        }


        /**
         * Displaying add new user page
         * URL: /admin/users/add
         *
         * @return void
         */
        public function addAction()
        {
            /* Displaying view template */
            View::renderTemplate('Admin/Users/add.twig', [
                'admin_tab'      => 'users', 
                'values'        => $_POST, 
                'is_admin_panel' => 1,
                'new_reports' => Report::count(),
            ]); 
        }


        /**
         * Displaying edit user page
         * URL: /admin/user/edit?id={{ id }}
         *
         * @return void
         */
        public function editAction()
        {
            /* Getting user by id */
            $user = User::getById($_GET['id']);

            /* Displaying view template */
            View::renderTemplate('Admin/Users/edit.twig', [
                'admin_tab'      => 'users', 
                'is_admin_panel' => 1,
                'user' => $user,
                'new_reports' => Report::count(),
            ]); 
        }


        /**
         * Add new user function
         *
         * @return void
         */
        public function newAction()
        {
            /* Creating user's object */
            $user = new User($_POST);

            /* Checking if user was create */
            if ($user->add()) {

                /* Show success message and redirect to manage users page */
                Flash::addMessage('User successfully added!', Flash::INFO);

                $this->redirect('/admin/users/index');

            } else {

                Flash::addMessage('Error! '.implode('; ', $user->errors), Flash::INFO);

                /* Displaying view template */
                View::renderTemplate('Admin/Users/add.twig', [
                    'admin_tab'      => 'users', 
                    'values'        => $_POST, 
                    'is_admin_panel' => 1,
                    'new_reports' => Report::count(),
                ]); 
            }
        }


        /**
         * Update / Save user function
         *
         * @return void
         */
        public function saveAction()
        {
            /* Creating user's object */
            $user = new User($_POST);

            /* Checking if user was update */
            if ($user->save()) {

                /* Show success message and redirect to manage users page */
                Flash::addMessage('User successfully updated!', Flash::INFO);

                $this->redirect('/admin/users/index');

            } else {

                Flash::addMessage('Error! '.implode('; ', $user->errors), Flash::INFO);

                /* Displaying view template */
                $this->redirect('/admin/users/edit?id='.$_POST['user_id']);
            }
        }


        /**
         * Ban user function
         *
         * @return void
         */
        public function banAction()
        {
            /* Getting user id */
            $userId = $_POST['id'];

            /* Checking if user was ban with success */
            if (User::ban($userId)) {

                Flash::addMessage('User was banned!', Flash::INFO);

            } else {

                Flash::addMessage('Error! Try to ban again', Flash::INFO);
            } 

            /* Redirect to manage users page and show messages */
            $this->redirect('/admin/users/index');
        }


        /**
         * Remove ban from user function
         *
         * @return void
         */
        public function unbanAction()
        {
            /* Getting user id */
            $userId = $_POST['id'];

            /* Checking if user was unban with success */
            if (User::unban($userId)) {

                Flash::addMessage('User was unbanned!', Flash::INFO);

            } else {

                Flash::addMessage('Error! Try to unban again', Flash::INFO);
            } 

            /* Redirect to manage users page and show messages */
            $this->redirect('/admin/users/banned');
        }


        /**
         * Delete user function
         *
         * @return void
         */
        public function deleteAction()
        {
            /* Getting user id */
            $userId = $_POST['id'];

            /* Checking if user was deleted with success */
            if (User::delete($userId)) {

                Flash::addMessage('User was deleted!', Flash::INFO);

                /* Redirect to manage users page and show messages */
                $this->redirect('/admin/users/index');

            } else {

                Flash::addMessage('Error! Try to delete again', Flash::INFO);

                /* Redirect to edit user page and show messages */
                $this->redirect('/admin/users/edit?id='.$userId);
            }
        }
    }