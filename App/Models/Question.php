<?php

    namespace App\Models;

    use PDO;
    use \App\Config;
    use \Core\View;
    use \App\Models\QuestionTopic;
    use \App\Models\QuestionImage;
    use \App\Models\User;

    /**
     * Question Model
     */
    class Question extends \Core\Model
    {
        /*
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
         * Return a question URL based on question's title
         * Check if already exists question with similar titles
         * If exists - add "-{i}" at the end, where is {i} is question count number
         *
         * @return string - Question's URL
         */
        public function getUrl()
        {
            /* Removing "?" at the end of question to remove "-" at the end of url */
            $title = str_replace("?", "", $this->title);

            /* Creating URL */
            $mainUrl = static::slug($title);
            $url = $mainUrl;

            $urlFound = 0;
            $matchFound = 0;

            /* Search for unique URL */
            do {

                $sql = 'SELECT id FROM questions WHERE url = :url LIMIT 1';

                $db = static::getDB();
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':url', $url, PDO::PARAM_STR);
                
                $stmt->execute();
                $result = $stmt->fetch();

                if (isset($this->id) && ($result['id'] == $this->id)) {

                    $urlFound = 1;

                } else {

                    if ($result['id'] > 0) {

                        $matchFound++;
                        $url = $mainUrl . '-' . $matchFound;

                    } else {

                        $urlFound = 1;
                    }
                }

            } while ($urlFound == 0);

            return $url;
        }


        /**
         * Validate current property values, adding valiation error messages to the errors array property
         *
         * @return void
         */
        protected function validate()
        {
            $config = Config::getValues();

            if (strlen($this->title) < 3) {
                $this->errors[] = 'Question title is required';
            }
            if (strlen($this->title) > $config->question_max_title) {
                $this->errors[] = 'Question title is too long';
            }

            if (strlen($this->description) > $config->question_max_description) {
                $this->errors[] = 'Question description is too long';
            }

            if (count($this->topics) == 0) {
                $this->errors[] = 'You need to add at least one topic';
            }
            if (count($this->topics) > $config->question_max_topics) {
                $this->errors[] = 'You can add max '.$config['question_max_topics'].' topics';
            }
        }


        /**
         * Filter Question function
         * Grammar fixes, such as "?" at end of question, first letter uppercase.
         * Removing banned words
         *
         * @return void
         */
        protected function filter()
        {
            $this->question = strip_tags($this->title);

            $bannedWords = explode(', ', Config::getValues('banned_words'));

            $this->title = ucfirst($this->title);
            if (substr($this->title, -1) <> '?') {
                $this->title .= '?';
            }

            if(count($bannedWords) > 1) {
                $this->title = preg_replace("/(".implode('|', $bannedWords).")/i", '***', $this->title);
            }

            $this->description = ucfirst($this->description) ?? "";

            if(count($bannedWords) > 1) {
                $this->description = preg_replace("/(".implode('|', $bannedWords).")/i", '***', $this->description);
            }
        }


        /**
         * Add new question function
         *
         * @return boolean - True if the question was created, False otherwise
         */
        public function add()
        {
            $this->validate();

            if (empty($this->errors)) {

                $this->filter();

                if (strlen($this->question) > 2) {

                    $this->url = $this->getUrl();

                    $sql = 'INSERT INTO questions (title, description, url, user_id, added_at)
                            VALUES (:title, :description, :url, :user_id, :added_at)';

                    $db = static::getDB();
                    $stmt = $db->prepare($sql);

                    $stmt->bindValue(':title', $this->title, PDO::PARAM_STR);
                    $stmt->bindValue(':description', $this->description, PDO::PARAM_STR);
                    $stmt->bindValue(':url', $this->url, PDO::PARAM_STR);
                    $stmt->bindValue(':user_id', $this->user_id, PDO::PARAM_INT);
                    $stmt->bindValue(':added_at', time(), PDO::PARAM_INT);

                    if ($stmt->execute()) {
                        
                        return $db->lastInsertId();
                    
                    }
                }
            }

            return false;
        }


        /**
         * Update question function
         *
         * @return boolean - True if the question was updated, False otherwise
         */
        public function update()
        {
            $this->validate();

            if (empty($this->errors)) {

                $this->filter();

                $this->url = $this->getUrl();

                $sql = 'UPDATE questions SET title = :title, description = :description, url = :url, modified_at = :modified_at WHERE id = :id';

                $db = static::getDB();
                $stmt = $db->prepare($sql);

                $stmt->bindValue(':title', $this->title, PDO::PARAM_STR);
                $stmt->bindValue(':description', $this->description, PDO::PARAM_STR);
                $stmt->bindValue(':url', $this->url, PDO::PARAM_STR);
                $stmt->bindValue(':modified_at', time(), PDO::PARAM_INT);
                $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

                return $stmt->execute();
            }

            return false;
        }


        /**
         * Return number of all question available for set parameters
         *
         * @param array $params - Array with parameters
         *
         * @return int - Number of questions
         */
        public static function count(array $params = [])
        {
            $params['active']       = $params['active'] ?? 1;
            $params['current_user'] = $params['current_user']->id   ?? 0;

            if (isset($params['followed'])) {

                $search_sql         = (isset($params['search'])) ? ' AND (q.title LIKE :search OR q.description LIKE :search)' : '';

                if ($params['current_user'] > 0) {
                    $exclude_hidden_sql = ' AND q.id NOT IN (SELECT question_id FROM hidden_questions WHERE user_id = '.intval($params['current_user']).')';
                } else {
                    $exclude_hidden_sql = '';
                }

                $sql = 'SELECT COUNT(q.id) AS total FROM questions q JOIN followed_questions f ON q.id = f.question_id WHERE q.active = :active AND f.user_id = '.intval($params['followed']).$search_sql.$exclude_hidden_sql;

            } else if (isset($params['topics'])) {

                $search_sql         = (isset($params['search'])) ? ' AND (q.title LIKE :search OR q.description LIKE :search)' : '';
                $topics_sql         = ' AND t.topic_id IN ('.implode(",", $params['topics']).')';
                $unanswered_sql     = (isset($params['unanswered'])) ? ' AND q.answers = 0 AND q.is_closed = 0' : '';
                $own_sql            = (isset($params['own'])) ? ' AND q.user_id = '.intval($params['user_id']) : '';

                if ($params['current_user'] > 0) {
                    $exclude_hidden_sql = ' AND q.id NOT IN (SELECT question_id FROM hidden_questions WHERE user_id = '.intval($params['current_user']).')';
                } else {
                    $exclude_hidden_sql = '';
                }

                $sql = 'SELECT COUNT(q.id) AS total FROM questions q JOIN question_topics t ON q.id = t.question_id WHERE q.active = :active'.$topics_sql.$own_sql.$unanswered_sql.$search_sql.$exclude_hidden_sql;

            } else {

                $search_sql         = (isset($params['search'])) ? ' AND (title LIKE :search OR description LIKE :search)' : '';
                $unanswered_sql     = (isset($params['unanswered'])) ?  ' AND answers = 0 AND is_closed = 0' : '';
                $own_sql            = (isset($params['own'])) ? ' AND user_id = '.intval($params['user_id']) : '';

                if ($params['current_user'] > 0) {
                    $exclude_hidden_sql = ' AND id NOT IN (SELECT question_id FROM hidden_questions WHERE user_id = '.intval($params['current_user']).')';
                } else {
                    $exclude_hidden_sql = '';
                }

                $sql = 'SELECT COUNT(id) AS total FROM questions WHERE active = :active'.$own_sql.$unanswered_sql.$search_sql;
            }

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':active', $params['active'], PDO::PARAM_INT);
            if ($search_sql) {
                $stmt->bindValue(':search', '%'.$params['search'].'%', PDO::PARAM_STR);
            }
            $stmt->execute();
            $result = $stmt->fetch();

            if ($result['total'] > 0) {
                
                return $result['total'];

            } else {
                
                return 0;
            }
        }


        /**
         * Return number of all question available for period of time
         *
         * @param int $timestamp - timestamp from which time count questions
         *
         * @return int - Number of questions
         */
        public static function countByTime(int $timestamp)
        {
           $sql = 'SELECT COUNT(id) AS total FROM questions WHERE added_at > :timestamp';

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
         * Function which returns questions
         *
         * @param array $params - Array with parameters
         *
         * @return mixed - Questions array with objects if found, False otherwise
         */
        public static function get(array $params = [])
        {
            $questions_array = [];

            $params['active']       = $params['active']             ?? 1;
            $params['limit']        = $params['limit']              ?? 100;
            $params['offset']       = $params['offset']             ?? 0;
            $params['order_by']     = $params['order_by']           ?? 'id';
            $params['order_type']   = $params['order_type']         ?? 'DESC';
            $params['current_user'] = $params['current_user']->id   ?? 0;

            if (isset($params['followed'])) {

                $search_sql = (isset($params['search'])) ? ' AND (q.title LIKE :search OR q.description LIKE :search)' : '';

                $sql = 'SELECT *, q.id AS id, q.user_id AS user_id FROM questions q JOIN followed_questions f ON q.id = f.question_id WHERE q.active = :active AND f.user_id = '.intval($params['followed']).$search_sql.' ORDER BY q.'.$params['order_by'].' '.$params['order_type'].' LIMIT :offset, :limit';

            } else if (isset($params['topics'])) {

                $search_sql     = (isset($params['search'])) ? ' AND (q.title LIKE :search OR q.description LIKE :search)' : '';
                $topics_sql     = ' AND t.topic_id IN ('.implode(",", $params['topics']).')';
                $unanswered_sql = (isset($params['unanswered'])) ? ' AND q.answers = 0 AND q.is_closed = 0' : '';
                $own_sql        = (isset($params['own'])) ? ' AND q.user_id = '.intval($params['user_id']) : '';

                if ($params['current_user'] > 0) {
                    $exclude_hidden_sql = ' AND q.id NOT IN (SELECT question_id FROM hidden_questions WHERE user_id = '.intval($params['current_user']).')';
                } else {
                    $exclude_hidden_sql = '';
                }

                $sql = 'SELECT *, q.id AS id FROM questions q JOIN question_topics t ON q.id = t.question_id WHERE q.active = :active'.$topics_sql.$own_sql.$unanswered_sql.$search_sql.$exclude_hidden_sql.' GROUP BY t.question_id ORDER BY q.'.$params['order_by'].' '.$params['order_type'].' LIMIT :offset, :limit';

            } else {

                $search_sql         = (isset($params['search'])) ? ' AND (title LIKE :search OR description LIKE :search)' : '';
                $unanswered_sql     = (isset($params['unanswered'])) ?  ' AND answers = 0 AND is_closed = 0' : '';
                $own_sql            = (isset($params['own'])) ? ' AND user_id = '.intval($params['user_id']) : '';

                if ($params['current_user'] > 0) {
                    $exclude_hidden_sql = ' AND id NOT IN (SELECT question_id FROM hidden_questions WHERE user_id = '.intval($params['current_user']).')';
                } else {
                    $exclude_hidden_sql = '';
                }

                $sql = 'SELECT * FROM questions WHERE active = :active'.$own_sql.$unanswered_sql.$search_sql.$exclude_hidden_sql.' ORDER BY '.$params['order_by'].' '.$params['order_type'].' LIMIT :offset, :limit';
            }

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':active', $params['active'], PDO::PARAM_INT);
            if ($search_sql) {
                $stmt->bindValue(':search', '%'.$params['search'].'%', PDO::PARAM_STR);
            }
            $stmt->bindValue(':offset', intval($params['offset']), PDO::PARAM_INT);
            $stmt->bindValue(':limit', intval($params['limit']), PDO::PARAM_INT);

            $stmt->execute();

            $questions = $stmt->fetchAll();

            foreach ($questions as $question) {

                /* Adding mentions with links */
                $question['description'] = preg_replace('/@([^@ ]+)/', '<a href="/user/$1" target="_blank">@$1</a> ', $question['description']);
                
                /* Getting question's topics */
                $question['topics'] = QuestionTopic::get($question['id']);

                /* Getting question's author user */
                $question['author'] = User::getById($question['user_id'], ['id', 'username', 'photo']);

                /* Getting question's images attachments */
                $question['images'] = QuestionImage::get($question['id']);

                /* Checking if user is follow question */
                if ($params['current_user'] > 0) {

                    $question['is_following'] = FollowedQuestion::isFollowing($params['current_user'], $question['id']);

                } else {

                    $question['is_following'] = false;
                }

                $questions_array[] = $question;
            }

            return $questions_array;
        }


        /**
         * Hide question (Set it inactive)
         *
         * @param int $question_id - Question id
         *
         * @return boolean - True if hidden, Null otherwise
         */
        public static function hide(int $question_id)
        {
            $sql = 'UPDATE questions SET active = 0 WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $question_id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Restore question (Set it active)
         *
         * @param int $question_id - Question id
         *
         * @return boolean - True if restored, Null otherwise
         */
        public static function restore(int $question_id)
        {
            $sql = 'UPDATE questions SET active = 1 WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $question_id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Increment number of votes for question
         *
         * @param int $question_id - Question id
         * @param int $value - Number of votes, by default = 1
         *
         * @return boolean - True if incremented, Null otherwise
         */
        public static function addVote(int $question_id, int $value = 1)
        {
            $sql = 'UPDATE questions SET votes = votes + '.intval($value).' WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $question_id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Decrement number of votes for question
         *
         * @param int $question_id - Question id
         * @param int $value - Number of votes, by default = 1
         *
         * @return boolean - True if decremented, Null otherwise
         */
        public static function removeVote(int $question_id, int $value = 1)
        {
            $sql = 'UPDATE questions SET votes = votes - '.intval($value).' WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $question_id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Increment number of answers for question
         *
         * @param int $question_id - Question id
         *
         * @return boolean - True if incremented, Null otherwise
         */
        public static function addAnswer(int $question_id)
        {
            $sql = 'UPDATE questions SET answers = answers + 1 WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $question_id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Decrement number of answers for question
         *
         * @param int $question_id - Question id
         *
         * @return boolean - True if decrement, Null otherwise
         */
        public static function removeAnswer(int $question_id)
        {
            $sql = 'UPDATE questions SET answers = answers - 1 WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $question_id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Increment number of followers for question
         *
         * @param int $question_id - Question id
         *
         * @return boolean - True if incremented, Null otherwise
         */
        public static function addFollower(int $question_id)
        {
            $sql = 'UPDATE questions SET followers = followers + 1 WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $question_id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Decrement number of followers for question
         *
         * @param int $question_id - Question id
         *
         * @return boolean - True if decrement, Null otherwise
         */
        public static function removeFollower(int $question_id)
        {
            $sql = 'UPDATE questions SET followers = followers - 1 WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $question_id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Find and return a question by id
         *
         * @param int $id - Question's id to search for
         * @param int $includeInactive - Returning also hidden questions if is 1 (0 by default)
         *
         * @return mixed - Question object if found, Null otherwise
         */
        public static function getById(int $id, int $includeInactive = 0)
        {
            if ($includeInactive == 1) {
                $sql = 'SELECT * FROM questions WHERE id = :id';
            } else {
                $sql = 'SELECT * FROM questions WHERE id = :id AND active = 1';
            }

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

            $stmt->execute();

            return $stmt->fetch();
        }


        /**
         * Delete a question by id
         *
         * @param int $id - Question's id for deleting
         *
         * @return boolean - True if question was deleted, Null otherwise
         */
        public static function delete(int $id)
        {
            $sql = 'DELETE FROM questions WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Close question by id
         *
         * @param int $id - Question's id for closing
         *
         * @return boolean - True if question was closed, Null otherwise
         */
        public static function close(int $id)
        {
            $sql = 'UPDATE questions SET is_closed = 1 WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Increment views for question by id
         *
         * @param int $id - Question's id for increment number of views
         *
         * @return boolean - True if incremented, Null otherwise
         */
        public static function addView(int $id)
        {
            $sql = 'UPDATE questions SET views = views+1 WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Find and return a question by URL
         *
         * @param string $url - Question's url to search for
         *
         * @return mixed - Question object if found, Null otherwise
         */
        public static function getByUrl(string $url)
        {
            $sql = 'SELECT * FROM questions WHERE url = :url AND active = 1';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':url', $url, PDO::PARAM_STR);

            $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

            $stmt->execute();

            return $stmt->fetch();
        }
    }