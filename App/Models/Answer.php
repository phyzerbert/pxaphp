<?php

    namespace App\Models;

    use PDO;
    use \App\Config;
    use \App\Models\User;
    use \App\Models\Question;
    use \App\Models\QuestionImage;

    /**
     * Answer Model
     */
    class Answer extends \Core\Model
    {
        /**
         * Array with errors
         */
        public $errors = [];


        /**
         * Class constructor
         *
         * @param array $data - Initial property values
         *
         * @return void
         */
        public function __construct(array $data = [])
        {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }


        /**
         * Filter answer function
         * Grammar fixes, making first letter uppercase.
         * Removing all iframes, excluding YouTube and Vimeo
         * Removing banned words
         *
         * @return void
         */
        protected function filter()
        {
            $this->answer = strip_tags($this->answer, '<br><a><p><code><b><strong><h1><h2><h3><h4><h5><h6><i><u><pre><img><iframe><span><div><em><ul><li>');

            $bannedWords = explode(', ', Config::getValues('banned_words'));

            $this->answer = ucfirst($this->answer);

            if(count($bannedWords) > 1) {
                $this->answer = preg_replace("/(".implode('|', $bannedWords).")/i", '***', $this->answer);
            }

            $this->answer = preg_replace('~<iframe(?![^>]*src="https://(www.youtube.com/embed|player.vimeo.com/video)/[^"]+")[^>]*></iframe>~s', '', $this->answer);
        }


        /**
         * Add new answer function
         *
         * @return boolean - True if the answer was add, False otherwise
         */
        public function add()
        {
            $this->filter();

            $answerAplhaNumeric = strip_tags($this->answer, "<img>");
            $answerAplhaNumeric = preg_replace("/[^A-Za-z0-9 ]/", '', $answerAplhaNumeric);

            /* Checking answer's length after filter */
            if (strlen($answerAplhaNumeric) > 0) {

                $sql = 'INSERT INTO answers (question_id, user_id, answer, added_at)
                            VALUES (:question_id, :user_id, :answer, :added_at)';

                $db = static::getDB();
                $stmt = $db->prepare($sql);

                $stmt->bindValue(':question_id', $this->question_id, PDO::PARAM_INT);
                $stmt->bindValue(':user_id', $this->user_id, PDO::PARAM_INT);
                $stmt->bindValue(':answer', $this->answer, PDO::PARAM_STR);
                $stmt->bindValue(':added_at', time(), PDO::PARAM_INT);

                if ($stmt->execute()) {

                    Question::addAnswer($this->question_id);

                    User::addPoints($this->user_id, 1);
                        
                    return $db->lastInsertId(); 
                }

            } else {

                $this->errors[] = 'Your answer is empty or contains words or videos which are not allowed!';
            }

            return false;
        }


        /**
         * Update answer function
         *
         * @return boolean - True if the answer was updated, False otherwise
         */
        public function update()
        {
            $this->filter();

            /* Checking answer's length after filter */
            $answerAplhaNumeric = strip_tags($this->answer, "<img>");
            $answerAplhaNumeric = preg_replace("/[^A-Za-z0-9 ]/", '', $answerAplhaNumeric);

            if (strlen($answerAplhaNumeric) > 0) {

                $sql = 'UPDATE answers SET answer = :answer, modified_at = :modified_at WHERE id = :id';

                $db = static::getDB();
                $stmt = $db->prepare($sql);

                $stmt->bindValue(':answer', $this->answer, PDO::PARAM_STR);
                $stmt->bindValue(':modified_at', time(), PDO::PARAM_INT);
                $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    
                    return 1;
                }

            } else {

                $this->errors[] = 'Your answer is too short!';
            }

            return false;
        }
        

        /**
         * Find and return an answer by id
         *
         * @param int $id - Answer's id to search for
         * @param int $includeInactive - Returning also hidden answers if is 1 (0 by default)
         *
         * @return mixed - Answer object if found, Null otherwise
         */
        public static function getById(int $id, int $includeInactive = 0)
        {
            if ($includeInactive == 1) {
                $sql = 'SELECT * FROM answers WHERE id = :id';
            } else {
                $sql = 'SELECT * FROM answers WHERE id = :id AND active = 1';
            }

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

            $stmt->execute();

            return $stmt->fetch();
        }


        /**
         * Delete an answer by id
         *
         * @param int $id - Answer's id for deleting
         *
         * @return boolean - True if answer was delete, False otherwise
         */
        public static function delete(int $id)
        {
            $answer = self::getById($id, 1);

            $sql = 'DELETE FROM answers WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            if ($stmt->execute()) {

                Question::removeAnswer($answer->question_id);

                User::removePoints($answer->user_id, 1);

                return true;
            }

            return false;
        }


        /**
         * Return number of all answers available for params
         *
         * @param array $params - Array with parameters
         * Required params: question_id or user_id (Load answers by question or user)
         *
         * @return int - Number of answers
         */
        public static function count(array $params = [])
        {
            /* Checking if was set question_id or user_id */
            if (isset($params['question_id']) || isset($params['user_id']) || isset($params['get_all'])) {

                $params['active']       = $params['active'] ?? 1;

                if (isset($params['get_all'])) {
                    $get_sql = 'id > 0';
                } else if (isset($params['question_id'])) {
                    $get_sql = 'question_id = :question_id';
                } else {
                    $get_sql = 'user_id = :user_id';
                }

                $search_sql = (isset($params['search'])) ? ' AND (answer LIKE :search)' : '';

                $sql = 'SELECT COUNT(id) AS total FROM answers WHERE '.$get_sql.' AND active = :active'.$search_sql;

                $db = static::getDB();
                $stmt = $db->prepare($sql);
                if (isset($params['get_all'])) {
                    // Nothing...
                } else if (isset($params['question_id'])) {
                    $stmt->bindValue(':question_id', $params['question_id'], PDO::PARAM_INT);
                } else {
                   $stmt->bindValue(':user_id', $params['user_id'], PDO::PARAM_INT);
                }
                $stmt->bindValue(':active', $params['active'], PDO::PARAM_INT);
                if ($search_sql) {
                    $stmt->bindValue(':search', '%'.$params['search'].'%', PDO::PARAM_STR);
                }
                $stmt->execute();
                $result = $stmt->fetch();

                if ($result['total'] > 0) {
                    
                    return $result['total'];
                }
            }

            return 0;
        }


        /**
         * Return number of all answers available for period of time
         *
         * @param int $timestamp - timestamp from which time count answers
         *
         * @return int - Number of answers
         */
        public static function countByTime(int $timestamp)
        {
           $sql = 'SELECT COUNT(id) AS total FROM answers WHERE id > 0 AND added_at > :timestamp';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':timestamp', $timestamp, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch();

            if ($result['total'] > 0) {
                
                return $result['total'];

            } else {
                
                return 0;
            }
        }


        /**
         * Function which returns answers
         *
         * @param array $params - Array with parameters
         * Required params: question_id or user_id (Load answers by question or user)
         *
         * @return mixed - Answers array with objects if found, Null otherwise
         */
        public static function get(array $params = [])
        {
            /* Array with all answers for return */
            $answers_array = [];

            /* Checking if was set question_id or user_id */
            if (isset($params['question_id']) || isset($params['user_id']) || isset($params['get_all'])) {

                if (isset($params['get_all'])) {
                    $get_sql = 'id > 0';
                } else if (isset($params['question_id'])) {
                    $get_sql = 'question_id = :question_id';
                } else {
                    $get_sql = 'user_id = :user_id';
                }

                /* Default parameters values */
                $params['active']       = $params['active']             ?? 1;
                $params['limit']        = $params['limit']              ?? 100;
                $params['offset']       = $params['offset']             ?? 0;
                $params['order_by']     = $params['order_by']           ?? 'id';
                $params['order_type']   = $params['order_type']         ?? 'DESC';

                /* Search query if is requested */
                $search_sql = (isset($params['search'])) ? ' AND (answer LIKE :search)' : '';

                /* Sql query for loading from database */
                $sql = 'SELECT * FROM answers WHERE '.$get_sql.' AND active = :active'.$search_sql.' ORDER BY '.$params['order_by'].' '.$params['order_type'].' LIMIT :offset, :limit';

                $db = static::getDB();
                $stmt = $db->prepare($sql);
                if (isset($params['get_all'])) {
                    // Nothing here...
                } else if (isset($params['question_id'])) {
                    $stmt->bindValue(':question_id', $params['question_id'], PDO::PARAM_INT);
                } else {
                   $stmt->bindValue(':user_id', $params['user_id'], PDO::PARAM_INT);
                }
                $stmt->bindValue(':active', $params['active'], PDO::PARAM_INT);
                if ($search_sql) {
                    $stmt->bindValue(':search', '%'.$params['search'].'%', PDO::PARAM_STR);
                }
                $stmt->bindValue(':offset', intval($params['offset']), PDO::PARAM_INT);
                $stmt->bindValue(':limit', intval($params['limit']), PDO::PARAM_INT);

                $stmt->execute();

                $answers = $stmt->fetchAll();

                foreach ($answers as $answer) {

                    /* Adding lightbox for images */
                    $answer['answer'] = preg_replace('/(<img [^>]*src="([^"]*)"[^>]*)>/i', '<a href="$2" class="image-link">$1 class="img-thumbnail"></a>', $answer['answer']);

                    /* Adding mention link */
                    $answer['answer'] = preg_replace('/(<span class="mention" [^>]*value="([^"]*)"[^>]*)>(.*?)<\/span>﻿<\/span>/i', '<a href="/user/$2" class="mention" target="_blank">@$2</a>', $answer['answer']);

                    /* Getting answers's author profile */
                    $answer['author'] = User::getById($answer['user_id'], ['id', 'username', 'photo', 'points']);

                    /* Check if user voted this answer, null if signed out */
                    if (isset($params['current_user'])) {
                        $a_is_voted = (intval($params['current_user']->id) > 0) ? VotedAnswer::isVoted(intval($params['current_user']->id), $answer['id']) : null;
                        $answer['is_voted'] = $a_is_voted['value'];
                    }

                    /* Check if is needed to include questions (For user answers page) */
                    if (isset($params['including_question'])) {

                        $question = Question::getById($answer['question_id'], 1);

                        /* Getting question's author user */
                        $question->author = User::getById($question->user_id, ['id', 'username', 'photo']);

                        /* Getting question's images attachments */
                        $question->images = QuestionImage::get($question->id);

                        $answer['question'] = $question;
                    }

                    $answers_array[] = $answer;
                }
            }

            return $answers_array;
        }


        /**
         * Increment number of votes for answer
         *
         * @param int $answer_id - Answer's id
         * @param int $value - Number of votes incremented, by default = 1
         *
         * @return boolean - True if incremented, Null otherwise
         */
        public static function addVote(int $answer_id, int $value = 1)
        {
            $sql = 'UPDATE answers SET votes = votes + '.intval($value).' WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $answer_id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Decrement number of votes for answer
         *
         * @param int $answer_id - Answer's id
         * @param int $value - Number of votes, by default = 1
         *
         * @return boolean - True if decremented, Null otherwise
         */
        public static function removeVote(int $answer_id, int $value = 1)
        {
            $sql = 'UPDATE answers SET votes = votes - '.intval($value).' WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $answer_id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Hide answer (Set it inactive)
         *
         * @param int $answer_id - Answer id
         *
         * @return boolean - True if hidden, Null otherwise
         */
        public static function hide(int $answer_id)
        {
            $sql = 'UPDATE answers SET active = 0 WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $answer_id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Restore answer (Set it active)
         *
         * @param int $answer_id - Answer id
         *
         * @return boolean - True if restored, Null otherwise
         */
        public static function restore(int $answer_id)
        {
            $sql = 'UPDATE answers SET active = 1 WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $answer_id, PDO::PARAM_INT);

            return $stmt->execute();
        }
    }