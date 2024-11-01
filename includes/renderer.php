<?php
/**
 * Class to create rendered to render template.
 */
class WTools_Renderer {
	private $template;
	/**
	 * 
	 * @param string $template
	 *  Default template file path.
	 */
	public function __construct($template) {
		$this->template = $template;
	}
	
	public static function getTemplate($default_template) {
		if (!($template = locate_template(basename($default_template)))) {
			$template = $default_template;
		}
		return $template;
	}
	
	public function render($return=FALSE) {
		if ($return) {
			ob_start();
			include static::getTemplate($this->template);
			$output = ob_get_clean();
			return $output;
		}
		else {
			include static::getTemplate($this->template);
		}
	}
}
