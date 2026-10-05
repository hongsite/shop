<?php
/*
*@author 牛头
*@company 鸿思特科技
*@function Mysqli 数据库驱动
*@date 2026/9/28
*@version hst-v1.0.0
*/
namespace DbMysqli;

use Db\Db AS Db;

class DbMysqli extends Db
{
    /** @var \mysqli|null */
    protected $linkID = null;

    /** @var \mysqli_result|null */
    protected $queryID = null;

    public function __construct($config = '')
    {
        if (!extension_loaded('mysqli')) {
            throw_exception('_NOT_SUPPERT_:mysqli');
        }
        if (!empty($config)) {
            $this->config = $config;
            if (empty($this->config['params'])) {
                $this->config['params'] = '';
            }
        }
    }

    /**
     * 连接数据库
     * @param array|string $config
     * @param int $linkNum
     * @return \mysqli
     */
    public function connect($config = '', $linkNum = 0)
    {
        if ($this->linkID instanceof \mysqli && $this->connected) {
            return $this->linkID;
        }

        if (empty($config)) {
            $config = $this->config;
        }

        // 抑制警告，用异常处理
        $mysqli = @new \mysqli(
            $config['DB_HOST'],
            $config['DB_USER'],
            $config['DB_PWD'],
            $config['DB_NAME'],
            !empty($config['DB_PORT']) ? intval($config['DB_PORT']) : 3306
        );

        if ($mysqli->connect_errno) {

			$js = array('status'=>0,'msg'=>'数据库连接失败: ' . $mysqli->connect_error);
			echo json_encode($js);
			exit;
        }

        // 设置字符集
        $charset = defined('C') ? C('DB_CHARSET') : 'utf8';
        if (!$mysqli->set_charset($charset)) {
            // 兼容旧版本
            $mysqli->query("SET NAMES '" . $charset . "'");
        }

        // 关闭严格模式
        $mysqli->query("SET sql_mode=''");

        $this->linkID = $mysqli;
        $this->connected = true;

        return $this->linkID;
    }

    /**
     * 释放查询结果
     */
    public function free()
    {
        if ($this->queryID instanceof \mysqli_result) {
            $this->queryID->free_result();
        }
        $this->queryID = null;
    }

    /**
     * 执行查询
     * @param string $str
     * @return array|false
     */
    public function query($str)
    {
        $this->initConnect(false);
        if (!$this->_linkID) {
            return false;
        }
        $this->queryStr = $str;

        if ($this->queryID) {
            $this->free();
        }

        $this->queryID = $this->_linkID->query($str);

        // 处理多结果集
        if ($this->_linkID->more_results()) {
            while ($this->_linkID->next_result()) {
                if ($res = $this->_linkID->store_result()) {
                    $res->free_result();
                }
            }
        }

        $this->debug();

        if (false === $this->queryID) {
            $this->error();
            return false;
        }

        $this->numRows = $this->queryID->num_rows;
        $this->numCols = $this->queryID->field_count;

        return $this->getAll();
    }

    /**
     * 执行写入
     * @param string $str
     * @return int|false
     */
    public function execute($str)
    {
        $this->initConnect(true);
        if (!$this->_linkID) {
            return false;
        }
        $this->queryStr = $str;

        if ($this->queryID) {
            $this->free();
        }

        $result = $this->_linkID->query($str);

        // 处理多结果集
        if ($this->_linkID->more_results()) {
            while ($this->_linkID->next_result()) {
                if ($res = $this->_linkID->store_result()) {
                    $res->free_result();
                }
            }
        }

        $this->debug();

        if (false === $result) {
            $this->error();
            return false;
        }

        $this->numRows = $this->_linkID->affected_rows;
        $this->lastInsID = $this->_linkID->insert_id;

        return $this->numRows;
    }

    public function startTrans()
    {
        $this->initConnect(true);
        if ($this->transTimes == 0) {
            $this->_linkID->autocommit(false);
        }
        $this->transTimes++;
    }

    public function commit()
    {
        if ($this->transTimes > 0) {
            $result = $this->_linkID->commit();
            $this->_linkID->autocommit(true);
            $this->transTimes = 0;
            if (!$result) {
                $this->error();
                return false;
            }
        }
        return true;
    }

    public function rollback()
    {
        if ($this->transTimes > 0) {
            $result = $this->_linkID->rollback();
            $this->transTimes = 0;
            if (!$result) {
                $this->error();
                return false;
            }
        }
        return true;
    }

    /**
     * 获取所有结果
     * @return array
     */
    private function getAll()
    {
        $result = array();
        if ($this->numRows > 0) {
            for ($i = 0; $i < $this->numRows; $i++) {
                $result[$i] = $this->queryID->fetch_assoc();
            }
            $this->queryID->data_seek(0);
        }
        return $result;
    }

    public function getFields($tableName)
    {
        $result = $this->query('SHOW COLUMNS FROM ' . $this->parseKey($tableName));
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[$val['Field']] = array(
                    'name'    => $val['Field'],
                    'type'    => $val['Type'],
                    'notnull' => (bool)($val['Null'] === ''),
                    'default' => $val['Default'],
                    'primary' => (strtolower($val['Key']) == 'pri'),
                    'autoinc' => (strtolower($val['Extra']) == 'auto_increment'),
                );
            }
        }
        return $info;
    }

    public function getTables($dbName = '')
    {
        $sql = !empty($dbName) ? 'SHOW TABLES FROM ' . $dbName : 'SHOW TABLES ';
        $result = $this->query($sql);
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[$key] = current($val);
            }
        }
        return $info;
    }

    public function replace($data, $options = array())
    {
        $values = $fields = array();
        foreach ($data as $key => $val) {
            $value = $this->parseValue($val);
            if (is_scalar($value)) {
                $values[] = $value;
                $fields[] = $this->parseKey($key);
            }
        }
        $sql = 'REPLACE INTO ' . $this->parseTable($options['table'])
             . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $values) . ')';
        return $this->execute($sql);
    }

    public function insertAll($datas, $options = array(), $replace = false)
    {
        if (!is_array($datas[0])) {
            return false;
        }
        $fields = array_keys($datas[0]);
        array_walk($fields, array($this, 'parseKey'));
        $values = array();
        foreach ($datas as $data) {
            $value = array();
            foreach ($data as $key => $val) {
                $val = $this->parseValue($val);
                if (is_scalar($val)) {
                    $value[] = $val;
                }
            }
            $values[] = '(' . implode(',', $value) . ')';
        }
        $sql = ($replace ? 'REPLACE' : 'INSERT') . ' INTO '
             . $this->parseTable($options['table'])
             . ' (' . implode(',', $fields) . ') VALUES ' . implode(',', $values);
        return $this->execute($sql);
    }

    public function close()
    {
        if ($this->linkID instanceof \mysqli) {
            $this->linkID->close();
        }
        $this->linkID = null;
        $this->_linkID = null;
        $this->connected = false;
    }

    public function error()
    {
        if ($this->_linkID instanceof \mysqli) {
            $this->error = $this->_linkID->errno . ':' . $this->_linkID->error;
        } else {
            $this->error = '数据库连接未初始化';
        }
        if ('' != $this->queryStr) {
            $this->error .= "\n [ SQL语句 ] : " . $this->queryStr;
        }
        return $this->error;
    }

    public function escapeString($str)
    {
        if ($this->_linkID instanceof \mysqli) {
            return $this->_linkID->real_escape_string($str);
        }
        return addslashes($str);
    }

    protected function parseKey(&$key)
    {
        $key = trim($key);
        if (!preg_match('/[,\'\"\*\(\)`.\s]/', $key)) {
            $key = '`' . $key . '`';
        }
        return $key;
    }
}