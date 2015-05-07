
<?php
/**
 * avos模型 - 业务逻辑模型
 * @author zwj <zhaoweijin@appgame.com> 
 * @version TS3.0
 */
tsload(ADDON_PATH.'/library/leancloud-php-library/AV.php');
class LeanCloudModel{
	// 表名(class)
    protected $class = '';
    protected $creditType = '';

	public function __construct($name='') {		
        $this->class   =  $name;
	}

	/**
	 * avos数据插入
	 * @param varchar classname 表名
	 * @param array data 数据
	 * @return object 
	 */
	public function cloud_save($data){
		$obj = new leancloud\AVObject(parse_name($this->class));
		foreach ($data as $key => $value) {
			if($key == 'admin_uid')
				$obj->$key = (int)$value;
			else
				$obj->$key = $value;
		}
		$save = $obj->save();
		return $save;
	}

	/**
	 * avos数据更新
	 * @param varchar classname 表名
	 * @param array wdata where数据 $key字段 $value值
	 * @param array data update数据 $key字段 $value值
	 * @return object 
	 */
	public function cloud_update($wdata,$data){

		$query = new leancloud\AVQuery(parse_name($this->class));
		if(is_array($wdata)){
			foreach ($wdata as $_k1 => $_v1) {

				if(is_array($_v1) && array_key_exists('in',$_v1)){
					$query->where($_k1,array("$all"=>$_v1['in']));					
				}else{
					$query->where($_k1,$_v1);	
				}
				$ret = $query->find();
				if($ret->results && is_array($ret->results)){
					foreach ($ret->results as $_k3 => $_v3) {
						$updateObject = new leancloud\AVObject(parse_name($this->name));
						$return[] = $updateObject->update($_v3->objectId);
					}															
				}

			}  
		}
	}

	/**
	 * avos数据删除
	 * @param varchar classname 表名
	 * @param array wdata where数据 array[key] => array('in' => array(...,...))
	 * @return object 
	 */
	public function cloud_delete($wdata){
		$query = new leancloud\AVQuery(parse_name($this->name));
		if(is_array($wdata)){
			foreach ($wdata as $_k1 => $_v1) {

				if(is_array($_v1) && array_key_exists('in',$_v1)){
					$query->where($_k1,array("$all"=>$_v1['in']));					
				}else{
					$query->where($_k1,$_v1);	
				}
				$ret = $query->find();
				if($ret->results && is_array($ret->results)){
					foreach ($ret->results as $_k3 => $_v3) {
						$updateObject = new leancloud\AVObject(parse_name($this->name));
						$return[] = $updateObject->delete($_v3->objectId);
					}															
				}

			}  
			return $return;			
		}
	}

	/**
	 * 获取所有系统积分规则
	 */
	public function get_credit_rules() {
		if (($res = F ( '_service_credit_rules' )) === false) {
			$res = M ( 'credit_setting' )->order ( 'type ASC' )->findAll ();
			F ( '_service_credit_rules', $res );
		}
		return $res;
	}


	public function get_credit_type(){
		if (($this->creditType = F ( '_service_credit_type' )) === false) {
			$this->creditType = M ( 'credit_type' )->order ( 'id ASC' )->findAll ();
			F ( '_service_credit_type', $this->creditType );
		}
	}

	/**
	 * TS2兼容方法：设置用户积分
	 * 操作用户积分
	 *
	 * @param int $uid
	 *        	用户ID
	 * @param array|string $action
	 *        	系统设定的积分规则的名称
	 *        	或临时定义的一个积分规则数组，例如array('score'=>-4,'experience'=>3)即socre减4点，experience加三点
	 * @param string|int $type
	 *        	reset:按照操作的值直接重设积分值，整型：作为操作的系数，-1可实现增减倒置
	 * @return Object
	 */
	public function cloud_set_user_credit($uid, $action, $type = 1) {
		$this->get_credit_type();
		if (! $uid) {
			$this->info = false;
			return $this;
		}
		if (is_array ( $action )) {
			$creditSet = $action;
		} else {
			// 获取配置规则
			$credit_ruls = $this->get_credit_rules ();
			foreach ( $credit_ruls as $v )
				if ($v ['name'] == $action)
					$creditSet = $v;
		}
		if (! $creditSet) {
			$this->info = '积分规则不存在';
			return $this;
		}
		$creditUserDao = M ( 'credit_user' );
		$creditUser = $creditUserDao->where ( "uid={$uid}" )->find (); // 用户积分
		                                                              // 积分计算
		if ($type == 'reset') {
			foreach ( $this->creditType as $v ) {
				$creditUser [$v ['name']] = $creditSet [$v ['name']];
			}
		} else {
			$type = intval ( $type );
			foreach ( $this->creditType as $v ) {
				$creditUser [$v ['name']] = $creditUser [$v ['name']] + ($type * $creditSet [$v ['name']]);
			}
		}
		$creditUser ['uid'] || $creditUser ['uid'] = $uid;
		// $res = $creditUserDao->save ( $creditUser ) || $res = $creditUserDao->add ( $creditUser ); // 首次进行积分计算的用户则为插入积分信息
		if($creditUserDao->where('uid='.$creditUser['uid'])->count()){
			$map['id'] = $creditUser['id'];
			$map['uid'] = $creditUser['uid'];
			unset($creditUser['id']);unset($creditUser['uid']);
			//$res = $creditUserDao->where($map)->save ( $creditUser );
			//$this->class = 'credit_user';
			$this->cloud_update($map,$creditUser);
		}else{
			//$res = $creditUserDao->add ( $creditUser );
			//$this->class = 'credit_user';
			$this->cloud_save($creditUser);
		}	
		
	}

}