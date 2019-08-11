<?php
    
    namespace App\Controllers\Admin;

    use Core\View;
    use App\Models\Setting;
    use App\Models\User;
    use App\Models\Question;
    use App\Models\QuestionImage;
    use App\Models\QuestionTopic;
    use App\Models\Report;
    use App\Models\Notification;
    use App\Auth;
    use App\Config;
    use App\Flash;
    use App\Upload;
    use App\Paginator;

    /**
     * Admin Panel. Questions Controller
     */
    class Questions extends \Core\Controller
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
         * Displaying the manage questions page
         * URL: /admin/questions/index
         *
         * @return void
         */
        public function indexAction()
        {
            /* Checking if is used search form */
            $search = $_GET['search'] ?? null;

            /* Getting total question's number */
            $totalQuestions = Question::count([
                'search' => $search
            ]);

            /* Adding pagination */
            $paginator = new Paginator($_GET['page'] ?? 1, intval(Config::getValues('per_page_admin')), $totalQuestions, '/admin/questions/index?page=(:num)');

            /* Getting questions list */
            $questions = Question::get([
                'offset' => $paginator->offset, 
                'limit' => $paginator->limit,
                'search' => $search
            ]);
        
            /* Displaying view template */
            View::renderTemplate('Admin/Questions/questions.twig', [
                'admin_tab'      => 'questions', 
                'is_admin_panel' => 1,
                'questions_number' => $totalQuestions,
                'questions' => $questions,
                'paginator' => $paginator,
                'search' => $search,
                'new_reports' => Report::count(),
            ]); 
        }


        /**
         * Displaying hidden questions page
         * URL: /admin/questions/hidden
         *
         * @return void
         */
        public function hiddenAction()
        {
            /* Checking if is used search form */
            $search = $_GET['search'] ?? null;
            
            /* Getting total hidden question's number */
            $totalQuestions = Question::count([
                'active' => 0, 
                'search' => $search
            ]);

            /* Adding pagination */
            $paginator = new Paginator($_GET['page'] ?? 1, intval(Config::getValues('per_page_admin')), $totalQuestions, '/admin/questions/hidden?page=(:num)');

            /* Getting disabled questions list */
            $questions = Question::get([
                'active' => 0, 
                'offset' => $paginator->offset, 
                'limit' => $paginator->limit,
                'search' => $search
            ]);
    
            /* Displaying view template */
            View::renderTemplate('Admin/Questions/hidden.twig', [
                'admin_tab'      => 'questions', 
                'is_admin_panel' => 1,
                'topics_number' => $totalQuestions,
                'questions' => $questions,
                'paginator' => $paginator,
                'search' => $search,
                'new_reports' => Report::count(),
            ]); 
        }


        /**
         * Hide question function
         *
         * @return void
         */
        public function hideAction()
        {
            $id = $_POST['id'];
            $notification = (isset($_POST['send_notification']) && $_POST['send_notification'] == 'on') ? 1 : 0;
            $notificationSent = '';

            /* Checking if question was hidden */
            if (Question::hide($id)) {

                /* If notification is check - send notification about removed question */
                if ($notification == 1) {

                    /* Getting question's author (inactive question) */
                    $question = Question::getById($id, 1);

                    /* Send notification to question's author */
                    Notification::add([
                        'to_user'       => $question->user_id,
                        'question_id'   => $question->id,
                        'type'          => 'dq',
                        'from_user'     => $question->user_id,
                    ]);

                    $notificationSent = ' Notification sent!';
                }

                Flash::addMessage('Question successfully hidden!'.$notificationSent, Flash::INFO);

            } else {

                Flash::addMessage('Error! Try to hide again', Flash::INFO);
            } 

            /* Redirect to redirect url or manage questions page and show messages */
            if (isset($_POST['redirect_url'])) {
                $this->redirect($_POST['redirect_url']);
            } else {
                $this->redirect('/admin/questions/index');
            }
        }


        /**
         * Restore question function
         *
         * @return void
         */
        public function restoreAction()
        {
            $id = $_POST['id'];

            /* Checking if question was restored */
            if (Question::restore($id)) {

                Flash::addMessage('Question successfully restored!', Flash::INFO);

            } else {

                Flash::addMessage('Error! Try to restore again', Flash::INFO);
            } 

            /* Redirect to manage hidden questions page and show messages */
            $this->redirect('/admin/questions/hidden');
        }


        /**
         * Delete question function
         *
         * @return void
         */
        public function deleteAction()
        {
            $id = $_POST['id'];

            /* Checking if question was deleted */
            if (Question::delete($id)) {

                Flash::addMessage('Question successfully deleted!', Flash::INFO);

            } else {

                Flash::addMessage('Error! Try to delete again', Flash::INFO);
            } 

            /* Redirect to redirect url or manage questions page and show messages */
            if (isset($_POST['redirect_url'])) {
                $this->redirect($_POST['redirect_url']);
            } else {
                $this->redirect('/admin/questions/index');
            }
        }


        /**
         * Displaying edit question page
         * URL: /admin/questions/edit?id={{ id }}
         *
         * @return void
         */
        public function editAction()
        {
            /* Getting question by id */
            $question = Question::getById($_GET['id'], 1);

            /* Displaying view template */
            View::renderTemplate('Admin/Questions/edit.twig', [
                'admin_tab'         => 'questions', 
                'is_admin_panel'    => 1,
                'question'          => $question,
                'new_reports'       => Report::count(),
            ]); 
        }


        /**
         * Save question function
         *
         * @return void
         */
        public function saveAction()
        {
            /* Getting Question by id */
            $this_question = Question::getById(intval($_POST['question_id']), 1);

            /* Updating question details */
            $question = new Question($_POST);
            $question->id = $this_question->id;
            if ($question->update()) {

                $this_question->url = $question->url;

                /* Removing topics then add new one */
                $topics = new QuestionTopic();
                $topics->delete($question->id);

                foreach ($question->topics as $topicId) {
                    $topics->add($question->id, $topicId);
                }

                /* Checking if user attached images for question */
                if (is_uploaded_file($_FILES['images']['tmp_name'][0])) {

                    /* Removing all old images */
                    QuestionImage::delete($question->id);

                    /* Flag for number of added images */
                    $uploadedImages = 0;

                    /* Getting number of max images allowed per question */
                    $maxImages = $this->config->question_max_images;

                    /* Passing all images uploaded */
                    for ($i = 0; $i < count($_FILES['images']['tmp_name']); $i++) {

                        /* If number of uploaded images is max allowed - skip image */
                        if ($uploadedImages >= $maxImages || $_FILES['images']['size'][$i] == 0) {
                            continue;
                        }

                        /* Creating new Upload's object */
                        $upload = new Upload([
                            'subfolder' => 'questions/',
                            'name' => $_FILES['images']['name'][$i],
                            'type' => $_FILES['images']['type'][$i],
                            'tmp_name' => $_FILES['images']['tmp_name'][$i],
                            'error' => $_FILES['images']['error'][$i],
                            'size' => $_FILES['images']['size'][$i],
                        ]);

                        /* Setting new random title for image */
                        $upload->name = $upload->getRandomFilename(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));

                        /* Uploading image */
                        if ($upload->upload()) {
                            
                            /* If image was upload - increment number of uploaded images */
                            $uploadedImages++;

                            /* Add image into database - question_images table */
                            $question_image = new QuestionImage();
                            $question_image->add($question->id, $upload->name);
                        }
                    }
                }

                /* Show success message */
                Flash::addMessage('Question saved!', Flash::INFO);

            } else {
            
                /* Show error messages */
                Flash::addMessage("Error: ".implode(", ", $question->errors), Flash::INFO);
            }

            /* Redirect to question's page */
            $this->redirect("/admin/questions/edit?id=".$this_question->id);
        }
    }