<?php
namespace Mouf\Utils\Log\MailLogger;

use Mouf\Utils\Mailer\MailInterface;

/**
 * This class represents an error mail to be sent by the Error Mail logger.
 * It must be configured with recipients and senders to be successfully sent.
 * 
 * Note: default encoding for the mail is UTF-8 if not specified.
 * 
 * @Component
 */
class ErrorMail implements MailInterface {
	
	private $bodyText;
	private $bodyHtml;
	private $title;
	
	private $from;
	private $toRecipients = array();
	private $ccRecipients = array();
	private $bccRecipients = array();
	private $attachements = array();
	private $encoding = "utf-8";
	private $autocreateMissingText = false;
	
	/**
	 * Returns the mail text body.
	 *
	 * @return string
	 */
	function getBodyText() {
		if ($this->bodyText != null) {
			return $this->bodyText;
		} elseif ($this->autocreateMissingText == true) {
			return $this->removeHtml($this->bodyHtml);
		}
	}
	
	/**
	 * The mail text body.
	 *
	 * @param string $bodyText
	 */
	function setBodyText($bodyText) {
		$this->bodyText = $bodyText;
	}
	
	/**
	 * Returns the mail html body.
	 *
	 * @return string
	 */
	function getBodyHtml() {
		return $this->bodyHtml;
	}

	/**
	 * The mail html body.
	 *
	 * @param string $bodyHtml
	 */
	function setBodyHtml($bodyHtml) {
		$this->bodyHtml = $bodyHtml;
	}
	
	/**
	 * Returns the mail title.
	 *
	 * @return string
	 */
	function getTitle() {
		return $this->title;
	}
	
	/**
	 * The mail title.
	 *
	 * @param string $title
	 */
	function setTitle($title) {
		$this->title = $title;
	}
	
	/**
	 * Returns the "From" email address
	 *
	 * @return MailInterface The first element is the email address, the second the name to display.
	 */
	function getFrom() {
		return $this->from;
	}

	/**
	 * The mail from address.
	 *
	 * @Property
	 * @param MailAddressInterface $from
	 */
	function setFrom(MailAddressInterface $from) {
		$this->from = $from;
	}
	
	/**
	 * Returns an array containing the recipients.
	 *
	 * @return array<MailAddressInterface>
	 */
	function getToRecipients() {
		return $this->toRecipients;
	}

	/**
	 * An array containing the recipients.
	 *
	 * @Property
	 * @param array<MailAddressInterface> $toRecipients
	 */
	function setToRecipients($toRecipients) {
		$this->toRecipients = $toRecipients;
	}
	
	/**
	 * Adss a recipient.
	 *
	 * @param MailAddressInterface $toRecipient
	 */
	function addToRecipient(MailAddressInterface $toRecipient) {
		$this->toRecipients[] = $toRecipient;
	}
	
	/**
	 * Returns an array containing the recipients in Cc.
	 *
	 * @return array<MailAddressInterface>
	 */
	function getCcRecipients() {
		return $this->ccRecipients;
	}
	
	/**
	 * An array containing the recipients.
	 *
	 * @Property
	 * @param array<MailAddressInterface> $ccRecipients
	 */
	function setCcRecipients($ccRecipients) {
		$this->ccRecipients = $ccRecipients;
	}
	
	/**
	 * Adds a recipient.
	 *
	 * @param MailAddressInterface $ccRecipient
	 */
	function addCcRecipient(MailAddressInterface $ccRecipient) {
		$this->ccRecipients[] = $ccRecipient;
	}
	
	/**
	 * Returns an array containing the recipients in Bcc.
	 *
	 * @return array<MailAddressInterface>
	 */
	function getBccRecipients() {
		return $this->bccRecipients;
	}
	
	/**
	 * An array containing the recipients.
	 *
	 * @Property
	 * @param array<MailAddressInterface> $bccRecipients
	 */
	function setBccRecipients($bccRecipients) {
		$this->bccRecipients = $bccRecipients;
	}
	
	/**
	 * Adds a recipient.
	 *
	 * @param MailAddressInterface $ccRecipient
	 */
	function addBccRecipient(MailAddressInterface $bccRecipient) {
		$this->bccRecipients[] = $bccRecipient;
	}
	
	/**
	 * Returns an array of attachements for that mail.
	 *
	 * @return array<MailAttachmentInterface>
	 */
	function getAttachements() {
		return $this->attachements;
	}
	
	/**
	 * An array containing the attachments.
	 *
	 * @Property
	 * @param array<MailAttachmentInterface> $attachements
	 */
	function setAttachements($attachements) {
		$this->attachements = $attachements;
	}
	
	/**
	 * Adds an attachment.
	 *
	 * @param MailAttachmentInterface attachement
	 */
	function addAttachement(MailAttachmentInterface $attachement) {
		$this->attachements[] = $attachement;
	}
	
	/**
	 * Returns the encoding of the mail.
	 *
	 * @return string
	 */
	function getEncoding() {
		return $this->encoding;
	}
	
	/**
	 * The mail encoding. Defaults to utf-8.
	 *
	 * @Property
	 * @param string $encoding
	 */
	function setEncoding($encoding) {
		$this->encoding = $encoding;
	}
	
	/**
	 * If no body text is set for that mail, and if autoCreateBodyText is set to true, this object will create the body text from the body HTML text,
	 * by removing any tags.
	 * 
	 * @param boolean $autoCreate
	 */
	public function autoCreateBodyText($autoCreate) {
		$this->autocreateMissingText = $autoCreate;
	}
	
	/**
	 * Removes the HTML tags from the text.
	 * 
	 * @param string $s
	 * @param string $keep The list of tags to keep
	 * @param string $expand The list of tags to remove completely, along their content
	 */
	private function removeHtml($s , $keep = '' , $expand = 'script|style|noframes|select|option'){
        /**///prep the string
        $s = ' ' . $s;
       
        /**///initialize keep tag logic
        if(strlen($keep) > 0){
            $k = explode('|',$keep);
            for($i=0;$i<count($k);$i++){
                $s = str_replace('<' . $k[$i],'[{(' . $k[$i],$s);
                $s = str_replace('</' . $k[$i],'[{(/' . $k[$i],$s);
            }
        }
       
        //begin removal
        /**///remove comment blocks
        while(stripos($s,'<!--') > 0){
            $pos[1] = stripos($s,'<!--');
            $pos[2] = stripos($s,'-->', $pos[1]);
            $len[1] = $pos[2] - $pos[1] + 3;
            $x = substr($s,$pos[1],$len[1]);
            $s = str_replace($x,'',$s);
        }
       
        /**///remove tags with content between them
        if(strlen($expand) > 0){
            $e = explode('|',$expand);
            for($i=0;$i<count($e);$i++){
                while(stripos($s,'<' . $e[$i]) > 0){
                    $len[1] = strlen('<' . $e[$i]);
                    $pos[1] = stripos($s,'<' . $e[$i]);
                    $pos[2] = stripos($s,$e[$i] . '>', $pos[1] + $len[1]);
                    $len[2] = $pos[2] - $pos[1] + $len[1];
                    $x = substr($s,$pos[1],$len[2]);
                    $s = str_replace($x,'',$s);
                }
            }
        }
       
        /**///remove remaining tags
        while(stripos($s,'<') > 0){
            $pos[1] = stripos($s,'<');
            $pos[2] = stripos($s,'>', $pos[1]);
            $len[1] = $pos[2] - $pos[1] + 1;
            $x = substr($s,$pos[1],$len[1]);
            $s = str_replace($x,'',$s);
        }
       
        /**///finalize keep tag
        for($i=0;$i<count($k);$i++){
            $s = str_replace('[{(' . $k[$i],'<' . $k[$i],$s);
            $s = str_replace('[{(/' . $k[$i],'</' . $k[$i],$s);
        }
       
        return trim($s);
    }
}
?>