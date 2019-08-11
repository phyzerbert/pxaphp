<?php
	
	namespace App\Controllers;

    use Core\View;
    use App\Models\User;
    use App\Models\Question;
    use App\Models\Answer;
    use App\Models\VotedAnswer;
    use App\Models\Notification;
    use App\Models\FollowedQuestion;
    use App\Models\Report;
    use App\Auth;
    use App\Flash;
    use App\Upload;
    use App\Paginator;

    /**
     * Answers Controller
     */
    class Answers extends \Core\Controller
    {
        /**
         * Add new answer function
         * Redirect to question's page where answer was add
         *
         * @return void
         */
        public function addAction()
        {
            /* Checking if user is signed in to be allowed to add new answer */
            $this->requireSignin();

            /* Getting Question by id */
            $question = Question::getById(intval($_POST['question_id']));

            if ($question) {

                /* Checking if question is not closed */
                if ($question->is_closed == 0 && $question->active == 1) {

                    /* Creating new Answer's object */
                    $answer = new Answer($_POST);

                    /* Checking if answer is not empty */
                    if (strlen($answer->answer) < 2) {

                        /* Show error message and redirect to question's page */
                        Flash::addMessage('You can not add emty answer', Flash::INFO);
                    }

                    /* Getting user's id and set it as question's user_id */
                    $user = Auth::getUser();
                    $answer->user_id = $user->id;

                    $answerId = $answer->add();

                    if ($answerId) {

                        /* Send notification to question's author */
                        Notification::add([
                            'to_user' => $question->user_id,
                            'question_id' => $question->id,
                            'answer_id' => $answerId,
                            'type' => 'na',
                            'from_user' => $answer->user_id,
                        ]);

                        /* Send notifications to users who are following question */
                        $followers = FollowedQuestion::getFollowers($question->id);
                        foreach ($followers as $follower) {

                            Notification::add([
                                'to_user' => $follower['user_id'],
                                'question_id' => $question->id,
                                'answer_id' => $answerId,
                                'type' => 'na',
                                'from_user' => $answer->user_id,
                            ]);
                        }

                        /* Send notifications to mentioned users */
                        $mention_number = 0;
                        preg_match_all('/(<span class="mention" [^>]*value="([^"]*)"[^>]*)>(.*?)<\/span>﻿<\/span>/i', $answer->answer, $mentions);

                        foreach($mentions[2] as $mention) {

                            /* Allow max 10 mentions */
                            if ($mention_number < 10) {

                                /* Check if user exists */
                                $this_user = User::getByUsername($mention);

                                if ($this_user) {

                                    Notification::add([
                                        'to_user' => $this_user->id,
                                        'question_id' => $question->id,
                                        'answer_id' => $answerId,
                                        'type' => 'nm',
                                        'from_user' => $answer->user_id,
                                    ]);
                                }
                            }
                        }

                        /* Redirect to question's page and show success message */
                        Flash::addMessage('Answer added!', Flash::INFO);
                        $this->redirect('/question/'.$question->url.'#answer_'.$answerId);

                    } else {
                    
                        /* Redirect to question's page and show error messages */
                        Flash::addMessage("Error: ".implode(", ", $answer->errors), Flash::INFO);
                    }

                } else {
                    
                    /* Redirect to question's page and show error message */
                    Flash::addMessage('This question is closed for new answers.', Flash::INFO);
                }

                $this->redirect('/question/'.$question->url);

            } else {

                /* Redirect to stream page and show error messages */
                Flash::addMessage('This question is unavailable.', Flash::INFO);
                $this->redirect('/');
            }
        }


        /** 
         * Function for uploading image for answer
         * from Quill editor
         *
         * @return json with image url or false if image was not upload
         */
        public function uploadImage()
        {
            /* Getting current user's data */
            $user = Auth::getUser();

            /* Checking if user is online */
            if ($user) {

                /* Checking if image was upload */
                if (is_uploaded_file($_FILES['image']['tmp_name'])) {

                    /* Creating new Upload's object */
                    $upload = new Upload([
                        'subfolder' => 'answers/',
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
                        echo json_encode('/media/images/answers/'.$upload->name);
                    }
                }
            }

            return false;
        }


        /**
         * Function for voting answer by AJAX.
         *
         * @return void
         */
        public function voteAction()
        {
            $return = 0;

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Checking if user is online and answer_id > 0 */
            if ($user && $_GET['id'] > 0) {
                
                /* Vote answer and return number of votes */
                $return = VotedAnswer::vote($user->id, $_GET['id']);
            }
            
            /* Return response */
            header('Content-Type: application/json');
            echo json_encode($return);
        } 


        /**
         * Function for unvoting answer by AJAX.
         *
         * @return void
         */
        public function unvoteAction()
        {
            $return = 0;

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Checking if user is online and answer_id > 0 */
            if ($user && $_GET['id'] > 0) {
                
                /* Unvote answer and return number of votes */
                $return = VotedAnswer::unvote($user->id, $_GET['id']);
            }
            
            /* Return response */
            header('Content-Type: application/json');
            echo json_encode($return);
        } 


        /**
         * Delete answer function
         * After deleting redirect to question's page
         *
         * @return void
         */
        public function deleteAction()
        {
            /* Check if user is signed in and redirect to sign in if is not */
            $this->requireSignin();

            /* Getting current user's data */
            $user = Auth::getUser();

            /* Redirect link after deleting. By default index page */
            $returnUrl = (isset($_POST['return'])) ? '/'.$_POST['return'] : '/';

            /* Getting Question by id */
            $answer = Answer::getById(intval($_POST['id']));

            /* If Answer exists and is active */
            if ($answer) {

                /* Checking if signed in user is answer's author */
                if ($answer->user_id == $user->id) {

                    /* Check if question deleted */
                    if (Answer::delete($answer->id)) {

                        Flash::addMessage("Answer deleted!", Flash::INFO);
                    
                    } else {

                        Flash::addMessage("You can not delete this answer!", Flash::INFO);
                    }

                } else {

                    Flash::addMessage("You can not delete this answer!", Flash::INFO);
                }

            } else {

                Flash::addMessage("You can not delete this answer!", Flash::INFO);
            }

            /* Redirect back */
            $this->redirect($returnUrl);
        }


        /**
         * Function which returns answer's content by AJAX.
         *
         * @return void
         */
        public function getAction()
        {
            $return = false;

            /* Getting Answer by id */
            $answer = Answer::getById(intval($_GET['id']));
            
            /* Return response */
            header('Content-Type: application/json');
            echo json_encode($answer);
        }


        /**
         * Edit answer function
         * Redirect to question's page
         *
         * @return void
         */
        public function editAction()
        {
            /* Checking if user is signed in to be allowed to edit answer */
            $this->requireSignin();

            /* Getting user's id */
            $user = Auth::getUser();
            
            /* Getting Answer by id */
            $this_answer = Answer::getById(intval($_POST['answer_id']));

            /* Getting Question by id for redirecting back after editing */
            $this_question = Question::getById($this_answer->question_id);

            /* Checking if user is answer's author */
            if ($this_answer->user_id == $user->id) {

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

            } else {

                /* Redirect to question's page and show error messages */
                Flash::addMessage("Error: You can not edit this answer.", Flash::INFO);
            }

            /* Redirect to question page */
            $this->redirect("/question/".$this_question->url);
        }


        /**
         * Report answer function
         * Redirect to previous page
         *
         * @return void
         */
        public function reportAction()
        {
            /* Checking if user is signed in to be allowed to report answer */
            $this->requireSignin();

            /* Getting user's id */
            $user = Auth::getUser();
            
            /* Getting Answer by id */
            $this_answer = Answer::getById(intval($_POST['answer_id']));

            /* Checking if user is online and the answer is not already deleted */
            if ($user->id > 0 && $this_answer->active > 0) {

                /* Creating new Report's object */
                $report = new Report([
                    'from_user' => $user->id,
                    'answer_id' => $this_answer->id,
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
                Flash::addMessage("Error: You can not report this answer.", Flash::INFO);
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