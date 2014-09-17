<?php

class form{
	function __construct($ra){
		$this->form = $ra;
		}

	function think(){
		$cnt = 0;
		foreach($this->form['input'] as $name => $_val){
			$ra =& $this->form['input'][$name];
			$val = $this->getValue($name);
			
			// required?
			//if($ra['required'] and !strlen(trim($val)))
			//	$this->error($name,'Required');

			// has val?
			//else
			if($this->has($name)){
				$cnt++;
				$ra['value'] = $val;
				
				// evaluate
				foreach($ra['eval'] as $e => $err){
					$e = self::toEval($e,'$ra["value"]');
					if(!eval($e)){
						$this->error($name,str_replace('{}',$ra['text'],$err));
						$this->form['success'] = false;
						}
					}
				}
			}
		
		$filled = ($cnt / count($this->form['input']));
		//funx::debug("The form has $cnt items filled in for a percentage of $filled");
		if($filled > 0.2 and !isset($this->form['success']))
			$this->form['success'] = true;

		return $this->form['success'];
		}
	
	function draw($sep = '<br />'){
		$ret = '';
	
		// draw input
		foreach($this->form['input'] as $name => $ra)
			$ret .= $this->drawSpan($name,$ra)."\n".
				$this->drawInput($name,$ra).
				(strlen($ra['type'] and $ra['type'] != 'hidden') ?
					self::drawErrors($ra).($ra['no-br'] ? '' : $sep) : '')."\n";

		// draw form
		$ret = '<form'.
			self::drawIf('name',$this->form['name']).
			self::drawIf('id',$this->form['name']).
			self::drawIf('enctype',$this->form['enctype']).
			' method="'.$this->form['method'].'"'.
			' action="'.$this->form['action'].'"'.
			'>'.$ret.'</form>';

		return $ret;
		}
	
	private function getValue($name){
		$ret = ($this->form['method'] == 'post' ?
			$_POST[$name] : $_GET[$name]);

		$filter = $this->form['input'][$name]['filter'];
		if(strlen($filter))
			$ret = eval(self::toEval($filter,'$ret'));
		
		return $ret;
		}
	
	function getAll($partOfName =''){
		$ret = array();
		
		foreach($this->form['input'] as $name => $i){
			if(!strlen($partOfName)
			or strstr($name,$partOfName))
				$ret[$name] = $i['value'];
			}
		
		return $ret;
		}

	function has($name){
		// special for file
		if($this->form['input'][$name]['type'] == 'file')
			return isset($_FILES['upload']['tmp_name']);
	
		return ($this->form['method'] == 'post' ?
			isset($_POST[$name]) : isset($_GET[$name]));
		}

	function hasErrors(){
		foreach($this->form['input'] as $i){
			foreach($i['errors'] as $j){
				if(strlen($j)){
					return true;
					}
				}
			}
		
		return false;
		}
	
	function clearErrors(){
		foreach($this->form['input'] as $k => $i){
			unset($this->form['input'][$k]['errors']);
			}
		}
	
	function error($name,$err){
		$this->form['input'][$name]['errors'][] = $err;
		}

	function get($name){
		return $this->form['input'][$name]['value'];
		}
	
	function set($name,$val){
		return $this->form['input'][$name]['value'] = $val;
		}
	
	function delete($name){
		unset($this->form['input'][$name]);
		}

	static private function drawIf($k,$i){
		if(strlen($i))
			return " $k=\"$i\"";
		return '';
		}
	
	static private function drawSpan($name,$ra){
		$ret = '';
		if($ra['type'] != 'hidden')
			$ret .= (strlen($ra['text']) ? $ra['text'].'' : '');
		if(strlen($ret)
		and strlen($ra['type']))
			$ret = "<label for=\"$name\">$ret</label>";
	
		return $ret;
		}

	static private function drawErrors($ra){
		$ret = '';
		foreach($ra['errors'] as $e)
			$ret .= (strlen($ret) ? '<br />' : '').$e;
		if(strlen($ret))
			$ret = '<div class="err">'.$ret.'</div>';
		return $ret;
		}
	
	private function drawInput($name,$ra){
		// call draw functions
		$func = ucfirst($ra['type']);
		if(strlen($func)){
			$func = 'draw'.$func;
			if(is_callable(array($this,$func)))
				$ret = call_user_func(array($this,$func),$name,$ra);
			}
		
		// draw special text around input
		if(strlen($ra['message']))
			$ret = str_replace('{}',$ret,$ra['message']);

		// wrap
		//if($ra['type'] != 'hidden')
		//	$ret = $ret;

		return $ret;
		}
	
	private function drawHidden($name,$ra){
		return '<input type="hidden"'.
			' name="'.$name.'"'.
			' value="'.$ra['value'].'"'.
			'>';
		}

	private function drawImage($name,$ra){
		return '<img src="'.$ra['src'].'"'.
			self::drawIf('align',$ra['align']).
			self::drawIf('alt',$ra['width']).
			self::drawIf('alt',$ra['height']).
			self::drawIf('alt',$ra['alt']).
			'/ >';
		}

	private function drawText($name,$ra){
		return '<input type="text"'.
			' name="'.$name.'" id="'.$name.'"'.
			' value="'.$ra['value'].'"'.
			self::drawIf('class',$ra['class']).
			self::drawIf('size',$ra['size']).
			self::drawIf('maxlength',$ra['max']).
			'>';
		}
	
	private function drawTextarea($name,$ra){
		return '<textarea'.
			' name="'.$name.'" id="'.$name.'"'.
			self::drawIf('id',$ra['id']).
			self::drawIf('rows',$ra['rows']).
			self::drawIf('cols',$ra['cols']).
			'>'.$ra['value'].'</textarea>';
		}
	
	private function drawDropdown($name,$ra){
		foreach($ra['options'] as $k => $i)
			$optionTxt .= '<option value="'.$k.'"'.
				((strlen($ra['selected']) and $ra['selected'] == $k) ? ' selected' : '').
				'>'.$i.'</option>';
		return '<select name="'.$name.'" id="'.$name.'">'.$optionTxt.'</select>';
		}

	private function drawRadio($name,$ra){
		$ret = '';
		
		$lastK = ra::lastKey($ra['options']);

		foreach($ra['options'] as $k => $i)
			$ret .= '<input type="radio"'.
				' name="'.$name.'" id="'.$name.'" value="'.$k.'"'.
				((strlen($ra['selected']) and $ra['selected'] == $k) ?
					' checked' : '').
				self::drawIf('onclick',$ra['onclick'][$k]).
				' />'.$i.($k == $lastK ? '' : '<br />');
		
		$ret = '<div class="radio_buttons">'.$ret.'</div>';

		return $ret;
		}

	private function drawCheckbox($name,$ra){
		return self::drawHidden($name,array()).
			'<input type="checkbox"'.
			' name="'.$name.'" id="'.$name.'"'.
			' value="on"'.
			($ra['value'] == 'on' ? ' checked' : '').
			'>';
		}

	private function drawFile($name,$ra){
		return self::drawHidden('MAX_FILE_SIZE',array('value' => $ra['max'])).
			'<input name="upload" type="file" '.
			self::drawIf('style',$ra['style']).
			' />';
		}

	private function drawSubmit($name,$ra){
		return '<input'.
			self::drawIf('id',$ra['id']).
			self::drawIf('class',$ra['class']).
			self::drawIf('onclick',$ra['onclick']).
			($ra['disabled'] ? ' disabled' : '').
			((strlen($ra['img']) and is_file($ra['img'])) ?
				' type="image"'.
				' src="/'.$ra['img'].'"'.
				' alt="'.$ra['value'].'"'.
				self::drawIf('width',$ra['width']).
				self::drawIf('height',$ra['height']).
				self::drawIf('style',$ra['style'])
				:
				' type="submit"'.
				' value="'.$ra['value'].'"'
				).
			' name="'.$name.'" id="'.$name.'"'.
			'>';
		}

	private static function toEval($code,$value){
		return str_replace('{}',$value,"return $code;");
		}
	}

?>
