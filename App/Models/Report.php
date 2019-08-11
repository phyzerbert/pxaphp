<?php

    namespace App\Models;

    use PDO;
    use \App\Models\User;
    use \App\Models\Question;
    use \App\Models\Answer;

    /**
     * Reports Model
     */
    class Report extends \Core\Model
    {
        public $question_id = 0;
        public $answer_id = 0;

        /**
         * Class constructor
         *
         * @param array $data - Initial property values
         *
         * @return void
         */
        public function __construct($data = [])
        {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }


        /**
         * Add report function
         *
         * @return boolean - True if the report was add, Null otherwise
         */
        public function add()
        {
            $sql = 'INSERT INTO reports (from_user, question_id, answer_id, message, timestamp, status) VALUES (:from_user, :question_id, :answer_id, :message, :timestamp, :status)';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':from_user', $this->from_user, PDO::PARAM_INT);
            $stmt->bindValue(':question_id', $this->question_id, PDO::PARAM_INT);
            $stmt->bindValue(':answer_id', $this->answer_id, PDO::PARAM_INT);
            $stmt->bindValue(':message', $this->message, PDO::PARAM_STR);
            $stmt->bindValue(':timestamp', time(), PDO::PARAM_INT);
            $stmt->bindValue(':status', 0, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Return number of all reports available
         *
         * @param array $params - Array with parameters
         *
         * @return int - Number of reports
         */
        public static function count(array $params = [])
        {
            $params['status'] = $params['status'] ?? 0;

            $search_sql = (isset($params['search'])) ? ' AND (message LIKE :search)' : '';

            $sql = 'SELECT COUNT(id) AS total FROM reports WHERE status = :status'.$search_sql;

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':status', $params['status'], PDO::PARAM_INT);
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
         * Function which returns reports
         *
         * @param array $params - Array with parameters
         *
         * @return mixed - Reports array with objects if found, Null otherwise
         */
        public static function get(array $params = [])
        {
            $reports = [];

            $params['status']       = $params['status']     ?? 0;
            $params['limit']        = $params['limit']      ?? 100;
            $params['offset']       = $params['offset']     ?? 0;
            $params['order_by']     = $params['order_by']   ?? 'id';
            $params['order_type']   = $params['order_type'] ?? 'DESC';

            $search_sql = (isset($params['search'])) ? ' AND (message LIKE :search)' : '';

            $sql = 'SELECT * FROM reports WHERE status = :status'.$search_sql.' ORDER BY '.$params['order_by'].' '.$params['order_type'].' LIMIT :offset, :limit';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':status', $params['status'], PDO::PARAM_INT);
            if ($search_sql) {
                $stmt->bindValue(':search', '%'.$params['search'].'%', PDO::PARAM_STR);
            }
            $stmt->bindValue(':offset', intval($params['offset']), PDO::PARAM_INT);
            $stmt->bindValue(':limit', intval($params['limit']), PDO::PARAM_INT);

            $stmt->execute();

            /* Adding additional information about user and question/answer reported */
            foreach($stmt->fetchAll() as $report) {

                $report['user'] = User::getById($report['from_user'], ['username', 'photo']);
                if ($report['question_id'] > 0) {
                    $report['question'] = Question::getById($report['question_id'], 1);
                }
                if ($report['answer_id'] > 0) {
                    $report['answer'] = Answer::getById($report['answer_id'], 1);
                    $report['answer']->question = Question::getById($report['answer']->question_id, 1);
                }

                array_push($reports, $report);
            }

            return $reports;
        }


        /**
         * Close Report (Mark it as resolved)
         *
         * @return boolean - True if the report was closed, Null otherwise
         */
        public static function close(int $reportId)
        {
            $sql = 'UPDATE reports SET status = 1 WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':id', $reportId, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Delete Report
         *
         * @return boolean - True if the report was deleted, Null otherwise
         */
        public static function delete(int $reportId)
        {
            $sql = 'DELETE FROM reports WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':id', $reportId, PDO::PARAM_INT);

            return $stmt->execute();
        }
    }