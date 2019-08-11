<?php
    
    namespace App\Controllers\Admin;

    use Core\View;
    use App\Models\Setting;
    use App\Models\User;
    use App\Models\Answer;
    use App\Models\Report;
    use App\Models\Notification;
    use App\Auth;
    use App\Config;
    use App\Flash;
    use App\Upload;
    use App\Paginator;

    /**
     * Admin Panel. Answers Controller
     */
    class Answers extends \Core\Controller
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
         * Displaying the manage answers page
         * URL: /admin/answers/index
         *
         * @return void
         */
        public function indexAction()
        {
            /* Checking if is used search form */
            $search = $_GET['search'] ?? null;

            /* Getting total answer's number */
            $totalAnswers = Answer::count([
                'get_all' => 1, 
                'search' => $search
            ]);

            /* Adding pagination */
            $paginator = new Paginator($_GET['page'] ?? 1, intval(Config::getValues('per_page_admin')), $totalAnswers, '/admin/answers/index?page=(:num)');

            /* Getting answers list */
            $answers = Answer::get([
                'including_question' => 1, 
                'get_all'           => 1,
                'offset'            => $paginator->offset, 
                'limit'             => $paginator->limit,
                'search'            => $search
            ]);
        
            /* Displaying view template */
            View::renderTemplate('Admin/Answers/answers.twig', [
                'admin_tab'         => 'answers', 
                'is_admin_panel'    => 1,
                'answers_number'    => $totalAnswers,
                'answers'           => $answers,
                'paginator'         => $paginator,
                'search'            => $search,
                'new_reports'       => Report::count(),
            ]); 
        }


        /**
         * Displaying hidden answers page
         * URL: /admin/answers/hidden
         *
         * @return void
         */
        public function hiddenAction()
        {
            /* Checking if is used search form */
            $search = $_GET['search'] ?? null;
            
            /* Getting total hidden question's number */
            $totalAnswers = Answer::count([
                'active' => 0, 
                'get_all' => 1,
                'search' => $search
            ]);

            /* Adding pagination */
            $paginator = new Paginator($_GET['page'] ?? 1, intval(Config::getValues('per_page_admin')), $totalAnswers, '/admin/answers/hidden?page=(:num)');

            /* Getting disabled answers list */
            $answers = Answer::get([
                'including_question' => 1, 
                'active'            => 0, 
                'get_all'           => 1,
                'offset'            => $paginator->offset, 
                'limit'             => $paginator->limit,
                'search'            => $search
            ]);
    
            /* Displaying view template */
            View::renderTemplate('Admin/Answers/hidden.twig', [
                'admin_tab'         => 'answers', 
                'is_admin_panel'    => 1,
                'answers_number'    => $totalAnswers,
                'answers'           => $answers,
                'paginator'         => $paginator,
                'search'            => $search,
                'new_reports'       => Report::count(),
            ]); 
        }


        /**
         * Hide answer function
         *
         * @return void
         */
        public function hideAction()
        {
            $id = $_POST['id'];
            $notification = (isset($_POST['send_notification']) && $_POST['send_notification'] == 'on') ? 1 : 0;
            $notificationSent = '';

            /* Checking if answer was hidden */
            if (Answer::hide($id)) {

                /* If notification is check - send notification about removed answer */
                if ($notification == 1) {

                    /* Getting answer's author (inactive answer) */
                    $answer = Answer::getById($id, 1);

                    /* Send notification to answer's author */
                    Notification::add([
                        'to_user'       => $answer->user_id,
                        'question_id'   => $answer->question_id,
                        'answer_id'     => $answer->id,
                        'type'          => 'da',
                        'from_user'     => $answer->user_id,
                    ]);

                    $notificationSent = ' Notification sent!';
                }

                Flash::addMessage('Answer successfully hidden!'.$notificationSent, Flash::INFO);

            } else {

                Flash::addMessage('Error! Try to hide again', Flash::INFO);
            } 

            /* Redirect to redirect url or manage answers page and show messages */
            if (isset($_POST['redirect_url'])) {
                $this->redirect($_POST['redirect_url']);
            } else {
                $this->redirect('/admin/answers/index');
            }
        }


        /**
         * Restore answer function
         *
         * @return void
         */
        public function restoreAction()
        {
            $id = $_POST['id'];

            /* Checking if answer was restored */
            if (Answer::restore($id)) {

                Flash::addMessage('Answer successfully restored!', Flash::INFO);

            } else {

                Flash::addMessage('Error! Try to restore again', Flash::INFO);
            } 

            /* Redirect to manage hidden answers page and show messages */
            $this->redirect('/admin/answers/hidden');
        }


        /**
         * Delete answer function
         *
         * @return void
         */
        public function deleteAction()
        {
            $id = $_POST['id'];

            /* Checking if answer was deleted */
            if (Answer::delete($id)) {

                Flash::addMessage('Answer successfully deleted!', Flash::INFO);

            } else {

                Flash::addMessage('Error! Try to delete again', Flash::INFO);
            } 

            /* Redirect to redirect url or manage answers page and show messages */
            if (isset($_POST['redirect_url'])) {
                $this->redirect($_POST['redirect_url']);
            } else {
                $this->redirect('/admin/answers/index');
            }
        }


        /**
         * Displaying edit answer page
         * URL: /admin/answers/edit?id={{ id }}
         *
         * @return void
         */
        public function editAction()
        {
            /* Getting answer by id */
            $answer = Answer::getById($_GET['id'], 1);

            /* Displaying view template */
            View::renderTemplate('Admin/Answers/edit.twig', [
                'admin_tab'         => 'answers', 
                'is_admin_panel'    => 1,
                'answer'            => $answer,
                'new_reports'       => Report::count(),
            ]); 
        }


        /**
         * Save answer function
         *
         * @return void
         */
        public function saveAction()
        {
            /* Getting Answer by id */
            $answer_id = intval($_POST['answer_id']);
            $this_answer = Answer::getById($answer_id, 1);

            /* Updating answer details */
            $answer = new Answer([ 
                'id' => $_POST['answer_id'],
                'answer' => $_POST['new_answer'], 
            ]);
            
            if ($answer->update()) {

                /* Show success message */
                Flash::addMessage("Done: Answer was updated!", Flash::INFO);
                
            } else {
            
                /* Show error messages */
                Flash::addMessage("Error: ".implode(", ", $answer->errors), Flash::INFO);
            }

            /* Redirect to question's page */
            $this->redirect("/admin/answers/edit?id=".$answer_id);
        }
    }