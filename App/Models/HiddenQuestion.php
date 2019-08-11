<?php

    namespace App\Models;

    use PDO;
    use \App\Token;
    use \App\Models\FollowedQuestion;

    /**
     * Hidden Question Model
     */
    class HiddenQuestion extends FollowedQuestion
    {
        /**
         * Hide question function
         *
         * @param int $user_id - User ID, which hide question
         * @param int $question_id - Question ID which is hidden
         *
         * @return boolean True if hidden ot False if not (already hidden)
         */
        public static function hide(int $user_id, int $question_id)
        {
            $sql = 'SELECT id FROM hidden_questions WHERE user_id = :user_id AND question_id = :question_id ';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);

            $stmt->execute();

            if (! $stmt->fetch()) {

                $sql = 'INSERT INTO hidden_questions (user_id, question_id) VALUES (:user_id, :question_id)';

                $db = static::getDB();
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);

                if ($stmt->execute()) {

                    return FollowedQuestion::unfollow($user_id, $question_id);
                }
            }

            return false;
        }


        /**
         * Function which returns true or false if user hidden a question
         *
         * @param int $user_id - User ID
         * @param int $question_id - Question ID
         *
         * @return boolean - True if is hidden, Null otherwise
         */
        public static function isHidden(int $user_id, int $question_id)
        {
            $sql = 'SELECT id FROM hidden_questions WHERE user_id = :user_id AND question_id = :question_id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);

            $stmt->execute();

            return $stmt->fetch();
        }
    }