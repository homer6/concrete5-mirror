<?php 
/**
 * @package Helpers
 * @category Concrete
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */

/**
 * Functions used to send mail in Concrete.
 * @package Helpers
 * @category Concrete
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */
 
defined('C5_EXECUTE') or die(_("Access Denied."));
class MailHelper {

	protected $headers = array();
	protected $to = array();
	protected $from = array();
	protected $data = array();
	protected $subject = '';
	public $body = '';
	protected $template; 
	
	/** 
	 * Adds a parameter to a mail template
	 * @param string $key
	 * @param string $val
	 * @return void
	 */
	public function addParameter($key, $val) {
		$this->data[$key] = $val;
	}
	
	/** 
	 * Loads an email template from the /mail/ directory
	 * @param string $template 
	 * @param string $pkgHandle 
	 * @return void
	 */
	public function load($template, $pkgHandle = null) {
		extract($this->data);

		// loads template from mail templates directory
		if (file_exists(DIR_FILES_EMAIL_TEMPLATES . "/{$template}.php")) {			
			include(DIR_FILES_EMAIL_TEMPLATES . "/{$template}.php");
		} else if ($pkgHandle != null) {
			if (is_dir(DIR_PACKAGES . '/' . $pkgHandle)) {
				include(DIR_PACKAGES . '/' . $pkgHandle . '/' . DIRNAME_MAIL_TEMPLATES . "/{$template}.php");
			} else {
				include(DIR_PACKAGES_CORE . '/' . $pkgHandle . '/' . DIRNAME_MAIL_TEMPLATES . "/{$template}.php");
			}
		} else {
			include(DIR_FILES_EMAIL_TEMPLATES_CORE . "/{$template}.php");
		}

		if (isset($from)) {
			$this->from($from[0], $from[1]);
		}
		$this->template = $template;
		$this->subject = $subject;
		$this->body = $body;
	}
	
	//if you don't want to use the load method
	public function setBody($body){
		$this->body = $body;
	}
	public function setSubject($subject){
		$this->subject = $subject;
	}	
	
	public function getSubject() {return $this->subject;}
	public function getBody() {return $this->body;}
	
	public function enableMailResponseProcessing($importer, $data) {
		foreach($this->to as $em) {
			$importer->setupValidation($em[0], $data);
		}
		$this->from($importer->getMailImporterEmail());
		$this->body = $importer->setupBody($this->body);		
	}
	
	protected function generateEmailStrings($arr) {
		$str = '';
		for ($i = 0; $i < count($arr); $i++) {
			$v = $arr[$i];
			if (isset($v[1])) {
				$str .= '"' . $v[1] . '" <' . $v[0] . '>';
			} else {
				$str .= $v[0];
			}
			if (($i + 1) < count($arr)) {
				$str .= ',';
			}
		}
		return $str;
	}
	
	/** 
	 * Sets the from address on the email about to be sent out
	 * @param string $email
	 * @param string $name
	 * @return void
	 */
	public function from($email, $name = null) {
		$this->from = array($email, $name);
	}
	
	/** 
	 * Sets to the to email address on the email about to be sent out.
	 * @param string $email
	 * @param string $name
	 * @return void
	 */
	public function to($email, $name = null) {
		$this->to[] = array($email, $name);
	}
		
	/** 
	 * Sends the email
	 */
	public function sendMail() {
		$fromStr = $this->generateEmailStrings($this->from);
		$toStr = $this->generateEmailStrings($this->to);
		if (ENABLE_EMAILS) {
			Loader::library('3rdparty/Zend/Mail');
			$mail = new Zend_Mail(APP_CHARSET);
			if (is_array($this->from)) {
				if ($this->from[0] != '') {
					$from = $this->from;
				}
			}
			if (!isset($from)) {
				$from = array('concrete5-noreply@' . str_replace(array('http://www.', 'https://www.', 'http://', 'https://'), '', BASE_URL), '');
			}

			if (MAIL_SEND_METHOD == "SMTP") {
				Loader::library('3rdparty/Zend/Mail/Transport/Smtp');
				$username = Config::get('MAIL_SEND_METHOD_SMTP_USERNAME');
				$password = Config::get('MAIL_SEND_METHOD_SMTP_PASSWORD');
				$port = Config::get('MAIL_SEND_METHOD_SMTP_PORT');
				if ($username != '') {
					$config = array('auth' => 'login', 'username' => $username, 'password' => $password);
					if ($port != '') {
						$config['port'] = $port;
					}
					$transport = new Zend_Mail_Transport_Smtp(Config::get('MAIL_SEND_METHOD_SMTP_SERVER'), $config);					
				} else {
					$transport = new Zend_Mail_Transport_Smtp(Config::get('MAIL_SEND_METHOD_SMTP_SERVER'));					
				}
			}
			$mail->setFrom($from[0], $from[1]);
			$mail->setSubject($this->subject);
			foreach($this->to as $to) {
				$mail->addTo($to[0], $to[1]);
			}
			$mail->setBodyText($this->body);
			if (MAIL_SEND_METHOD == "SMTP") {
				$mail->send($transport);
			} else {
				$mail->send();
			}
		}
		
		// add email to log
		if (ENABLE_LOG_EMAILS) {
			$l = new Log(LOG_TYPE_EMAILS, true, true);
			if (ENABLE_EMAILS) {
				$l->write('**' . t('EMAILS ARE ENABLED. THIS EMAIL WAS SENT TO mail()') . '**');
			} else {
				$l->write('**' . t('EMAILS ARE DISABLED. THIS EMAIL WAS LOGGED BUT NOT SENT') . '**');
			}
			$l->write(t('Template Used') . ': ' . $this->template);
			$l->write(t('To') . ': ' . $toStr);
			$l->write(t('From') . ': ' . $fromStr);
			$l->write(t('Subject') . ': ' . $this->subject);
			$l->write(t('Body') . ': ' . $this->body);
			$l->close();
		}		
	}
	
}

?>