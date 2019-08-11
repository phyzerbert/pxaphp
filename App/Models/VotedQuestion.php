<?php

    namespace App\Models;

    use PDO;
    use \App\Token;
    use \App\Models\Question;

    /**
     * Voted Question Model
     */
    class VotedQuestion extends Question
    {
        /**
         * Vote question function
         * Increment number of votes when vote question
         *
         * @param int $user_id - User ID, which voted question
         * @param int $question_id - Question ID which is votes
         *
         * @return int - Number of votes added
         */
        public static function vote(int $user_id, int $question_id)
        {
            /* Checking if user already voted that question */
            $sql = 'SELECT * FROM voted_questions WHERE user_id = :user_id AND question_id = :question_id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);

            $stmt->execute();
            $result = $stmt->fetch();

            if (! $result) {

                /* If user never voted it - adding new vote */
                $sql = 'INSERT INTO voted_questions (user_id, question_id, value) VALUES (:user_id, :question_id, 1)';

                $db = static::getDB();
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);

                if ($stmt->execute()) {

                    /* Incrementing numer of votes with 1, as that is first vote */
                    Question::addVote($question_id, 1);

                    return 1;
                }

            } else {

                /* Updating old vote if user changing his vote */
                $sql = 'UPDATE voted_questions SET value = 1 WHERE user_id = :user_id AND question_id = :question_id';

                $db = static::getDB();
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);

                if ($stmt->execute() && $result['value'] != 1) {

                    /* Incrementing numer of votes with 1, as user already wanted and need to remove old */
                    Question::addVote($question_id, 2);

                    return 2;
                }
            }

            return 0;
        }


        /**
         * Unvote question function
         * Decrement number of votes when unvote question
         *
         * @param int $user_id - User ID, which unvoted question
         * @param int $question_id - Question ID which is unvotes
         *
         * @return int - Number of votes removed
         */
        public static function unvote(int $user_id, int $question_id)
        {
            /* Checking if user already unvoted that question */
            $sql = 'SELECT * FROM voted_questions WHERE user_id = :user_id AND question_id = :question_id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);

            $stmt->execute();
            $result = $stmt->fetch();

            if (! $result) {

                /* If user never voted it - adding new unvote */
                $sql = 'INSERT INTO voted_questions (user_id, question_id, value) VALUES (:user_id, :question_id, -1)';

                $db = static::getDB();
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);

                if ($stmt->execute()) {

                    /* Decrementing numer of votes with 1, as that is first unvote */
                    Question::removeVote($question_id, 1);

                    return 1;
                }

            } else {

                /* Updating old vote if user changing his vote */
                $sql = 'UPDATE voted_questions SET value = -1 WHERE user_id = :user_id AND question_id = :question_id';

                $db = static::getDB();
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);

                if ($stmt->execute() && $result['value'] != -1) {

                    /* Decrementing numer of votes with 2, as user already voted */
                    Question::removeVote($question_id, 2);

                    return 2;
                }
            }

            return 0;
        }


        /**
         * Function which returns 1 if user voted / -1 if unvoted and false if did not vote question
         *
         * @param int $user_id - User ID
         * @param int $question_id - Question ID
         *
         * @return Mixed: "1" if user voted / "-1" if unvoted and "false" if did not vote question
         */
        public static function isVoted(int $user_id, int $question_id)
        {
            $sql = 'SELECT value FROM voted_questions WHERE user_id = :user_id AND question_id = :question_id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);

            $stmt->execute();

            return $stmt->fetch();
        }
    }