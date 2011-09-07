<?
include_once _'Imap.class.php';

$imap = new Imap('localhost','email_username','email_password');
var_dump($imap->get_is_connected());
echo "<pre>";
print_r($imap->returnImapMailBoxmMsgInfoObj());
print_r($imap->returnImapHeadersArr());
print_r($imap->returnMailboxListArr());
print_r($imap->returnMailBoxHeaderArr());
print_r($imap->returnEmailMessageArr(1));
echo "</pre>";

echo $imap->saveAttachment(2,2,'/path/to/where/you/want/the/attachment/saved'.md5('14'.date('Y-m-d H:i:s')))
?> 