<?php
	
	namespace App\Controllers;

    use Core\View;
    use App\Models\User;
    use App\Models\Question;
    use App\Models\QuestionImage;
    use App\Models\QuestionTopic;
    use App\Models\FollowedTopic;
    use App\Models\FollowedQuestion;
    use App\Models\HiddenQuestion;
    use App\Models\VotedQuestion;
    use App\Models\Answer;
    use App\Models\VotedAnswer;
    use App\Models\Notification;
    use App\Models\Report;
    use App\Auth;
    use App\Flash;
    use App\Upload;
    use App\Paginator;

    /**
     * Questions Controller
     */
    class Questions extends \Core\Controller
    {
        /* Question's sorting type */
        public $sortTypeName = 'Newest';
        public $sortTypeColumn = 'id';

        /* Answer's sorting type */
        public $sortAnswersTypeName = 'Newest';
        public $sortAnswersTypeColumn = 'id';


        /**
         * Before filter - checking if was change sorting type
         * If sort type is changed - set new cookie
         *
         * @return void
         */
        public function before()
        {
            /* Checking if was change sorting type */
            if (isset($_GET['sort_by'])) {

                /* Setting new sorting type for questions */
                if ($_GET['sort_type'] == 'questions') {

                    /* Setting COOKIE with new sort type */
                    setcookie('sort_by', $_GET['sort_by'], time() + (86400 * 30), "/");

                    /* Redirecting to the same page */
                    $this->redirect('/'.$this->route_params['controller'].'/'.$this->route_params['action']);

                } else if ($_GET['sort_type'] == 'answers') {

                    /* Setting COOKIE with new sort type */
                    setcookie('a_sort_by', $_GET['sort_by'], time() + (86400 * 30), "/");

                    /* Redirecting to the same page */
                    $this->redirect($_GET['url']);
                }
            }

            /* Checking if COOKIE is set for Questions. Sort by cookie value or "id" by default */
            if (isset($_COOKIE['sort_by'])) {

                if ($_COOKIE['sort_by'] == 'voted') {

                    $this->sortTypeName = 'Most Voted';
                    $this->sortTypeColumn = 'votes';

                } else if ($_COOKIE['sort_by'] == 'viewed') {

                    $this->sortTypeName = 'Most Viewed';
                    $this->sortTypeColumn = 'views';
                
                } else {
                    
                    $this->sortTypeName = 'Newest';
                    $this->sortTypeColumn = 'id';
                }
            }

            /* Checking if COOKIE is set for Answers. Sort by cookie value or "id" by default */
            if (isset($_COOKIE['a_sort_by'])) {

                if ($_COOKIE['a_sort_by'] == 'voted') {

                    $this->sortAnswersTypeName = 'Most Voted';
                    $this->sortAnswersTypeColumn = 'votes';

                } else {
                    
                    $this->sortAnswersTypeName = 'Newest';
                    $this->sortAnswersTypeColumn = 'id';
                }
            }
        }


        /**
         * Show the stream page
         * URL: /
         *
         * @return void
         */
        public function indexAction()
        {
            /* Getting current user's data */
            $user = Auth::getUser();

            /* Checking if user is online */
            if ($user) {

                /* If user is online - load feeds page */
                $this->feedAction();

            } else {

                /* If user is not online - load all questions page */
                $this->allAction();
            }
        }


        /**
         * Show the all questons page
         * URL: /questions/all
         *
         * @return void
         */
        public function allAction()
        {
            /* Getting total questions number */
            $totalQuestions = Question::count();

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Adding pagination */
            $paginator = new Paginator($_GET['page'] ?? 1, $this->config->per_page_user, $totalQuestions, '/questions/all?page=(:num)');

            /* Getting questions list */
            $questions = Question::get([
                'offset'       => $paginator->offset, 
                'limit'        => $paginator->limit,
                'order_by'     => $this->sortTypeColumn,
                'order_type'   => 'DESC',
                'current_user' => $user
            ]);

            /* Load view template */
            View::renderTemplate('Questions/stream.twig',[
                'this_page'           => [
                    'title'        => 'All Questions',
                    'menu'         => 'questions_all',
                    'url'          => 'questions/all',
                    'order_name'   => $this->sortTypeName,
                ],
                'questions'           => $questions,
                'paginator'           => $paginator,
                'total_questions'     => $totalQuestions,
            ]);
        }


        /**
         * Show the search questons page
         * URL: /questions/search?search=search_anything
         *
         * @return void
         */
        public function searchAction()
        {
            $searched = $_GET['search'];

            /* Getting total questions number */
            $totalQuestions = Question::count([
                'search' => $searched,
            ]);

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Adding pagination */
            $paginator = new Paginator($_GET['page'] ?? 1, $this->config->per_page_user, $totalQuestions, '/questions/search?search='.$searched.'&page=(:num)');

            /* Getting questions list */
            $questions = Question::get([
                'offset'       => $paginator->offset, 
                'limit'        => $paginator->limit,
                'order_by'     => $this->sortTypeColumn,
                'order_type'   => 'DESC',
                'current_user' => $user,
                'search'       => $searched,
            ]);

            /* Load view template */
            View::renderTemplate('Questions/stream.twig',[
                'this_page'           => [
                    'title'        => 'Search in Questions',
                    'menu'         => 'questions_search',
                    'url'          => 'questions/search?search='.$searched,
                    'order_name'   => $this->sortTypeName,
                    'searched'     => $searched,
                ],
                'questions'           => $questions,
                'paginator'           => $paginator,
                'total_questions'     => $totalQuestions,
            ]);
        }


        /**
         * Show the feed stream page
         * URL: /questions/feed
         *
         * @return void
         */
        public function feedAction()
        {
            /* Check if user is signed in and redirect if is not */
            $this->requireSignin();

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Getting topics followed by user */
            $followedTopics = FollowedTopic::getAll($user->id);

            /**
             * Checking if user is following at least one topic.
             * If he does not - redirect to follow topics page
             */
            if (count($followedTopics) == 0) {

                Flash::addMessage('Choose which topics are interesting for you before starting', Flash::INFO);

                $this->redirect('/topics/select');
            }

            /* Getting total questions number for followed topics */
            $totalQuestions = Question::count([
                'topics'        => $followedTopics,
                'current_user'  => $user
            ]);

            /* Adding pagination */
            $paginator = new Paginator($_GET['page'] ?? 1, $this->config->per_page_user, $totalQuestions, '/questions/feed?page=(:num)');

            /* Getting questions list */
            $questions = Question::get([
                'offset'       => $paginator->offset, 
                'limit'        => $paginator->limit,
                'order_by'     => $this->sortTypeColumn,
                'order_type'   => 'DESC',
                'topics'       => $followedTopics,
                'current_user' => $user
            ]);

            /* Load view template */
            View::renderTemplate('Questions/stream.twig',[
                'this_page'           => [
                    'title'        => 'Your Questions Feed',
                    'menu'         => 'questions_feed',
                    'url'          => 'questions/feed',
                    'order_name'   => $this->sortTypeName,
                ],
                'questions'           => $questions,
                'paginator'           => $paginator,
                'total_questions'     => $totalQuestions,
            ]);
        }


        /**
         * Show the stream page with followed questions
         * URL: /questions/followed
         *
         * @return void
         */
        public function followedAction()
        {
            /* Check if user is signed in and redirect if is not */
            $this->requireSignin();

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Getting total questions number for followed topics */
            $totalQuestions = Question::count([
                'followed' => $user->id
            ]);

            /* Adding pagination */
            $paginator = new Paginator($_GET['page'] ?? 1, $this->config->per_page_user, $totalQuestions, '/questions/followed?page=(:num)');

            /* Getting questions list */
            $questions = Question::get([
                'offset'       => $paginator->offset, 
                'limit'        => $paginator->limit,
                'order_by'     => $this->sortTypeColumn,
                'order_type'   => 'DESC',
                'followed'     => $user->id,
                'current_user' => $user
            ]);

            /* Load view template */
            View::renderTemplate('Questions/stream.twig',[
                'this_page'           => [
                    'title'        => 'Your Followed Questions',
                    'menu'         => 'questions_followed',
                    'url'          => 'questions/followed',
                    'order_name'   => $this->sortTypeName,
                ],
                'questions'           => $questions,
                'paginator'           => $paginator,
                'total_questions'     => $totalQuestions,
            ]);
        }


        /**
         * Show the feed stream page with unanswered questions
         * URL: /questions/without-answer
         *
         * @return void
         */
        public function withoutAnswerAction()
        {
            /* Check if user is signed in and redirect if is not */
            $this->requireSignin();

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Getting topics followed by user */
            $followedTopics = FollowedTopic::getAll($user->id);

            /**
             * Checking if user is following at least one topic.
             * If he does not - redirect to follow topics page
             */
            if (count($followedTopics) == 0) {

                Flash::addMessage('Choose which topics are interesting for you before starting', Flash::INFO);

                $this->redirect('/topics/select');
            }

            /* Getting total questions number for followed topics */
            $totalQuestions = Question::count([
                'topics' => $followedTopics, 
                'unanswered' => 1
            ]);

            /* Adding pagination */
            $paginator = new Paginator($_GET['page'] ?? 1, $this->config->per_page_user, $totalQuestions, '/questions/without-answer?page=(:num)');

            /* Getting questions list */
            $questions = Question::get([
                'unanswered'   => 1,
                'offset'       => $paginator->offset, 
                'limit'        => $paginator->limit,
                'order_by'     => $this->sortTypeColumn,
                'order_type'   => 'DESC',
                'topics'       => $followedTopics,
                'current_user' => $user
            ]);

            /* Load view template */
            View::renderTemplate('Questions/stream.twig',[
                'this_page'           => [
                    'title'        => 'Questions without answers',
                    'menu'         => 'questions_unanswered',
                    'url'          => 'questions/without-answer',
                    'order_name'   => $this->sortTypeName,
                ],
                'questions'           => $questions,
                'paginator'           => $paginator,
                'total_questions'     => $totalQuestions,
            ]);
        }


        /**
         * Show the feed stream page with own questions
         * URL: /questions/own
         *
         * @return void
         */
        public function ownAction()
        {
            /* Check if user is signed in and redirect if is not */
            $this->requireSignin();

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Getting total questions number for followed topics */
            $totalQuestions = Question::count([
                'own'       => 1,
                'user_id'   => $user->id
            ]);

            /* Adding pagination */
            $paginator = new Paginator($_GET['page'] ?? 1, $this->config->per_page_user, $totalQuestions, '/questions/own?page=(:num)');

            /* Getting questions list */
            $questions = Question::get([
                'own'          => 1,
                'user_id'      => $user->id,
                'offset'       => $paginator->offset, 
                'limit'        => $paginator->limit,
                'order_by'     => $this->sortTypeColumn,
                'order_type'   => 'DESC',
                'current_user' => $user
            ]);

            /* Load view template */
            View::renderTemplate('Questions/stream.twig',[
                'this_page'           => [
                    'title'        => 'Your own questions',
                    'menu'         => 'questions_own',
                    'url'          => 'questions/own-answer',
                    'order_name'   => $this->sortTypeName,
                ],
                'questions'           => $questions,
                'paginator'           => $paginator,
                'total_questions'     => $totalQuestions,
            ]);
        }


        /**
         * Ask new question function
         * Redirect to new asked question's page
         *
         * @return void
         */
        public function askAction()
        {
            /* Checking if user is signed in to be allowed to add new question */
            $this->requireSignin();

            /* Creating new Question's object */
            $question = new Question($_POST);

            /* Getting user's id and set it as question's user_id */
            $user = Auth::getUser();
            $question->user_id = $user->id;

            /* Checking if user has enough points. If not - redirect to stream page and show error message */
            $questionPrice = $this->config->question_price;
            if ($user->points < $questionPrice) {

                /* Show error message and redirect to stream page */
                Flash::addMessage('You do not have enough points to add new question. You need to have at least '.$questionPrice.' points to add new question.', Flash::INFO);
                
                $this->redirect('/');
            }

            /* Adding new question */
            $questionId = $question->add();

            /* Check if question was add */
            if ($questionId > 0) {

                /* Adding mention notifications */
                $mention_number = 0;
                preg_match_all('/(^|\s)(@\w+)/', $question->description, $mentions);

                foreach($mentions[2] as $mention) {

                    /* Allow max 10 mentions */
                    if ($mention_number < 10) {

                        /* Check if user exists */
                        $this_user = User::getByUsername(ltrim($mention, '@'));
                        if ($this_user) {

                            Notification::add([
                                'to_user' => $this_user->id,
                                'question_id' => $questionId,
                                'type' => 'nm',
                                'from_user' => $question->user_id,
                            ]);
                        }
                    }
                }

                /* Adding topics for question */
                $topics = new QuestionTopic();
                foreach ($question->topics as $topicId) {
                    $topics->add($questionId, $topicId);
                }

                /* Remove points from user's account for new question */
                User::removePoints($user->id, $questionPrice);

                /* Checking if user attached images for question */
                if (count($_FILES['images']['name']) > 0) {

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
                            $question_image->add($questionId, $upload->name);
                        }
                    }
                }

            } else {
                
                /* Redirect to stream page and show error messages */
                Flash::addMessage("Error: ".implode(", ", $question->errors), Flash::INFO);

                $this->redirect('/');
            }

            /* Redirect to question page */
            $this->redirect("/question/".$question->url);
        }


        /**
         * Vote question by AJAX.
         *
         * @return void
         */
        public function voteAction()
        {
            $return = 0;

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Checking if user is online and question_id > 0 */
            if ($user && $_GET['id'] > 0) {
                
                /* Vote question and return number of votes */
                $return = VotedQuestion::vote($user->id, $_GET['id']);
            }
            
            /* Return response */
            header('Content-Type: application/json');
            echo json_encode($return);
        } 


        /**
         * Unvote question by AJAX.
         *
         * @return void
         */
        public function unvoteAction()
        {
            $return = 0;

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Checking if user is online and question_id > 0 */
            if ($user && $_GET['id'] > 0) {
                
                /* Unvote question and return number of votes */
                $return = VotedQuestion::unvote($user->id, $_GET['id']);
            }
            
            /* Return response */
            header('Content-Type: application/json');
            echo json_encode($return);
        } 


        /**
         * Follow question by AJAX.
         *
         * @return void
         */
        public function followAction()
        {
            $return = false;

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Checking if user is online and question_id > 0 */
            if ($user && $_GET['id'] > 0) {
                
                /* Follow question and set $return = true */
                if (FollowedQuestion::follow($user->id, $_GET['id'])) {
                    $return = true;
                }
            }
            
            /* Return response */
            header('Content-Type: application/json');
            echo json_encode($return);
        } 


        /**
         * Unfollow question by AJAX.
         *
         * @return void
         */
        public function unfollowAction()
        {
            $return = false;

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Checking if user is online and question_id > 0 */
            if ($user && $_GET['id'] > 0) {
                
                /* Unfollow question and set $return=true */
                if (FollowedQuestion::unfollow($user->id, $_GET['id'])) {
                    $return = true;
                }
            }
            
            /* Return response */
            header('Content-Type: application/json');
            echo json_encode($return);
        }


        /**
         * Delete question function
         * After deleting redirect to stream page
         *
         * @return void
         */
        public function deleteAction()
        {
            /* Check if user is signed in and redirect if is not */
            $this->requireSignin();

            /* Getting current user */
            $user = Auth::getUser();

            /* Redirect link after deleting. By default index page */
            $returnUrl = (isset($_POST['return'])) ? '/'.$_POST['return'] : '/';

            /* Getting Question by id */
            $question = Question::getById(intval($_POST['id']));

            /* If Question exists and is active */
            if ($question) {

                /* Checking if signed in user is question's author */
                if ($question->user_id == $user->id) {

                    /* Check if question was delete */
                    if (Question::delete($question->id)) {

                        Flash::addMessage("Question deleted", Flash::INFO);
                    
                    } else {

                        Flash::addMessage("You can not delete this question", Flash::INFO);
                    }

                } else {

                    Flash::addMessage("You can not delete this question", Flash::INFO);
                }

            } else {

                Flash::addMessage("You can not delete this question", Flash::INFO);
            }

            /* Redirect back */
            $this->redirect($returnUrl);
        }


        /**
         * Close question by AJAX.
         *
         * @return void
         */
        public function closeAction()
        {
            $return = false;

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Getting Question by id */
            $question = Question::getById(intval($_GET['id']));

            /* Checking if user is question's owner */
            if ($user->id == $question->user_id) {
                
                /* Close question and set $return=true */
                if (Question::close($question->id)) {

                    /* Send notifications to users who follow question that question was closed */
                    $followers = FollowedQuestion::getFollowers($question->id);
                    foreach ($followers as $follower) {

                        Notification::add([
                            'to_user' => $follower['user_id'],
                            'question_id' => $question->id,
                            'type' => 'cq',
                            'from_user' => $user->id,
                        ]);
                    }

                    $return = true;
                }
            }
            
            /* Return response */
            header('Content-Type: application/json');
            echo json_encode($return);
        } 


        /**
         * Hide question function
         * After hiding redirect to stream page
         *
         * @return void
         */
        public function hideAction()
        {
            /* Check if user is signed in and redirect if is not */
            $this->requireSignin();

            /* Getting current user */
            $user = Auth::getUser();

            /* Redirect link after deleting. By default index page */
            $returnUrl = (isset($_POST['return'])) ? '/'.$_POST['return'] : '/';

            /* Getting Question by id */
            $question = Question::getById(intval($_POST['id']));

            /* If Question exists and is active */
            if ($question) {

                /* Checking if signed in user is not question's author, as you can not hide own question */
                if ($question->user_id <> $user->id) {

                    /* Check if question was hide */
                    if (HiddenQuestion::hide($user->id, $question->id)) {

                        Flash::addMessage("Question was hide for your account", Flash::INFO);
                    
                    } else {

                        Flash::addMessage("You can not hide this question", Flash::INFO);
                    }

                } else {

                    Flash::addMessage("You can not hide this question", Flash::INFO);
                }

            } else {

                Flash::addMessage("You can not hide this question", Flash::INFO);
            }

            /* Redirect back */
            $this->redirect($returnUrl);
        }


        /**
         * Load page with question and all answers for that question
         * URL: /question/{{ question.url }}
         *
         * @return void
         */
        public function viewAction()
        {
            /* Getting question by URL */
            $question = Question::getByUrl($this->route_params['url']);

            /* Checking if question exists */
            if ($question) {

                /* Adding mentions as links */
                $question->description = preg_replace('/@([^@ ]+)/', '<a href="/user/$1" target="_blank">@$1</a> ', $question->description);

                /* Increment views number for current question */
                Question::addView($question->id);
                $question->views++;

                /* Getting question's topics */
                $question->topics = QuestionTopic::get($question->id);

                /* Getting question's author user */
                $question->author = User::getById($question->user_id, ['id', 'username', 'photo']);

                /* Getting question's images attachments */
                $question->images = QuestionImage::get($question->id);

                /* Getting current user's data */
                $user = Auth::getUser();

                /* Check if user is following this question, null if signed out */
                $is_following = ($user) ? FollowedQuestion::isFollowing($user->id, $question->id) : null;

                /* Check if user voted this question, null if signed out */
                $is_voted = ($user) ? VotedQuestion::isVoted($user->id, $question->id) : null;

                /* Getting total answers number for current question */
                $totalAnswers = Answer::count([
                    'question_id' => $question->id
                ]);

                /* Checking if was requested page with only one answer */
                if (isset($_GET['answer_id']) && intval($_GET['answer_id']) > 0) {

                    $oneAnswer = 1;

                    $paginator = null;

                    $answer = Answer::getById($_GET['answer_id']);

                    /* Adding lightbox for images */
                    $answer->answer = preg_replace('/(<img [^>]*src="([^"]*)"[^>]*)>/i', '<a href="$2" class="image-link">$1 class="img-thumbnail"></a>', $answer->answer);

                    /* Adding mentions links */
                    $answer->answer = preg_replace('/(<span class="mention" [^>]*value="([^"]*)"[^>]*)>(.*?)<\/span>﻿<\/span>/i', '<a href="/user/$2" class="mention" target="_blank">@$2</a>', $answer->answer);
                    
                    /* Getting answer's author user */
                    $answer->author = User::getById($answer->user_id, ['id', 'username', 'photo', 'points']);

                    /* Check if user voted this answer, null if signed out */
                    $a_is_voted = ($user) ? VotedAnswer::isVoted($user->id, $answer->id) : null;
                    $answer->is_voted = $a_is_voted['value'];

                    $answers[] = $answer;

                } else {

                    $oneAnswer = 0;

                    /* Adding pagination */
                    $paginator = new Paginator($_GET['page'] ?? 1, $this->config->per_page_user, $totalAnswers, '/question/'.$question->url.'?page=(:num)');

                    /* Getting answers per current page ordered by id DESC */
                    $answers = Answer::get([
                        'offset'            => $paginator->offset, 
                        'limit'             => $paginator->limit,
                        'order_by'          => $this->sortAnswersTypeColumn,
                        'order_type'        => 'DESC',
                        'question_id'       => $question->id,
                        'current_user'      => $user
                    ]);
                }

                /* Loading view template */
                View::renderTemplate('Questions/view.twig',[
                    'this_page'         => [
                        'title'      => 'Menu',
                        'menu'       => '',
                        'url'        => '/question/'.$question->url,
                        'one_answer' => $oneAnswer,
                        'order_name' => $this->sortAnswersTypeName,
                    ],
                    'question'          => $question,
                    'is_following'      => $is_following,
                    'is_voted'          => $is_voted,
                    'answers'           => $answers,
                    'paginator'         => $paginator,
                    'total_answers'     => $totalAnswers,
                ]);

            } else {

                /* Loading view template */
                View::renderTemplate('Questions/error.twig',[
                    'this_page'         => [
                        'title'    => 'Question was not found',
                        'menu'     => '',
                        'url'      => '',
                    ],
                ]);
            }
        }


        /**
         * Get question by AJAX.
         *
         * @return void
         */
        public function getAction()
        {
            $return = false;

            /* Getting Question by id */
            $question = Question::getById(intval($_GET['id']));

            /* Getting question's topics */
            $question->topics = QuestionTopic::get($question->id);
            
            /* Return response */
            header('Content-Type: application/json');
            echo json_encode($question);
        }


        /**
         * Edit question function
         * Redirect to edited question's page
         *
         * @return void
         */
        public function editAction()
        {
            /* Checking if user is signed in to be allowed to edit question */
            $this->requireSignin();

            /* Getting current user */
            $user = Auth::getUser();
            
            /* Getting Question by id */
            $this_question = Question::getById(intval($_POST['question_id']));

            /* Checking if user is question's author */
            if ($this_question->user_id == $user->id) {

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

            } else {

                /* Redirect to stream page and show error messages */
                Flash::addMessage("Error: You can not edit this question.", Flash::INFO);

                $this->redirect('/');
            }

            /* Redirect to question page */
            $this->redirect("/question/".$this_question->url);
        }


        /**
         * Report question function
         * Redirect to previous page
         *
         * @return void
         */
        public function reportAction()
        {
            /* Checking if user is signed in to be allowed to report question */
            $this->requireSignin();

            /* Getting current user */
            $user = Auth::getUser();
            
            /* Getting Question by id */
            $this_question = Question::getById(intval($_POST['question_id']));

            /* Checking if user is online and question is not deleted already */
            if ($user->id > 0 && $this_question->active > 0) {

                /* Creating new Report's object */
                $report = new Report([
                    'from_user' => $user->id,
                    'question_id' => $this_question->id,
                    'message' => $_POST['report_message'],
                ]);

                /* Adding new report */
                if ($report->add()) {

                    /* Show success message */
                    Flash::addMessage("Done: Report sent!", Flash::INFO);
                    
                } else {
                
                    /* Show error messages */
                    Flash::addMessage("Error: ".implode(", ", $report->errors), Flash::INFO);
                }

            } else {

                /* Show error messages */
                Flash::addMessage("Error: You can not report this question.", Flash::INFO);
            }

            /* Redirect to previous page */
            if (isset($_REQUEST["destination"])) {

                header("Location: ".$_REQUEST['redirect_url']);

            } else if (isset($_SERVER["HTTP_REFERER"])) {

                header("Location: ".$_SERVER["HTTP_REFERER"]);
            
            } else {

                $this->redirect('/');
            }
        }
    }