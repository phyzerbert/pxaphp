<?php
	
	namespace App\Controllers;

    use Core\View;
    use App\Auth;
    use App\Paginator;
    use App\Models\User;
    use App\Models\Answer;
    use App\Models\Question;
    use App\Models\Notification;

    /**
     * Notifications Controller
     */
    class Notifications extends \Core\Controller
    {
        /**
         * Show the notifications page
         * URL: /notifications
         *
         * @return void
         */
        public function indexAction()
        {
            /* Checking if user is signed in */
            $this->requireSignin();

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Getting total notification's number */
            $totalNotifications = Notification::count([
                'to_user'       => $user->id
            ]);

            /* Adding pagination */
            $paginator = new Paginator($_GET['page'] ?? 1, 30, $totalNotifications, '/notifications?page=(:num)');

            /* Getting notifications per current page ordered by id DESC */
            $notifications = Notification::get([
                'offset'        => $paginator->offset, 
                'limit'         => $paginator->limit,
                'to_user'       => $user->id
            ]);

            $notifications_array = [];

            foreach($notifications as $notification) {

                $id = $notification['id'];

                $notifications_array[$id]['id'] = $id;
                $notifications_array[$id]['to_user'] = $notification['to_user'];

                /* Getting question information */
                $notifications_array[$id]['question_id'] = $notification['question_id'];
                $question = Question::getById($notification['question_id'], 1);
                $notifications_array[$id]['question_title'] = $question->title;
                $notifications_array[$id]['question_url'] = $question->url;

                $notifications_array[$id]['answer_id'] = $notification['answer_id'];
                $notifications_array[$id]['type'] = $notification['type'];

                /* Getting answer's information */
                if ($notification['type'] == 'da') {
                    $answer = Answer::getById($notification['answer_id'], 1);
                    $notifications_array[$id]['answer'] = strip_tags($answer->answer);
                }

                /* Getting user information */
                $notifications_array[$id]['from_user'] = $notification['from_user'];
                $from_user = User::getById($notification['from_user']);
                $notifications_array[$id]['from_user_name'] = $from_user->name;
                $notifications_array[$id]['from_user_username'] = $from_user->username;
                $notifications_array[$id]['from_user_photo'] = $from_user->photo;

                $notifications_array[$id]['created_at'] = $notification['created_at'];
                $notifications_array[$id]['viewed'] = $notification['viewed'];
            }

            /* Displaying notifications template */
            View::renderTemplate('Notifications/all.twig',[
                'this_page'             => [
                    'title'        => 'All Notifications',
                    'menu'         => 'notifications_all',
                    'url'          => 'notifications',
                ],
                'notifications'         => $notifications_array,
                'paginator'             => $paginator,
                'total_notifications'   => $totalNotifications,
            ]);
        }


        /**
         * Send notifications for last one hour through cron
         * Cron task: 0 * * * * curl --request GET 'https://{{YOUR_DOMAIN}}/notifications/send?secret={{YOUR_SECRET_KEY}}'
         * Secret key is available in Config.php file
         *
         * @return void
         */
        public function sendAction()
        {
            /* Checking if option is enabled and set for cron emailing */
            if ($this->config->email_notifications == 2) {

                /* Checking if secret key is valid */
                if ($_GET['secret'] == $this->config->secret) {

                    /* Send emails */
                    Notification::sendAll();

                } else {

                    /* Otherwise show error */
                    die("Wrong secret key!");
                }

            } else {

                /* Otherwise show error */
                die("Option is disabled!");
            }
        }


        /**
         * Getting number of unviewed notifications
         *
         * @return void
         */
        public function checkAction()
        {
            $return = 0;

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Checking if user is online */
            if ($user) {
                
                /* Getting number of new notifications */
                $return = Notification::count([
                    'to_user' => $user->id, 
                    'viewed' => 0,
                ]);
            }
            
            /* Return response */
            header('Content-Type: application/json');
            echo json_encode($return);
        } 


        /**
         * Load last 10 notifications by AJAX
         * For notifications menu in navbar
         *
         * @return void
         */
        public function loadAction()
        {
            $notifications_array = [];

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Checking if user is online */
            if ($user) {
                
                /* Getting last 10 notifications */
                $notifications = Notification::get([
                    'to_user' => $user->id, 
                    'limit' => 10,
                ]);

                foreach($notifications as $notification) {

                    $id = $notification['id'];

                    $notifications_array[$id]['id'] = $id;
                    $notifications_array[$id]['to_user'] = $notification['to_user'];

                    /* Getting question's information */
                    $notifications_array[$id]['question_id'] = $notification['question_id'];
                    $question = Question::getById($notification['question_id'], 1);
                    $notifications_array[$id]['question_title'] = $question->title;
                    $notifications_array[$id]['question_url'] = $question->url;

                    $notifications_array[$id]['answer_id'] = $notification['answer_id'];
                    $notifications_array[$id]['type'] = $notification['type'];

                    /* Getting answer's information */
                    if ($notification['type'] == 'da') {
                        $answer = Answer::getById($notification['answer_id'], 1);
                        $notifications_array[$id]['answer'] = strip_tags($answer->answer);
                    }

                    /* Getting user's information */
                    $notifications_array[$id]['from_user'] = $notification['from_user'];
                    $from_user = User::getById($notification['from_user']);
                    $notifications_array[$id]['from_user_name'] = $from_user->name;
                    $notifications_array[$id]['from_user_username'] = $from_user->username;
                    $notifications_array[$id]['from_user_photo'] = $from_user->photo;

                    $notifications_array[$id]['created_at'] = $notification['created_at'];
                    $notifications_array[$id]['viewed'] = $notification['viewed'];
                }

                /* Load HTML template */
                $returned['notifications'] = View::getTemplate('Notifications/load.twig', [
                    'notifications' => $notifications_array,
                ]);
            }
            
            /* Return response */
            header('Content-Type: application/json');
            echo json_encode($returned['notifications']);
        } 


        /**
         * Set all notifications as viewed
         *
         * @return void
         */
        public function readallAction()
        {
            $return = 0;

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Checking if user is online */
            if ($user) {
                
                /* Setting Notifications as Read */
                if (Notification::setAllAsViewed($user->id)) {

                    $return = 1;
                }
            }
            
            /* Return response */
            header('Content-Type: application/json');
            echo json_encode($return);
        } 
    }