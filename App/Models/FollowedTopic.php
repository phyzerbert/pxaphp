<?php

    namespace App\Models;

    use PDO;
    use \App\Token;
    use \App\Models\Topic;

    /**
     * Followed Topics Model
     */
    class FollowedTopic extends Topic
    {
        /**
         * Follow topic function
         * Increment number of followers when follow topic
         *
         * @param int $user_id - User ID, which follow topic
         * @param int $topic_id - Topic ID which is followed
         *
         * @return boolean - True if followed ot False if not (already followed)
         */
        public static function follow(int $user_id, int $topic_id)
        {
            $sql = 'SELECT * FROM followed_topics WHERE user_id = :user_id AND topic_id = :topic_id ';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);

            $stmt->execute();

            if (! $stmt->fetch()) {

                $sql = 'INSERT INTO followed_topics (user_id, topic_id) VALUES (:user_id, :topic_id)';

                $db = static::getDB();
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);

                if ($stmt->execute()) {

                    return Topic::addFollower($topic_id);
                }
            }

            return false;
        }


        /**
         * Mass Follow topics function
         * Follow topics from array and unfollow which are not in array.
         *
         * @param int $user_id - User ID, which follow topic
         * @param array $topic_id - Array with Topics ID which are followed
         *
         * @return void
         */
        public static function followMore(int $user_id, array $topics_id)
        {
            foreach ($topics_id as $topic) {

                self::follow($user_id, $topic);
            }

            $sql = 'SELECT topic_id FROM followed_topics WHERE user_id = :user_id AND topic_id NOT IN('.implode($topics_id, ',').')';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if ($result) {

                foreach ($result as $topic_id) {
                   
                    self::unfollow($user_id, $topic_id);
                }
            }
        }


        /**
         * Unfollow topic function
         *
         * @param int $user_id - User ID, which follow topic
         * @param int $topic_id - Topic ID which is unfollowed
         *
         * @return boolean - True if unfollowed or Null if not (already unfollowed)
         */
        public static function unfollow(int $user_id, int $topic_id)
        {
            $sql = 'DELETE FROM followed_topics WHERE user_id = :user_id AND topic_id = :topic_id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);

            if ($stmt->execute()) {

                return Topic::removeFollower($topic_id);
            }
        }


        /**
         * Function which returns all topics followed by user
         *
         * @param int $user_id - User ID
         *
         * @return mixed - Array with topics id or Null if user does not follow any topic
         */
        public static function getAll(int $user_id)
        {
            $sql = 'SELECT topic_id FROM followed_topics WHERE user_id = :user_id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }


        /**
         * Function which returns true or false if user follow any topic
         *
         * @param int $user_id - User ID
         * @param int $topic_id - Topic ID
         *
         * @return boolean - True if is following, False otherwise
         */
        public static function isFollowing(int $user_id, int $topic_id)
        {
            $sql = 'SELECT id FROM followed_topics WHERE user_id = :user_id AND topic_id = :topic_id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);

            $stmt->execute();

            return $stmt->fetch();
        }
    }