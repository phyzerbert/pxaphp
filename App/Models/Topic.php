<?php

    namespace App\Models;

    use PDO;
    use \App\Config;
    use \Core\View;

    /**
     * Topics Model
     */
    class Topic extends \Core\Model
    {
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
         * Return number of all topics available
         *
         * @param array $params - Array with parameters
         *
         * @return int - Number of topics
         */
        public static function count(array $params = [])
        {
            $params['active'] = $params['active'] ?? 1;

            $search_sql = (isset($params['search'])) ? ' AND (title LIKE :search OR description LIKE :search)' : '';

            $sql = 'SELECT COUNT(id) AS total FROM topics WHERE active = :active'.$search_sql;

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
         * Create new topic
         *
         * @return boolean - True if the topic was created, Null otherwise
         */
        public function add()
        {
            $sql = 'INSERT INTO topics (title, description, image, url)
                        VALUES (:title, :description, :image, :url)';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $this->url = $this->getUrl($this->title);

            $stmt->bindValue(':title', $this->title, PDO::PARAM_STR);
            $stmt->bindValue(':description', $this->description, PDO::PARAM_STR);
            $stmt->bindValue(':image', $this->image, PDO::PARAM_STR);
            $stmt->bindValue(':url', $this->url, PDO::PARAM_STR);

            return $stmt->execute();
        }


        /**
         * Update topic (Save topic)
         *
         * @return boolean - True if the topic was updated, Null otherwise
         */
        public function update()
        {
            $image_sql = (isset($this->image)) ? ' image = :image,' : '';

            $sql = 'UPDATE topics SET title = :title, description = :description,'.$image_sql.' url = :url WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $this->url = $this->getUrl($this->title);

            $stmt->bindValue(':title', $this->title, PDO::PARAM_STR);
            $stmt->bindValue(':description', $this->description, PDO::PARAM_STR);
            if ($image_sql) {
                $stmt->bindValue(':image', $this->image, PDO::PARAM_STR);
            }
            $stmt->bindValue(':url', $this->url, PDO::PARAM_STR);
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Return a topic URL based on topic's title
         * Check if already exists topic with similar title
         * if exists - add "-{i}" at the end, where is {i} is topic id
         *
         * @return string - Topic's URL
         */
        public function getUrl()
        {
            $sql = 'SELECT COUNT(id) AS total FROM topics WHERE title LIKE :title';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':title', $this->title.'%', PDO::PARAM_STR);
            
            $stmt->execute();
            $result = $stmt->fetch();
            if ($result['total'] > 0) {

                return static::slug($this->title).'-'.$result['total'];

            } else {

                return static::slug($this->title);
            }
        }


        /**
         * Function which returns topics
         *
         * @param array $params - Array with parameters
         *
         * @return mixed - Topics array with objects if found, Null otherwise
         */
        public static function get(array $params = [])
        {
            $params['active']       = $params['active']     ?? 1;
            $params['limit']        = $params['limit']      ?? 100;
            $params['offset']       = $params['offset']     ?? 0;
            $params['order_by']     = $params['order_by']   ?? 'id';
            $params['order_type']   = $params['order_type'] ?? 'DESC';

            $search_sql = (isset($params['search'])) ? ' AND (t.title LIKE :search OR t.description LIKE :search)' : '';

            $sql = 'SELECT *,(SELECT COUNT(id) FROM question_topics where topic_id = t.id) AS questions FROM topics t WHERE t.active = :active'.$search_sql.' ORDER BY t.'.$params['order_by'].' '.$params['order_type'].' LIMIT :offset, :limit';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':active', $params['active'], PDO::PARAM_INT);
            if ($search_sql) {
                $stmt->bindValue(':search', '%'.$params['search'].'%', PDO::PARAM_STR);
            }
            $stmt->bindValue(':offset', intval($params['offset']), PDO::PARAM_INT);
            $stmt->bindValue(':limit', intval($params['limit']), PDO::PARAM_INT);

            $stmt->execute();

            return $stmt->fetchAll();
        }


        /**
         * Function which returns topics by array of topic's id
         *
         * @param array $params - Array with topic's ids
         *
         * @return mixed - Topics array with objects if found, Null otherwise
         */
        public static function getByIds(array $topic_ids = [])
        {
            $filteredIds = [];

            /* Clearing array to keep only ids */
            foreach ($topic_ids as $key => $value) {
                $filteredIds[] = intval($value);
            }

            if (count($filteredIds) > 0) {

                $params['active']       = $params['active']     ?? 1;
                $params['order_by']     = $params['order_by']   ?? 'followers';
                $params['order_type']   = $params['order_type'] ?? 'DESC';

                $sql = 'SELECT *,(SELECT COUNT(id) FROM question_topics where topic_id = t.id) AS questions FROM topics t WHERE t.active = :active AND t.id IN ('.implode(",",$filteredIds).') ORDER BY t.'.$params['order_by'].' '.$params['order_type'];

                $db = static::getDB();
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':active', $params['active'], PDO::PARAM_INT);

                $stmt->execute();

                return $stmt->fetchAll();
            }

            return false;
        }


        /**
         * Find and return a topic by id
         *
         * @param int $id - Topics's id to search for
         *
         * @return mixed - Topic object if found, Null otherwise
         */
        public static function getById(int $id)
        {
            $sql = 'SELECT * FROM topics WHERE id = :id AND active = 1';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

            $stmt->execute();

            return $stmt->fetch();
        }


        /**
         * Hide topic function
         *
         * @return boolean - True if the topic was hidden, Null otherwise
         */
        public function hide()
        {
            $sql = 'UPDATE topics SET active = 0 WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Re-activate topic function
         *
         * @return boolean - True if the topic was activated, Null otherwise
         */
        public function activate()
        {
            $sql = 'UPDATE topics SET active = 1 WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Delete topic function
         *
         * @return boolean - True if the topic was deleted, Null otherwise
         */
        public function delete()
        {
            $sql = 'DELETE FROM topics WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Find a topic model by URL
         *
         * @param string $url - Topics's url to search for
         *
         * @return mixed - Topic object if found, Null otherwise
         */
        public static function getByURL(string $url)
        {
            $sql = 'SELECT * FROM topics WHERE url = :url AND active = 1';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':url', $url, PDO::PARAM_STR);

            $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

            $stmt->execute();

            return $stmt->fetch();
        }


        /**
         * Increment number of followers for topic
         *
         * @param int $topic_id - Topic's id
         *
         * @return boolean - True if incremented, Null otherwise
         */
        public static function addFollower(int $topic_id)
        {
            $sql = 'UPDATE topics SET followers = followers + 1 WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $topic_id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Decrement number of followers for topic
         *
         * @param int $topic_id - Topic's id
         *
         * @return boolean - True if decrement, Null otherwise
         */
        public static function removeFollower(int $topic_id)
        {
            $sql = 'UPDATE topics SET followers = followers - 1 WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $topic_id, PDO::PARAM_INT);

            return $stmt->execute();
        }
    }