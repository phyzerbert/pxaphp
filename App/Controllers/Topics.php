<?php
	
	namespace App\Controllers;

    use Core\View;
    use App\Models\Topic;
    use App\Models\FollowedTopic;
    use App\Models\Question;
    use App\Auth;
    use App\Flash;
    use App\Paginator;

    /**
     * Topics Controller
     */
    class Topics extends \Core\Controller
    {
        /**
         * Load page which show all topics for selecting which one to follow
         * URL: /topics/select
         *
         * @return void
         */
        public function selectAction()
        {
            /* Check if user is signed in and redirect if is not */
            $this->requireSignin();

            /* Getting total number of topics */
            $totalTopics = Topic::count();

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Getting topics list ordered by followers DESC */
            $topics = Topic::get([
                'limit'             => $totalTopics,
                'order_by'          => 'followers',
                'order_type'        => 'DESC'
            ]);

            /* Displaying view template */
            View::renderTemplate('Topics/select.twig',[
                'this_page'         => [
                    'search_action' => 'topics/search',
                    'search_type' => 'topics',
                ],
                'topics_number'     => $totalTopics,
                'topics'            => $topics,
                'followed_topics'   => FollowedTopic::getAll($user->id),
            ]);
        }


        /**
         * Load page which show all topics (paginated)
         * URL: /topics/all
         *
         * @return void
         */
        public function allAction()
        {
            /* Getting number of all topics for pagination */
            $totalTopics = Topic::count();

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Adding pagination (50 topics per page) */
            $paginator = new Paginator($_GET['page'] ?? 1, 50, $totalTopics, '/topics/all?page=(:num)');

            /* Getting followed topic if user is signed in, null otherwise */
            $followed_topics = ($user) ? FollowedTopic::getAll($user->id) : null;

            /* Getting topics per current page ordered by followers DESC */
            $topics = Topic::get([
                'offset'            => $paginator->offset, 
                'limit'             => $paginator->limit,
                'order_by'          => 'followers',
                'order_type'        => 'DESC'
            ]);

            /* Displaying view template */
            View::renderTemplate('Topics/all.twig',[
                'this_page'         => [
                    'title'    => 'All Topics',
                    'menu'     => 'all_topics',
                    'url'      => '',
                    'search_action' => 'topics/search',
                    'search_type' => 'topics',
                ],
                'topics_number'     => $totalTopics,
                'topics'            => $topics,
                'paginator'         => $paginator,
                'followed_topics'   => $followed_topics,
            ]);
        }


        /**
         * Load page which show searched topics (paginated)
         * URL: /topics/search?search=topicname
         *
         * @return void
         */
        public function searchAction()
        {
            $searched = $_GET['search'];

            /* Getting number of all searched topics for pagination */
            $totalTopics = Topic::count([
                'search' => $searched,
            ]);

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Adding pagination (50 topics per page) */
            $paginator = new Paginator($_GET['page'] ?? 1, 50, $totalTopics, '/topics/search?search='.$searched.'&page=(:num)');

            /* Getting followed topic if user is signed in, null otherwise */
            $followed_topics = ($user) ? FollowedTopic::getAll($user->id) : null;

            /* Getting topics per current page ordered by followers DESC */
            $topics = Topic::get([
                'offset'            => $paginator->offset, 
                'limit'             => $paginator->limit,
                'order_by'          => 'followers',
                'order_type'        => 'DESC',
                'search'            => $searched,
            ]);

            /* Displaying view template */
            View::renderTemplate('Topics/all.twig',[
                'this_page'         => [
                    'title'    => 'Search in Topics',
                    'menu'     => 'search_topics',
                    'url'      => '',
                    'search_action' => 'topics/search?search='.$searched,
                    'search_type' => 'topics',
                    'searched' => $searched,
                ],
                'topics_number'     => $totalTopics,
                'topics'            => $topics,
                'paginator'         => $paginator,
                'followed_topics'   => $followed_topics,
            ]);
        }


        /**
         * Load page with questions per topic
         * URL: /topic/{{ topic.url }}
         *
         * @return void
         */
        public function viewAction()
        {
            /* Getting topic by URL */
            $topic = Topic::getByURL($this->route_params['url']);

            /* Checking if topic exists */
            if ($topic) {

                /* Getting current user's data */
                $user = Auth::getUser();

                /* Check if user is following this topic, null if signed out */
                $is_following = ($user) ? FollowedTopic::isFollowing($user->id, $topic->id) : null;

                /* Getting total questions number for current topic */
                $totalQuestions = Question::count([
                    'topics'            => [ $topic->id ]
                ]);

                /* Adding pagination */
                $paginator = new Paginator($_GET['page'] ?? 1, $this->config->per_page_user, $totalQuestions, '/topic/'.$topic->url.'?page=(:num)');

                /* Getting questions per current page ordered by id DESC */
                $questions = Question::get([
                    'offset'            => $paginator->offset, 
                    'limit'             => $paginator->limit,
                    'order_by'          => 'id',
                    'order_type'        => 'DESC',
                    'topics'            => [ $topic->id ],
                    'current_user'      => $user
                ]);

                /* Displaying view template */
                View::renderTemplate('Topics/view.twig',[
                    'this_page'         => [
                        'title'    => $topic->title,
                        'menu'     => '',
                        'url'      => '',
                    ],
                    'topic'             => $topic,
                    'is_following'      => $is_following,
                    'questions'         => $questions,
                    'paginator'         => $paginator,
                    'total_questions'   => $totalQuestions,
                ]);

            } else {

                /* Displaying view template */
                View::renderTemplate('Topics/error.twig',[
                    'this_page'         => [
                        'title'    => 'Topic was not found',
                        'menu'     => '',
                        'url'      => '',
                    ],
                ]);
            }
        }


        /**
         * Save more topics (when user is following more topics at the same time)
         * from topics/select page
         *
         * @return void
         */
        public function saveAction()
        {
            /* Check if user is signed in and redirect if is not */
            $this->requireSignin();

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Set choosen topics as followed for current user */
            FollowedTopic::followMore($user->id, $_POST['topics']);

            /* Show message that followed topics was update */
            Flash::addMessage('Topics list updated. Now you will see questions only from these topics in questions feed.', Flash::INFO);

            /* Redirect to stream */
            $this->redirect('/');
        } 


        /**
         * Return all topics in JSON format.
         * used when someone add new question
         *
         * @return void
         */
        public function getAction()
        {
            /* Getting number of all topics for loading all from database */
            $totalTopics = Topic::count();

            /* Getting all topics sorted by followers DESC */
            $topics = Topic::get([
                'limit'             => $totalTopics,
                'order_by'          => 'followers',
                'order_type'        => 'DESC'
            ]);

            /* Initialize return array */
            $return = [];

            /* Filtering data which will be returned */
            foreach ($topics as $topic) {

                $array['id'] = $topic['id'];
                $array['title'] = $topic['title'];
                $array['description'] = $topic['description'];
                $array['image'] = $topic['image'];
                $array['url'] = $topic['url'];
                $array['followers'] = $topic['followers'];
                $array['questions'] = $topic['questions'];

                array_push($return, $array);
            }
            
            /* Returning JSON with topics */
            header('Content-Type: application/json');
            echo json_encode($return);
        } 


        /**
         * Follow topics by AJAX function.
         *
         * @return void
         */
        public function followAction()
        {
            $return = false;

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Checking if user is online and topic id > 0 */
            if ($user && $_GET['id'] > 0) {
                
                /* Follow topic and set $return=true */
                if (FollowedTopic::follow($user->id, $_GET['id'])) {
                    $return = true;
                }
            }
            
            /* Return response */
            header('Content-Type: application/json');
            echo json_encode($return);
        } 


        /**
         * Unfollow topics by AJAX function.
         *
         * @return void
         */
        public function unfollowAction()
        {
            $return = false;

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Checking if user is online and topic id > 0 */
            if ($user && $_GET['id'] > 0) {
                
                /* Unfollow topic and set $return=true */
                if (FollowedTopic::unfollow($user->id, $_GET['id'])) {
                    $return = true;
                }
            }
            
            /* Return response */
            header('Content-Type: application/json');
            echo json_encode($return);
        }  
    }