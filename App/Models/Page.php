<?php

    namespace App\Models;

    use PDO;
    use \App\Config;

    /**
     * Page Model
     */
    class Page extends \Core\Model
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
         * Add new page function
         *
         * @return boolean - True if the page was add, False otherwise
         */
        public function add()
        {
            $this->url = preg_replace("/[^a-zA-Z0-9_]+/", "", $this->url);
            if (strlen($this->title) > 2 && strlen($this->content) > 5 && strlen($this->url) > 2) {

                /* Checking if link is unique */
                $sql = 'SELECT COUNT(id) AS total FROM pages WHERE url = :url';

                $db = static::getDB();
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':url', $this->url, PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetch();

                if ($result['total'] > 0) {

                    $this->errors[] = 'This url is already used for another page!';
                
                } else {

                    $sql = 'INSERT INTO pages (title, content, url, added_at, active)
                                VALUES (:title, :content, :url, :added_at, :active)';

                    $db = static::getDB();
                    $stmt = $db->prepare($sql);

                    $stmt->bindValue(':title', $this->title, PDO::PARAM_STR);
                    $stmt->bindValue(':content', $this->content, PDO::PARAM_STR);
                    $stmt->bindValue(':url', $this->url, PDO::PARAM_STR);
                    $stmt->bindValue(':added_at', time(), PDO::PARAM_INT);
                    $stmt->bindValue(':active', $this->active, PDO::PARAM_INT);

                    if ($stmt->execute()) {
                            
                        return $db->lastInsertId(); 
                    }
                }

            } else {

                $this->errors[] = 'Your page has too short title / content / url!';
            }

            return false;
        }


        /**
         * Update page function
         *
         * @return boolean - True if the page was updated, False otherwise
         */
        public function update()
        {
            if (strlen($this->title) > 2 && strlen($this->content) > 5 && strlen($this->url) > 2) {

                /* Checking if link is changed and is unique */
                $this_page = self::getById($this->page_id, 1);
                
                $sql = 'SELECT COUNT(id) AS total FROM pages WHERE url = :url';

                $db = static::getDB();
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':url', $this->url, PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetch();

                if ($result['total'] > 0 && $this_page->url != $this->url) {

                    $this->errors[] = 'This url is already used for another page!';
                
                } else {

                    $sql = 'UPDATE pages SET title = :title, url = :url, content = :content, active = :active WHERE id = :id';

                    $db = static::getDB();
                    $stmt = $db->prepare($sql);

                    $stmt->bindValue(':title', $this->title, PDO::PARAM_STR);
                    $stmt->bindValue(':url', $this->url, PDO::PARAM_STR);
                    $stmt->bindValue(':content', $this->content, PDO::PARAM_STR);
                    $stmt->bindValue(':active', $this->active, PDO::PARAM_INT);
                    $stmt->bindValue(':id', $this->page_id, PDO::PARAM_INT);

                    if ($stmt->execute()) {
                        
                        return 1;
                    }
                }

            } else {

                $this->errors[] = 'Your page has too short title / content / url!';
            }

            return false;
        }
        

        /**
         * Find and return page by id
         *
         * @param int $id - Page's id to search for
         * @param int $includeInactive - Returning also hidden pages if is 1 (0 by default)
         *
         * @return mixed - Page object if found, Null otherwise
         */
        public static function getById(int $id, int $includeInactive = 0)
        {
            if ($includeInactive == 1) {
                $sql = 'SELECT * FROM pages WHERE id = :id';
            } else {
                $sql = 'SELECT * FROM pages WHERE id = :id AND active = 1';
            }

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

            $stmt->execute();

            return $stmt->fetch();
        }


        /**
         * Find and return page by URL
         *
         * @param string $url - Page's URL to search for
         * @param int $includeInactive - Returning also hidden pages if is 1 (0 by default)
         *
         * @return mixed - Page object if found, Null otherwise
         */
        public static function getByURL(string $url, int $includeInactive = 0)
        {
            if ($includeInactive == 1) {
                $sql = 'SELECT * FROM pages WHERE url = :url';
            } else {
                $sql = 'SELECT * FROM pages WHERE url = :url AND active = 1';
            }

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':url', $url, PDO::PARAM_STR);

            $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

            $stmt->execute();

            return $stmt->fetch();
        }


        /**
         * Delete page by id
         *
         * @param int $id - Page's id for deleting
         *
         * @return boolean - True if page was delete, False otherwise
         */
        public static function delete(int $id)
        {
            $sql = 'DELETE FROM pages WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Return number of all pages available for params
         *
         * @param array $params - Array with parameters
         *
         * @return int - Number of pages
         */
        public static function count(array $params = [])
        {
            $params['active'] = $params['active'] ?? 1;

            $search_sql = (isset($params['search'])) ? ' AND (title LIKE :search OR content LIKE :search OR url LIKE :search)' : '';

            $sql = 'SELECT COUNT(id) AS total FROM pages WHERE active = :active'.$search_sql;

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
            }

            return 0;
        }


        /**
         * Function which returns pages
         *
         * @param array $params - Array with parameters
         *
         * @return mixed - Pages array with objects if found, Null otherwise
         */
        public static function get(array $params = [])
        {
            /* Default parameters values */
            $params['active']       = $params['active']             ?? 1;
            $params['limit']        = $params['limit']              ?? 100;
            $params['offset']       = $params['offset']             ?? 0;
            $params['order_by']     = $params['order_by']           ?? 'id';
            $params['order_type']   = $params['order_type']         ?? 'DESC';

            /* Search query if is requested */
            $search_sql = (isset($params['search'])) ? ' AND (title LIKE :search OR content LIKE :search OR url LIKE :search)' : '';

            /* SQL query for loading from database */
            $sql = 'SELECT * FROM pages WHERE active = :active'.$search_sql.' ORDER BY '.$params['order_by'].' '.$params['order_type'].' LIMIT :offset, :limit';

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
         * Function which returns pages for users template links
         *
         * @return mixed - Pages array with titles and urls if found, Null otherwise
         */
        public static function getActive()
        {
            /* SQL query for loading from database */
            $sql = 'SELECT id, title, url FROM pages WHERE active = 1 ORDER BY id DESC LIMIT 100';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll();
        }


        /**
         * Hide page (Set it inactive)
         *
         * @param int $page_id - Page id
         *
         * @return boolean - True if hidden, Null otherwise
         */
        public static function hide(int $page_id)
        {
            $sql = 'UPDATE pages SET active = 0 WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $page_id, PDO::PARAM_INT);

            return $stmt->execute();
        }


        /**
         * Restore page (Set it active)
         *
         * @param int $page_id - Page id
         *
         * @return boolean - True if restored, Null otherwise
         */
        public static function restore(int $page_id)
        {
            $sql = 'UPDATE pages SET active = 1 WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $page_id, PDO::PARAM_INT);

            return $stmt->execute();
        }
    }