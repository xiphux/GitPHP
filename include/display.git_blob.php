<?php
/*
 *  display.git_blob.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - blob
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('gitutil.git_get_hash_by_path.php');
 require_once('gitutil.git_cat_file.php');
 require_once('gitutil.git_path_trees.php');
 require_once('gitutil.read_info_ref.php');
 require_once('util.file_mime.php');

function git_blob($hash, $file, $hashbase)
{
	global $tpl, $gitphp_current_project;

	if (!$gitphp_current_project)
		return;

	$cachekey = sha1($gitphp_current_project->GetProject()) . "|" . $hashbase . "|" . $hash . "|" . sha1($file);

	if (!$tpl->is_cached('blob.tpl',$cachekey)) {
		$head = $gitphp_current_project->GetHeadCommit()->GetHash();
		if (!isset($hashbase))
			$hashbase = $head;
		if (!isset($hash) && isset($file))
			$hash = git_get_hash_by_path($hashbase,$file,"blob");
		$catout = git_cat_file($hash);
		$tpl->assign("hash",$hash);
		$tpl->assign("hashbase",$hashbase);
		$tpl->assign("head", $head);
		$co = $gitphp_current_project->GetCommit($hashbase);
		if ($co) {
			$tpl->assign("fullnav",TRUE);
			$refs = read_info_ref();
			$tpl->assign("tree",$co->GetTree()->GetHash());
			$tpl->assign("title",$co->GetTitle());
			if (isset($file))
				$tpl->assign("file",$file);
			if ($hashbase == "HEAD") {
				if (isset($refs[$head]))
					$tpl->assign("hashbaseref",$refs[$head]);
			} else {
				if (isset($refs[$hashbase]))
					$tpl->assign("hashbaseref",$refs[$hashbase]);
			}
		}
		$paths = git_path_trees($hashbase, $file);
		$tpl->assign("paths",$paths);

		if (GitPHP_Config::GetInstance()->GetValue('filemimetype', true)) {
			$mime = file_mime($catout,$file);
			if ($mime)
				$mimetype = strtok($mime, "/");
		}

		if ($mimetype == "image") {
			$tpl->assign("mime", $mime);
			$tpl->assign("data", base64_encode($catout));
		} else {
			$usedgeshi = GitPHP_Config::GetInstance()->GetValue('geshi', true);
			if ($usedgeshi) {
				$usedgeshi = FALSE;
				include_once(GitPHP_Config::GetInstance()->GetValue('geshiroot', 'lib/geshi/') . "geshi.php");
				if (class_exists("GeSHi")) {
					$geshi = new GeSHi("",'php');
					if ($geshi) {
						$lang = "";
						if (isset($file))
							$lang = $geshi->get_language_name_from_extension(substr(strrchr($file,'.'),1));
						if (isset($lang) && (strlen($lang) > 0)) {
							$geshi->enable_classes();
							$geshi->set_source($catout);
							$geshi->set_language($lang);
							$geshi->set_header_type(GESHI_HEADER_DIV);
							$geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS);
							$tpl->assign("geshiout",$geshi->parse_code());
							$tpl->assign("extracss",$geshi->get_stylesheet());
							$usedgeshi = TRUE;
						}
					}
				}
			}

			if (!$usedgeshi) {
				$lines = explode("\n",$catout);
				$tpl->assign("lines",$lines);
			}
		}
	}

	$tpl->display('blob.tpl', $cachekey);
}

?>
