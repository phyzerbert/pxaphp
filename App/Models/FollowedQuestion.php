<?php

    namespace App\Models;

    use PDO;
    use \App\Token;
    use \App\Models\Question;

    /**
     * Followed Question Model
     */
    class FollowedQuestion extends Question
    {
        /**
         * Follow question function
         * Increment number of followers when follow question
         *
         * @param int $user_id - User ID, which follow question
         * @param int $question_id - Question ID which is followed
         *
         * @return boolean - True if followed or False if not (already followed)
         */
        public static function follow(int $user_id, int $question_id)
        {
            $sql = 'SELECT * FROM followed_questions WHERE user_id = :user_id AND question_id = :question_id ';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);

            $stmt->execute();

            if (! $stmt->fetch()) {

                $sql = 'INSERT INTO followed_questions (user_id, question_id) VALUES (:user_id, :question_id)';

                $db = static::getDB();
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);

                if ($stmt->execute()) {

                    return Question::addFollower($question_id);
                }
            }

            return false;
        }


        /**
         * Unfollow question function
         *
         * @param int $user_id - User ID, which follow question
         * @param int $question_id - Question ID which is unfollowed
         *
         * @return boolean - True if unfollowed or Null if not (already unfollowed)
         */
        public static function unfollow(int $user_id, int $question_id)
        {
            $sql = 'DELETE FROM followed_questions WHERE user_id = :user_id AND question_id = :question_id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);

            if ($stmt->execute()) {

                return Question::removeFollower($question_id);
            }
        }


        /**
         * Function which returns true or false if user follow any question
         *
         * @param int $user_id - User ID
         * @param int $question_id - Question ID
         *
         * @return boolean - True if is following, Null otherwise
         */
        public static function isFollowing(int $user_id, int $question_id)
        {
            $sql = 'SELECT id FROM followed_questions WHERE user_id = :user_id AND question_id = :question_id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);

            $stmt->execute();

            return $stmt->fetch();
        }


        /**
         * Function which returns all users ids who follow question
         *
         * @param int $question_id - Question ID
         *
         * @return mixed - Array with users ids or Null if nobody follow it
         */
        public static function getFollowers(int $question_id)
        {
            $sql = 'SELECT user_id FROM followed_questions WHERE question_id = :question_id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);

            $stmt->execute();

            return $stmt->fetchAll();
        }
    }