<?php

if (!$_SERVER['HTTP_HOST'] || $_SERVER['REMOTE_ADDR'] == '127.0.0.1' || strpos($_SERVER['REMOTE_ADDR'], '10.') === 0) {

} else {
	if (is_file("index.php"))
		die(header("location: /index.php"));
	else
		die("No input file specified." . date('r'));
}
if ($_SERVER['argv'] && !$_SERVER['HTTP_HOST']) {
	$str_array = array();
	$str = join('&', $_SERVER['argv']);
	parse_str($str, $str_array);
	settype($str_array, 'array');
	settype($_GET, 'array');
	$_GET = $str_array + $_GET;
}

ini_set("display_errors", false);
$m = new m;
$_GET['act'] = $_GET['act'] ? $_GET['act'] : "index";
$m->$_GET['act']();
class  m
{
	/**
	 * @desc   WHAT?
	 * @author 
	 * @since  2013-08-22 15:16:43
	 * @throws 注意:无DB异常处理
	 */
	function index()
	{
		//设置载入的数据表格:
		$dir = './crontab';
		exec("chmod 0755 {$dir}/* ");
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if ($file == '.' || $file == '..')
					continue;
				//全路径
				$nfile = $dir . '/' . $file;
				if (!is_dir($nfile) && strpos($file, '.sh') !== false) {
					$data = file_get_contents($nfile);
					$data = str_replace("\r", NULL, $data);
					print_r("修改文件:{$nfile}");
					print_r("<br>\n");
					$fp = fopen($nfile, 'w');
					if ($fp) {
						fwrite($fp, $data);
						fclose($fp);
						chmod($nfile, 0755);
					}
				}
			}
			closedir($dh);
		}

		//设置载入的数据表格:
		$dir = './shell';
		if (is_dir($dir)) {
			exec("chmod 0755 {$dir}/*.sh ");
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if ($file == '.' || $file == '..')
						continue;
					//全路径
					$nfile = $dir . '/' . $file;
					if (!is_dir($nfile) && strpos($file, '.sh') !== false) {
						$data = file_get_contents($nfile);
						$data = str_replace("\r", NULL, $data);
						print_r("修改文件:{$nfile}");
						print_r("<br>\n");
						$fp = fopen($nfile, 'w');
						if ($fp) {
							fwrite($fp, $data);
							fclose($fp);
							chmod($nfile, 0755);
						}
					}
				}
				closedir($dh);
			}
		}
	}

	/**
	 * @desc   WHAT?
	 * @author 
	 * @since  2013-08-23 18:23:20
	 * @throws 注意:无DB异常处理
	 */
	function mini_css_js($dir)
	{
		ini_set("display_errors", true);
		echo date("\nY-m-d H:i:s\n");
		$arr = array();
		$conn_db = ocinlogon('OA_SVN', 'OA_SVN', 'gcwo');
		if ($_GET['model_id']) {
			$sql = "select *
  from OA.T_SVN_COMMITLOGS t
 where t.model_id = :model_id
   and t.commit_date > (select *
                          from (select t.datetime
                                  from OA.T_SVN_UPGRADE_LOG t
                                 where t.model_id = :model_id
                                   and t.out_ok = 0
                                   and t.status = 'OUTER_SUCC'
                                 order by t.datetime desc) x
                         where rownum <= 1)
   and (instr(t.commit_file, '.js') > 0 or instr(t.commit_file, '.css') > 0)
 order by t.commit_date desc ";
			$stmt = ociparse($conn_db, $sql);
			ocibindbyname($stmt, ':model_id', $_GET['model_id']);
			$ocierror = ociexecute($stmt);
			$arr = $_row = array();
			while ($_row = apm_db_fetch_assoc($stmt)) {
				$arr[$_row['COMMIT_FILE']] = true;
			}
		}
		//本次没有提交过js的修改
		if ($_GET['model_id'] && empty($arr)) {
			echo "本次没有提交过js的修改";
			die;
		}
		$this->_mini_css_js($arr, $dir);
		die;

		//如果有版本号码.那么进行版本备份.
		$sql = "select t.curr_version,t.model_name
  from OA.T_SVN_COMMITLOGS t
 where t.model_id = :model_id
   and t.commit_date > (select *
                          from (select t.datetime
                                  from OA.T_SVN_UPGRADE_LOG t
                                 where t.model_id = :model_id
                                   and t.out_ok = 0
                                   and t.status = 'OUTER_SUCC'
                                 order by t.datetime desc) x
                         where rownum <= 1)
 order by t.commit_date desc";
		$stmt = ociparse($conn_db, $sql);
		ocibindbyname($stmt, ':model_id', $_GET['model_id']);
		$ocierror = ociexecute($stmt);
		$arr = $_row = array();
        $_row = apm_db_fetch_assoc($stmt);
		if ($_row) {
			//仅同步tag目录的代码.webid3就是TAG目录
			exec("rsync -vzrtopg --progress  --port 873   --exclude=.svn  --exclude=.settings   --exclude=crontab.php   /home/webid3/sh/{$_row['MODEL_NAME']}/ webid@10.1.20.42::disk/{$_row['MODEL_NAME']}");
		}
	}

	/**
	 * @desc   WHAT?
	 * @author 
	 * @since  2013-08-22 15:16:53
	 * @throws 注意:无DB异常处理
	 */

	function _mini_css_js($arr, $dir)
	{
		static $i = 0;

		if ($i++ > 300)
			die("\$i++ >300 ");
		if (!$dir)
			$dir = $_GET['dir'];
		if (!$dir)
			$dir = '.';
		if (!$_GET['charset'])
			$charset = 'GB18030';
		// Open a known directory, and proceed to read its contents
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if ($file == '.' || $file == '..') continue;
					$real_file = $dir . '/' . $file;
					if (strpos($real_file, 'project/') !== false) continue;
					if (strpos($real_file, '.svn/') !== false) continue;
					if (is_dir($real_file)) {
						$this->_mini_css_js($arr, $real_file);
					} else if (ereg("\.css$", strtolower($file)) || ereg("\.js$", strtolower($file))) {
						if (strpos(strtolower($file), '.mini.') !== false) continue;
						if ($_GET['model_id']) {
							$continue = false;
							foreach ($arr as $k => $v) {
								if (strpos($k, $real_file) !== false) {
									$continue = true;
									break;
								}
							}
							if (!$continue) continue;
						}
						$mini = explode('.', $file);
						$mini_ex = array_pop($mini);
						$mini_str = $dir . '/' . join('.', $mini) . '.mini.' . $mini_ex;
						$exec = escapeshellcmd("java  -jar crontab/yuicompressor.jar  --charset {$charset} '{$real_file}' -o '$mini_str' ");
						echo $mini_str . "\n";
						echo exec($exec);
					}
				}
				closedir($dh);
			}
		}
	}

	/**
	 * @desc   WHAT?
	 * @author 
	 * @since  2013-08-24 15:18:58
	 * @throws 注意:无DB异常处理
	 */
	function tags()
	{
		echo date("Y-m-d H:i:s") . " TAGS :\n";
		ini_set("display_errors", true);
		$conn_db = ocinlogon('OA_SVN', 'OA_SVN', 'PPS_gcwo');
		$sql = "select * from OA.T_SVN_MODELS t where t.model_id=:model_id";
		$stmt = ociparse($conn_db, $sql);
		ocibindbyname($stmt, ':model_id', $_GET['model_id']);
		$ocierror = ociexecute($stmt);
        $_row = apm_db_fetch_assoc($stmt);

		//TAG目标文件夹
		$model_path = str_replace("/webid/", "/webid3/", dirname($_row['MODEL_PATH']));
		exec("mkdir -p {$model_path}");
		//上线测试版本文件夹
		$model_path2 = str_replace("/webid/", "/webid4/", dirname($_row['MODEL_PATH']));
		exec("mkdir -p {$model_path2}");
		//取出
		$svnroot = str_replace("/home/svn/", "http://10.1.20.56/", $_row['SVNROOT']);

		#svn签出,如果存在,就更新.
		if (!is_dir("{$model_path}/{$_row['MNAME']}")) {
			exec("cd {$model_path}; svn co --username {$_row['MODEL_USERNAME']} --password {$_row['MODEL_PASSWORD']}  {$svnroot}/{$_row['MNAME']}/tags/ {$_row['MNAME']};");
		} else {
			exec("cd {$model_path}/{$_row['MNAME']};svn cleanup;  svn up   --username {$_row['MODEL_USERNAME']} --password {$_row['MODEL_PASSWORD']} ;");
		}
		#svn进行备份,删除掉已经不存在的代码
		$rsync = "rsync  -vzrtopg --delete  {$model_path}/{$_row['MNAME']}/ {$model_path2}/{$_row['MNAME']}/ ";
		echo $rsync . "\n";
		exec($rsync);

		if ($_GET['exec']) {
			$runexec = "cd {$model_path2}/{$_row['MNAME']}; {$_GET['exec']}";
			echo $runexec . "\n";
			exec($runexec);
		}
		//发布到测试环境中
		if ($_GET['test_rsync']) {
			$MODEL_IGNORE_str = array_diff(explode(";", $_row['MODEL_IGNORE']), array("", NULL));
			$MODEL_IGNORE_str = " --exclude=" . join(" --exclude=", $MODEL_IGNORE_str);
			$rsync = "rsync  -vzrtopg {$MODEL_IGNORE_str} {$model_path2}/{$_row['MNAME']}/ {$_GET['test_rsync']}";
			echo $rsync . "\n";
			exec($rsync);
		}

		die("\n" . date("Y-m-d H:i:s") . ',file:' . __FILE__ . ',line:' . __LINE__ . "\n");
	}

}
