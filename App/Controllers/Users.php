<?php
	
	namespace App\Controllers;

    use Core\View;
    use App\Models\User;
    use App\Models\Topic;
    use App\Models\FollowedTopic;
    use App\Models\Question;
    use App\Models\Answer;
    use App\Auth;
    use App\Flash;
    use App\Paginator;
    use App\Upload;

    /**
     * Users Controller
     */
    class Users extends \Core\Controller
    {
        /* Users Stream Sorting type for questions and answers */
        public $sortTypeName = 'Newest';
        public $sortTypeColumn = 'id';


        /**
         * Before filter - checking if was change sorting type
         * If sorting type is changed - set new cookie
         *
         * @return void
         */
        public function before()
        {
            /* Checking if is changed sorting type */
            if (isset($_GET['sort_by'])) {

                /* Setting COOKIE with new sort type */
                setcookie('u_sort_by', $_GET['sort_by'], time() + (86400 * 30), "/");

                /* Redirecting to the same page */
                $this->redirect($_GET['url']);
            }

            /* Checking if COOKIE is set for Users Stream. Sort by cookie value or by default - by id */
            if (isset($_COOKIE['u_sort_by'])) {

                if ($_COOKIE['u_sort_by'] == 'voted') {

                    $this->sortTypeName = 'Most Voted';
                    $this->sortTypeColumn = 'votes';

                } else {
                    
                    $this->sortTypeName = 'Newest';
                    $this->sortTypeColumn = 'id';
                }
            }
        }


        /**
         * Load page which displays all users
         * URL: /users/all
         *
         * @return void
         */
        public function allAction()
        {
            /* Getting number of all users for pagination */
            $totalUsers = User::count();

            /* Adding pagination (50 users per page) */
            $paginator = new Paginator($_GET['page'] ?? 1, 50, $totalUsers, '/users/all?page=(:num)');

            /* Getting users per current page ordered by points DESC */
            $users = User::get([
                'offset'            => $paginator->offset, 
                'limit'             => $paginator->limit,
                'order_by'          => 'points',
                'order_type'        => 'DESC'
            ]);

            /* Displaying view template */
            View::renderTemplate('Users/all.twig',[
                'this_page'         => [
                    'title'    => 'All Users',
                    'menu'     => 'all_users',
                    'url'      => 'users/all',
                    'search_action' => 'users/search',
                    'search_type' => 'users',
                ],
                'users_number'      => $totalUsers,
                'users'             => $users,
                'paginator'         => $paginator,
            ]);
        }


        /**
         * Load page which displays all users by search
         * URL: /users/search?search=username
         *
         * @return void
         */
        public function searchAction()
        {
            $searched = $_GET['search'];

            /* Getting number of all users for pagination */
            $totalUsers = User::count([
                'search' => $searched,
            ]);

            /* Adding pagination (50 users per page) */
            $paginator = new Paginator($_GET['page'] ?? 1, 50, $totalUsers, '/users/search?search='.$searched.'&page=(:num)');

            /* Getting users per current page ordered by points DESC */
            $users = User::get([
                'offset'            => $paginator->offset, 
                'limit'             => $paginator->limit,
                'order_by'          => 'points',
                'order_type'        => 'DESC',
                'search'            => $searched,
            ]);

            /* Displaying view template */
            View::renderTemplate('Users/all.twig',[
                'this_page'         => [
                    'title'    => 'Search in Users',
                    'menu'     => 'search_users',
                    'url'      => 'users/search?search='.$searched,
                    'search_action' => 'users/search',
                    'search_type' => 'users',
                    'searched' => $searched,
                ],
                'users_number'      => $totalUsers,
                'users'             => $users,
                'paginator'         => $paginator,
            ]);
        }


        /**
         * Load user page
         * URLs: 
         * /user/{{ user.username }}
         * /user/{{ user.username }}/topics
         * /user/{{ user.username }}/answers
         * /user/{{ user.username }}/questions
         *
         * @return void
         */
        public function viewAction()
        {
            /* Getting user by username */
            $user = User::getByUsername($this->route_params['username']);

            /* Checking if user exists */
            if ($user) {

                /* Checking if user is active */
                if ($user->active == 1) {

                    /* Getting current user's data */
                    $current_user = Auth::getUser();

                    /* Getting total topics number for current user */
                    $allTopics = FollowedTopic::getAll($user->id);
                    $user->total_topics = count($allTopics);

                    /* Getting followed topic for signed in user, null otherwise */
                    $followed_topics = ($current_user) ? FollowedTopic::getAll($current_user->id) : null;

                    /* Getting total questions number for current user */
                    $totalQuestions = Question::count([
                        'own'       => 1,
                        'user_id'   => $user->id
                    ]);
                    $user->total_questions = $totalQuestions;

                    /* Getting total answers number for current user */
                    $totalAnswers = Answer::count([
                        'user_id' => $user->id,
                    ]);
                    $user->total_answers = $totalAnswers;

                    /* Loading user's stream with topics / questions / answers */
                    switch ($this->route_params['tab']) {

                        case 'questions':   

                            $url = '/user/'.$user->username.'/questions';

                            /* Adding pagination */
                            $paginator = new Paginator($_GET['page'] ?? 1, $this->config->per_page_user, $totalQuestions, $url.'?page=(:num)');

                            /* Getting questions per current page */
                            $stream = Question::get([
                                'own'          => 1,
                                'user_id'      => $user->id,
                                'offset'       => $paginator->offset, 
                                'limit'        => $paginator->limit,
                                'order_by'     => $this->sortTypeColumn,
                                'order_type'   => 'DESC',
                                'current_user' => $current_user
                            ]);
                            break;

                        case 'answers': 

                            $url = '/user/'.$user->username.'/answers';

                            /* Adding pagination */
                            $paginator = new Paginator($_GET['page'] ?? 1, $this->config->per_page_user, $totalAnswers, $url.'?page=(:num)');

                            /* Getting answers per current page */
                            $stream = Answer::get([
                                'user_id'               => $user->id,
                                'offset'                => $paginator->offset, 
                                'limit'                 => $paginator->limit,
                                'order_by'              => $this->sortTypeColumn,
                                'order_type'            => 'DESC',
                                'current_user'          => $current_user,
                                'including_question'    => 1
                            ]);
                            break;
                        
                        case 'topics':
                        default:

                            $url = '/user/'.$user->username.'/topics';

                            /* We do not need pagination here */
                            $paginator = null;

                            /* Getting questions per current page ordered by id DESC */
                            $stream = Topic::getByIds($allTopics);
                            break;
                    }

                    /* Displaying view template */
                    View::renderTemplate('Users/view.twig',[
                        'this_page'    => [
                            'title'         => $user->username,
                            'menu'          => '',
                            'url'           => $url,
                            'tab'           => $this->route_params['tab'],
                            'order_name'    => $this->sortTypeName,
                        ],
                        'user'         => $user,
                        'stream'       => $stream,
                        'paginator'    => $paginator,
                        'followed_topics' => $followed_topics,
                    ]);

                } else {

                    /* Displaying view template */
                    View::renderTemplate('Users/error.twig',[
                        'this_page'         => [
                            'title'    => 'User is not active',
                        ],
                    ]);
                }

            } else {

                /* Displaying view template */
                View::renderTemplate('Users/error.twig',[
                    'this_page'         => [
                        'title'    => 'User was not found',
                    ],
                ]);
            }
        }


        /**
         * Displaying the settings page
         * URL: /user/settings
         *
         * @return void
         */
        public function settingsAction()
        {
            /* Checking if user is signed in */
            $this->requireSignin();

            /* Getting current user's data */
            $current_user = Auth::getUser();

            /* Displaying view template */
            View::renderTemplate('Users/settings.twig', [
                //'app_settings'  => Config::getValues(),
                'user' => $current_user
            ]); 
        }


        /**
         * Save settings form
         *
         * @return void
         */
        public function updateAction()
        {
            /* Checking if user is signed in */
            $this->requireSignin();

            /* Getting current user's data */
            $current_user = Auth::getUser();

            /* Getting all settings from HTML form */
            $new_settings = $_POST;
            $new_settings['user_id'] = $current_user->id;

            /* Checking if file was upload for changing users's photo */
            if (is_uploaded_file($_FILES['photo']['tmp_name'])) {

                /* If was upload - creating Upload's object */
                $upload = new Upload($_FILES['photo']);
                $upload->subfolder = 'users/';

                /* Getting random filename */
                $fileName = $upload->getRandomFilename('jpg');

                /* Setting image's name */
                $upload->name = $fileName;

                /* Checking if file was upload. If was not - set error message */
                if ($upload->upload()) {

                    $new_settings['photo'] = $fileName;

                } else {

                    Flash::addMessage('Error! Image was not uploaded', Flash::INFO);
                }
            }

            $user = new User($new_settings);

            /* Trying to save settings */
            if ($user->update()) {

                /* Show successful message and refresh page */ 
                Flash::addMessage('Settings updated', Flash::INFO);

            } else {

                /* Show error message */
                Flash::addMessage("Error: ".implode("; ", $user->errors), Flash::INFO);
            }

            $this->redirect('/user/settings');
        }
    }