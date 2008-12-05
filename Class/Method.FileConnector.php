/**
 * File Connector
 *
 * @author Anakeen 2008
 * @version $Id: Method.FileConnector.php,v 1.1 2008/12/05 18:09:06 marc Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package freedom-fileconnector
 */
/**
 */


function postModify() {


  // compute URI
  $uri = $uris = "";
  switch ($this->getValue("ifc_mode")) {
    
  case "FTP":
  case "HTTP":
  case "HTTPS":
    $uri = strtolower($this->getValue("ifc_mode"))."://";
    $uris = strtolower($this->getValue("ifc_mode"))."://";
    if ($this->getValue("ifc_login")!="") {
      $uri .= $this->getValue("ifc_login");
      $uris .= $this->getValue("ifc_login");
      if ($this->getValue("ifc_password")!="") {
	$uri .= ":".$this->getValue("ifc_password");
	$uris .= ":*********";
      }
      $uri .= "@";
      $uris .= "@";
    }
    $uri .= $this->getValue("ifc_host");
    $uris .= $this->getValue("ifc_host");
    if ($this->getValue("ifc_port")!="") {
      $uri .= ":".$this->getValue("ifc_port");
      $uris .= ":".$this->getValue("ifc_port");
    }
    $uri .= $this->getValue("ifc_path");
    $uris .= $this->getValue("ifc_path");
    break;
    
  default:
    $uri = $this->getValue("ifc_path");
    $uris = $this->getValue("ifc_path");
  }
  $oa = $this->getAttribute("ifc_uri");
  $oa->setVisiBility('R');
  $this->setValue("ifc_uri", $uri);
  $this->setValue("ifc_uris", $uris);
  $valid = 0;
//   $dt = opendir($uri,$this->getContext());
  $dt = opendir($uri);
  if (!$dt) {
    AddWarningMsg(sprintf(_("(ifc) can't access file from %s"),$uris));
  } else {
    AddWarningMsg(sprintf(_("(ifc) access to %s is checked"),$uris));
    $valid = 1;
    closedir($dt);
  }
  
  $this->setValue("ifc_opened", $valid);
  $this->modify(true,array("ifc_uri", "ifc_uris", "ifc_sl_opened"),true);
  return;
}

function checkHost() {
  
  $error_message = "";
  $proposal = array();

  $proto = $this->getValue("ifc_sl_mode");

  if ($this->getValue("ifc_sl_host")=="" && 
      ( ($proto=="FTP") || ($proto=="HTTP") || ($proto=="HTTPS")) ) {
    $error_message = sprintf(_("hostname required for protocol %s"),$this->getValue("ifc_sl_mode"));
    $proposal[] = _("give server full qualified name or its IP address");
  }
  return array( "err" => $error_message,
                "sug" => $proposal );
}



protected function scanSource() {
  global $action;
  $dir = $this->getValue("ifc_uri");

//   $dt = opendir($dir, $this->getContext());
  $dt = opendir($dir);
  if (!$dt) {
    $action->log->error("[".$this->title."]: can't open dir ".$this->getValue("ifc_uris"));
    return;
  }
  $nfk = $nfn = $nfs = $nfc = $nfm = $nfx = array();
  clearstatcache();
  $root = $this->getValue("ifc_uri");
  $ke=0;
  while (false !== ($entry = readdir($dt))) {
    if (!is_file($root."/".$entry)) continue;
    $st = stat($root."/".$entry);
    $nfk[$ke] = md5($entry.$st['mtime']);
    $nfn[$ke] = $entry;
    $nfs[$ke] = $st['size']/1024;
    $nfc[$ke] = date("Y-m-d H:i:s", $st['ctime']);
    $nfm[$ke] = date("Y-m-d H:i:s", $st['mtime']);
    $nfx[$ke] = 'N';
    $ke++;
  }
    
  closedir($dt);

  $patterns_n = $this->getTValue('ifc_sl_name');
  $patterns_v = $this->getTValue('ifc_sl_pattern');
  $patterns_f = $this->getTValue('ifc_sl_familyid');
  $patterns_a = $this->getTValue('ifc_sl_attrid');
  $patterns_r = $this->getTValue('ifc_sl_supress');

  $ofp = $this->getTvalue("ifc_c_match");
  $ofk = $this->getTvalue("ifc_c_key");
  $ofn = $this->getTvalue("ifc_c_name");
  $ofs = $this->getTvalue("ifc_c_size");
  $ofc = $this->getTvalue("ifc_c_ctime");
  $ofm = $this->getTvalue("ifc_c_mtime");
  $ofx = $this->getTvalue("ifc_c_state");

  $cfk = $cfn = $cfs = $cfc = $cfm = $cfx = array();
  $kk = 0;
  foreach ($nfk as $k=>$v) {

    $f = false;
    $p=array_search($v, $ofk);
    if (!$p) {
      // new file => added
      foreach ($patterns_v as $kpm=>$vpm) {
	if (ereg($vpm, $nfn[$k],$reg)) {
	  $cfp[$kk] = $patterns_n[$kpm];
	  $action->log->debug("[".$nfn[$k]."] match pattern {".$cfp[$kk]."}");
	  $cfk[$kk] = $nfk[$k];
	  $cfn[$kk] = $nfn[$k];
	  $cfs[$kk] = $nfs[$k];
	  $cfc[$kk] = $nfc[$k];
	  $cfm[$kk] = $nfm[$k];
	  $cfx[$kk] = $nfx[$k];
	  $kk++;
	  break;
	} 
      }
    } else {
      // already in list => get the old one 
      $cfp[$kk] = $ofp[$p];
      $cfk[$kk] = $ofk[$p];
      $cfn[$kk] = $ofn[$p];
      $cfs[$kk] = $ofs[$p];
      $cfc[$kk] = $ofc[$p];
      $cfm[$kk] = $ofm[$p];
      $cfx[$kk] = $ofx[$p];
      $kk++;
    }
  }
      
//   echo "NEW"; print_r2($nfk);
//   echo "OLD"; print_r2($ofk);
//   echo "INSERT"; print_r2($cfk);

  $this->deleteValue('ifc_c_match');
  $this->deleteValue('ifc_c_key');
  $this->deleteValue('ifc_c_name');
  $this->deleteValue('ifc_c_size');
  $this->deleteValue('ifc_c_ctime');
  $this->deleteValue('ifc_c_mtime');
  $this->deleteValue('ifc_c_state');

  $this->setValue('ifc_c_match', $this->_array2val($cfp));
  $this->setValue('ifc_c_key',   $this->_array2val($cfk));
  $this->setValue('ifc_c_name',  $this->_array2val($cfn));
  $this->setValue('ifc_c_size',  $this->_array2val($cfs));
  $this->setValue('ifc_c_ctime', $this->_array2val($cfc));
  $this->setValue('ifc_c_mtime', $this->_array2val($cfm));
  $this->setValue('ifc_c_state', $this->_array2val($cfx));

  $this->setValue("ifc_lastscan", $this->getDate());

  $this->modify(true, array("ifc_lastscan", 'ifc_c_match',
			    'ifc_c_key','ifc_c_name','ifc_c_size','ifc_c_ctime','ifc_c_mtime','ifc_c_state'), 
		true);
  
}


function resetScan() {
  $this->deleteValue('ifc_c_match');
  $this->deleteValue('ifc_c_key');
  $this->deleteValue('ifc_c_name');
  $this->deleteValue('ifc_c_size');
  $this->deleteValue('ifc_c_ctime');
  $this->deleteValue('ifc_c_mtime');
  $this->deleteValue('ifc_c_state');
  $this->modify(true, 
		array('ifc_c_match', 'ifc_c_key','ifc_c_name','ifc_c_size','ifc_c_ctime','ifc_c_mtime','ifc_c_state'), 
		true);
}



function verifyNewCxFiles() {

  $this->scanSource();

  $st = $this->getTValue("ifc_c_state");
  foreach ($st as $k=>$v) {
    if ($v=='N') return true;
  }
  return false;
}

function getNewCxFiles() {
  $ret = array();
  $st = $this->getTValue("ifc_c_state");
  $fn = $this->getTValue("ifc_c_name");
  foreach ($st as $k=>$v) {
    if ($v=='N') $ret[] = $fn[$k];
  }
  return $ret;
}

function transfertNewCxFiles() {
  $nf = $this->getNewCxFiles();
  $err = '';
  foreach ($nf as $k=>$v) {
    $err .= $this->transfertNewCxFiles($v);
  }
  return $err;
}

function transfertCxFile($file='') {
  if (!$this->isValidCxFile($file)) {
    $err = sprintf(_("(ifc) no such file %s"),$file);
  }
  if ($err=='') {
    $infos = $this->iGetFileTransf($file);
    $doc = createDoc($this->dbaccess, $infos['fam'], false);
    if (!$doc->isAlive()) $err = sprintf(_("(ifc) can't transfert file %s to family %s"),$file,$infos['fam']);
    else {
      $doc->disableEditControl();
      $err = $doc->storeFiles($infos['attr'], array($this->getValue('')."/".$file));
      if ($err!="") sprintf(_("(ifc) can't store file %s (fam %s / attr %s) err=%s"),$file,$infos['fam'],$infos['attr'],$err);
      else {
	$doc->add();
	if ($err!="") sprintf(_("(ifc) can't store file %s (fam %s / attr %s) err=%s"),$file,$infos['fam'],$infos['attr'],$err);
      }
    }
  }
  if ($err!="") $action->log->error($err);
  return $err;
}

function getCxFiles() {
  return $this->getTValue("ifc_c_name");
}

function getCxFileContent($file='') {
  if (!$this->isValidCxFile($file)) {
    return sprintf(_("(ifc) no such file %s"),$file);
  }
  $c = file_get_contents($this->getValue("ifc_uri")."/".$file);
  if (!$c) $c = sprintf(_("(ifc) can't retrieve content for file %s"),$this->getValue("ifc_uri")."/".$file);
  return $c;
}

function copyCxFile($file='', $path='') {
  if (!$this->isValidCxFile($file)) {
    return sprintf(_("(ifc) no such file %s"),$file);
  }
  if (!is_dir($path)) return sprintf(_("(ifc) can't access directory %s"),$path);
  if (!is_writeable($path)) return sprintf(_("(ifc) can't write into directory %s"),$path);
  
//   $err = copy($this->getValue("ifc_uri")."/".$file, $path, $this->getContext());
  $err = copy($this->getValue("ifc_uri")."/".$file, $path);
  if (!$err) return sprintf(_("(ifc) can't copy file %s to %s"),$this->getValue("ifc_uri")."/".$file,$path);
  return "";
}

function removeCxFile($file='') {
  if (!$this->isValidCxFile($file)) {
    return sprintf(_("(ifc) no such file %s"),$file);
  }
//   $err = unlink($this->getValue("ifc_uri")."/".$file, $this->getContext());
  $err = unlink($this->getValue("ifc_uri")."/".$file);
  if (!$err) return sprintf(_("(ifc) can't remove file %s"),$this->getValue("ifc_uri")."/".$file);
  return "";

}

protected function isValidCxFile($file='') {
  $ft = $this->getTValue("ifc_c_name");
  if (!in_array($file,$ft)) return false; 
  else return true;
}
 
 
protected function iGetFileTransf($file) {

  $fn = $this->getTValue("ifc_c_name");
  $fm = $this->getTValue("ifc_c_match");
  $p = array_search($file, $fn);
  $m = $fm[$p];

  $trn = $this->getTValue("ifc_sl_name");
  $trf = $this->getTValue("ifc_sl_familyid");
  $tra = $this->getTValue("ifc_sl_attrid");
  $trs = $this->getTValue("ifc_sl_suppr");
  $pr = array_search($file, $trn);

  return array( "name"=>$fn[$p],
		"match"=>$fm,
		"fam"=>$trf[$pm],
		"att"=>$tra[$pm],
		"sup"=>$trs[$pm]
		);
}
 

protected function getContext() {
}