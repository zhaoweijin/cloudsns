<?php namespace leancloud;

class AVObject extends AVRestClient{
	public $_includes = array();
	private $_className = '';
	private $_version = '/1.1/';

	public function __construct($class=''){
		if($class != ''){
			$this->_className = $class;
		}
		else{
			$this->throwError('include the className when creating a AVObject');
		}

		parent::__construct();
	}

	public function __set($name,$value){
		if($name != '_className'){
			$this->data[$name] = $value;
		}
	}

	public function save(){
		if(count($this->data) > 0 && $this->_className != ''){
			$request = $this->request(array(
				'method' => 'POST',
				'requestUrl' => 'classes/'.$this->_className,
				'data' => $this->data,
			));
			return $request;
		}
	}

	/**
	 * 批量更新 add by zwj
	 * @return object 
	 */
	public function save_all(){
		if(count($this->data) > 0 && $this->_className != ''){
			foreach ($this->data['requests'] as $k => &$v) {
				$v['method'] = 'POST';
				$v['path'] = $this->_version.'classes/'.$this->_className;
			}
			
			//echo(json_encode($this->data));exit;
			$request = $this->request(array(
				'method' => 'POST',
				'requestUrl' => 'batch',
				'requestUrlSon' => 'classes/'.$this->_className,
				'data' => $this->data,
			));
			return $request;
		}
	}

	public function get($id){
		if($this->_className != '' || !empty($id)){
			$request = $this->request(array(
				'method' => 'GET',
				'requestUrl' => 'classes/'.$this->_className.'/'.$id
			));

			if(!empty($this->_includes)){
				$request['include'] = implode(',', $this->_includes);
			}

			return $request;
		}
	}

	public function update($id){
		if($this->_className != '' || !empty($id)){
			$request = $this->request(array(
				'method' => 'PUT',
				'requestUrl' => 'classes/'.$this->_className.'/'.$id,
				'data' => $this->data,
			));

			return $request;
		}
	}

	public function increment($field, $amount){
		$this->data[$field] = $this->dataType('increment', $amount);
	}

	public function decrement($field, $amount){
		$this->data[$field] = $this->dataType('decrement', $amount);
	}


	public function delete($id){
		if($this->_className != '' || !empty($id)){
			$request = $this->request(array(
				'method' => 'DELETE',
				'requestUrl' => 'classes/'.$this->_className.'/'.$id
			));

			return $request;
		}
	}

	public function addInclude($name){
		$this->_includes[] = $name;
	}
}

?>
