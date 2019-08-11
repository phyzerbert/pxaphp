<?php

    namespace App\Models;

    use PDO;
    use \App\Models\Topic;

    /**
     * Question Topics Model
     */
    class QuestionTopic extends \Core\Model
    {
        /**
         * Add new topic for question
         *
         * @param $question_id integer - Question id
         * @param $topic_id integer - Topic id
         *
         * @return boolean - True if the topic was added for question, False otherwise
         */
        public function add(int $question_id, int $topic_id)
        {
            $sql = 'INSERT INTO question_topics (question_id, topic_id) VALUES (:question_id, :topic_id)';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
            $stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Remove all topics for question
         *
         * @param $question_id integer - Question id
         *
         * @return boolean - True if topics was removed for question, Null otherwise
         */
        public function delete(int $question_id)
        {
            $sql = 'DELETE FROM question_topics WHERE question_id = :question_id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Get question's topics
         *
         * @param int $question_id - Question id
         *
         * @return mixed - Array with topics, Null otherwise
         */
        public static function get(int $question_id)
        {
            $topics_array = [];

            $sql = 'SELECT * FROM question_topics WHERE question_id = :question_id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                
                $topics = $stmt->fetchAll();

                foreach ($topics as $topic) {

                    $this_topic = Topic::getById($topic['topic_id']);
                    if ($this_topic) {
                        $topics_array[] = $this_topic;
                    }
                }
            }

            return $topics_array;
        }
    }