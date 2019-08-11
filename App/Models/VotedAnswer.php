<?php

    namespace App\Models;

    use PDO;
    use \App\Token;
    use \App\Models\Answer;
    use \App\Models\User;

    /**
     * Voted Answer Model
     */
    class VotedAnswer extends Answer
    {
        /**
         * Vote answer function
         * Increment number of votes when vote answer
         *
         * @param int $user_id - User ID, which voted answer
         * @param int $answer_id - Answer ID which is votes
         *
         * @return int - Number of votes added
         */
        public static function vote(int $user_id, int $answer_id)
        {
            /* Checking if answer is active */
            $answer = Answer::getById($answer_id);

            if ($answer->active == 1) {

                /* Checking if user already voted that answer */
                $sql = 'SELECT * FROM voted_answers WHERE user_id = :user_id AND answer_id = :answer_id';

                $db = static::getDB();
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':answer_id', $answer_id, PDO::PARAM_INT);

                $stmt->execute();
                $result = $stmt->fetch();

                if (! $result) {

                    /* If user never voted it - adding new vote */
                    $sql = 'INSERT INTO voted_answers (user_id, answer_id, value) VALUES (:user_id, :answer_id, 1)';

                    $db = static::getDB();
                    $stmt = $db->prepare($sql);
                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->bindValue(':answer_id', $answer_id, PDO::PARAM_INT);

                    if ($stmt->execute()) {

                        /* Adding one point for answer's author if does not vote himself */
                        if ($answer->user_id != $user_id) {
                            User::addPoints($answer->user_id, 1);
                        }

                        /* Incrementing numer of votes with 1, as that is first vote */
                        Answer::addVote($answer_id, 1);

                        return 1;
                    }

                } else {

                    /* Updating old vote if user changing his vote */
                    $sql = 'UPDATE voted_answers SET value = 1 WHERE user_id = :user_id AND answer_id = :answer_id';

                    $db = static::getDB();
                    $stmt = $db->prepare($sql);
                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->bindValue(':answer_id', $answer_id, PDO::PARAM_INT);

                    if ($stmt->execute() && $result['value'] != 1) {

                        /* Incrementing numer of votes with 1, as user already wanted and need to remove old */
                        Answer::addVote($answer_id, 2);

                        return 2;
                    }
                }
            }

            return 0;
        }


        /**
         * Unvote answer function
         * Decrement number of votes when unvote answer
         *
         * @param int $user_id - User ID, which unvoted answer
         * @param int $answer_id - Answer ID which is unvotes
         *
         * @return int - Number of votes removed
         */
        public static function unvote(int $user_id, int $answer_id)
        {
            /* Checking if answer is active */
            $answer = Answer::getById($answer_id);

            if ($answer->active == 1) {

                /* Checking if user already unvoted that answer */
                $sql = 'SELECT * FROM voted_answers WHERE user_id = :user_id AND answer_id = :answer_id';

                $db = static::getDB();
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':answer_id', $answer_id, PDO::PARAM_INT);

                $stmt->execute();
                $result = $stmt->fetch();

                if (! $result) {

                    /* If user never voted it - adding new unvote */
                    $sql = 'INSERT INTO voted_answers (user_id, answer_id, value) VALUES (:user_id, :answer_id, -1)';

                    $db = static::getDB();
                    $stmt = $db->prepare($sql);
                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->bindValue(':answer_id', $answer_id, PDO::PARAM_INT);

                    if ($stmt->execute()) {

                        /* Removing one point for answer's author if does not unvote himself */
                        if ($answer->user_id != $user_id) {
                            User::removePoints($answer->user_id, 1);
                        }

                        /* Decrementing numer of votes with 1, as that is first unvote */
                        Answer::removeVote($answer_id, 1);

                        return 1;
                    }

                } else {

                    /* Updating old vote if user changing his vote */
                    $sql = 'UPDATE voted_answers SET value = -1 WHERE user_id = :user_id AND answer_id = :answer_id';

                    $db = static::getDB();
                    $stmt = $db->prepare($sql);
                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->bindValue(':answer_id', $answer_id, PDO::PARAM_INT);

                    if ($stmt->execute() && $result['value'] != -1) {

                        /* Decrementing numer of votes with 2, as user already voted */
                        Answer::removeVote($answer_id, 2);

                        return 2;
                    }
                }
            }

            return 0;
        }


        /**
         * Function which returns 1 if user voted / -1 if unvoted and false if did not vote answer
         *
         * @param int $user_id - User ID
         * @param int $answer_id - Answer ID
         *
         * @return Mixed: "1" if user voted / "-1" if unvoted and "false" if did not vote answer
         */
        public static function isVoted(int $user_id, int $answer_id)
        {
            $sql = 'SELECT value FROM voted_answers WHERE user_id = :user_id AND answer_id = :answer_id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':answer_id', $answer_id, PDO::PARAM_INT);

            $stmt->execute();

            return $stmt->fetch();
        }
    }