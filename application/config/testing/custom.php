<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/********
 * CUSTOM CONSTANTS
 **/

define('SENDER_EMAIL', 'hhlets@gmail.com');
define('CONTACT_EMAIL', 'hhlets@gmail.com');
define('ADMIN_EMAIL', 'thierry@thde.pro');
define('ENCODE_CODE_WORD', 'azerty123456');
define('ROOT_PATH',APPPATH.'../');
define('UPLOAD_PATH', 'upload/');
define('MEDIA_PATH', UPLOAD_PATH.'medias/');
define('CACHE_PATH', UPLOAD_PATH.'cache/');
define('DEFAULT_AVATAR', '/assets/img/avatar.png');
define('DEFAULT_AVATAR_FAKE', '/assets/img/avatar_fake.png');
define('DEFAULT_IMAGE', '/assets/img/image.png');
define('CACHE_MEDIA', TRUE);

putenv("CLOUDINARY_URL=cloudinary://924521254223698:s2GiPJ4TVqUzvDXCSxUY6oPTunY@lets");

$config['dummy'] = TRUE;


/* End of file custom.php */
/* Location: ./application/config/custom.php */