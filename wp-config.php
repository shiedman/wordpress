<?php
/**
 * WordPress基础配置文件。
 *
 * 本文件包含以下配置选项：MySQL设置、数据库表名前缀、密钥、
 * WordPress语言设定以及ABSPATH。如需更多信息，请访问
 * {@link http://codex.wordpress.org/zh-cn:%E7%BC%96%E8%BE%91_wp-config.php
 * 编辑wp-config.php}Codex页面。MySQL设置具体信息请咨询您的空间提供商。
 *
 * @package WordPress
 */

//define('WP_DEBUG', true);
//define('SAVEQUERIES', true);

/**
 * SAE Storage domain
 * 用于保存上传文件。如果还没有开启Storage服务，登录SAE后，
 * 服务管理>Storage>新建Domain，名字建议取wordpress。
 * 如果不打算上传任何文件，可以将SAE_STORAGE设为FALSE: define('SAE_STORAGE',FALSE);
 */
define('SAE_STORAGE',wordpress);

require('wp-config-sae.php');

/** local mysql setting **/
include('wp-config-local.php');

/** 创建数据表时默认的文字编码 */
define('DB_CHARSET', 'utf8');

/**
 * WordPress数据表前缀。
 *
 * 如果您有在同一数据库内安装多个WordPress的需求，请为每个WordPress设置
 * 不同的数据表前缀。前缀名只能为数字、字母加下划线。
 */
$table_prefix  = 'wp_';

/** 数据库整理类型。如不确定请勿更改 */
define('DB_COLLATE', '');

/**#@+
 * 身份认证密钥与盐。
 *
 * 修改为任意独一无二的字串！
 * 或者直接访问{@link https://api.wordpress.org/secret-key/1.1/salt/
 * WordPress.org密钥生成服务}
 * 任何修改都会导致所有cookies失效，所有用户将必须重新登录。
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '1?=R`12I.B[FuX+3ESvKskt>es)8:RD8!r V16Z5:|+m+8OF}J:opt*@9p(!+L05');
define('SECURE_AUTH_KEY',  ';r@oKC{Q5tyX5g~Dx${Mm`qvd1_G:?(&>)FM#G4VX5R#%88w[wto%3< .C>Y:*:k');
define('LOGGED_IN_KEY',    'kd+ux/!Z~,:>!K7r}hks;]# Ld{~j|@UVnk~im(+e2>.[N+>&<-Ov9ruu,M]yPi2');
define('NONCE_KEY',        'YmmQn`r7t2@BEOy+4RJ7OGO_J&!D0oHxI%GuY?)lQb$: `c2r<47zfA4*S~[7XJF');
define('AUTH_SALT',        'KB#SyqEsB9}*MM|i/-.G+PR*V-@UBXsBFV4c~!Ag`+QM0HRxltD^),N!Kq!|6.A+');
define('SECURE_AUTH_SALT', '3~OksgDM~-V@K|9jghw&,N*4inN_&)|D|!f-dY.|T{y9$<C94%6-PS?+;Y$=Rk+{');
define('LOGGED_IN_SALT',   '-W(>Bc5s]+q]5:EW4!u.9d^AW1DEjxTB:~}n,dh[Zg^kU2A-(-Kj5A$Y=0563_l{');
define('NONCE_SALT',       '2Yg+;y=0*_{_l4hY[:i#K_JUtB#U#{t<3zq&~Ga3g3H#~QK**|2FH|bkKv*?} TV');

/**
 * WordPress语言设置，中文版本默认为中文。
 *
 * 本项设定能够让WordPress显示您需要的语言。
 * wp-content/languages内应放置同名的.mo语言文件。
 * 例如，要使用WordPress简体中文界面，请在wp-content/languages
 * 放入zh_CN.mo，并将WPLANG设为'zh_CN'。
 */
define('WPLANG', 'zh_CN');

/** 
 * Turn off WordPress Post Revisions and Autosav 
 * DELETE FROM wp_posts WHERE post_type = "revision";
 */
define('WP_POST_REVISIONS', false);
define('AUTOSAVE_INTERVAL', 36000);
define('AUTOMATIC_UPDATER_DISABLED', true);
/**
 * with cron enabled, every time page refresh, cron checking would trigger.
 * Disable the cron entirely and manully by requesting to:
 * http://yourwebsite.com/wp-cron.php?doing_wp_cron
 */
define('DISABLE_WP_CRON', true);

/**
 * zh_CN本地化设置：启用ICP备案号显示
 *
 * 可在设置→常规中修改。
 * 如需禁用，请移除或注释掉本行。
 */
//define('WP_ZH_CN_ICP_NUM', true);

/* 好了！请不要再继续编辑。请保存本文件。使用愉快！ */

/** WordPress目录的绝对路径。 */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** 设置WordPress变量和包含文件。 */
require_once(ABSPATH . 'wp-settings.php');
