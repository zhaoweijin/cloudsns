
<?php
/**
 * avos模型 - 业务逻辑模型
 * @author zwj <zhaoweijin@appgame.com> 
 * @version TS3.0
 */
tsload(ADDON_PATH.'/library/leancloud-php-library/AV.php');
class LeanCloudModel extends Model{
	// 表名(class)

    protected $creditType = '';
    protected $int_array = array('admin_uid','follower_uid','who_can_post');
    protected $fields_array = '';

    // protected $db_feed = array('feed_id'=>'int',
				// 				'uid'=>'int',
				// 				'type'=>'string',
				// 				'app'=>'string',
				// 				'app_row_table'=>'string',
				// 				'app_row_id'=>'int',
				// 				'publish_time'=>'int',
				// 				'is_del'=>'int',
				// 				'from tin'=>'int',
				// 				'comment_count'=>'int',
				// 				'repost_count'=>'int',
				// 				'comment_all_count'=>'int',
				// 				'digg_count'=>'int',
				// 				'is_repost'=>'int',
				// 				'is_audit'=>'int'
				// 			);


	/**
     * 转换类型
     * @param string $name C格式表名
     * @access public
     * @return string
     */
    public function get_field($name){
    	$this->tableName = $name;
    	if(false && S('leancloud_'.$name)){
    		return S('leancloud_'.$name);
    	}else{
    		$this->flush();
    		$fields = $this->db->getFields($this->getTableName());
    		
	    	foreach ($fields as $k => $v) {
	    		if(strpos($v['type'],'int')!==false)
	    			$data[$k] = 'int';
	    		else
	    			$data[$k] = 'string';    		
	    	}
	    	S('leancloud_'.$name,$data);
	    	return $data;
    	}
    }

	/**
     * 数组数值转换
     * @param array $data 
     * @param array $fdata
     * @access public
     * @return string
     */
	public function mychange(&$data,$fdata) 
	{
		foreach ($data as $k => &$v) {
			if(is_array($v))
				continue;
			if ($fdata[$k]=="int")
				$v = (int)$v;
			else
				$v = (string)$v;			
		}
	}

	/**
     * 转换类型
     * @access public
     * @return string
     */
    public function changeType(&$array){
    	foreach ($array as $key => $value) {
    		if(in_array($key, $this->int_array))
				$array[$key] = (int)$value;
			else
				$array[$key] = $value;
    	}
    }

    /**
     * 得到完整的数据表名
     * @param array $array 需要转换数组
     * @access public
     * @return string
     */
    public function getTableName()
    {
        
        $tableName  = !empty($this->tablePrefix) ? $this->tablePrefix : '';
        if(!empty($this->tableName)) {
            $tableName .= $this->tableName;
        }else{
            $tableName .= parse_name($this->name);
        }
        $tableName .= !empty($this->tableSuffix) ? $this->tableSuffix : '';
        if(!empty($this->dbName))
            $tableName    =  $this->dbName.'.'.$tableName;
        $this->trueTableName    =   strtolower($tableName);
        
        return $this->trueTableName;
    }

	/**
	 * avos数据插入
	 * @param varchar classname 表名
	 * @param int last_id 插入id
	 * @param array data 数据
	 * @return object 
	 */
	public function cloud_save($classname,$last_id,$data){
		//$this->changeType($data);


		$fdata = $this->get_field(parse_name($classname));
		$data = array_intersect_key($data, $fdata);
		$this->mychange($data,$fdata);		

		$this->tableName = parse_name($classname);
		$this->flush();
		$pk = $this->getPk();

		$obj = new leancloud\AVObject(parse_name($classname));
		foreach ($data as $key => $value) {			
			$obj->$key = $value;
		}	

		$pk = $pk == 'id'?'di':$pk;	
		
		$obj->$pk = $last_id;
		
		$save = $obj->save();

		if(is_string($save) && strstr($save,'system_error')){
			var_dump($save);exit;
		}
			
		return $save;
	}

	/**
	 * avos批量数据插入
	 * @param varchar classname 表名
	 * @param array last_id=>data(array) 键值对二维数组数据
	 * @return object 
	 */
	public function cloud_save_all($classname,$ldata){

		$obj = new leancloud\AVObject(parse_name($classname));
		$fdata = $this->get_field(parse_name($classname));
		$this->tableName = parse_name($classname);
		$this->flush();
		$pk = $this->getPk();
		$pk = $pk == 'id'?'di':$pk;
		$requests = array();

		foreach ($ldata as $k => $v) {
			$v = array_intersect_key($v, $fdata);
			$this->mychange($v,$fdata);
			$v[$pk] = $k;
			$requests[]['body'] = $v;
		}
		$obj->requests = $requests;
		$save = $obj->save_all();
//var_dump($obj->requests);exit;	
		if(is_string($save) && strstr($save,'system_error')){
			var_dump($save);exit;
		}
			
		return $save;
	}

	/**
	 * avos数据更新
	 * @param varchar classname 表名
	 * @param array wdata where数据 $key字段 $value值
	 * @param array data update数据 $key字段 $value值
	 * @return object 
	 */
	public function cloud_update($classname,$wdata,$data){
		//$this->changeType($data);

		$fdata = $this->get_field(parse_name($classname));
		$wdata = array_intersect_key($wdata, $fdata);
		$this->mychange($wdata,$fdata);		



		$query = new leancloud\AVQuery(parse_name($classname));
		if(is_array($wdata)){
			foreach ($wdata as $_k1 => $_v1) {
				$_k1 = $_k1 == 'id'?'di':$_k1;
				if(is_array($_v1) && $_v1[0]=='in'){					
					//$query->where($_k1,array('$all'=>implode(',',$_v1[1])));					
					$query->where($_k1,array('$in'=>array_map('intval',$_v1[1])));					
				}else{
					$query->where($_k1,$_v1);	
				}
			}
				
			$ret = $query->find();
			
			if($ret->results && is_array($ret->results)){
				foreach ($ret->results as $_k3 => $_v3) {
					$updateObject = new leancloud\AVObject(parse_name($classname));
					
					foreach ($data as $_k4 => $_v4) {							
						$updateObject->$_k4 = $_v4;
					}
											
					$return[] = $updateObject->update($_v3->objectId);
				}															
			}

			
			return $return;
		}
	}

	/**
	 * avos数据删除
	 * @param varchar classname 表名
	 * @param array wdata where数据 array[key] => array('in' => array(...,...))
	 * @return object 
	 */
	public function cloud_delete($classname,$wdata){
		$query = new leancloud\AVQuery(parse_name($classname));
		if(is_array($wdata)){
			foreach ($wdata as $_k1 => $_v1) {
				$_k1 = $_k1 == 'id'?'di':$_k1;
				if(is_array($_v1) && $_v1[0]=='in'){
					$query->where($_k1,array('$in'=>array_map('intval',$_v1[1])));					
				}else{
					$query->where($_k1,$_v1);	
				}
			} 	
			$ret = $query->find();
			if($ret->results && is_array($ret->results)){
				foreach ($ret->results as $_k3 => $_v3) {
					$updateObject = new leancloud\AVObject(parse_name($classname));
					$return[] = $updateObject->delete($_v3->objectId);
				}															
			}			 
			return $return;			
		}
	}
}